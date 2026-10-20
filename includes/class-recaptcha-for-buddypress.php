<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wbcomdesigns.com/
 * @since      1.0.0
 *
 * @package    Recaptcha_For_BuddyPress
 * @subpackage bp_recaptcha/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Recaptcha_For_BuddyPress
 * @subpackage bp_recaptcha/includes
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
class Recaptcha_For_BuddyPress {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Recaptcha_For_BuddyPress_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'RFB_PLUGIN_VERSION' ) ) {
			$this->version = RFB_PLUGIN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'buddypress-recaptcha';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Recaptcha_For_BuddyPress_Loader. Orchestrates the hooks of the plugin.
	 * - Recaptcha_For_BuddyPress_I18n. Defines internationalization functionality.
	 * - Recaptcha_For_BuddyPress_Admin. Defines all hooks for the admin area.
	 * - Recaptcha_For_BuddyPress_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-recaptcha-for-buddypress-loader.php';

		/**
		 * Service architecture classes
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/captcha-service-interface.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-captcha-service-base.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-captcha-service-manager.php';

		/**
		 * Helper functions for consistent reCAPTCHA version handling.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/recaptcha-helper-functions.php';

		/**
		 * Option name compatibility for handling typos in option names.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/option-name-compatibility.php';

		/**
		 * Captcha verification helper functions.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/captcha-verification-helper.php';

		/**
		 * Settings integration for service architecture.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-settings-integration.php';

		/**
		 * Settings migration for simplified settings.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-settings-migration.php';

		/**
		 * Option name migration for standardized naming.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-option-migration.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-recaptcha-for-buddypress-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-recaptcha-for-buddypress-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-recaptcha-for-buddypress-public.php';

		/* Card-panel admin (replaces the legacy admin/wbcom/ wrapper as of 2.1.0). */
		require_once plugin_dir_path( __DIR__ ) . 'includes/admin/class-bprc-admin.php';
		// LRL Class Files login, registration and lost password.
		require_once plugin_dir_path( __DIR__ ) . 'public/lrl-classes/Login.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/lrl-classes/Registration.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/lrl-classes/Lostpassword.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/woocommerce-lrl-classes/Woocommerce_Register.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/woocommerce-lrl-classes/Woocommerce_Login.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/woocommerce-lrl-classes/Woocommerce_Lostpassword.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/woocommerce-extra/Woocommerce_Review_Order.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/woocommerce-extra/Woocommerce_Register_Post.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/woocommerce-extra/LostpasswordPost.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/woocommerce-extra/Woocommerce_Process_Login_Errors.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/woocommerce-extra/Woocommerce_After_Checkout_Validation.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/woocommerce-extra/Woocommerce_Filter.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/woocommerce-extra/Woocommerce_Order.php';

		// Buddy Press.
		require_once plugin_dir_path( __DIR__ ) . 'public/bp-classes/Registrationbp.php';

		// Contact Form 7.
		require_once plugin_dir_path( __DIR__ ) . 'public/cf7-classes/CF7_Form.php';

		// WPForms.
		require_once plugin_dir_path( __DIR__ ) . 'public/wpforms-classes/WPForms_Form.php';

		// Gravity Forms.
		require_once plugin_dir_path( __DIR__ ) . 'public/gravityforms-classes/GravityForms_Form.php';

		// Ninja Forms.
		require_once plugin_dir_path( __DIR__ ) . 'public/ninjaforms-classes/NinjaForms_Form.php';

		// Forminator.
		require_once plugin_dir_path( __DIR__ ) . 'public/forminator-classes/Forminator_Form.php';

		// Elementor Pro.
		require_once plugin_dir_path( __DIR__ ) . 'public/elementorpro-classes/ElementorPro_Form.php';

		// Divi Builder.
		require_once plugin_dir_path( __DIR__ ) . 'public/divi-classes/Divi_Form.php';

		// Easy Digital Downloads.
		require_once plugin_dir_path( __DIR__ ) . 'public/edd-classes/EDD_Form.php';

		// MemberPress.
		require_once plugin_dir_path( __DIR__ ) . 'public/memberpress-classes/MemberPress_Form.php';

		// Ultimate Member.
		require_once plugin_dir_path( __DIR__ ) . 'public/ultimatemember-classes/UltimateMember_Form.php';

