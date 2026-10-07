<?php
/**
 * Makes Brizy page builder compatible with WP-Parsidate plugin
 *
 * Brizy compiles pages into static HTML and applies the `brizy_content`
 * filter to it on every front-end render, so this integration converts
 * Gregorian date strings and English numbers inside that compiled output
 * without touching Brizy's stored design data.
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/Brizy
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;
use WPParsidate\Core\Names;
use WPParsidate\Helper\{Assets, Number, Param, TextProtector, WordPress};
use WPParsidate\Settings\Settings;

/**
 * Brizy page builder integration addon
 *
 * Converts Gregorian dates and English digits inside Brizy's compiled
 * front-end output to Jalali (Shamsi) dates and Persian digits, and applies
 * the plugin Vazir font to Brizy's editor surfaces (admin edit screen and
 * the front-end editor iframe) without ever touching regular site views.
 *
 * How it works:
 *
 * 1. Brizy renders a page by compiling its stored design into static HTML
 *    and passing it through the `brizy_content` filter. This addon hooks
 *    that filter at priority 999 (after all of Brizy's own processors) and
 *    converts visible text only: tags, attributes, <script>, <style> and
 *    <code> blocks are shielded by TextProtector before conversion.
 *
 * 2. Brizy also re-uses `brizy_content` for its raw inline CSS/JS assets
 *    inside wp_enqueue_scripts (asset-enqueue-manager.php), passing a
 *    "head"/"body" context as the 4th argument. Those contents are machine
 *    code, so they are returned untouched (see filterContent()). The same
 *    contexts are used at render time for visible content — the page body
 *    (insert_page_content()) and popups — which is converted normally, so
 *    the asset calls are excluded by their wp_enqueue_scripts timing, not
 *    by the context value.
 *
 * 3. Brizy's editor chrome (sidebar, toolbar, fixed panels) lives in a
 *    front-end iframe request carrying `is-editor-iframe` — not in wp-admin.
 *    That iframe, and the admin edit screen of a Brizy post, are the only
 *    places the editor font is loaded.
 *
 * Custom filters provided for developers:
 *
 * - `wp_parsidate_brizy_skip`         Force-skip conversion for a render.
 *                                     Receives the current WP_Post (or null).
 * - `wp_parsidate_brizy_content`      Final converted HTML before it goes
 *                                     back to Brizy. Receives the WP_Post.
 * - `wp_parsidate_brizy_editor_css`   Editor font CSS injected into the
 *                                     admin screen and editor iframe.
 *
 * Addon settings (Brizy section, Integration tab):
 *
 * - `convert_dates`   Convert Gregorian dates to Shamsi (default on).
 * - `convert_numbers` Convert English digits to Persian (default on).
 * - `editor_font`     Vazir font in the Brizy editor (default on).
 *
 * @package WP-Parsidate
 * @subpackage Plugins/Brizy
 * @since   6.3
 */
class Brizy extends Addon {
  /**
   * Addon unique identifier, also used as the settings option key
   *
   * @var string
   */
  public string $addonID = 'brizy';

  /**
   * Settings tab where the addon section is displayed
   *
   * @var string
   */
  public string $currentTab = 'integration';

  /**
   * Month name regex alternations and name lookup, built once per request
   *
   * Structure:
   * - full:   `a|b|c` alternation of full English month names, regex-quoted
   * - short:  same for the abbreviated names ("Jan", "Feb", ...)
   * - months: Jalali month number (1-12) keyed by lowercase month name,
   *           including the "sept" spelling
   *
   * @var array{full:string, short:string, months:array<string,int>}|null Null until first use
   */
  private static ?array $monthPatterns = null;

  /**
   * Registers the plugin hooks
   *
   * @return void
   */
  public function initAction(): void {
    add_filter( 'brizy_content', [ $this, 'filterContent' ], 999, 4 );
  }

