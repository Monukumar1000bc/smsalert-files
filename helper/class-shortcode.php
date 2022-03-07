<?php
/**
 * Shortcode helper.
 *
 * @category Shortcode
 * @author   cozy vision technologies pvt ltd <support@cozyvision.com>
 * @package  Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	
/**
 * Shortcode class
 */
class Shortcode {

	/**
	 * Construct function.
	 */
	public function __construct() {
		$user_authorize = new smsalert_Setting_Options();
		if($user_authorize->is_user_authorised())
		{		   
		   add_shortcode( 'sa_loginwithotp', array( $this, 'add_sa_loginwithotp' ), 100 );
		   add_shortcode( 'sa_signupwithmobile', array( $this, 'add_sa_signupwithmobile' ), 100 );
		}
	}
	

	/**
	 * loginwithotp function.
	 *
	 * @return string
	 */
	public function add_sa_loginwithotp() {
		$enabled_login_with_otp = smsalert_get_option( 'login_with_otp', 'smsalert_general' );
		$unique_class    = 'sa-lwo-'.mt_rand(1,100);
		if ( ('on' !== $enabled_login_with_otp) || (is_user_logged_in() && !current_user_can('administrator')) ) {
			return;
		}	
		ob_start();
		global $wp;
		echo '<form class="sa-lwo-form sa_loginwithotp-form '.$unique_class.'" method="post" action="' . home_url($wp->request) . '/?option=smsalert_verify_login_with_otp">';
		get_smsalert_template( 'template/login-with-otp-form.php', array() );
		echo wp_nonce_field('smsalert_wp_loginwithotp_nonce','smsalert_loginwithotp_nonce', true, false);
		echo '</form><style>.sa_default_login_form{display:none;}</style>';
		echo do_shortcode( '[sa_verify phone_selector=".sa_mobileno" submit_selector= ".'.$unique_class.' .smsalert_login_with_otp_btn"]' );
		  ?>
		<script>
		setTimeout(function() {
			if(jQuery(".modal.smsalertModal").length==0)	
			{			
			var popup = '<?php echo str_replace(array("\n","\r","\r\n"),'',(get_smsalert_template( "template/otp-popup.php", array(), true))); ?>';
			jQuery('body').append(popup);
			}
		}, 200);
		</script>
		 <?php	
		$content = ob_get_clean();
        return $content;
	}
	
	/**
	 * signupwithmobile function.
	 *
	 * @return string
	 */
	public function add_sa_signupwithmobile() {
		$enabled_signup_with_mobile = smsalert_get_option( 'signup_with_mobile', 'smsalert_general' );
		$unique_class    = 'sa-swm-'.mt_rand(1,100);
		if ( ('on' !== $enabled_signup_with_mobile) || (is_user_logged_in() && !current_user_can('administrator')) ) {
			return;
		}	
		ob_start();
		global $wp;
		echo '<form class="sa-lwo-form sa-signupwithotp-form '.$unique_class.'" method="post" action="' . home_url($wp->request) . '/?option=signwthmob">';
		get_smsalert_template( 'template/sign-with-mobile-form.php', array() );
		echo wp_nonce_field('smsalert_wp_signupwithmobile_nonce','smsalert_signupwithmobile_nonce', true, false);
		echo '</form><style>.sa_default_signup_form{display:none;}</style>';
		echo do_shortcode( '[sa_verify phone_selector="#reg_with_mob" submit_selector= ".'.$unique_class.' .smsalert_reg_with_otp_btn"]' );
		  ?>
		<script>
		setTimeout(function() {
			if(jQuery(".modal.smsalertModal").length==0)	
			{			
			var popup = '<?php echo str_replace(array("\n","\r","\r\n"),'',(get_smsalert_template( "template/otp-popup.php", array(), true))); ?>';
			jQuery('body').append(popup);
			}
		}, 200);
		</script>
		 <?php		
		$content = ob_get_clean();
        return $content;
	}

}
new Shortcode();
?>
