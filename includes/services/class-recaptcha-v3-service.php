<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Service class uses simplified naming convention.
/**
 * ReCAPTCHA v3 Service Implementation
 *
 * @package    Recaptcha_For_BuddyPress
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * ReCAPTCHA v3 service class.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 */
class WBC_Recaptcha_V3_Service extends WBC_Captcha_Service_Base {

	/**
	 * Service ID
	 *
	 * @var string
	 */
	protected $id = 'recaptcha-v3';

	/**
	 * Service name
	 *
	 * @var string
	 */
	protected $name = 'reCAPTCHA v3';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->init_hooks();
	}

	/**
	 * Initialize service configuration
	 */
	protected function init_config() {
		// Initialize any v3-specific configuration here.
		$this->config = array(
			'version'    => 'v3',
			'script_url' => 'https://www.google.com/recaptcha/api.js',
		);
	}

	/**
	 * Get script URL for the service
	 *
	 * @return string
	 */
	public function get_script_url() {
		$site_key = $this->get_site_key();
		return 'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $site_key );
	}

	/**
	 * Get script handle for the service
	 *
	 * @param string $context The context identifier.
	 * @return string
	 */
	public function get_script_handle( $context = 'default' ) {
		return 'wbc-recaptcha-v3';
	}

	/**
	 * Check if no-conflict mode is required
	 *
	 * @return bool
	 */
	public function requires_no_conflict() {
		return 'yes' === $this->get_no_conflict_setting();
	}

	/**
	 * Resolve no-conflict mode across both option spellings.
	 *
	 * No-conflict was stored per provider as `wbc_recapcha_no_conflict_v3`, and
	 * WBC_Settings_Migration consolidates it into `wbc_recapcha_no_conflict`. This
	 * service read only the `_v3` spelling, so on any site that had been migrated -
	 * i.e. the canonical key was the one holding the value - no-conflict mode was
	 * silently off. Resolve exactly the way wbc_get_no_conflict_option() does.
	 *
	 * @since 2.2.0
	 *
	 * @return string 'yes' or 'no'.
	 */
	private function get_no_conflict_setting() {
		if ( function_exists( 'wbc_get_no_conflict_option' ) ) {
			return wbc_get_no_conflict_option();
		}

		foreach ( array( 'wbc_recapcha_no_conflict', 'wbc_recapcha_no_conflict_v3' ) as $key ) {
			if ( 'yes' === get_option( $key ) ) {
				return 'yes';
			}
		}

		return 'no';
	}

	/**
	 * Get the verification endpoint URL
	 *
	 * @return string
	 */
	public function get_verify_endpoint() {
		return 'https://www.google.com/recaptcha/api/siteverify';
	}

	/**
	 * Get form field name for the response
	 *
	 * @return string
	 */
	public function get_response_field_name() {
		return 'g-recaptcha-response';
	}

	/**
	 * Get URLs that might conflict
	 *
	 * @return array
	 */
	protected function get_conflict_urls() {
		return array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );
	}

	/**
	 * Get allowed script handles
	 *
	 * @return array
	 */
	protected function get_allowed_handles() {
		return array( 'wbc-recaptcha-v3', 'wbc-woo-captcha-v3' );
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Enqueue scripts when needed.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) );
	}

	/**
	 * Get site key
	 *
	 * @return string
	 */
	public function get_site_key() {
		// Try standard format first (with underscore).
		$site_key = get_option( 'wbc_recaptcha_v3_site_key', '' );
		if ( empty( $site_key ) ) {
			// Fallback to hyphen format for backward compatibility.
			$site_key = get_option( 'wbc_recaptcha-v3_site_key', '' );
		}
		return $site_key;
	}

	/**
	 * Get secret key
	 *
	 * @return string
	 */
	public function get_secret_key() {
		// Try standard format first (with underscore).
		$secret_key = get_option( 'wbc_recaptcha_v3_secret_key', '' );
		if ( empty( $secret_key ) ) {
			// Fallback to hyphen format for backward compatibility.
			$secret_key = get_option( 'wbc_recaptcha-v3_secret_key', '' );
		}
		return $secret_key;
	}

	/**
	 * Check if service is properly configured
	 *
	 * @return bool
	 */
	public function is_configured() {
		$site_key   = $this->get_site_key();
		$secret_key = $this->get_secret_key();

		return ! empty( $site_key ) && ! empty( $secret_key );
	}

	/**
	 * Render captcha
	 *
	 * @param string $context The context where captcha is being rendered.
	 * @param array  $args    Additional arguments.
	 * @return void
	 */
	public function render( $context = '', $args = array() ) {
		if ( ! $this->should_render( $context ) ) {
			return;
		}

		if ( ! $this->is_configured() ) {
			if ( current_user_can( 'manage_options' ) ) {
				echo '<p class="wbc-captcha-error">' . esc_html__( 'reCAPTCHA v3 is not properly configured. Please check your settings.', 'buddypress-recaptcha' ) . '</p>';
			}
			return;
		}

		$site_key       = $this->get_site_key();
		$action         = $this->get_action_for_context( $context );
		$token_field_name = 'wbc_recaptcha_' . $context . '_token';
		$token_field_id   = $token_field_name;
		if ( ! empty( $args['instance_id'] ) ) {
			$token_field_id .= '_' . sanitize_key( $args['instance_id'] );
		}

		// Add hidden field for token.
		echo '<input type="hidden" name="' . esc_attr( $token_field_name ) . '" id="' . esc_attr( $token_field_id ) . '" value="" />';

		// Add nonce for security.
		wp_nonce_field( 'wbc_captcha_' . $context, 'wbc_captcha_nonce_' . $context );

		// Enqueue script if not already enqueued.
		$this->enqueue_script();

		// Add inline script for this specific form.
		$this->add_inline_script( $context, $site_key, $action, $token_field_id );
	}

	/**
	 * Verify captcha response
	 *
	 * @param string $response The captcha response (token).
	 * @param array  $args     Additional arguments including context.
	 * @return bool
	 */
	public function verify( $response, $args = array() ) {
		// Extract context from args for backward compatibility.
		$context = isset( $args['context'] ) ? $args['context'] : '';

		// Skip only when render() would also have been skipped (IP allowlist / filter).
		if ( $this->should_skip_verification( $context ) ) {
			return true;
		}

		// Skip nonce verification if not in POST context or nonce not set.
		// Nonce is optional as many forms don't use our custom nonce.
		$nonce_field  = 'wbc_captcha_nonce_' . $context;
		$nonce_action = 'wbc_captcha_' . $context;

		if ( isset( $_POST[ $nonce_field ] ) ) {
			// Only verify if nonce is present.
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ), $nonce_action ) ) {
				return false;
			}
		}

		// Use provided response or get token from POST data.
		$token = '';

		if ( ! empty( $response ) ) {
			$token = $response;
		} else {
			// Get token from POST data.
			$token_field_id = 'wbc_recaptcha_' . $context . '_token';
			$token          = isset( $_POST[ $token_field_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ $token_field_id ] ) ) : '';

			if ( empty( $token ) ) {
				// Try legacy field names for backward compatibility.
				$legacy_fields = array(
					'wbc_recaptcha_wp_login_token',
					'wbc_recaptcha_wp_register_token',
					'wbc_recaptcha_wp_lostpassword_token',
					'wbc_login_token',
					'wbc_signup_token',
					'wbc_lostpassword_token',
					'wbc_checkout_token',
					'wbc_recaptcha_bbpress_topic_token',
					'wbc_recaptcha_bbpress_reply_token',
				);

				foreach ( $legacy_fields as $field ) {
					if ( isset( $_POST[ $field ] ) && ! empty( $_POST[ $field ] ) ) {
						$token = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
						break;
					}
				}
			}
		}

		if ( empty( $token ) ) {
			return false;
		}

		// Verify with Google.
		$secret_key = $this->get_secret_key();
		$remote_ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => $secret_key,
					'response' => $token,
					'remoteip' => $remote_ip,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );
		if ( empty( $response_body ) ) {
			return false;
		}

		$result = json_decode( $response_body, true );

		if ( ! isset( $result['success'] ) || ! $result['success'] ) {
			return false;
		}

		// Check score threshold.
		$score_threshold = $this->get_score_threshold( $context );
		$score           = isset( $result['score'] ) ? floatval( $result['score'] ) : 0;

		if ( $score < $score_threshold ) {
			return false;
		}

		// Check action if configured.
		$expected_action = $this->get_action_for_context( $context );
		if ( ! empty( $expected_action ) && isset( $result['action'] ) && $result['action'] !== $expected_action ) {
			return false;
		}

		// Allow filtering of result.
		//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( 'wbc_recaptcha_v3_verify', true, $result, $context );
	}

	/**
	 * Get action name for context
	 *
	 * @param string $context The context identifier.
	 * @return string
	 */
	private function get_action_for_context( $context ) {
		$action_map = array(
			'wp_login'           => get_option( 'wbc_recapcha_wp_login_method_action_v3', 'wp_login' ),
			'wp_register'        => get_option( 'wbc_recapcha_wp_register_method_action_v3', 'wp_registration' ),
			'wp_lostpassword'    => get_option( 'wbc_recapcha_wp_lostpassword_method_action_v3', 'wp_lostpassword' ),
			'woo_login'          => get_option( 'wbc_recapcha_login_action_v3', 'login' ),
			'woo_register'       => get_option( 'wbc_recapcha_signup_action_v3', 'signup' ),
			'woo_lostpassword'   => get_option( 'wbc_recapcha_lostpassword_action_v3', 'lostpassword' ),
			'woo_checkout_guest' => get_option( 'wbc_recapcha_checkout_action_v3', 'checkout' ),
			'woo_checkout_login' => get_option( 'wbc_recapcha_checkout_action_v3', 'checkout' ),
			'bp_register'        => get_option( 'wbc_recapcha_signup_action_v3_bp', 'signup' ),
			'bbpress_topic'      => get_option( 'wbc_recapcha_bbpress_topic_action_v3', 'bbPress_topic' ),
			'bbpress_reply'      => get_option( 'wbc_recapcha_bbpress_reply_action_v3', 'bbPress_reply' ),
			'comment_form'       => 'comment',
			'order_tracking'     => 'order_tracking',
		);

		return isset( $action_map[ $context ] ) ? $action_map[ $context ] : $context;
	}

	/**
	 * Get score threshold for context
	 *
	 * @param string $context The context identifier.
	 * @return float
	 */
	private function get_score_threshold( $context ) {
		// Primary source: the global threshold the admin UI actually saves
		// (Advanced tab -> reCAPTCHA v3 score threshold). A single global value
		// applies to every context unless a legacy per-context override exists.
		$global = get_option( 'wbc_recaptcha_v3_score_threshold', '' );

		// Legacy per-context overrides (pre-2.1 typo-spelled keys). Kept only as a
		// fallback so older installs that set these keep their tuned values.
		$legacy_map = array(
			'wp_login'           => 'wbc_recapcha_login_score_threshold_v3',
			'wp_register'        => 'wbc_recapcha_wp_register_score_threshold_v3',
			'wp_lostpassword'    => 'wbc_recapcha_wp_lostpassword_score_threshold_v3',
			'woo_login'          => 'wbc_recapcha_login_score_threshold_v3',
			'woo_register'       => 'wbc_recapcha_signup_score_threshold_v3',
			'woo_lostpassword'   => 'wbc_recapcha_lostpassword_score_threshold_v3',
			'woo_checkout_guest' => 'wbc_recapcha_checkout_score_threshold_v3',
			'woo_checkout_login' => 'wbc_recapcha_checkout_score_threshold_v3',
			'bp_register'        => 'wbc_recapcha_signup_score_threshold_v3_bp',
			'bbpress_topic'      => 'wbc_recapcha_bbpress_topic_score_threshold_v3',
			'bbpress_reply'      => 'wbc_recapcha_bbpress_reply_score_threshold_v3',
		);

		$legacy = isset( $legacy_map[ $context ] ) ? get_option( $legacy_map[ $context ], '' ) : '';

		// Prefer the global admin value; fall back to a legacy override; then 0.5.
		if ( '' !== $global ) {
			$threshold = $global;
		} elseif ( '' !== $legacy ) {
			$threshold = $legacy;
		} else {
			$threshold = '0.5';
		}

		/**
		 * Filter the reCAPTCHA v3 score threshold for a given context.
		 *
		 * @param float  $threshold The score threshold (0.0 - 1.0).
		 * @param string $context   The verification context.
		 */
		//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return (float) apply_filters( 'wbc_recaptcha_v3_score_threshold_value', floatval( $threshold ), $context );
	}

	/**
	 * Enqueue scripts for a specific context
	 *
	 * Required by WBC_Captcha_Service_Interface. Called by service manager.
	 *
	 * @param string $context The context where scripts are needed.
	 * @return void
	 */
	public function enqueue_scripts( $context = '' ) {
		if ( ! $this->is_configured() ) {
			return;
		}

		$this->enqueue_script();
	}

	/**
	 * Check if scripts should be enqueued
	 *
	 * @return void
	 */
	public function maybe_enqueue_scripts() {
		if ( ! $this->is_configured() ) {
			return;
		}

		/*
		 * Only the active provider may load its script.
		 *
		 * This service enqueues itself on wp_enqueue_scripts / login_enqueue_scripts,
		 * and it used to ask only "am I configured?". So a site that had once used
		 * reCAPTCHA v3 and then switched to another provider still had v3 keys in the
		 * options table, and the v3 api.js kept loading alongside the active provider -
		 * two CAPTCHA scripts on the same form.
		 */
		if ( ! $this->is_active_service() ) {
			return;
		}

		// Check if we're on a page that might need captcha.
		if ( $this->is_captcha_page() ) {
			$this->enqueue_script();
		}
	}

	/**
	 * Whether this provider is the one the site owner selected.
	 *
	 * @return bool
	 */
	private function is_active_service() {
		if ( ! class_exists( 'WBC_Captcha_Service_Manager' ) ) {
			return true;
		}

		$manager = WBC_Captcha_Service_Manager::get_instance();
		$active  = $manager ? $manager->get_active_service() : null;

		if ( ! $active ) {
			return true;
		}

		return $this->get_service_id() === $active->get_service_id();
	}

	/**
	 * Check if current page might need captcha
	 *
	 * @return bool
	 */
	private function is_captcha_page() {
		$is_captcha_page = false;

		// Login pages.
		if ( in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ), true ) ) {
			$is_captcha_page = true;
		}

		// WooCommerce pages.
		if ( ! $is_captcha_page && class_exists( 'WooCommerce' ) ) {
			if ( is_account_page() || is_checkout() ) {
				$is_captcha_page = true;
			}
		}

		// BuddyPress registration and group creation.
		if ( ! $is_captcha_page && function_exists( 'bp_is_register_page' ) && bp_is_register_page() ) {
			$is_captcha_page = true;
		}
		if ( ! $is_captcha_page && function_exists( 'bp_is_group_create' ) && bp_is_group_create() ) {
			$is_captcha_page = true;
		}

		// bbPress pages.
		if ( ! $is_captcha_page && class_exists( 'bbPress' ) ) {
			if ( is_singular( array( 'forum', 'topic' ) ) ) {
				$is_captcha_page = true;
			}
		}

		// Easy Digital Downloads checkout / account.
		if ( ! $is_captcha_page && function_exists( 'edd_is_checkout' ) && edd_is_checkout() ) {
			$is_captcha_page = true;
		}

		// Comments.
		if ( ! $is_captcha_page && is_singular() && comments_open() ) {
			$is_captcha_page = true;
		}

		/*
		 * This list is only a pre-warm: render() enqueues the script itself, so a form
		 * on any other page still works. It cannot be exhaustive, because the login
		 * widget, the Login/Logout block and every form-builder integration (CF7,
		 * WPForms, Gravity, Ninja, Forminator, Elementor, Divi, MemberPress, Ultimate
		 * Member) can appear on literally any page, including the front page.
		 *
		 * The filter lets a site pre-warm the script on pages this cannot know about -
		 * useful when a form is injected so late that it renders after the footer
		 * scripts have already printed.
		 *
		 * @since 2.2.0
		 *
		 * @param bool $is_captcha_page Whether to pre-load the v3 script on this request.
		 */
		//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return (bool) apply_filters( 'wbc_recaptcha_v3_is_captcha_page', $is_captcha_page );
	}

	/**
	 * Enqueue reCAPTCHA v3 script
	 *
	 * @return void
	 */
	private function enqueue_script() {
		if ( wp_script_is( 'wbc-recaptcha-v3', 'enqueued' ) || wp_script_is( 'wbc-recaptcha-v3', 'done' ) ) {
			return;
		}

		$site_key = $this->get_site_key();

		// Check for no-conflict mode.
		if ( 'yes' === $this->get_no_conflict_setting() ) {
			$this->dequeue_conflicting_scripts();
		}

		/*
		 * A form can render after the footer scripts have already printed (some
		 * form builders and page builders output that late). Enqueuing then is a
		 * silent no-op, so print the tag directly instead. Load order does not
		 * matter: the bootstrap in add_inline_script() waits for grecaptcha.
		 *
		 * The printed tag is invisible to wp_script_is(), so the guard above cannot
		 * see it. Track it separately - the manager calls enqueue_scripts() and then
		 * render() calls enqueue_script() again, which would otherwise emit api.js
		 * twice with the same DOM id and load Google's script twice.
		 */
		if ( $this->scripts_already_printed() ) {
			if ( self::$printed_late_script_tag ) {
				return;
			}
			self::$printed_late_script_tag = true;

			wp_print_script_tag(
				array(
					'id'    => 'wbc-recaptcha-v3-js',
					'src'   => 'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $site_key ),
					'defer' => true,
				)
			);
			return;
		}

		wp_enqueue_script(
			'wbc-recaptcha-v3',
			'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $site_key ),
			array(),
			'3.0',
			true
		);
	}

	/**
	 * Whether the deferred api.js tag has already been printed directly this request.
	 *
	 * Only used on the late-render path, where the tag never enters the WP script
	 * registry and so is invisible to wp_script_is().
	 *
	 * @since 2.2.0
	 * @var bool
	 */
	private static $printed_late_script_tag = false;

	/**
	 * Whether WordPress has already printed the footer scripts for this request.
	 *
	 * Past that point wp_enqueue_script() / wp_add_inline_script() do nothing at all
	 * and fail silently, which would leave the form with a hidden token field that
	 * nothing ever fills in.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	private function scripts_already_printed() {
		return (bool) did_action( 'wp_print_footer_scripts' );
	}

	/**
	 * Add inline script for specific form
	 *
	 * @param string $context The context identifier.
	 * @param string $site_key        The reCAPTCHA site key.
	 * @param string $action           The reCAPTCHA action name.
	 * @param string $token_field_id   The token field ID.
	 * @return void
	 */
	private function add_inline_script( $context, $site_key, $action, $token_field_id ) {
		/*
		 * This inline script is attached to the `wbc-recaptcha-v3` handle, which IS
		 * Google's api.js — and that tag is served with `defer` (see
		 * Recaptcha_For_BuddyPress_Public::google_recaptcha_defer_parsing_of_js()).
		 *
		 * An inline "after" script is NOT deferred: it runs during parsing, while the
		 * deferred api.js only runs once parsing has finished. So anything that touches
		 * `grecaptcha` at top level throws "grecaptcha is not defined", which aborted
		 * this whole IIFE — no initial token, no refresh interval, no submit handler.
		 * The hidden token field then stayed empty and every v3 context failed
		 * server-side verification.
		 *
		 * Everything below therefore waits for `grecaptcha` to exist before touching it,
		 * and the form is held back on submit if the token has not landed yet, rather
		 * than posting empty and bouncing the user back to a re-rendered form.
		 */
		$script = "
		(function() {
			var siteKey = '" . esc_js( $site_key ) . "';
			var action = '" . esc_js( $action ) . "';
			var tokenFieldId = '" . esc_js( $token_field_id ) . "';
			var MAX_WAIT = 20000;
			var POLL_INTERVAL = 50;

			function getField() {
				return document.getElementById(tokenFieldId);
			}

			// Shared by every v3 form on the page. refresh(fieldId) lets a form that sends
			// itself (the AJAX login widget) fetch a fresh, single-use token right before
			// sending. Token requests run one at a time, so a refresh made while the
			// page's first token is still loading waits for it: even if the refresh
			// itself fails, the field then holds the newest token there is.
			var v3 = window.wbcRecaptchaV3 = window.wbcRecaptchaV3 || {
				queue: Promise.resolve(),
				refreshers: {},
				refresh: function(fieldId) {
					var refresher = this.refreshers[fieldId];
					return refresher ? refresher() : Promise.resolve('');
				}
			};

			// Resolve once the deferred api.js has executed and defined grecaptcha.
			function whenGrecaptchaReady(callback, onTimeout) {
				if (window.grecaptcha && typeof window.grecaptcha.ready === 'function') {
					window.grecaptcha.ready(callback);
					return;
				}
				var waited = 0;
				var timer = setInterval(function() {
					if (window.grecaptcha && typeof window.grecaptcha.ready === 'function') {
						clearInterval(timer);
						window.grecaptcha.ready(callback);
						return;
					}
					waited += POLL_INTERVAL;
					if (waited >= MAX_WAIT) {
						clearInterval(timer);
						console.error('reCAPTCHA v3: api.js did not load within ' + (MAX_WAIT / 1000) + 's.');
						onTimeout();
					}
				}, POLL_INTERVAL);
			}

			// Generate a token and write it into the hidden field. Always resolves, so a
			// caller waiting on it is never left hanging; an empty result never
			// overwrites a token that is already in the field.
			function generateToken() {
				var job = v3.queue.then(function() {
					return requestToken();
				});
				v3.queue = job;
				return job;
			}

			function requestToken() {
				return new Promise(function(resolve) {
					whenGrecaptchaReady(function() {
						window.grecaptcha.execute(siteKey, {action: action})
							.then(function(token) {
								var tokenField = getField();
								if (token && tokenField) {
									tokenField.value = token;
								}
								resolve(token || '');
							})
							.catch(function(error) {
								console.error('reCAPTCHA v3 error:', error);
								resolve('');
							});
					}, function() {
						resolve('');
					});
				});
			}
			v3.refreshers[tokenFieldId] = generateToken;

			// Initial token, then refresh every 110s (tokens expire after 120s).
			generateToken();
			setInterval(generateToken, 110000);

			function bindForm() {
				var tokenField = getField();
				if (!tokenField || !tokenField.form || tokenField.form.wbcV3Bound) {
					return;
				}
				var form = tokenField.form;
				form.wbcV3Bound = true;

				// Forms that send themselves (the AJAX login widget) request a fresh
				// token through wbcRecaptchaV3.refresh(). Holding and re-submitting them
				// here too would send the request twice, the first time with no token.
				if (form.hasAttribute('data-wbc-v3-self-submit')) {
					return;
				}

				form.addEventListener('submit', function(e) {
					var field = getField();

					// Token already present: refresh in the background, submit now.
					if (field && field.value) {
						generateToken();
						return;
					}

					// No token yet — hold the submit, fetch one, then resubmit once.
					if (form.wbcV3Submitting) {
						return;
					}
					form.wbcV3Submitting = true;
					var submitter = e.submitter || null;
					e.preventDefault();

					generateToken().then(function() {
						if (submitter && typeof form.requestSubmit === 'function') {
							form.requestSubmit(submitter);
						} else if (typeof form.requestSubmit === 'function') {
							form.requestSubmit();
						} else {
							if (submitter && submitter.name) {
								var proxy = document.createElement('input');
								proxy.type = 'hidden';
								proxy.name = submitter.name;
								proxy.value = submitter.value || '';
								form.appendChild(proxy);
							}
							form.submit();
						}
					});
				}, false);
			}

			if ('loading' === document.readyState) {
				document.addEventListener('DOMContentLoaded', bindForm);
			} else {
				bindForm();
			}
		})();
		";

		// Same late-render case as enqueue_script(): once the footer scripts are out,
		// wp_add_inline_script() is a silent no-op, so print the bootstrap directly.
		if ( $this->scripts_already_printed() ) {
			wp_print_inline_script_tag( $script );
			return;
		}

		wp_add_inline_script( 'wbc-recaptcha-v3', $script );
	}

	/**
	 * Dequeue conflicting reCAPTCHA scripts
	 *
	 * @return void
	 */
	private function dequeue_conflicting_scripts() {
		global $wp_scripts;

		if ( ! isset( $wp_scripts->queue ) ) {
			return;
		}

		$urls            = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );
		$allowed_handles = array( 'wbc-recaptcha-v3', 'wbc-woo-captcha-v3' );

		foreach ( $wp_scripts->queue as $handle ) {
			if ( in_array( $handle, $allowed_handles, true ) ) {
				continue;
			}

			if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
				continue;
			}

			foreach ( $urls as $url ) {
				if ( false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) ) {
					wp_dequeue_script( $handle );
					wp_deregister_script( $handle );
					break;
				}
			}
		}
	}
}
