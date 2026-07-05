const fs = require('fs');
const path = require('path');
const TerserPlugin = require("terser-webpack-plugin");

const optimization = {
  minimize: true,
  minimizer: [
    new TerserPlugin({
      terserOptions: {
        ecma: undefined,
        parse: {},
        compress: {},
        mangle: true,
        module: false,
        // Deprecated
        output: null,
        format: null,
        toplevel: false,
        nameCache: null,
        ie8: false,
        keep_classnames: undefined,
        keep_fnames: true,
        safari10: false,
      },
    }),
  ],
};

let watchOptions = {
  aggregateTimeout: 200,
  poll: 1000,
  ignored: /node_modules/,
};

module.exports = [
  {
    name: 'site-min',
    mode: 'production',
    watch: true,
    optimization: optimization,
    watchOptions: watchOptions,

    entry: (() => {
      const toReturn = {};

      const addFiles = (dirpath) => fs.readdirSync(dirpath).forEach((f) => {
        toReturn[f.split('.').slice(0, -1).join('.')] = dirpath + "/" + f;
      });

      addFiles("./assets/js-src");
      //  toReturn["main"] = "./js/index.js";

      return toReturn;
    })(),
    output: {
      path: path.resolve('./assets/js'), filename: "[name].min.js"
    }
  },
  {
    name: 'site',
    mode: 'development',
    watch: true,
    optimization: {
      minimize: false,
    },
    watchOptions: watchOptions,

    entry: (() => {
      const toReturn = {};

      const addFiles = (dirpath) => fs.readdirSync(dirpath).forEach((f) => {
        toReturn[f.split('.').slice(0, -1).join('.')] = dirpath + "/" + f;
      });

      addFiles("./assets/js-src");
      //  toReturn["main"] = "./js/index.js";

      return toReturn;
    })(),
    output: {
      path: path.resolve('./assets/js'), filename: "[name].js"
    }
  },
  {
    name: 'admin-min',
    mode: 'production',
    watch: true,
    optimization: optimization,
    watchOptions: watchOptions,

    entry: (() => {
      const toReturn = {};

      const addFiles = (dirpath) => fs.readdirSync(dirpath).forEach((f) => {
        toReturn[f.split('.').slice(0, -1).join('.')] = dirpath + "/" + f;
      });

      addFiles("./assets/js-admin-src");

      return toReturn;
    })(),
    output: {
      path: path.resolve('./assets/js-admin'), filename: "[name].min.js"
    }
  },
  {
    name: 'admin',
    mode: 'development',
    watch: true,
    optimization: {
      minimize: false,
      nodeEnv: "development",
      splitChunks: false
    },
    watchOptions: watchOptions,

    entry: (() => {
      const toReturn = {};

      const addFiles = (dirpath) => fs.readdirSync(dirpath).forEach((f) => {
        toReturn[f.split('.').slice(0, -1).join('.')] = dirpath + "/" + f;
      });

      addFiles("./assets/js-admin-src");

      return toReturn;
    })(),
    output: {
      path: path.resolve('./assets/js-admin'), filename: "[name].js"
    }
  }
];
