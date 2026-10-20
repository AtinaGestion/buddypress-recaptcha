<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Service class uses simplified naming convention.
/**
 * ALTCHA Service
 *
 * @package    Recaptcha_For_BuddyPress
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- Helper function must be defined alongside service class.
// Load ALTCHA verification library if standalone altcha-spam-protection plugin not active.
if ( ! class_exists( 'AltchaPlugin' ) && ( ! function_exists( 'is_plugin_active' ) || ! is_plugin_active( 'altcha-spam-protection/altcha.php' ) ) ) {
	/*
	 * Load the bundled ALTCHA library.
	 *
	 * This was never required, so AltchaPlugin did not exist and the library's
	 * `rest_api_init` callback never ran - which meant /altcha/v1/challenge 404'd and
	 * the ALTCHA provider could not issue a proof-of-work challenge to its widget.
	 * The library defines the class, self-instantiates, and registers the route.
	 */
	require_once plugin_dir_path( __DIR__ ) . 'lib/altcha/class-altcha-lib.php';

	// Define a minimal wrapper for ALTCHA verification.
	if ( ! function_exists( 'altcha_random_secret' ) ) {
		/**
		 * Generate a random secret for ALTCHA.
		 *
		 * @param int $length The length of the secret.
		 * @return string The generated secret.
		 */
		function altcha_random_secret( $length = 64 ) {
			return bin2hex( random_bytes( $length / 2 ) );
		}
	}
}

/**
 * ALTCHA implementation - Privacy-first, self-hosted proof-of-work captcha
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 */
class WBC_Altcha_Service extends WBC_Captcha_Service_Base {
	/** @var array|null Bundled widget translations, loaded once per request. */
	private $widget_catalogs;

	/**
	 * Resolve the widget language independently of WordPress gettext catalogs.
	 *
	 * The explicit CAPTCHA language takes precedence over the current frontend
	 * locale. Passing strings in the element also works after AJAX replacement,
	 * without relying on a separate script's execution order or an external CDN.
	 *
	 * @return array
	 */
	private function get_widget_translations() {
		if ( null === $this->widget_catalogs ) {
			$this->widget_catalogs = wp_json_file_decode(
				dirname( __DIR__, 2 ) . '/languages/altcha/catalogs.json',
				array( 'associative' => true )
			) ?? array();
		}

		$language = trim( (string) get_option( 'wbc_recaptcha_language', '' ) );
		if ( '' === $language ) {
			$language = get_locale();
		}
		$language = strtolower( str_replace( '_', '-', $language ) );
		$base     = explode( '-', $language )[0];
		$aliases  = array( 'es' => 'es-es', 'fr' => 'fr-fr', 'pt' => 'pt-pt', 'no' => 'nb', 'iw' => 'he' );
		$alias    = $aliases[ $base ] ?? $base;
		if ( 'es' === $base && 'es' !== $language && 'es-es' !== $language ) {
			$alias = 'es-419';
		}
		foreach ( array( $language, $alias, $base, 'en' ) as $candidate ) {
			if ( isset( $this->widget_catalogs[ $candidate ] ) ) {
				return array( 'language' => $candidate, 'strings' => $this->widget_catalogs[ $candidate ] );
			}
		}

		// The bundled ALTCHA library itself always includes English defaults.
		return array( 'language' => 'en', 'strings' => array() );
	}

	/**
	 * Initialize service configuration
	 */
	protected function init_config() {
		$this->config = array(
			'service_id'      => 'altcha',
			'service_name'    => 'ALTCHA (Self-Hosted)',
			'script_url'      => plugins_url( 'public/js/altcha.min.js', dirname( __DIR__ ) ),
			'verify_endpoint' => '', // Server-side verification handled differently.
			'response_field'  => 'altcha',
		);
	}

	/**
	 * Get the service identifier
	 *
	 * @return string
	 */
	public function get_service_id() {
		return 'altcha';
	}