		// Login Widget and Block.
		require_once plugin_dir_path( __DIR__ ) . 'includes/widgets/class-wbc-login-widget.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-wbc-login-block.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-wbc-ajax-login-handler.php';

		// bbPress.
		require_once plugin_dir_path( __DIR__ ) . 'public/bbPress/class-wbc-bbpress-reply-recaptcha.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/bbPress/class-wbc-bbpress-topic-recaptcha.php';

		$this->loader = new Recaptcha_For_BuddyPress_Loader();

		// Check and run settings migration if needed.
		if ( class_exists( 'WBC_Settings_Migration' ) && WBC_Settings_Migration::is_migration_needed() ) {
			add_action( 'admin_init', array( 'WBC_Settings_Migration', 'migrate' ), 5 );
		}
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Recaptcha_For_BuddyPress_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Recaptcha_For_BuddyPress_I18n();

		// init, not plugins_loaded: loading a text domain before init triggers
		// _load_textdomain_just_in_time on WordPress 6.7+.
		$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );

		// Run option migration.
		$this->loader->add_action( 'plugins_loaded', $this, 'run_option_migration' );
	}

	/**
	 * Run option name migration
	 *
	 * @since 2.0.0
	 */
	public function run_option_migration() {
		WBC_Option_Migration::migrate();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		// Legacy admin object — RETAINED, but its UI surface is no longer
		// hooked as of 2.1.0. It still loads WBC_BuddyPress_Settings_Page
		// (the wbc_save()/wbc_output() engine the new panel delegates to)
		// and initialises WBC_Settings_Integration in its constructor.
		// BPRC_Admin now owns the menu, enqueue, page render, and notice
		// suppression. See references/wbcom-wrapper-migration.md Part 3.
		$plugin_admin = new Recaptcha_For_BuddyPress_Admin( $this->get_plugin_name(), $this->get_version() );
		unset( $plugin_admin ); // Instantiated for its constructor side effects only.

		// New card-panel admin (menu + enqueue + render + hub takeover).
		$bprc_admin = new BPRC_Admin();
		$bprc_admin->register();

		// Surface a persistent admin notice when the active CAPTCHA service is
		// missing required keys (set during verify()).
		if ( function_exists( 'wbc_captcha_service_manager' ) ) {
			$this->loader->add_action( 'admin_notices', wbc_captcha_service_manager(), 'maybe_render_misconfiguration_notice' );
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Recaptcha_For_BuddyPress_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Load style, js language.
		add_action( 'plugins_loaded', array( $plugin_public, 'woo_load_lang_for_woo_recaptcha' ) );
		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'woo_recaptcha_load_styles_and_js' ), 9999 );
		add_action( 'login_enqueue_scripts', array( $plugin_public, 'woo_recaptcha_load_styles_and_js' ), 9999 );

		// Login, registration and lost password.
		// Classes are uniquely prefixed (WBC_*) to avoid fatal "cannot redeclare"
		// collisions with other plugins/themes that define generic Login/etc.
		if ( ! class_exists( 'WBC_Login' ) || ! class_exists( 'WBC_Registration' ) || ! class_exists( 'WBC_Lostpassword' ) ) {
			return;
		}

		$login        = new WBC_Login();
		$registration = new WBC_Registration();
		$lostpassword = new WBC_Lostpassword();
		add_action( 'login_form', array( $login, 'woo_extra_wp_login_form' ) );
		add_action( 'reign_recaptcha_after_login_form', array( $login, 'woo_extra_wp_login_form' ) );
		add_action( 'buddyxpro_recaptcha_after_login_form', array( $login, 'woo_extra_wp_login_form' ) );
		add_action( 'register_form', array( $registration, 'woo_extra_wp_register_form' ) );
		add_action( 'reign_recaptcha_after_register_form', array( $registration, 'woo_extra_wp_register_form' ) );
		add_action( 'buddyxpro_recaptcha_after_register_form', array( $registration, 'woo_extra_wp_register_form' ) );
		add_filter( 'registration_errors', array( $registration, 'woo_extra_validate_extra_register_fields' ), 10, 3 );
		add_action( 'lostpassword_form', array( $lostpassword, 'woo_extra_wp_lostpassword_form' ) );

		// WordPress core comment + lost-password CAPTCHA. These are core WP forms
		// (their toggles live under "WordPress" in the Protection tab), so render
		// AND verify must run independently of WooCommerce. Previously these were
		// registered only inside register_woocommerce_hooks(), so on non-Woo sites
		// the comment widget never rendered and neither comment nor lost-password
		// submissions were ever verified. Each callback self-checks its own
		// enable-flag, so registering them unconditionally here is safe.
		$wp_comment_field    = new Woocommerce_Order();
		$wp_comment_verifier = new Woocommerce_Filter();
		$wp_lostpassword     = new LostpasswordPost();
		add_filter( 'comment_form_fields', array( $wp_comment_field, 'woo_comment_form_captcha_field' ), 20 );
		add_filter( 'preprocess_comment', array( $wp_comment_verifier, 'woo_verify_comment_captcha' ), 10 );
		add_action( 'lostpassword_post', array( $wp_lostpassword, 'woocomm_validate_lostpassword_captcha' ), 10, 1 );

		$is_wp_login_recaptcha_enabled = get_option( 'wbc_recaptcha_enable_on_wplogin' );
		if ( 'yes' === $is_wp_login_recaptcha_enabled ) {
			// Server-side CAPTCHA validation for WordPress core login (/wp-login.php).
			// This MUST be registered independently of WooCommerce, because
			// wp_authenticate_user fires for every authentication path (wp-login.php submit
			// via button/Enter/direct POST, the WooCommerce login form, and any form built
			// with wp_login_form() that posts to wp-login.php), so attaching it here also
			// keeps the WooCommerce login path validated.
			$wp_login_filter = new Woocommerce_Filter();
			add_filter( 'wp_authenticate_user', array( $wp_login_filter, 'woo_wp_verify_login_captcha' ), 10, 2 );

			/*
			 * Render side for that same validator.
			 *
			 * `login_form` only fires on wp-login.php. Everything else that logs a user in
			 * through core - the Login/Logout block, the login widget, theme login modals -
			 * is built by wp_login_form(), which fires only login_form_top/middle/bottom.
			 * Registering the validator without this filter is what made login impossible
			 * from those forms: nothing rendered, yet every POST was verified.
			 *
			 * This is deliberately NOT gated on WooCommerce and NOT behind an extra opt-in
			 * toggle. Verification is already gated by wbc_recaptcha_enable_on_wplogin, and
			 * render must follow verify exactly - any gap between the two locks users out.
			 */
			add_filter( 'login_form_middle', array( $login, 'filter_login_form_middle' ), 10, 1 );

			add_action( 'bppcp_after_login_form', array( $registration, 'woo_extra_wp_register_form' ) );
			add_action( 'bppcp_after_register_form', array( $registration, 'woo_extra_wp_register_form' ) );
			add_action( 'bp_lock_after_login_form', array( $registration, 'woo_extra_wp_register_form' ) );
			add_action( 'bp_lock_after_register_form', array( $registration, 'woo_extra_wp_register_form' ) );
		}

		// Buddypress - register hooks via bp_init (will only fire if BuddyPress is active).
		add_action( 'bp_init', array( $this, 'register_buddypress_hooks' ), 10 );

		// Contact Form 7 - register hooks after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'register_cf7_hooks' ), 20 );

		// WPForms - register hooks after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'register_wpforms_hooks' ), 20 );

		// Gravity Forms - register hooks after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'register_gravityforms_hooks' ), 20 );

		// Ninja Forms - register hooks after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'register_ninjaforms_hooks' ), 20 );

		// Forminator - register hooks after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'register_forminator_hooks' ), 20 );

		// Elementor Pro - register hooks after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'register_elementorpro_hooks' ), 20 );

		// Divi's theme initializes ET_BUILDER_VERSION during init, not theme setup.
		add_action( 'init', array( $this, 'register_divi_hooks' ), 20 );

		// Easy Digital Downloads - register hooks after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'register_edd_hooks' ), 20 );

		// MemberPress - register hooks after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'register_memberpress_hooks' ), 20 );

		// Ultimate Member - register hooks after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'register_ultimatemember_hooks' ), 20 );

		// Register AJAX Login Widget and Block.
		add_action( 'widgets_init', array( $this, 'register_login_widget' ) );
		add_action( 'init', array( $this, 'register_login_block' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_login_widget_assets' ) );
		add_action( 'wp_ajax_wbc_ajax_login', array( $this, 'handle_ajax_login' ) );
		add_action( 'wp_ajax_nopriv_wbc_ajax_login', array( $this, 'handle_ajax_login' ) );

		// bbPress - only load if bbPress is active.
		if ( class_exists( 'bbPress' ) ) {
			$bbpress_topic_class = new Recaptcha_bbPress_Topic();
			// Priority 99 ensures reCAPTCHA appears after other form fields.
			add_action( 'bbp_theme_before_topic_form_submit_wrapper', array( $bbpress_topic_class, 'wbr_bbpress_topic_form_field' ), 99 );
			add_action( 'bbp_new_topic_pre_extras', array( $bbpress_topic_class, 'wbr_bbpress_topic_recaptcha_verify' ) );
			// Remove non-existent method call: wbr_bbpress_topic_v2_checkbox_script.

			$bbpress_reply_class = new Recaptcha_bbPress_Reply();
			// Priority 99 ensures reCAPTCHA appears after other form fields.
			add_action( 'bbp_theme_before_reply_form_submit_wrapper', array( $bbpress_reply_class, 'wbr_bbpress_reply_form_field_reply' ), 99 );
			add_action( 'bbp_new_reply_pre_extras', array( $bbpress_reply_class, 'wbr_bbpress_reply_recaptcha_verify' ) );
			// Remove non-existent method call: wbr_bbpress_reply_v2_checkbox_script.
		}

		// WooCommerce - register hooks after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'register_woocommerce_hooks' ), 20 );

		// IE X-UA-Compatible meta tag. Registered unconditionally; the callback
		// self-gates to IE internally, so no bootstrap user-agent sniff is needed
		// (sniffing here would poison full-page caches with UA-specific hooks).
		add_action( 'wp_head', array( $plugin_public, 'woo_recaptcha_add_header_metadata_for_ie' ) );
		add_action( 'login_head', array( $plugin_public, 'woo_recaptcha_add_header_metadata_for_ie' ) );

		// Defer reCAPTCHA / Turnstile API scripts for ALL browsers. The script is
		// render-blocking otherwise, hurting LCP/FCP and Core Web Vitals.
		add_filter( 'script_loader_tag', array( $plugin_public, 'google_recaptcha_defer_parsing_of_js' ), 10 );

		/*
		 * `login_form_middle` used to be registered here, gated on WooCommerce being
		 * active plus an off-by-default `wbc_recaptcha_custom_wp_login_form_login`
		 * toggle, and pointed at Woocommerce_Login::woo_extra_login_fields(). That
		 * wiring could not work: the callback echoes instead of returning (so its
		 * markup landed outside the <form> and was never submitted) and it rendered
		 * the `woo_login` context, while the validator on wp_authenticate_user
		 * verifies `wp_login`. It is now registered once, unconditionally, next to
		 * that validator in define_public_hooks() - see WBC_Login::filter_login_form_middle().
		 */
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Recaptcha_For_BuddyPress_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Register BuddyPress hooks after bp_init
	 *
	 * @since     2.1.0
	 */
	public function register_buddypress_hooks() {
		$registration_bp = new Registrationbp();

		// Member registration hooks.
		add_action( 'bp_before_registration_submit_buttons', array( $registration_bp, 'woo_extra_bp_register_form' ), 36 );
		add_action( 'bp_signup_validate', array( $registration_bp, 'innovage_validate_user_registration' ) );

		// Group creation hooks.
		add_action( 'bp_after_group_details_creation_step', array( $registration_bp, 'render_bp_group_create_captcha' ), 10 );
		add_action( 'groups_group_before_save', array( $registration_bp, 'validate_bp_group_create_captcha' ), 10 );
	}

	/**
	 * Register Contact Form 7 hooks
	 *
	 * Only registers if Contact Form 7 plugin is active.
	 *
	 * @since     2.0.0
	 */
	public function register_cf7_hooks() {
		// Only load if Contact Form 7 is active.
		if ( ! class_exists( 'WPCF7' ) ) {
			return;
		}

		$cf7_form = new CF7_Form();

		// Render CAPTCHA in CF7 forms.
		add_filter( 'wpcf7_form_elements', array( $cf7_form, 'render_cf7_captcha' ), 10, 1 );

		// Validate CAPTCHA on CF7 form submission.
		add_filter( 'wpcf7_validate', array( $cf7_form, 'validate_cf7_captcha' ), 20, 2 );
	}

	/**
	 * Register WPForms hooks
	 *
	 * Only registers if WPForms plugin is active.
	 *
	 * @since     2.0.0
	 */
	public function register_wpforms_hooks() {
		// Only load if WPForms is active.
		if ( ! function_exists( 'wpforms' ) ) {
			return;
		}

		$wpforms_form = new WPForms_Form();

		// Render CAPTCHA in WPForms forms (priority 19 to appear before submit button).
		add_action( 'wpforms_frontend_output', array( $wpforms_form, 'render_wpforms_captcha' ), 19, 5 );

		// Validate CAPTCHA on WPForms form submission.
		add_action( 'wpforms_process', array( $wpforms_form, 'validate_wpforms_captcha' ), 10, 3 );
	}

	/**
	 * Register WooCommerce hooks
	 *
	 * Only registers if WooCommerce plugin is active.
	 * Called on plugins_loaded to ensure WooCommerce is loaded.
	 *
	 * @since     2.0.1
	 */
	public function register_woocommerce_hooks() {
		// Only load if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// WooCommerce Login, registration and lost password forms.
		$woocommerce_register      = new Woocommerce_Register();
		$woocommerce_login         = new Woocommerce_Login();
		$woocommerce_lost_password = new Woocommerce_Lostpassword();
		add_action( 'woocommerce_register_form', array( $woocommerce_register, 'woo_extra_register_fields' ) );
		add_action( 'woocommerce_login_form', array( $woocommerce_login, 'woo_extra_login_fields' ) );
		add_action( 'woocommerce_lostpassword_form', array( $woocommerce_lost_password, 'woo_extra_lostpassword_fields' ) );

		// WooCommerce validation hooks.
		$woocommerce_review_order              = new Woocommerce_Review_Order();
		$woocommerce_register_post             = new Woocommerce_Register_Post();
		$woocommerce_process_login_errors      = new Woocommerce_Process_Login_Errors();
		$woocommerce_after_checkout_validation = new Woocommerce_After_Checkout_Validation();
		add_action( 'woocommerce_review_order_before_submit', array( $woocommerce_review_order, 'woo_extra_checkout_fields' ) );
		add_action( 'woocommerce_register_post', array( $woocommerce_register_post, 'woocomm_validate_signup_captcha' ), 10, 3 );
		add_action( 'woocommerce_process_login_errors', array( $woocommerce_process_login_errors, 'woocomm_validate_login_captcha' ), 10, 3 );
		add_action( 'woocommerce_after_checkout_validation', array( $woocommerce_after_checkout_validation, 'woocomm_validate_checkout_captcha' ), 10, 2 );

		// Note: the WP core login validator (wp_authenticate_user), the comment form
		// CAPTCHA (display + verify), and the lost-password validator (lostpassword_post)
		// are all registered in define_public_hooks() so they run with or without
		// WooCommerce. They are deliberately NOT re-registered here - a second
		// registration would verify the single-use CAPTCHA token twice and reject it
		// on the second check.
		$woocommerce_order = new Woocommerce_Order();

		// WooCommerce Order hooks.
		add_action( 'woocommerce_pay_order_before_submit', array( $woocommerce_order, 'woo_extra_checkout_fields_pay_order' ) );
		add_action( 'woocommerce_before_pay_action', array( $woocommerce_order, 'woo_validate_pay_order_captcha' ) );
		// Priority 999 ensures reCAPTCHA is added to checkout blocks last.
		add_filter( 'render_block_woocommerce/checkout-payment-block', array( $woocommerce_order, 'woo_recaptcha_alter_checkout_payment_block' ), 999, 1 );
		// Add CAPTCHA to WooCommerce product review forms.
		add_filter( 'woocommerce_product_review_comment_form_args', array( $woocommerce_order, 'woo_product_review_captcha_field' ), 10 );
	}

	/**
	 * Register Gravity Forms hooks
	 *
	 * Only registers if Gravity Forms plugin is active.
	 *
	 * @since     2.0.0
	 */
	public function register_gravityforms_hooks() {
		// Only load if Gravity Forms is active.
		if ( ! class_exists( 'GFForms' ) ) {
			return;
		}

		$gravityforms_form = new GravityForms_Form();

		// Render CAPTCHA in Gravity Forms (priority 10).
		add_filter( 'gform_get_form_filter', array( $gravityforms_form, 'render_gravityforms_captcha' ), 10, 2 );

		// Validate CAPTCHA on Gravity Forms form submission.
		add_filter( 'gform_validation', array( $gravityforms_form, 'validate_gravityforms_captcha' ), 10, 1 );
	}

	/**
	 * Register Ninja Forms hooks
	 *
	 * Only registers if Ninja Forms plugin is active.
	 *
	 * @since     2.0.0
	 */
	public function register_ninjaforms_hooks() {
		// Only load if Ninja Forms is active.
		if ( ! class_exists( 'Ninja_Forms' ) ) {
			return;
		}

		$ninjaforms_form = new NinjaForms_Form();

		// Render CAPTCHA in Ninja Forms (priority 999 to appear at the end).
		add_action( 'ninja_forms_display_after_fields', array( $ninjaforms_form, 'render_ninjaforms_captcha' ), 999 );

		// Validate CAPTCHA on Ninja Forms form submission.
		add_filter( 'ninja_forms_submit_data', array( $ninjaforms_form, 'validate_ninjaforms_captcha' ), 10, 1 );
	}

	/**
	 * Register Forminator hooks
	 *
	 * Only registers if Forminator plugin is active.
	 *
	 * @since     2.0.0
	 */
	public function register_forminator_hooks() {
		// Only load if Forminator is active.
		if ( ! class_exists( 'Forminator' ) ) {
			return;
		}

		$forminator_form = new Forminator_Form();

		// Render CAPTCHA in Forminator forms (priority 10).
		add_filter( 'forminator_render_button_markup', array( $forminator_form, 'render_forminator_captcha' ), 10, 2 );

		// Validate CAPTCHA on Forminator form submission.
		add_filter( 'forminator_custom_form_submit_errors', array( $forminator_form, 'validate_forminator_captcha' ), 10, 3 );
	}

	/**
	 * Register Elementor Pro hooks
	 *
	 * Only registers if Elementor Pro plugin is active.
	 *
	 * @since     2.0.0
	 */
	public function register_elementorpro_hooks() {
		// Only load if Elementor Pro is active.
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return;
		}

		$elementorpro_form = new ElementorPro_Form();

		// Render CAPTCHA in Elementor Pro forms (priority 10).
		add_action( 'elementor_pro/forms/render/item', array( $elementorpro_form, 'render_elementorpro_captcha' ), 10, 3 );

		// Validate CAPTCHA on Elementor Pro form submission.
		add_action( 'elementor_pro/forms/validation', array( $elementorpro_form, 'validate_elementorpro_captcha' ), 10, 2 );
	}

	/**
	 * Register Divi Builder hooks
	 *
	 * Only registers if Divi Builder is active.
	 *
	 * @since     2.0.0
	 */
	public function register_divi_hooks() {
		// Only load if Divi Builder is active.
		if ( ! defined( 'ET_BUILDER_VERSION' ) ) {
			return;
		}

		$divi_form = new Divi_Form();

		// Render CAPTCHA in Divi contact forms (priority 10).
		add_filter( 'et_pb_contact_form_shortcode_output', array( $divi_form, 'render_divi_captcha' ), 10, 2 );
		add_filter( 'render_block_divi/contact-form', array( $divi_form, 'render_divi_block_captcha' ), 10, 2 );

		// Both handlers process mail during rendering; check before they run.
		add_filter( 'divi_module_library_register_module_attrs', array( $divi_form, 'validate_divi5_captcha' ), 20, 2 );
		add_filter( 'pre_do_shortcode_tag', array( $divi_form, 'validate_divi_shortcode_captcha' ), 10, 2 );
	}

	/**
	 * Register Easy Digital Downloads hooks
	 *
	 * Only registers if Easy Digital Downloads plugin is active.
	 *
	 * @since     2.0.0
	 */
	public function register_edd_hooks() {
		// Only load if Easy Digital Downloads is active.
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			return;
		}

		$edd_form = new EDD_Form();

		// Render CAPTCHA in EDD forms.
		add_action( 'edd_purchase_form_before_submit', array( $edd_form, 'render_edd_checkout_captcha' ), 10 );
		add_action( 'edd_login_fields_after', array( $edd_form, 'render_edd_login_captcha' ), 10 );
		add_action( 'edd_register_fields_after', array( $edd_form, 'render_edd_register_captcha' ), 10 );

		// Validate CAPTCHA on EDD form submissions.
		add_action( 'edd_checkout_error_checks', array( $edd_form, 'validate_edd_checkout_captcha' ), 10, 2 );
		add_action( 'edd_process_login_form', array( $edd_form, 'validate_edd_login_captcha' ), 10, 1 );
		add_action( 'edd_process_register_form', array( $edd_form, 'validate_edd_register_captcha' ), 10, 1 );
	}

	/**
	 * Register MemberPress hooks
	 *
	 * Only registers if MemberPress plugin is active.
	 *
	 * @since     2.0.0
	 */
	public function register_memberpress_hooks() {
		// Only load if MemberPress is active.
		if ( ! defined( 'MEPR_VERSION' ) ) {
			return;
		}

		$memberpress_form = new MemberPress_Form();

		// Render CAPTCHA in MemberPress forms.
		add_action( 'mepr-login-form-before-submit', array( $memberpress_form, 'render_memberpress_login_captcha' ), 10 );
		add_action( 'mepr-signup-form-before-submit', array( $memberpress_form, 'render_memberpress_register_captcha' ), 10 );

		// Validate CAPTCHA on MemberPress form submissions.
		add_filter( 'mepr-validate-login', array( $memberpress_form, 'validate_memberpress_login_captcha' ), 10, 1 );
		add_filter( 'mepr-validate-signup', array( $memberpress_form, 'validate_memberpress_register_captcha' ), 10, 1 );
	}

	/**
	 * Register Ultimate Member hooks
	 *
	 * Only registers if Ultimate Member plugin is active.
	 *
	 * @since     2.0.0
	 */
	public function register_ultimatemember_hooks() {
		// Only load if Ultimate Member is active.
		if ( ! defined( 'ultimatemember_version' ) ) {
			return;
		}

		$um_form = new UltimateMember_Form();

		// Render CAPTCHA in Ultimate Member forms (before submit button).
		add_action( 'um_after_form_fields', array( $um_form, 'render_um_login_captcha' ), 10, 1 );
		add_action( 'um_after_form_fields', array( $um_form, 'render_um_register_captcha' ), 10, 1 );
		add_action( 'um_after_form_fields', array( $um_form, 'render_um_password_captcha' ), 10, 1 );

		// Validate CAPTCHA on Ultimate Member form submissions.
		add_action( 'um_submit_form_errors_hook', array( $um_form, 'validate_um_login_captcha' ), 10, 1 );
		add_action( 'um_submit_form_errors_hook', array( $um_form, 'validate_um_register_captcha' ), 10, 1 );
		add_action( 'um_submit_form_errors_hook', array( $um_form, 'validate_um_password_captcha' ), 10, 1 );
	}

	/**
	 * Register login widget
	 *
	 * @since     2.0.0
	 */
	public function register_login_widget() {
		register_widget( 'WBC_Login_Widget' );
	}

	/**
	 * Register login block
	 *
	 * @since     2.0.0
	 */
	public function register_login_block() {
		$block = new WBC_Login_Block();
		$block->register_block();
	}

	/**
	 * Enqueue login widget assets
	 *
	 * @since     2.0.0
	 */
	public function enqueue_login_widget_assets() {
		// Enqueue CSS.
		wp_enqueue_style(
			'wbc-ajax-login',
			plugin_dir_url( __DIR__ ) . 'public/css/wbc-ajax-login.css',
			array(),
			$this->version,
			'all'
		);

		// Enqueue JavaScript.
		wp_enqueue_script(
			'wbc-ajax-login',
			plugin_dir_url( __DIR__ ) . 'public/js/wbc-ajax-login.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Get current CAPTCHA service. Must read the same option key the service
		// manager writes ('wbc_captcha_service'), and the same service-id format it
		// stores (e.g. 'recaptcha-v2'), so the JS can reset the correct widget after
		// a failed AJAX login.
		$service_id = get_option( 'wbc_captcha_service', 'recaptcha-v2' );

		// Localize script.
		wp_localize_script(
			'wbc-ajax-login',
			'wbcAjaxLogin',
			array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'recaptchaType' => $service_id,
				'errorMessage'  => __( 'An error occurred. Please try again.', 'buddypress-recaptcha' ),
			)
		);
	}

	/**
	 * Handle AJAX login request
	 *
	 * @since     2.0.0
	 */
	public function handle_ajax_login() {
		$handler = new WBC_AJAX_Login_Handler();
		$handler->handle_ajax_login();
	}
}
