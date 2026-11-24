'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const test = require('node:test');

const pluginRoot = path.resolve(__dirname, '../..');
const bundleDir = path.join(pluginRoot, 'assets', 'js-admin');

/**
 * Rebuilds the assets/js-admin bundles into a temporary directory, leaving the
 * committed files untouched.
 *
 * @param {string} outputDir Temporary directory that receives the output.
 * @return {Promise<void>}
 */
function buildAdminBundles(outputDir) {
  // Required lazily so a checkout without dev dependencies fails with webpack's
  // own error instead of a module-not-found at file load time.
  const webpack = require('webpack');
  const configs = require(path.join(pluginRoot, 'webpack.config.js'))
    .filter((config) => config.name === 'admin' || config.name === 'admin-min')
    .map((config) => ({
      ...config,
      watch: false,
      output: { ...config.output, path: path.join(outputDir, config.name) },
    }));

  return new Promise((resolve, reject) => {
    webpack(configs, (error, stats) => {
      if (error) {
        reject(error);

        return;
      }

      if (stats.hasErrors()) {
        reject(new Error(stats.toString({ all: false, errors: true })));

        return;
      }

      resolve();
    });
  });
}

/**
 * Collects the files webpack emitted, keyed by the output directory it wrote.
 *
 * @param {string} outputDir Build directory passed to buildAdminBundles().
 * @return {string[][]} Pairs of [outputSubdirectory, fileName], sorted by name.
 */
function emittedBundles(outputDir) {
  const bundles = [];

  for (const name of ['admin', 'admin-min']) {
    const dir = path.join(outputDir, name);

    for (const file of fs.readdirSync(dir).sort()) {
      bundles.push([dir, file]);
    }
  }

  return bundles;
}

test('committed assets/js-admin bundles match a fresh build from assets/js-admin-src', async (t) => {
  const outputDir = fs.mkdtempSync(path.join(os.tmpdir(), 'wppd-js-admin-'));

  t.after(() => fs.rmSync(outputDir, { recursive: true, force: true }));

  await buildAdminBundles(outputDir);

  const bundles = emittedBundles(outputDir);

  assert.ok(bundles.length > 0, 'webpack produced no assets/js-admin bundles');

  const problems = [];

  for (const [builtDir, name] of bundles) {
    const built = fs.readFileSync(path.join(builtDir, name));
    const committed = path.join(bundleDir, name);

    if (!fs.existsSync(committed)) {
      problems.push(`${name} is missing from assets/js-admin`);

      continue;
    }

    if (!built.equals(fs.readFileSync(committed))) {
      problems.push(`${name} is out of date`);
    }
  }

  assert.deepEqual(
    problems,
    [],
    `assets/js-admin bundles do not match assets/js-admin-src:\n  ${problems.join('\n  ')}\n` +
    'Rebuild and commit them with: npx webpack --no-watch'
  );
});