	/**
	 * Get the service display name
	 *
	 * @return string
	 */
	public function get_service_name() {
		// Translated here, not in init_config(): services are constructed while the
		// plugin file loads, long before init, and calling __() there triggered WP 6.7's
		// _load_textdomain_just_in_time notice on every page load.
		return __( 'ALTCHA (Self-Hosted)', 'buddypress-recaptcha' );
	}

	/**
	 * Get the site key (HMAC key for ALTCHA)
	 *
	 * @return string
	 */
	public function get_site_key() {
		return trim( get_option( 'wbc_altcha_hmac_key', '' ) );
	}

	/**
	 * Get the secret key (same as HMAC key for ALTCHA)
	 *
	 * @return string
	 */
	public function get_secret_key() {
		return trim( get_option( 'wbc_altcha_hmac_key', '' ) );
	}

	/**
	 * Get the script handle for this service
	 *
	 * @param string $context The context where the script is used.
	 * @return string
	 */
	public function get_script_handle( $context = 'default' ) {
		return 'wbc-altcha-captcha';
	}

	/**
	 * Get the script URL for this service
	 *
	 * @return string
	 */
	public function get_script_url() {
		return $this->config['script_url'];
	}

	/**
	 * Enqueue scripts for this service
	 *
	 * @param string $context The context where the script is used.
	 * @return void
	 */
	public function enqueue_scripts( $context = 'default' ) {
		$hmac_key = $this->get_site_key();
		if ( empty( $hmac_key ) ) {
			return;
		}

		wp_enqueue_script(
			$this->get_script_handle( $context ),
			$this->get_script_url(),
			array(),
			'2.0.0',
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		// Add module type attribute.
		add_filter( 'script_loader_tag', array( $this, 'add_module_type_attribute' ), 10, 2 );
	}

	/**
	 * Add type="module" to ALTCHA script tag
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public function add_module_type_attribute( $tag, $handle ) {
		if ( $this->get_script_handle() === $handle ) {
			$tag = str_replace( ' src=', ' type="module" src=', $tag );
		}
		return $tag;
	}

	/**
	 * Generate ALTCHA challenge
	 *
	 * @return array|false Challenge data or false on failure
	 */
	private function generate_challenge() {
		$hmac_key = $this->get_secret_key();
		if ( empty( $hmac_key ) ) {
			return false;
		}

		// Use ALTCHA plugin if available.
		if ( class_exists( 'AltchaPlugin' ) && isset( AltchaPlugin::$instance ) ) {
			$complexity = intval( get_option( 'wbc_altcha_max_number', 100000 ) );
			$expires    = intval( get_option( 'wbc_altcha_expires', 3600 ) );
			return AltchaPlugin::$instance->generate_challenge( $hmac_key, $complexity, $expires );
		}

		// Self-hosted generation.
		$salt       = bin2hex( random_bytes( 16 ) );
		$max_number = intval( get_option( 'wbc_altcha_max_number', 100000 ) );
		$expires    = intval( get_option( 'wbc_altcha_expires', 3600 ) );

		// Add expiration to salt.
		if ( $expires > 0 ) {
			$salt = $salt . '?expires=' . ( time() + $expires );
		}

		// Generate random number.
		$number    = wp_rand( 1, $max_number );
		$challenge = hash( 'sha256', $salt . $number );
		$signature = hash_hmac( 'sha256', $challenge, $hmac_key );

		return array(
			'algorithm' => 'SHA-256',
			'challenge' => $challenge,
			'maxnumber' => $max_number,
			'salt'      => $salt,
			'signature' => $signature,
		);
	}

	/**
	 * Check if HTTPS is required and available
	 *
	 * @return bool
	 */
	private function is_secure_context() {
		// Check if HTTPS is enabled.
		if ( is_ssl() ) {
			return true;
		}

		// Allow local development environments.
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) && 'local' === WP_ENVIRONMENT_TYPE ) {
			return true;
		}

