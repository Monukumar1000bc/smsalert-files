jQuery(document).ready(function(){
	jQuery('body').on('click','#smsalert_share_cart',function(e){	
	    e.preventDefault();
		jQuery(this).addClass('button--loading');
		jQuery.ajax({
			url:ajax_url.ajaxurl,
			type:'POST',
			data:'action=check_cart_data',
			success : function(response) {
				if(response === '0'){
					jQuery('#smsalert_scp_ul').addClass('woocommerce-error').css({"padding":"1em 1.618em"});
					jQuery('#smsalert_scp_ul').html('<li>Sorry, You cannot share your cart, Your cart is empty</li>');
				}
				jQuery('body').addClass("smsalert_sharecart_popup_body");
				jQuery("#smsalert_sharecart_popup").css("display","block");
				jQuery('#smsalert_share_cart').removeClass('button--loading');
				jQuery('#sc_umobile').trigger('keyup');
			},
			error: function() {
				alert('Error occured');
			}
		});
		return false;
   	});

	jQuery(document).on('click','.close',function(){
		var modal_style = jQuery('.smsalertModal').attr('data-modal-close');
		jQuery('.smsalertModal').addClass(modal_style+'Out');
		jQuery("#smsalert_sharecart_popup").css("display","none");
		jQuery('body').removeClass("smsalert_sharecart_popup_body");
		setTimeout(function() {
			jQuery('.smsalertModal').removeClass(modal_style+'Out');
		}, 500);
		jQuery('#smsalert_scp_ul').removeClass('woocommerce-error').css({"padding":"0"});
	});

	jQuery('body').on('click','#sc_btn',function(e){
		e.preventDefault();
		jQuery('#sc_btn').attr("disabled",true);
		var uname 	= jQuery("#sc_uname").val();
		var umobile = jQuery("#sc_umobile").val();
		var fname 	= jQuery("#sc_fname").val();
		var fmobile = jQuery("#sc_fmobile").val();
		var intRegex = /^\d+$/;
		
		if((!intRegex.test(umobile) && umobile != '') || (!intRegex.test(fmobile) && fmobile != '')) {
			jQuery('#sc_btn').before('<li class="sc_error" style="color:red">*Invalid Mobile Number</li>');
			setTimeout(function() {
				jQuery('.sc_error').remove();
			}, 2000);
			jQuery('#sc_btn').attr("disabled",false);
			return false;
		}
		
		if(uname != '' && umobile != '' && fname != '' && fmobile != '') {
			jQuery(this).addClass('button--loading');
			var formdata = jQuery(".sc_form").serialize();
			if(formdata.search("sc_uname") == -1){
				formdata = formdata+'&sc_uname='+encodeURI(uname);
			}
			jQuery.ajax({
				url:ajax_url.ajaxurl,
				type:'POST',
				data:'action=save_cart_data&'+formdata,
				success : function(response) {
					jQuery('#sc_btn').removeClass('button--loading');
					jQuery('.sc_form').hide();
					jQuery('#sc_response').html(response);
					setTimeout(function() {
						jQuery("#smsalert_sharecart_popup").css("display","none"); 
						jQuery('body').removeClass("smsalert_sharecart_popup_body");
						jQuery('.sc_form').show();
						jQuery('#sc_response').html('');
					}, 2000);
				},
				error: function(errorMessage) {
					jQuery('#sc_btn').removeClass('button--loading');
					alert('Error occured');
				}
			});
		}
		else {
			jQuery('#sc_btn').attr("disabled",false);
			jQuery('#sc_btn').before('<li class="sc_error" style="color:red">*Please fill all fields</li>');
			setTimeout(function() {
				jQuery('.sc_error').remove();
			}, 2000);
		}
		return false;
	});
});