jQuery(document).ready( function($) {

	// if device is mobile.
	if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
	    jQuery('body').addClass( 'mobile-device' );
	}

	var deactivate_url = '';

	// Add Deactivation id to all deactivation links.
	embed_id_to_deactivation_urls();

	// On click of deactivate.
	if( 'plugins.php' == smsf.current_screen ) {

		add_deactivate_slugs_callback( smsf.current_supported_slug );

		jQuery( document ).on( 'change','.on-boarding-radio-field' ,function(e){

			e.preventDefault();
			if ( 'other' == jQuery( this ).attr( 'id' ) ) {
				jQuery( '#deactivation-reason-text' ).removeClass( 'smsf-keep-hidden' );
			} else {
				jQuery( '#deactivation-reason-text' ).addClass( 'smsf-keep-hidden' );
			}
		});
	}

	// Close Button Click.
	jQuery( document ).on( 'click','.smsf-on-boarding-close-btn a',function(e){
		e.preventDefault();
		smsf_hide_onboard_popup();
	});

	// Skip and deactivate.
	jQuery( document ).on( 'click','.smsf-deactivation-no_thanks',function(e){

		window.location.replace( deactivate_url );
		smsf_hide_onboard_popup();
	});

	// Submitting Form.
	jQuery( document ).on( 'submit','form.smsf-on-boarding-form',function(e){

		jQuery('.smsf-on-boarding-submit').addClass('button--loading').attr('disabled',true);
		e.preventDefault();
		var form_data = jQuery( 'form.smsf-on-boarding-form' ).serializeArray(); 

		jQuery.ajax({
            type: 'post',
            dataType: 'json',
            url: smsf.ajaxurl,
            data: {
                nonce : smsf.auth_nonce, 
                action: 'send_onboarding_data' ,
                form_data: form_data,  
            },
            success: function( msg ){
            	jQuery( document ).find('#smsf_wgm_loader').hide();
        		if( 'plugins.php' == smsf.current_screen ) {
					window.location.replace( deactivate_url );
				}
                smsf_hide_onboard_popup();
				jQuery('.smsf-on-boarding-submit').removeClass('button--loading').attr('disabled',false);
            }
        });
	});

	// Open Popup.
	function smsf_show_onboard_popup() {
		jQuery( '.smsf-onboarding-section' ).show();
		jQuery( '.smsf-on-boarding-wrapper-background' ).addClass( 'onboard-popup-show' );

	    if( ! jQuery( 'body' ).hasClass( 'mobile-device' ) ) {
	    	jQuery( 'body' ).addClass( 'smsf-on-boarding-wrapper-control' );
	    }
	}

	// Close Popup.
	function smsf_hide_onboard_popup() {
		jQuery( '.smsf-on-boarding-wrapper-background' ).removeClass( 'onboard-popup-show' );
		jQuery( '.smsf-onboarding-section' ).hide();
		if( ! jQuery( 'body' ).hasClass( 'mobile-device' ) ) {
	    	jQuery( 'body' ).removeClass( 'smsf-on-boarding-wrapper-control' );
	    }
	}

	// Apply deactivate in all the smsf plugins.
	function add_deactivate_slugs_callback( all_slugs ) {
		
		for ( var i = all_slugs.length - 1; i >= 0; i-- ) {

			jQuery( document ).on( 'click', '#deactivate-' + all_slugs[i] ,function(e){
				e.preventDefault();
				deactivate_url = jQuery( this ).attr( 'href' );
				plugin_name = jQuery( this ).attr( 'aria-label' );
				jQuery( '#plugin-name' ).val( plugin_name.replace( 'Deactivate ', '' ) );
				plugin_name = plugin_name.replace( 'Deactivate ', '' );
				jQuery( '#plugin-name' ).val( plugin_name );
				jQuery( '.smsf-on-boarding-heading' ).text( plugin_name + ' Feedback' );
				var placeholder = jQuery( '#deactivation-reason-text' ).attr( 'placeholder' );
				jQuery( '#deactivation-reason-text' ).attr( 'placeholder', placeholder.replace( '{plugin-name}', plugin_name ) );
				smsf_show_onboard_popup();
			});
		}
	}

	// Add deactivate id in all the plugins links.
	function embed_id_to_deactivation_urls() {
		jQuery( 'a' ).each(function(){
		    if ( 'Deactivate' == jQuery(this).text() && 0 < jQuery(this).attr( 'href' ).search( 'action=deactivate' ) ) {
		    	if( 'undefined' == typeof jQuery(this).attr( 'id' ) ) {
			    	var slug = jQuery(this).closest( 'tr' ).attr( 'data-slug' );
			    	jQuery(this).attr( 'id', 'deactivate-' + slug );
		    	}
		    }
		});	
	}
});