		// Allow localhost and 127.0.0.1.
		$host = filter_input( INPUT_SERVER, 'HTTP_HOST', FILTER_SANITIZE_SPECIAL_CHARS );
		if ( empty( $host ) ) {
			return false;
		}

		if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
			return true;
		}

		// Allow .local domains (common in local dev).
		if ( preg_match( '/\.local$/i', $host ) ) {
			return true;
		}

		// Allow .test domains (Laravel Valet, etc.).
		if ( preg_match( '/\.test$/i', $host ) ) {
			return true;
		}

		// Allow .dev domains (though deprecated, some still use it).
		if ( preg_match( '/\.dev$/i', $host ) ) {
			return true;
		}

		// Check for WP_DEBUG mode as an override (optional, can be removed if too permissive).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ) {
			return true;
		}

		return false;
	}

	/**
	 * Render the captcha field
	 *
	 * @param string $context The context where captcha is rendered.
	 * @param array  $args    Additional arguments.
	 * @return void
	 */
	public function render( $context, $args = array() ) {
		$hmac_key = $this->get_site_key();
		if ( empty( $hmac_key ) ) {
			return;
		}

		// Check for secure context (HTTPS required for Web Crypto API).
		if ( ! $this->is_secure_context() ) {
			$current_host = filter_input( INPUT_SERVER, 'HTTP_HOST', FILTER_SANITIZE_SPECIAL_CHARS );
			$current_host = ! empty( $current_host ) ? $current_host : 'unknown';
			?>
			<div class="wbc_captcha_field wbc_altcha_field">
				<div class="wbc-altcha-warning" style="padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; color: #856404;">
					<strong><?php esc_html_e( 'ALTCHA requires HTTPS or localhost:', 'buddypress-recaptcha' ); ?></strong>
					<p><?php esc_html_e( 'ALTCHA uses Web Crypto API which requires a secure context (HTTPS). For local development, use localhost or .local/.test domains.', 'buddypress-recaptcha' ); ?></p>
					<p><strong><?php esc_html_e( 'Your current host:', 'buddypress-recaptcha' ); ?></strong> <?php echo esc_html( $current_host ); ?></p>
					<p><?php esc_html_e( 'For production: Enable HTTPS. For local testing: Use localhost, 127.0.0.1, or .local domains.', 'buddypress-recaptcha' ); ?></p>
				</div>
			</div>
			<?php
			return;
		}

		// Generate challenge.
		$challenge = $this->generate_challenge();
		if ( ! $challenge ) {
			return;
		}

		// Get settings.
		$auto_verify = get_option( 'wbc_altcha_auto_verify', 'off' );
		$hide_logo   = get_option( 'wbc_altcha_hide_logo', 'no' ) === 'yes';
		$translation = $this->get_widget_translations();

		// Generate unique ID.
		$widget_id = 'altcha-' . $context . '-wbc';

		// Get nonce.
		$nonce_action = $this->get_nonce_action( $context );

		// Render HTML.
		?>
		<input type="hidden" autocomplete="off" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( wp_create_nonce( $nonce_action ) ); ?>" />
		<div class="wbc_captcha_field wbc_altcha_field input">
			<altcha-widget
				id="<?php echo esc_attr( $widget_id ); ?>"
				language="<?php echo esc_attr( $translation['language'] ); ?>"
				<?php if ( ! empty( $translation['strings'] ) ) : ?>
				strings="<?php echo esc_attr( wp_json_encode( $translation['strings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); ?>"
				<?php endif; ?>
				challengejson='<?php echo wp_json_encode( $challenge ); ?>'
				<?php if ( 'off' !== $auto_verify ) : ?>
				auto="<?php echo esc_attr( $auto_verify ); ?>"
				<?php endif; ?>
				<?php if ( $hide_logo ) : ?>
				hidelogo
				<?php endif; ?>
				name="altcha"
			></altcha-widget>
		</div>
		<?php
	}

	/**
	 * Verify the captcha response
	 *
	 * @param string $response The captcha response (base64 encoded payload).
	 * @param array  $args     Additional arguments.
	 * @return bool
	 */
	public function verify( $response, $args = array() ) {
		// Skip only when render() would also have been skipped (IP allowlist / filter).
		// Checked before the key guard: unlike the other providers ALTCHA fails closed
		// when unconfigured, so an allowlisted visitor would otherwise be blocked by a
		// missing key on a form that never showed a widget.
		if ( $this->should_skip_verification( isset( $args['context'] ) ? $args['context'] : '' ) ) {
			return true;
		}

		$hmac_key = $this->get_secret_key();
		if ( empty( $hmac_key ) ) {
			return false; // ALTCHA requires HMAC key.
		}

		// Verify nonce if present.
		$context = isset( $args['context'] ) ? $args['context'] : '';
		if ( ! empty( $context ) ) {
			$nonce_action = $this->get_nonce_action( $context );
			if ( isset( $_POST[ $nonce_action ] ) ) {
				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_action ] ) ), $nonce_action ) ) {
					return false;
				}
			}
		}

		if ( empty( $response ) ) {
			return false;
		}

		// Use ALTCHA plugin if available.
		if ( class_exists( 'AltchaPlugin' ) && isset( AltchaPlugin::$instance ) ) {
			$verified = AltchaPlugin::$instance->verify( $response, $hmac_key );
		} else {
			// Self-hosted verification.
			$verified = $this->verify_solution( $response, $hmac_key );
		}
		//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( 'wbc_captcha_verified', $verified, array(), $response, $this->get_service_id() );
	}

	/**
	 * Verify solution manually (fallback if ALTCHA plugin not available)
	 *
	 * @param string $payload  Base64 encoded payload.
	 * @param string $hmac_key HMAC key.
	 * @return bool
	 */
	private function verify_solution( $payload, $hmac_key ) {
		// Validate base64 encoding.
		if ( ! preg_match( '/^[A-Za-z0-9+\/=]+$/', $payload ) ) {
			return false;
		}

		$decoded = base64_decode( $payload, true );
		if ( false === $decoded ) {
			return false;
		}

		$data = json_decode( $decoded );
		if ( ! is_object( $data ) ) {
			return false;
		}

		// Validate required properties exist.
		if ( ! isset( $data->algorithm, $data->challenge, $data->salt, $data->signature, $data->number ) ) {
			return false;
		}

		// Check expiration if present.
		if ( is_string( $data->salt ) ) {
			$salt_url = wp_parse_url( $data->salt );
			if ( is_array( $salt_url ) && isset( $salt_url['query'] ) && ! empty( $salt_url['query'] ) ) {
				parse_str( $salt_url['query'], $salt_params );
				if ( ! empty( $salt_params['expires'] ) ) {
					$expires = intval( $salt_params['expires'], 10 );
					if ( $expires > 0 && $expires < time() ) {
						return false;
					}
				}
			}
		}

		// Verify algorithm.
		$alg_ok = ( 'SHA-256' === $data->algorithm );

		// Verify challenge.
		$calculated_challenge = hash( 'sha256', $data->salt . $data->number );
		$challenge_ok         = ( $data->challenge === $calculated_challenge );

		// Verify signature.
		$calculated_signature = hash_hmac( 'sha256', $data->challenge, $hmac_key );
		$signature_ok         = ( $data->signature === $calculated_signature );

		return $alg_ok && $challenge_ok && $signature_ok;
	}

	/**
	 * Get the verification endpoint URL
	 *
	 * @return string
	 */
	public function get_verify_endpoint() {
		return ''; // ALTCHA verifies locally, no external endpoint.
	}

	/**
	 * Get form field name for the response
	 *
	 * @return string
	 */
	public function get_response_field_name() {
		return $this->config['response_field'];
	}

	/**
	 * Check if this service requires no-conflict mode
	 *
	 * @return bool
	 */
	public function requires_no_conflict() {
		return false;
	}
}
