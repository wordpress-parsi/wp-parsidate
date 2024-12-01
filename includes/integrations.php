<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

if ( ! class_exists( 'WPP_Integrations' ) ) {

	/**
	 * Integrations Management
	 */
	class WPP_Integrations {

		/**
		 * List of supported plugins
		 *
		 * @var array[]
		 */
		private $supported_plugins;

		public function __construct() {
			$this->supported_plugins = array(
				'woocommerce'            => array(
					'name'        => __( 'WooCommerce', 'wp-parsidate' ),
					'slug'        => 'woocommerce',
					'path_check'  => 'woocommerce/woocommerce.php',
					'logo'        => 'https://ps.w.org/woocommerce/assets/icon-256x256.gif',
					'description' => 'Fix prices, Postal code and phone validation, Payment gateways, Customize checkout fields,....',
					'tags'        => 'وکامرس, ووکامرس, woocommerce, wc',
					'has_options' => true,
					'has_wp_repo' => true,
				),
				'elementor'              => array(
					'name'        => __( 'Elementor', 'wp-parsidate' ),
					'slug'        => 'elementor',
					'path_check'  => 'elementor/elementor.php',
					'logo'        => 'https://ps.w.org/elementor/assets/icon-256x256.gif',
					'description' => 'Jalali date-picker, Fix styles',
					'tags'        => 'المنتور, صفحه ساز, elementor',
					'has_options' => true,
					'has_wp_repo' => true,
				),
				'seo-by-rank-math'       => array(
					'name'         => __( 'Rank Math SEO', 'wp-parsidate' ),
					'slug'         => 'seo-by-rank-math',
					'path_check'   => 'seo-by-rank-math/rank-math.php',
					'logo'         => 'https://ps.w.org/seo-by-rank-math/assets/icon.svg',
					'description'  => 'Fix structured data (Schema)',
					'tags'         => 'rankmath, rank math,رنک مث, رنک مت',
					'has_options'  => false,
					'force_enable' => true,
					'has_wp_repo'  => true,
				),
				'advanced-custom-fields' => array(
					'name'        => __( 'Secure Custom Fields (formerly Advanced Custom Fields)', 'wp-parsidate' ),
					'slug'        => 'rank_math',
					'path_check'  => 'advanced-custom-fields/acf.php',
					'logo'        => 'https://ps.w.org/advanced-custom-fields/assets/icon.svg',
					'description' => 'Add Jalali date and time picker fields',
					'tags'        => 'scf, acf, ACF, SCF, Secure custom fields, secure custom fields, advanced custom fields, Advanced custom fields, ای سی اف',
					'has_options' => true,
					'has_wp_repo' => true,
				),
				'easy-digital-downloads' => array(
					'name'        => __( 'Easy Digital Downloads', 'wp-parsidate' ),
					'slug'        => 'easy-digital-downloads',
					'path_check'  => 'easy-digital-downloads/easy-digital-downloads.php',
					'logo'        => 'https://ps.w.org/easy-digital-downloads/assets/icon.svg',
					'description' => 'Digital downloads for WordPress',
					'tags'        => 'edd downloads digital products',
					'has_options' => true,
					'has_wp_repo' => true,
				),
				'gravityforms'           => array(
					'name'         => __( 'Gravity Forms', 'wp-parsidate' ),
					'slug'         => 'gravity_forms',
					'path_check'   => 'gravityforms/gravityforms.php',
					'logo'         => WP_PARSI_URL . 'assets/images/gravity-forms-logo.jpg',
					'description'  => 'Fix dates and numbers, Support forms AJAX submission',
					'tags'         => 'gravity forms, gravityforms, گراویتی فرم, گرویتی فرم, فرمز',
					'has_options'  => false,
					'force_enable' => true,
					'has_wp_repo'  => false,
				),
			);

			$this->load_integrations();

			add_action( 'admin_menu', array( $this, 'add_integrations_submenu' ), 99 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_integration_scripts' ) );
			add_action( 'wp_ajax_wpp_toggle_integration', array( $this, 'toggle_integration' ) );
		}

		public function load_integrations() {
			$files = array(
				'woocommerce'            => 'woocommerce/woocommerce',
				'easy-digital-downloads' => 'easy-digital-downloads/easy-digital-downloads',
				'advanced-custom-fields' => 'advanced-custom-fields/acf',
				'elementor'              => 'elementor/elementor',
			);


			foreach ( $files as $file => $path ) {
				if ( $this->is_plugin_integrated( $file ) ) {
					require_once( WP_PARSI_DIR . 'includes/plugins/' . $path . '.php' );
				}
			}

			require_once( WP_PARSI_DIR . 'includes/plugins/disable.php' );
		}

		/**
		 * @return void
		 */
		public function add_integrations_submenu() {
			add_submenu_page(
				'wp-parsi-settings',
				__( 'Integrations', 'wp-parsidate' ),
				__( 'Integrations', 'wp-parsidate' ),
				'manage_options',
				'wpp-integrations',
				array( $this, 'render_integrations_page' ),
				20,
			);
		}

		/**
		 * Check if a specific plugin is integrated
		 *
		 * @param string $plugin_slug Plugin slug to check
		 *
		 * @return bool Whether the plugin is integrated
		 */
		public function is_plugin_integrated( $plugin_slug ) {
			// Get current integrations from wp_options
			$wpp_integrations = get_option( 'wpp_integrations', array() );

			// Check if the plugin is in the supported plugins list
			if ( ! isset( $this->supported_plugins[ $plugin_slug ] ) ) {
				return false;
			}

			$plugin_info   = $this->supported_plugins[ $plugin_slug ] ?? false;
			$slug_to_check = $plugin_info ? $plugin_info['slug'] : false;
			$path_to_check = $plugin_info ? $plugin_info['path_check'] : false;

			// Check if plugin is in integrations and meets criteria
			return isset( $wpp_integrations[ $slug_to_check ] ) && WP_Parsidate::is_plugin_activated( $path_to_check );
		}

		public function enqueue_integration_scripts( $hook ) {
			if ( 'parsi-date_page_wpp-integrations' !== $hook ) {
				return;
			}

			wp_enqueue_style( 'wpp-integrations', WP_PARSI_URL . 'assets/css/integrations.css', array(), '1.0.0' );
			wp_enqueue_script( 'wpp-integrations', WP_PARSI_URL . 'assets/js/integrations.js', array( 'jquery' ), '1.0.0', true );

			wp_localize_script(
				'wpp-integrations',
				'wppIntegrations',
				array(
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'nonce'            => wp_create_nonce( 'wpp-integrations-nonce' ),
					'supportedPlugins' => $this->localize_supported_plugins(),
					'baseOptionsURL'   => self_admin_url( 'options-general.php?page=wp-parsi-settings&tab=' ),
					'i18n'             => array(
						'notInstalled'      => __( 'Plugin Not Installed', 'wp-parsidate' ),
						'installPlugin'     => __( 'Install Plugin', 'wp-parsidate' ),
						'notActivated'      => __( 'Plugin Not Activated', 'wp-parsidate' ),
						'activePlugin'      => __( 'Activate Plugin', 'wp-parsidate' ),
						'alwaysEnabled'     => __( 'Always Enabled', 'wp-parsidate' ),
						'enableIntegration' => __( 'Enable Integration', 'wp-parsidate' ),
						'options'           => __( 'Options', 'wp-parsidate' ),
					)
				),
			);
		}

		/**
		 * Return localized supported plugins
		 *
		 * @return array|array[]
		 * @sicne 5.1.3
		 */
		private function localize_supported_plugins() {
			return array_map(
				function ( $plugin ) {
					// Add runtime check for installed and active status
					$plugin['is_installed'] = is_plugin_active( $plugin['path_check'] );
					$plugin['is_active']    = is_plugin_active( $plugin['path_check'] );
					$plugin['integrated']   = self::is_plugin_integrated( $plugin['slug'] );
					$plugin['install_url']  = $plugin['has_wp_repo'] ? wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin['slug'] ), 'install-plugin_' . $plugin['slug'] ) : self_admin_url( 'plugin-install.php' );
					$plugin['activate_url'] = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . $plugin['path_check'] ), 'activate-plugin_' . $plugin['path_check'] );

					return $plugin;
				},
				$this->supported_plugins,
			);
		}

		/**
		 * @return void
		 */
		public function toggle_integration() {
			check_ajax_referer( 'wpp-integrations-nonce', 'nonce' );

			$plugin_slug = sanitize_text_field( $_POST['plugin_slug'] );
			$is_enabled  = sanitize_text_field( $_POST['is_enabled'] ) === 'true';

			// Specific logic for always-on plugins
			$plugin_info   = $this->supported_plugins[ $plugin_slug ] ?? null;
			$forced_enable = $plugin_info && ! $plugin_info['has_options'];

			// Get current integrations
			$wpp_integrations = get_option( 'wpp_integrations', array() );

			if ( $is_enabled || $forced_enable ) {
				// Add to integrations if not exists
				$wpp_integrations[ $plugin_slug ] = array(
					'enabled_at'   => current_time( 'mysql' ),
					'last_updated' => current_time( 'mysql' ),
					'force_enable' => $forced_enable,
				);
			} else {
				// Remove from integrations
				unset( $wpp_integrations[ $plugin_slug ] );
			}

			// Update options
			update_option( 'wpp_integrations', $wpp_integrations );

			$response = array(
				'status'       => $is_enabled || $forced_enable,
				'force_enable' => $forced_enable,
				'message'      => $is_enabled || $forced_enable
					? sprintf( __( 'Parsidate integration with %s activated', 'wp-parsidate' ), $plugin_info['name'] )
					: sprintf( __( 'Parsidate integration with %s deactivated', 'wp-parsidate' ), $plugin_info['name'] )
			);

			wp_send_json_success( $response );
		}

		public function check_plugin_status() {
			check_ajax_referer( 'wpp-integrations-nonce', 'nonce' );

			$plugin_slug = sanitize_text_field( $_POST['plugin_slug'] );

			$response = [
				'installed'    => is_plugin_active( $plugin_slug ),
				'active'       => is_plugin_active( $plugin_slug ),
				'install_url'  => wp_nonce_url(
					self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ),
					'install-plugin_' . $plugin_slug,
				),
				'activate_url' => wp_nonce_url(
					self_admin_url( 'plugins.php?action=activate&plugin=' . $plugin_slug ),
					'activate-plugin_' . $plugin_slug,
				)
			];

			wp_send_json_success( $response );
		}

		public function render_integrations_page() {
			?>
            <header class="wpp-header">
                <h1><?php _e( 'Plugin Integrations', 'wp-parsidate' ); ?></h1>
            </header>

            <div class="wpp-integrations-container">
                <div class="integrations-search-container">
                    <input
                            type="text"
                            id="wpp-plugin-search"
                            placeholder="<?php _e( 'Search plugins...', 'wp-parsidate' ); ?>"
                            class="wpp-search-input"
                    >
                </div>

                <div id="wpp-plugins-grid" class="integrations-grid">
                    <!-- Plugins will be dynamically loaded here -->
                </div>
            </div>
			<?php
		}
	}

	new WPP_Integrations();
}