  /**
   * Builds the English month name patterns used by the named-date conversion
   *
   * Names come from Names::getGregorianMonths(), which reads WordPress' own
   * month names when the site locale provides them.
   *
   * @return array{full:string, short:string, months:array<string,int>}
   */
  private static function getMonthPatterns(): array {
    if ( self::$monthPatterns !== null ) {
      return self::$monthPatterns;
    }

    $alternations = [];
    $months       = [];

    foreach ( [ Names::getGregorianMonths( 'english' ), Names::getGregorianMonths( 'english', true ) ] as $names ) {
      $alternation = [];

      foreach ( $names as $number => $name ) {
        if ( ! is_string( $name ) || $name === '' ) {
          continue;
        }

        // Names can be replaced by a filter, so never trust them as literal regex
        $alternation[] = preg_quote( $name, '~' );

        $lower            = mb_strtolower( $name );
        $months[ $lower ] = (int) $number;

        // "Sep" is also commonly written "Sept"
        if ( $lower === 'sep' ) {
          $alternation[]  = preg_quote( $name, '~' ) . 't';
          $months['sept'] = (int) $number;
        }
      }

      $alternations[] = implode( '|', $alternation );
    }

    return self::$monthPatterns = [
      'full'   => $alternations[0],
      'short'  => $alternations[1],
      'months' => $months,
    ];
  }

  /**
   * Converts Gregorian dates and English numbers inside Brizy compiled HTML
   *
   * Only visible text is converted: tags, attributes, <script>, <style> and
   * <code> blocks are protected by TextProtector first, and the conversion
   * runs on regular front-end renders only (see shouldConvert()).
   *
   * @hook brizy_content Priority 999, 4 args.
   *
   * @param mixed            $html    Compiled HTML or raw asset content,
   *                                  depending on the call site.
   * @param object|null      $project Brizy_Editor_Project instance.
   * @param \WP_Post|null    $post    Post being rendered; null for popups,
   *                                  global blocks and asset contexts.
   * @param string|null      $context Brizy passes "head" or "body" as the
   *                                  4th argument at several call sites with
   *                                  different meanings (see class docblock).
   *
   * @return mixed Unmodified input for asset contents or skip conditions,
   *               otherwise the converted HTML.
   */
  public function filterContent( $html, $project = null, $post = null, $context = null ) {
    // Brizy re-uses brizy_content for its raw inline CSS/JS assets inside
    // wp_enqueue_scripts (asset-enqueue-manager.php). That is machine code,
    // never visible text: converting its digits would produce invalid CSS
    // ("margin:۰;") and break the whole page layout. Only that call site
    // runs during wp_enqueue_scripts — the visible-content call sites with
    // the same "head"/"body" contexts (page body, popups) run later, at
    // render time, and must still be converted.
    if ( doing_action( 'wp_enqueue_scripts' ) ) {
      return $html;
    }

    if ( ! is_string( $html ) || $html === '' ) {
      return $html;
    }

    if ( apply_filters( 'wp_parsidate_brizy_skip', false, $post ) || ! $this->shouldConvert() ) {
      return $html;
    }

    // Brizy's compiled HTML *is* the post content, so digits follow the
    // "Post content" toggle of the "Convert numbers to Persian" group,
    // on top of the addon's own "Convert numbers" switch
    $convertDigits = $this->getSetting( 'convert_numbers', true )
                     && Settings::get( 'conv_contents', false );

    // Jalali output is gated the same way as every other theme output
    $convertDates = $this->getSetting( 'convert_dates', true )
                    && Settings::get( 'persian_date', false );

    if ( ! $convertDigits && ! $convertDates ) {
      return $html;
    }

    // Dates and digits conversion both need ASCII digits to work with
    if ( ! preg_match( '/[0-9]/', $html ) ) {
      return $html;
    }

    // Protect tags, attributes and script/style/code blocks: only visible text is converted
    $protected = [];
    $working   = TextProtector::protect( $html, $protected );

    if ( $working !== null ) {
      if ( $convertDates ) {
        $working = $this->convertDateStrings( $working );
      }

      if ( $convertDigits ) {
        // No "<" left in protected text, so fixNumber takes its fast plain-text path
        $working = Number::fixNumber( $working );
      }

      /**
       * Filters the converted Brizy content before it is returned
       *
       * @param string       $content Converted HTML.
       * @param \WP_Post|null $post    Post being rendered.
       *
       * @since 6.3
       */
      return apply_filters( 'wp_parsidate_brizy_content', TextProtector::restore( $working, $protected ), $post );
    }

    // Fail-safe: protection failed, run only the digit conversion (has its own fail-safe)
    if ( $convertDigits ) {
      $html = Number::fixNumber( $html );
    }

    /** This filter is documented above. */
    return apply_filters( 'wp_parsidate_brizy_content', $html, $post );
  }

