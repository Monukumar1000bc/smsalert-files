(function( $ ) {
	'use strict';

	/**
	* All of the code for your public-facing JavaScript source
	* should reside in this file.
	*
	* Note: It has been assumed you will write jQuery code here, so the
	* $ function reference has been prepared for usage within the scope
	* of this function.
	*
	* This enables you to define handlers, for when the DOM is ready:
	*
	* $(function() {
	*
	* });
	*
	* When the window is loaded:
	*
	* $( window ).load(function() {
	*
	* });
	*
	* ...and/or other possibilities.
	*
	* Ideally, it is not considered best practise to attach more than a
	* single DOM-ready or window-load handler for a particular page.
	* Although scripts in the WordPress core, Plugins and Themes may be
	* practising this, we should strive to set a better example in our own work.
	*/

	jQuery(document).ready(function(){

		var timer;

		function getCheckoutData() { //Reading WooCommerce field values
			if(typeof sa_otp_settings !=  'undefined' && sa_otp_settings['show_countrycode']=='on' )
			{
				var mob_field = jQuery("[name=billing_phone]:hidden");
			} else{
				var mob_field = jQuery("[name=billing_phone]");
			}
			
			if( mob_field.length > 0 ){ //If phone number exists

				var ab_cart_phone 	= mob_field.val();
				var ab_cart_email 	= jQuery("#billing_email").val();
				

				if (typeof ab_cart_phone === 'undefined' || ab_cart_phone === null) { //If phone number field does not exist on the Checkout form
				   ab_cart_phone 	= '';
				}

				clearTimeout(timer);

				if (ab_cart_phone.length >= 1){ //Checking if the phone number is longer than 1 digit
					//If Phone valid
					var ab_cart_name 				= jQuery("#billing_first_name").val();
					var ab_cart_nonce 				= jQuery("#smsalert_abcart_nonce").val();
					var ab_cart_surname 			= jQuery("#billing_last_name").val();
					var ab_cart_phone 				= mob_field.val();
					var ab_cart_country 			= jQuery("#billing_country").val();
					var ab_cart_city 				= jQuery("#billing_city").val();

					//Other fields used for "Remember user input" function
					var ab_cart_billing_company 	= jQuery("#billing_company").val();
					var ab_cart_billing_address_1 	= jQuery("#billing_address_1").val();
					var ab_cart_billing_address_2 	= jQuery("#billing_address_2").val();
					var ab_cart_billing_state 		= jQuery("#billing_state").val();
					var ab_cart_billing_postcode 	= jQuery("#billing_postcode").val();
					var ab_cart_shipping_first_name = jQuery("#shipping_first_name").val();
					var ab_cart_shipping_last_name 	= jQuery("#shipping_last_name").val();
					var ab_cart_shipping_company 	= jQuery("#shipping_company").val();
					var ab_cart_shipping_country 	= jQuery("#shipping_country").val();
					var ab_cart_shipping_address_1 	= jQuery("#shipping_address_1").val();
					var ab_cart_shipping_address_2 	= jQuery("#shipping_address_2").val();
					var ab_cart_shipping_city 		= jQuery("#shipping_city").val();
					var ab_cart_shipping_state 		= jQuery("#shipping_state").val();
					var ab_cart_shipping_postcode 	= jQuery("#shipping_postcode").val();
					var ab_cart_order_comments 		= jQuery("#order_comments").val();
					var ab_cart_create_account 		= jQuery("#createaccount");
					var ab_cart_ship_elsewhere 		= jQuery("#ship-to-different-address-checkbox");
					if(ab_cart_create_account.is(':checked')){
						ab_cart_create_account = 1;
					}else{
						ab_cart_create_account = 0;
					}

					if(ab_cart_ship_elsewhere.is(':checked')){
						ab_cart_ship_elsewhere = 1;
					}else{
						ab_cart_ship_elsewhere = 0;
					}

					var data = {
						action:					  	  "save_data",
						ab_cart_email:				  ab_cart_email,
						smsalert_abcart_nonce:			ab_cart_nonce,
						ab_cart_name:				  ab_cart_name,
						ab_cart_surname:			  ab_cart_surname,
						ab_cart_phone:				  ab_cart_phone,
						ab_cart_country:			  ab_cart_country,
						ab_cart_city:				  ab_cart_city,
						ab_cart_billing_company:	  ab_cart_billing_company,
						ab_cart_billing_address_1:	  ab_cart_billing_address_1,
						ab_cart_billing_address_2:    ab_cart_billing_address_2,
						ab_cart_billing_state:		  ab_cart_billing_state,
						ab_cart_billing_postcode: 	  ab_cart_billing_postcode,
						ab_cart_shipping_first_name:  ab_cart_shipping_first_name,
						ab_cart_shipping_last_name:   ab_cart_shipping_last_name,
						ab_cart_shipping_company: 	  ab_cart_shipping_company,
						ab_cart_shipping_country: 	  ab_cart_shipping_country,
						ab_cart_shipping_address_1:   ab_cart_shipping_address_1,
						ab_cart_shipping_address_2:   ab_cart_shipping_address_2,
						ab_cart_shipping_city: 	      ab_cart_shipping_city,
						ab_cart_shipping_state: 	  ab_cart_shipping_state,
						ab_cart_shipping_postcode:    ab_cart_shipping_postcode,
						ab_cart_order_comments: 	  ab_cart_order_comments,
						ab_cart_create_account: 	  ab_cart_create_account,
						ab_cart_ship_elsewhere: 	  ab_cart_ship_elsewhere
					}

					timer = setTimeout(function(){
							jQuery.post(ab_cart_checkout_form_data.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
							function(response) {
								//console.log(response);
								//If we have successfully captured abandoned cart, we do not have to display Exit intent form anymore
								removeExitIntentForm();
							});

					}, 800);
				}else{
					//console.log("Not a valid phone number");
				}
			}
		}

		function removeExitIntentForm(){//Removing Exit Intent form
			if($('#cart-exit-intent-form').length > 0){ //If Exit intent HTML exists on page
				$('#cart-exit-intent-form').remove();
				$('#cart-exit-intent-form-backdrop').remove();
			}
		}

		jQuery("#billing_email, #billing_phone, input.input-text, input.input-checkbox, textarea.input-text").on("keyup keypress change", getCheckoutData ); //All action happens on or after changing Phone fields or any other fields in the Checkout form. All Checkout form input fields are now triggering plugin action. Data saved to Database only after Phone fields have been entered.
		jQuery(window).on("load", getCheckoutData ); //Automatically collect and save input field data if input fields already filled on page load
	});
})( jQuery );