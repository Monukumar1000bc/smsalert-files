jQuery(function() {
	jQuery('.woocommerce-address-fields [name=billing_phone]').on("change", function (e) {
			if(smsalert_mdet.update_otp_enable=='on')
			{
				var new_phone = jQuery('[name=billing_phone]:last-child').val();
				var old_phone = jQuery('#old_billing_phone').val();
				if(new_phone != '' && new_phone != old_phone)
				{
					jQuery(this).parents('form').find('[id^="sa_verify_"]').removeClass("sa-default-btn-hide");
					jQuery('[name="save_address"]').addClass("sa-default-btn-hide");
				}
				else{
					jQuery('[name="save_address"]').removeClass("sa-default-btn-hide");
					jQuery(this).parents('form').find('[id^="sa_verify_"]').addClass("sa-default-btn-hide");
				}
			}
        });
		/* jQuery('.sa-default-btn-hide[name="save_address"]').each(function(index) {
			jQuery(this).removeClass('sa-default-btn-hide');
			jQuery(this).parents('form').find('#sa_verify').addClass("sa-default-btn-hide");
		}); */
		
		jQuery('input[id="reg_email"]').each(function(index) {
			//if(smsalert_mdet.mail_accept==0)
			{
				//jQuery(this).closest(".form-required").removeClass("form-required").find(".description").remove();
				//jQuery(this).parent().hide();
			}
			/* else if(smsalert_mdet.mail_accept==1){
				jQuery(this).parent().children("label").html("Email");
				jQuery(this).closest(".form-required").removeClass("form-required").find(".description").remove();
			} */
			});
		var register = jQuery("#smsalert_name").closest(".register");
		register.find(".woocommerce-Button, button[name='register']").each(function()
		{
			if (jQuery(this).attr("name") == "register") {
				if (!jQuery(this).text()!=smsalert_mdet.signupwithotp) {
				   //jQuery(this).val(smsalert_mdet.signupwithotp);
				   //jQuery(this).find('span').text(smsalert_mdet.signupwithotp);
				}
			}
		});
});
// login js
jQuery(function($) {
    function isEmpty(el) {
        return !jQuery.trim(el)
    }
    var tokenCon;
    var akCallback = -1;
    var body = jQuery("body");
    var modcontainer = jQuery(".smsalert-modal");
    var noanim = false;
    /* $.fn.smsalert_login_modal = function($this) {
        show_smsalert_login_modal($this);
        return false
    }; */
    jQuery(document).on("click", ".smsalert-login-modal", function() {
		//jQuery('.smsalert-modal').show();
       // if (!jQuery(this).attr("attr-disclick")) {
            show_smsalert_login_modal(jQuery(this))
       // }
        return false
    });
	function getUrlParams(url) {
		var params = {};
		url.substring(0).replace(/[?&]+([^=&]+)=([^&]*)/gi,
			function (str, key, value) {
				 params[key] = value;
			});
		return params;
	}
	
	function show_smsalert_login_modal($this) {
		//jQuery(".u-column2").css("display",'none');
		var windowWidth = jQuery(window).width();
		var params 		= getUrlParams($this.attr("href"));
		var def 		= params["default"];
		var showonly 	= params["showonly"];
		var modal_id 	= params["modal_id"];
		
		jQuery("#"+modal_id+".smsalert-modal").show();
		
		if (showonly == 'login,register' || showonly == 'register,login') {
		
			if(def == 'login')
			{
				jQuery("#"+modal_id+" .u-column2").css("display",'none');
				jQuery("#"+modal_id+" .u-column1, #"+modal_id+" .signdesc").css("display",'block');
			}
			else{
				jQuery("#"+modal_id+" .backtoLoginContainer, #"+modal_id+" .u-column2").css("display",'block');
				jQuery("#"+modal_id+" .u-column1, #"+modal_id+" .signdesc").css("display",'none');
				//jQuery("#"+modal_id+" #slide_form").css("transform","translateX(-373px)");
			}
		}
		else if ((def == 'register' && showonly=='') || showonly=='register') {
			jQuery("#"+modal_id+" .u-column1,#"+modal_id+" .signdesc").css("display",'none');
			jQuery("#"+modal_id+" .u-column2").css("display",'block');
			//jQuery("#slide_form").css("transform","translateX(-373px)");
		}
		else if ((def == 'register' && showonly=='') || showonly=='register_with_otp') {
			jQuery("#"+modal_id+" .u-column1,#"+modal_id+" .signdesc").css("display",'none');
			jQuery("#"+modal_id+" .u-column2").css("display",'block');
			jQuery("#"+modal_id+" .sa_myaccount_btn[name=sa_myaccount_btn_signup]").trigger("click");
			//jQuery("#slide_form").css("transform","translateX(-373px)");
		}
		else if ((def == 'login' && showonly=='') || showonly=='login') {
			jQuery("#"+modal_id+" .u-column1").css("display",'block');
			jQuery("#"+modal_id+" .u-column2,#"+modal_id+" .signdesc").css("display",'none');
		}
		
		var display = $this.attr('data-display');
		
		jQuery("#"+modal_id+".smsalert-modal.smsalertModal").removeClass("from-left from-right");
		jQuery("#"+modal_id+".smsalert-modal.smsalertModal").addClass(display);
		
		if(display == 'from-right'){
			jQuery("#"+modal_id+".from-right > .modal-content").animate({
																		right:'0',
																		opacity:'1',
																		padding: '15px'
																		}, 
																		{
																			easing: 'swing',
																			duration: 200,
																			complete: function() { 
																				var wc_width = jQuery("#"+modal_id+" .smsalert_validate_field").width();
																				if(jQuery("#"+modal_id+" #slide_form .u-column1").length==0){
																				jQuery("#"+modal_id+" #slide_form .woocommerce").css({"width":wc_width});
																				}
																				else
																				{
																					jQuery("#"+modal_id+" #slide_form .u-column1, #"+modal_id+" #slide_form .u-column2").css({"width":wc_width});
																				}
																			}
																		}
																	);
		}
		if(display == 'from-left'){
			jQuery("#"+modal_id+".from-left > .modal-content").animate({
																		left:'0',
																		opacity:'1',
																		padding: '15px'
																		}, 
																		{
																			easing: 'swing',
																			duration: 200,
																			complete: function() { 
																				if(jQuery("#"+modal_id+" #slide_form .u-column1").length==0){
																				var wc_width = jQuery("#"+modal_id+" .smsalert_validate_field").width();
																				jQuery("#"+modal_id+" #slide_form .woocommerce").css({"width":wc_width});
																				}
																				else
																				{
																					jQuery("#"+modal_id+" #slide_form .u-column1, #"+modal_id+" #slide_form .u-column2").css({"width":wc_width});
																				}
																			}
																		});
		}
		
		
		
		
		
		
		/* modcontainer.css({
			display: "block"
		}); */
		return false
    }
	

    jQuery(document).on("click", ".smsalert-modal .backtoLogin", function() {
		var modal_id = jQuery(this).parents(".smsalert-modal").attr("id");
		jQuery("#"+modal_id+" .backtoLoginContainer").css("display",'none');
		jQuery("#"+modal_id+" .signdesc").css("display",'block');
		
		//if(jQuery("#"+modal_id+".from-left #slide_form").length || jQuery("#"+modal_id+".from-right #slide_form").length || jQuery("#"+modal_id+".center #slide_form").length){
		
		if(jQuery("#"+modal_id+" #slide_form").length){
			
		
			jQuery("#"+modal_id+" #slide_form").css("transform","translateX(0)");
			jQuery("#"+modal_id+" .u-column1, #"+modal_id+" .signdesc").show();
		}else{
			jQuery("#"+modal_id+" .u-column2").css("display",'none');
			jQuery("#"+modal_id+" .u-column1").css("display",'block');
			jQuery("#"+modal_id+" .signupbutton").css("display",'block');
		}
	});
	
    jQuery(document).on("click", ".smsalert-modal .signupbutton", function() {
	
		var modal_id = jQuery(this).parents(".smsalert-modal").attr("id");
        jQuery("#"+modal_id+" .backtoLoginContainer").css("display",'block');
		jQuery("#"+modal_id+" .signdesc").css("display",'none');
		//if(jQuery("#"+modal_id+".from-left #slide_form").length || jQuery("#"+modal_id+".from-right #slide_form").length || jQuery("#"+modal_id+".center #slide_form").length){
		
		//if(jQuery("#"+modal_id+" #slide_form").length){
			jQuery("#"+modal_id+" .u-column2").show();
			jQuery("#"+modal_id+" .u-column1").css("display",'none');
			//jQuery("#"+modal_id+" #slide_form").css("transform","translateX(-373px)");
		//}else{
			
			//jQuery("#"+modal_id+" .u-column2").css("display",'block');
			//jQuery("#"+modal_id+" .u-column1").css("display",'none');
		//}
	});
});

/* jQuery(document).on("click", ".smsalert-login-modal", function(){
	
	var modal_id = jQuery(this).attr('data-modal-id');
	var display = jQuery(this).attr('data-display');
	
	jQuery(".smsalert-modal.smsalertModal").removeClass("from-left from-right");
	jQuery(".smsalert-modal.smsalertModal").addClass(display);
	if(display == 'from-right'){
		jQuery(".from-right > .modal-content").animate({right:'0',opacity:'1'}, 100);
	}
	if(display == 'from-left'){
		jQuery(".from-left > .modal-content").animate({left:'0',opacity:'1'}, 100);;
	}
}); */

jQuery(document).on("click",".from-right > .modal-content > .close,.from-left > .modal-content > .close",function(){
	jQuery(".modal-content").removeAttr("style");
	jQuery(".smsalert-modal.smsalertModal").hide('slow');
});

jQuery('body').click(function(e){
	var container = jQuery(".modal-content");
	if (!container.is(e.target) && container.has(e.target).length === 0) {
		jQuery('.smsalert-modal > .modal-content > .close').trigger('click');
	}
});