  /**
   * Whether the current request is a regular front-end render
   *
   * Admin, AJAX, REST, cron and CLI requests are skipped so converted output
   * is never baked into Brizy's stored compiled HTML, and the editor iframe
   * (?is-editor-iframe=...) is skipped so editors always see the real stored
   * content they are editing. Feeds and sitemaps are excluded too.
   *
   * @return bool True when content conversion may run.
   */
  private function shouldConvert(): bool {
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
      return false;
    }

    // The editor iframe (?is-editor-iframe=...) is an editing surface, not
    // site output: editors must see the real stored content they are editing
    if ( isset( $_REQUEST['is-editor-iframe'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      return false;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
      return false;
    }

    if ( defined( 'WP_CLI' ) && WP_CLI ) {
      return false;
    }

    return ! WordPress::isFeed() && ! WordPress::isSitemap();
  }

  /**
   * Converts Gregorian date strings in plain text to Jalali
   *
   * Supported shapes: Y-m-d / Y/m/d (with optional H:i / H:i:s time part),
   * "15 September 2026", "September 15, 2026", "15 Sep 2026" and
   * "Sep 15, 2026". Ordinal suffixes ("15th"), the "Sept" spelling and an
   * optional dot after short month names are accepted. Each candidate is
   * validated with checkdate() and a 1900-2100 year range before
   * conversion, so non-date numbers pass through unchanged.
   *
   * @param string $text Visible text (tags already protected)
   *
   * @return string Text with supported date strings converted to Jalali.
   */
  private function convertDateStrings( string $text ): string {
    // Digit style of converted dates follows the "Dates" toggle, like FixDates does
    $lang     = Settings::get( 'conv_dates', false ) ? 'per' : 'eng';
    $patterns = self::getMonthPatterns();


    // Y-m-d or Y/m/d, with optional time part
    $text = preg_replace_callback(
              '~\b(\d{4})([-/])(\d{1,2})\2(\d{1,2})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?\b~u',
              function ( array $m ) use ( $lang ): string {
                [ $year, $month, $day ] = [ (int) $m[1], (int) $m[3], (int) $m[4] ];

                if ( $year < 1900 || $year > 2100 || ! checkdate( $month, $day, $year ) ) {
                  return $m[0];
                }

                $format   = $m[2] === '/' ? 'Y/m/d' : 'Y-m-d';
                $dateTime = sprintf( '%04d-%02d-%02d', $year, $month, $day );

                // Optional time part, validated
                $hour   = (int) ( $m[5] ?? - 1 );
                $minute = (int) ( $m[6] ?? - 1 );
                $second = (int) ( $m[7] ?? - 1 );

                if ( $hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59 ) {
                  if ( $second >= 0 && $second <= 59 ) {
                    $format   .= ' H:i:s';
                    $dateTime .= sprintf( ' %02d:%02d:%02d', $hour, $minute, $second );
                  } else {
                    $format   .= ' H:i';
                    $dateTime .= sprintf( ' %02d:%02d', $hour, $minute );
                  }
                }

                return parsidate( $format, $dateTime, $lang );
              },
              $text
            ) ?? $text;

    // "15 September 2026"
    $text = $this->convertNamedDates( $text, $lang,
      '~\b(\d{1,2})(?:st|nd|rd|th)?\s+(' . $patterns['full'] . ')\s+(\d{4})\b~iu',
      'j F Y', [ 2, 1, 3 ], $patterns['months'] );

    // "September 15, 2026" / "September 15th 2026"
    $text = $this->convertNamedDates( $text, $lang,
      '~\b(' . $patterns['full'] . ')\s+(\d{1,2})(?:st|nd|rd|th)?,?\s*(\d{4})\b~iu',
      'F j, Y', [ 1, 2, 3 ], $patterns['months'] );

    // "15 Sep 2026" / "15 Sept. 2026"
    $text = $this->convertNamedDates( $text, $lang,
      '~\b(\d{1,2})\s+(' . $patterns['short'] . ')\.?\s+(\d{4})\b~iu',
      'j M Y', [ 2, 1, 3 ], $patterns['months'] );

    // "Sep 15, 2026" / "Sept. 15th 2026"
    $text = $this->convertNamedDates( $text, $lang,
      '~\b(' . $patterns['short'] . ')\.?\s+(\d{1,2})(?:st|nd|rd|th)?,?\s*(\d{4})\b~iu',
      'M j, Y', [ 1, 2, 3 ], $patterns['months'] );

    return $text;
  }

  /**
   * Converts month-name based date strings to Jalali
   *
   * Shared worker for the four named-date shapes; also used for the two
   * digit-style passes' validation semantics (checkdate + year range).
   *
   * @param string            $text    Visible text.
   * @param string            $lang    parsidate() language flag: "per" for
   *                                   Persian digits, "eng" for ASCII digits.
   * @param string            $pattern Regex with three capture groups.
   * @param string            $format  Output date format passed to parsidate().
   * @param array<int,int>    $groups  Capture group indexes ordered
   *                                   [month, day, year].
   * @param array<string,int> $months  Jalali month number keyed by lowercase
   *                                   English month name.
   *
   * @return string Text with the matched date strings converted; the input
   *                is returned unchanged on regex failure.
   */
  private function convertNamedDates( string $text, string $lang, string $pattern, string $format, array $groups, array $months ): string {
    $result = preg_replace_callback(
      $pattern,
      function ( array $m ) use ( $lang, $format, $groups, $months ): string {
        $month = $months[ rtrim( mb_strtolower( $m[ $groups[0] ] ), '.' ) ] ?? 0;
        $day   = (int) $m[ $groups[1] ];
        $year  = (int) $m[ $groups[2] ];

        if ( $month === 0 || $year < 1900 || $year > 2100 || ! checkdate( $month, $day, $year ) ) {
          return $m[0];
        }

        return parsidate( $format, sprintf( '%04d-%02d-%02d', $year, $month, $day ), $lang );
      },
      $text
    );

    return $result ?? $text;
  }

  /**
   * Enqueues the editor font on the admin edit screen of a Brizy post
   *
   * Hooked via Addon::registerAdminEnqueueScriptsAction() on the
   * `admin_enqueue_scripts` action. Covers screens that embed editor chrome
   * in the admin area; the real chrome itself lives in the front-end iframe
   * and is handled by wpEnqueueScriptsAction().
   *
   * Gated on: the addon's "Vazir font in editor" toggle, the global
   * "Vazir Font" setting, and the screen being the edit screen of a
   * Brizy-managed post.
   *
   * @return void
   */
  public function adminEnqueueScriptsAction(): void {
    if ( ! $this->getSetting( 'editor_font', true )
         || ! Settings::get( 'enable_fonts', false )
         || ! $this->isBrizyEditorScreen() ) {
      return;
    }

    $this->enqueueEditorStyle();
  }

  /**
   * Enqueues the editor font on Brizy editor iframe requests only
   *
   * Hooked via Addon::registerWpEnqueueScriptsAction() on the
   * `wp_enqueue_scripts` action. Brizy's editor chrome (sidebar, toolbar,
   * fixed panels) is a front-end request carrying `is-editor-iframe`, not an
   * admin screen, so the font has to ride wp_enqueue_scripts — but never on
   * regular site views. The full Vazir stylesheet is enqueued first so its
   * @font-face declarations are actually available inside the iframe.
   *
   * Gated on: not an admin request, the `is-editor-iframe` request marker,
   * the addon's "Vazir font in editor" toggle, the global "Vazir Font"
   * setting, and the requested post being Brizy-managed (autosaves and
   * revisions resolve to the parent post).
   *
   * @return void
   */
  public function wpEnqueueScriptsAction(): void {
    if ( is_admin()
         || ! isset( $_REQUEST['is-editor-iframe'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
         || ! $this->getSetting( 'editor_font', true )
         || ! Settings::get( 'enable_fonts', false ) ) {
      return;
    }

    $postId = (int) Param::get( 'post', 0 );

    if ( ! $this->isBrizyPost( $postId ) ) {
      return;
    }

    $debugName = WP_PARSI_DEBUG_MODE ? '' : '.min';

    // The real @font-face declarations for Vazir/Vazirmatn — without this
    // sheet the inline rules reference a font the browser never downloads
    wp_enqueue_style(
      WP_PARSI_KEY_SLUG . '-vazir-font',
      Assets::url( 'css-admin/vazir-font' . $debugName . '.css' ),
      false,
      Assets::getVersion()
    );

    // Scoping rules for Brizy's editor chrome, on top of the font faces
    $this->enqueueEditorStyle();
  }

  /**
   * Registers and enqueues the src-less style handle carrying the editor
   * font CSS
   *
   * The handle `wp-parsidate_brizy_editor` is registered without a src and
   * receives the font rules as inline CSS, overridable via the
   * wp_parsidate_brizy_editor_css filter. Skipped silently when a filter
   * empties the CSS.
   *
   * Override strategy: Brizy's runtime compiler generates per-element rules
   * of the form `.brz [data-generated-css="brz-css-…"]` with literal
   * `font-family … !important` declarations (no CSS variables involved) and
   * appends its <style> tag to <head> after ours — so cascade order and
   * inheritance lose. Specificity wins over both: every generated rule
   * carries a single .brz class, while our selectors root at
   * body.brz.brz-ed (the editor iframe body classes from Brizy's
   * body_class_editor()), giving (0,3,1) on the bare attribute selector
   * versus the compiler's (0,2,0). Matching the bare [data-generated-css]
   * attribute — without a hash — keeps this working for every element the
   * compiler styles, present and future.
   *
   * The same body roots keep the chrome covered: [class*="brz-ed-"] catches
   * every editor UI prefix shipped in main.editor.min.css (sidebar, toolbar,
   * fixed panels, popovers, controls, tooltips, …) and .brz-ui-v2 catches
   * Brizy's React component library. Blanket coverage of the canvas is
   * intentional — the font is meant to apply across the editor. The admin
   * edit screen has no brz/brz-ed body classes, so the second root is
   * body.brizy-editor-enabled, the class Brizy adds via admin_body_class
   * (admin/main.php filter_add_body_class).
   *
   * Used by both font entry points: adminEnqueueScriptsAction() and
   * wpEnqueueScriptsAction().
   *
   * @return void
   */
  private function enqueueEditorStyle(): void {
    $css = '
      body.brz.brz-ed,
      body.brz.brz-ed [data-generated-css],
      body.brz.brz-ed [class*="brz-ed-"],
      body.brz.brz-ed .brz-ui-v2,
      body.brizy-editor-enabled,
      body.brizy-editor-enabled [class*="brz-ed-"],
      body.brizy-editor-enabled .brz-ui-v2 {
        font-family: Vazirmatn, Vazir, Tahoma, Arial, sans-serif !important;
      }';

    /**
     * Filters the editor font CSS applied to Brizy's editor chrome
     *
     * Runs on the admin edit screen and the editor iframe. Return an empty
     * string to disable the font styles for this request.
     *
     * @param string $css Inline CSS targeting Brizy's editor chrome.
     *
     * @since 6.3
     */
    $css = apply_filters( 'wp_parsidate_brizy_editor_css', $css );

    if ( $css ) {
      wp_register_style( WP_PARSI_KEY . '_brizy_editor', false, [], Assets::getVersion() );
      wp_enqueue_style( WP_PARSI_KEY . '_brizy_editor' );
      wp_add_inline_style( WP_PARSI_KEY . '_brizy_editor', $css );
    }
  }

  /**
   * Whether the current admin screen is the edit screen of a Brizy post
   *
   * Recognizes both the `post` (edit existing) and `post-new` (create new)
   * screen bases. `post-new` has no post ID yet, so it only matches when a
   * `post` request parameter is present and belongs to a Brizy post.
   *
   * @return bool True when the editor font should load in the admin area.
   */
  private function isBrizyEditorScreen(): bool {
    $screen = get_current_screen();

    if ( ! $screen || ! in_array( $screen->base, [ 'post', 'post-new' ], true ) ) {
      return false;
    }

    $postId = (int) Param::get( 'post', 0 );

    return $postId > 0 && $this->isBrizyPost( $postId );
  }

  /**
   * Whether a post was created or edited with Brizy
   *
   * Delegates to Brizy_Editor_Entity::isBrizyEnabled() when Brizy is active.
   * Autosave and revision IDs are resolved recursively to their parent post,
   * so editor iframe loads of autosaves still match. Any throwable from
   * Brizy's internals is treated as "not a Brizy post".
   *
   * @param int $postId Post ID (or autosave/revision ID) to check.
   *
   * @return bool True when the post is managed by Brizy.
   */
  private function isBrizyPost( int $postId ): bool {
    // Fully qualified: Brizy defines these in the global namespace
    if ( $postId <= 0 || ! class_exists( '\Brizy_Editor_Entity' ) || ! method_exists( '\Brizy_Editor_Entity', 'isBrizyEnabled' ) ) {
      return false;
    }

    try {
      if ( \Brizy_Editor_Entity::isBrizyEnabled( $postId ) ) {
        return true;
      }

      // Editor iframe loads can reference an autosave/revision of the post
      if ( wp_is_post_autosave( $postId ) || wp_is_post_revision( $postId ) ) {
        return $this->isBrizyPost( (int) wp_get_post_parent_id( $postId ) );
      }

      return false;
    } catch ( \Throwable $e ) {
      return false;
    }
  }

  /**
   * Registers the addon settings section in the Integration tab
   *
   * Each ability of the integration gets its own toggle so users can
   * enable or disable it independently of the other features. All toggles
   * default to true, so existing behavior is unchanged until a user opts
   * out. Settings are stored under the `brizy` option key and read through
   * Addon::getSetting().
   *
   * Toggles:
   *
   * - `convert_dates`   Shamsi date conversion (ANDed with the global
   *                     `persian_date` setting.
   * - `convert_numbers` Persian digit conversion (ANDed with the global
   *                     `conv_contents` "Post content" setting).
   * - `editor_font`     Vazir font in the Brizy editor (ANDed with the
   *                     global `enable_fonts` setting).
   *
   * @hook wp_parsidate_integration_settings_sections Via
   *       Addon::registerAddSectionSettings().
   *
   * @param array $sections Settings sections of the current tab.
   *
   * @return array Sections with the Brizy section added.
   */
  public function addSectionSettings( $sections ): array {
    $sections[ $this->addonID ] = array(
      'title'        => esc_html__( 'Brizy', 'wp-parsidate' ),
      'desc'         => esc_html__( 'ParsiDate integration for Brizy page builder', 'wp-parsidate' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'brizy_start_grid'        => array(
          'id'    => 'brizy_start_grid',
          'title' => esc_html__( 'Content conversion', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ),
        'convert_dates'           => array(
          'id'       => 'convert_dates',
          'title'    => esc_html__( 'Convert dates to Shamsi', 'wp-parsidate' ),
          'desc'     => esc_html__( 'Converts Gregorian dates inside Brizy page output to Jalali (Shamsi). Requires the Shamsi date setting and a Persian locale.', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => true,
          'sanitize' => 'bool'
        ),
        'convert_numbers'         => array(
          'id'       => 'convert_numbers',
          'title'    => esc_html__( 'Convert numbers to Persian', 'wp-parsidate' ),
          'desc'     => esc_html__( 'Converts English digits inside Brizy page output to Persian digits. Requires the "Post content" option of the number conversion settings.', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => true,
          'sanitize' => 'bool'
        ),
        'brizy_end_grid'          => array(
          'type' => 'endGrid',
        ),
        'brizy_editor_start_grid' => array(
          'id'    => 'brizy_editor_start_grid',
          'title' => esc_html__( 'Editor', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ),
        'editor_font'             => array(
          'id'       => 'editor_font',
          'title'    => esc_html__( 'Vazir font in editor', 'wp-parsidate' ),
          'desc'     => esc_html__( 'Applies the plugin Vazir font to the Brizy editor interface (admin edit screen). The site front end is never affected.', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => true,
          'sanitize' => 'bool'
        ),
        'brizy_editor_end_grid'   => array(
          'type' => 'endGrid',
        ),
      ]
    );

    return $sections;
  }

  /**
   * Addon registration metadata for the addons dashboard
   *
   * Declares Brizy as a required dependency (detected via the BRIZY_VERSION
   * constant or the Brizy_Editor class, so both free and Pro builds match)
   * and embeds the Brizy icon as an inline SVG.
   *
   * @hook wp_parsidate_addons Via Addon::registerAddon().
   *
   * @return array{id:string, title:string, desc:string, force_enable:bool, icon:string,
   *               image_link:string, tags:string[], cat:string, settings_key:string,
   *               requires_plugins:array} Addon metadata.
   */
  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 38 38"><defs><filter id="b" width="104.1%" height="113.6%" x="-2%" y="-3.4%"><feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/><feColorMatrix in="shadowOffsetOuter1" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.27 0"/></filter><filter id="c" width="104.1%" height="123.5%" x="-2%" y="-5.9%"><feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/><feColorMatrix in="shadowOffsetOuter1" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.29 0"/></filter><linearGradient id="a" x1="-884.49" x2="-884.72" y1="546.2" y2="545.25" gradientTransform="matrix(38 0 0 -38 33633 20760)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#3d3ac4"/><stop offset=".69" stop-color="#56c5fd"/><stop offset="1" stop-color="#bbf8ff"/></linearGradient></defs><path d="M19 38c19 0 19-3.8 19-19S38 0 19 0 0 3.8 0 19s0 19 19 19" style="fill-rule:evenodd;fill:url(#a)"/><g style="filter:url(#b)"><path fill-rule="evenodd" d="m6.35 16.08 12.26-7.34 12.26 7.34-12.26 7.34zm4.4.17 7.86 4.72 7.86-4.72-7.86-4.72z"/></g><path fill="#fff" fill-rule="evenodd" d="m6.35 16.08 12.26-7.34 12.26 7.34-12.26 7.34zm4.4.17 7.86 4.72 7.86-4.72-7.86-4.72z" data-name="path-2"/><g style="opacity:.75"><g style="filter:url(#c)"><path fill-rule="evenodd" d="m6.35 22.24 2.2-1.3 10.06 5.92 10.14-5.88 2.13 1.26-12.27 7.21z"/></g><path fill="#fff" fill-rule="evenodd" d="m6.35 22.24 2.2-1.3 10.06 5.92 10.14-5.88 2.13 1.26-12.27 7.21z" data-name="path-4"/></g></svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Brizy', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Brizy Page Builder', 'wp-parsidate' ),
      'force_enable'     => false,
      'icon'             => $svg,
      'image_link'       => 'https://wordpress.org/plugins/brizy/',
      'tags'             => [ esc_html__( 'Brizy', 'wp-parsidate' ) ],
      'cat'              => 'integration',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'brizy/brizy.php' => array(
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/brizy/',
          'define_check' => 'BRIZY_VERSION',
          'class_check'  => 'Brizy_Editor',
        )
      ]
    );
  }
}
