<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Divi Builder Integration
 *
 * Handles CAPTCHA rendering and validation for Divi Builder contact forms.
 *
 * @link       https://wbcomdesigns.com/
 * @since      2.0.0
 *
 * @package    Recaptcha_For_BuddyPress
 * @subpackage bp_recaptcha/public/divi-classes
 */

/**
 * Divi Builder CAPTCHA Handler
 *
 * Integrates CAPTCHA protection with Divi Builder contact forms.
 *
 * @package    Recaptcha_For_BuddyPress
 * @subpackage bp_recaptcha/public/divi-classes
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
class Divi_Form {
	/** @var array CAPTCHA outcomes, cached only for this HTTP request. */
	private $verification_results = array();

	/** @var array Error messages keyed by Divi's submitted form identifier. */
	private $submission_errors = array();

	/**
	 * Render CAPTCHA in Divi Builder contact forms
	 *
	 * Adds CAPTCHA HTML to the contact form module.
	 *
	 * @param string $output The form output.
	 * @param string $module_slug The module slug.
	 * @return string Modified form output with CAPTCHA.
	 */
	public function render_divi_captcha( $output, $module_slug ) {
		// Only process contact form modules.
		if ( ! is_string( $output ) || ! in_array( $module_slug, array( 'et_pb_contact_form', 'divi/contact-form' ), true ) ) {
			return $output;
		}

		// Do not create live challenges while editing or insert the widget twice.
		if ( ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) || false !== strpos( $output, 'wbc-divi-captcha' ) ) {
			return $output;
		}

		// Divi 4 and 5 both render this nonce name in the corresponding form.
		$form_id = '';
		if ( preg_match( '~name=[\'"]_wpnonce-et-pb-contact-form-submitted-([^\'"]+)[\'"]~', $output, $nonce_match ) ) {
			$form_id = $nonce_match[1];
		}
		if ( isset( $this->submission_errors[ $form_id ] ) ) {
			$error_html = '<p class="et_pb_contact_error_text" role="alert">' . esc_html( $this->submission_errors[ $form_id ] ) . '</p>';
			$output = preg_replace_callback(
				'~<div\b[^>]*class=[\'"][^\'"]*\bet-pb-contact-message\b[^\'"]*[\'"][^>]*>~i',
				static function ( $match ) use ( $error_html ) {
					return $match[0] . $error_html;
				},
				$output,
				1
			);
		}

		// Match a class token, allowing other classes and attributes in Divi 5.
		$submit_pattern = '~<div\b[^>]*\bclass\s*=\s*([\'"])[^\'"]*\bet_contact_bottom_container\b[^\'"]*\1[^>]*>~i';
		if ( ! preg_match( $submit_pattern, $output ) ) {
			return $output;
		}

		// Check if CAPTCHA is enabled for Divi.
		$is_enabled = get_option( 'wbc_recaptcha_enable_on_divi' );
		if ( 'yes' !== $is_enabled ) {
			return $output;
		}

		// Render CAPTCHA using service manager.
		if ( function_exists( 'wbc_captcha_service_manager' ) ) {
			$suffix = '' !== $form_id ? substr( hash( 'sha256', $form_id ), 0, 16 ) : wp_unique_id();
			ob_start();
			// Keep provider fields out of Divi's custom-field validation metadata.
			echo '<div class="wbc-divi-captcha" style="clear:both;flex:0 0 100%;width:100%">';
			wbc_captcha_service_manager()->render( 'divi', array( 'instance_id' => $suffix ) );
			echo '</div>';
			$captcha_html = ob_get_clean();

			// Scope provider container IDs to this form as well.
			$captcha_html = str_replace( array( 'h-captcha-divi-wbc', 'cf-turnstile-divi-wbc', 'altcha-divi-wbc' ), array( 'h-captcha-divi-wbc_' . $suffix, 'cf-turnstile-divi-wbc_' . $suffix, 'altcha-divi-wbc_' . $suffix ), $captcha_html );

			wp_enqueue_script( 'wbc-divi-captcha', plugin_dir_url( __DIR__ ) . 'js/wbc-divi-captcha.js', array(), RFB_PLUGIN_VERSION, true );
			wp_localize_script( 'wbc-divi-captcha', 'wbcDiviCaptcha', array( 'error' => wbc_get_captcha_error_message( 'divi', 'invalid' ) ) );

			// Insert CAPTCHA before the submit button.
			$output = preg_replace_callback(
				$submit_pattern,
				static function ( $matches ) use ( $captcha_html ) {
					return $captcha_html . $matches[0];
				},
				$output,
				1
			);
		}

		return $output;
	}

	/**
	 * Render the CAPTCHA in the native Divi 5 contact-form block.
	 *
	 * @param string $output Rendered block HTML.
	 * @param array  $block  Parsed block data.
	 * @return string
	 */
	public function render_divi_block_captcha( $output, $block ) {
		if ( 'divi/contact-form' !== ( $block['blockName'] ?? '' ) ) {
			return $output;
		}

		return $this->render_divi_captcha( $output, 'divi/contact-form' );
	}

	/**
	 * Verify before Divi 5 constructs ContactFormHandler (which sends the mail).
	 *
	 * @param array $attrs Module attributes.
	 * @param array $args Module registration arguments.
	 * @return array
	 */
	public function validate_divi5_captcha( $attrs, $args ) {
		if ( 'divi/contact-form' !== ( $args['name'] ?? '' ) || ! isset( $args['id'], $args['storeInstance'] ) ) {
			return $attrs;
		}
		$store = '\\ET\\Builder\\FrontEnd\\BlockParser\\BlockParserStore';
		$utils = '\\ET\\Builder\\Packages\\ModuleLibrary\\ContactForm\\ContactFormUtils';
		if ( ! class_exists( $store ) || ! class_exists( $utils ) ) {
			return $attrs;
		}
		$module = $store::get( $args['id'], $args['storeInstance'] );
		if ( $module ) {
			$form_id = $utils::get_unique_id( $attrs, (array) $module );
			$this->verify_submission( $form_id, 'et-pb-contact-form-submit-' . $form_id );
		}
		return $attrs;
	}

	/**
	 * Check legacy forms before the shortcode callback sends mail.
	 *
	 * @param false|string $output Short-circuit result.
	 * @param string       $tag Shortcode tag.
	 * @return false|string
	 */
	public function validate_divi_shortcode_captcha( $output, $tag ) {
		if ( 'et_pb_contact_form' !== $tag || false !== $output ) {
			return $output;
		}
		foreach ( array_keys( $_POST ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified below.
			if ( preg_match( '/^_wpnonce-et-pb-contact-form-submitted-(\d+)$/', $key, $match ) ) {
				$this->verify_submission( $match[1], 'et-pb-contact-form-submit' );
			}
		}
		return $output;
	}

	/**
	 * Refuse failed submissions before Divi processes fields or sends mail.
	 *
	 * Divi 5 has no pre-send validation filter. Its handler considers a form
	 * submitted when its native nonce is present. On CAPTCHA failure, remove
	 * only this form's processing markers for this request and render a normal
	 * form with an escaped error through its HTML response. The newly rendered
	 * nonce remains valid for the next attempt; no theme files are changed.
	 *
	 * @param string $form_id Divi form identifier.
	 * @param string $nonce_action Divi nonce action.
	 * @return void
	 */
	private function verify_submission( $form_id, $nonce_action ) {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || 'yes' !== get_option( 'wbc_recaptcha_enable_on_divi' ) || ! function_exists( 'wbc_verify_captcha' ) ) {
			return;
		}
		$nonce_name = '_wpnonce-et-pb-contact-form-submitted-' . $form_id;
		if ( ! isset( $_POST[ $nonce_name ] ) || array_key_exists( $form_id, $this->verification_results ) ) {
			return;
		}
		// Leave invalid native nonces to Divi. Do not consume CAPTCHA tokens for them.
		if ( ! is_string( $_POST[ $nonce_name ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) ), $nonce_action ) ) {
			return;
		}
		$this->verification_results[ $form_id ] = wbc_verify_captcha( 'divi' );
		if ( ! $this->verification_results[ $form_id ] ) {
			$this->submission_errors[ $form_id ] = wbc_get_captcha_error_message( 'divi', 'invalid' );
			unset( $_POST[ $nonce_name ], $_POST[ 'et_pb_contactform_submit_' . $form_id ] );
		}
	}
}
