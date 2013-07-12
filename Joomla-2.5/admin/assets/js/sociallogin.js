jQuery(document).ready(function($) {
	
	/* Autodetect API Connection Handler */
	$('#oa_social_login_autodetect_api_connection_handler').click(function(){	
		var message_string;		
		var message_container;
		var is_success;	
		
		var data = {'action' : 'autodetect_api_connection_handler'};
		var ajaxurl = 'index.php?option=com_sociallogin&task=autodetect_api_connection_handler';
		
		
		message_container = jQuery('#oa_social_login_api_connection_handler_result');	
		message_container.removeClass('success_message error_message').addClass('working_message');
		message_container.html('Contacting API - please wait ...');
		
		jQuery.post(ajaxurl,data, function(response) {				
			
			/* CURL/FSOCKOPEN Radio Boxs */
			var radio_curl = jQuery("#oa_social_login_api_connection_handler_curl");
			var radio_fsockopen = jQuery("#oa_social_login_api_connection_handler_fsockopen");											
			radio_curl.removeAttr("checked");
			radio_fsockopen.removeAttr("checked");
				
			/* CURL detected */
			if (response == 'success_autodetect_api_curl')
			{
				is_success = true;
				radio_curl.attr("checked", "checked");				
				message_string = 'Autodetected PHP CURL - do not forget to save your changes!';
			}			
			else
			{
				/* FSOCKOPEN detected */
				if (response == 'success_autodetect_api_fsockopen')
				{
					is_success = true;
					radio_fsockopen.attr("checked", "checked");					
					message_string = 'Autodetected PHP FSOCKOPEN - do not forget to save your changes!';
				}
				/* No handler detected */
				else
				{
					is_success = false;
					radio_curl.attr("checked", "checked");					
					message_string = 'Autodetection Error - our <a href="http://docs.oneall.com/plugins/guide/social-login-joomla/" target="_blank">documentation</a> might help you fix this issue.';
				}
			}		
			message_container.removeClass('working_message');
			message_container.html(message_string);
			
			if (is_success){
				message_container.addClass('success_message');
			} else {
				message_container.addClass('error_message');
			}						
		});
		return false;	
	});
	
	
	/* Test API Settings */
	$('#oa_social_login_test_api_settings').click(function() {
		
		var message_string;		
		var message_container;
		var is_success;	
		
		var radio_curl_val = jQuery("#oa_social_login_api_connection_handler_curl:checked").val();
		var radio_fsockopen_val = jQuery("#oa_social_login_api_connection_handler_fsockopen:checked").val();	
		
		var subdomain = jQuery('#settings_api_subdomain').val();
		var key = jQuery('#settings_api_key').val();
		var secret = jQuery('#settings_api_secret').val();
		var handler = (radio_fsockopen_val == 'fsockopen' ? 'fsockopen' : 'curl');		

		var data = {
		  'action' : 'check_api_settings',
		  'api_subdomain' : subdomain,
		  'api_key' : key,
		  'api_secret' : secret,
		  'api_connection_handler' : handler
		};

		var ajaxurl = 'index.php?option=com_sociallogin&task=check_api_settings';		
				
		message_container = jQuery('#oa_social_login_api_test_result');	
		message_container.removeClass('success_message error_message').addClass('working_message');
		message_container.html('Contacting API - please wait ...');

		jQuery.post(ajaxurl, data, function(response) {
			
			is_success = false;			
			if (response == 'error_selected_handler_faulty') {
				message_string = 'The connection handler does not work, try using the Autodetection';
			} else if (response == 'error_not_all_fields_filled_out') {
				message_string = 'Please fill out each of the fields above'
			} else if (response == 'error_subdomain_wrong') {
				message_string = 'The subdomain does not exist. Have you filled it out correctly?'
			} else if (response == 'error_subdomain_wrong_syntax') {
				message_string = 'The subdomain has a wrong syntax!'
			} else if (response == 'error_communication') {
				message_string = 'Could not contact API. Try using another connection handler'
			} else if (response == 'error_authentication_credentials_wrong') {
				message_string = 'The API credentials are wrong';
			} else {
				is_success = true;
				message_string = 'The settings are correct - do not forget to save your changes!';
			}

			message_container.removeClass('working_message');
			message_container.html(message_string);
			
			if (is_success){
				message_container.addClass('success_message');
			} else {
				message_container.addClass('error_message');
			}
		});
		return false;
	});
});