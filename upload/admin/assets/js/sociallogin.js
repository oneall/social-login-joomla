
/* Joomla 1.5 uses MooTols. This is to prevent conflicts (http://api.jquery.com/jQuery.noConflict/) */
jQuery.noConflict();

(function($) {
	$(function() {		
		$(document).ready(function($) {
			
			$('#oa_social_login_test_api_settings').click(function() {
				var subdomain = $('#settings_api_subdomain').val();
				var key = $('#settings_api_key').val();
				var secret = $('#settings_api_secret').val();
				var api_use_curl = ($('#api_use_curl_yes').is(':checked') ? 1 : 0);
		
				var data = {
				  'action' : 'check_api_settings',
				  'api_subdomain' : subdomain,
				  'api_key' : key,
				  'api_secret' : secret,
				  'api_use_curl' : api_use_curl
				};
		
				var ajaxurl = 'index.php?option=com_sociallogin&task=check_api_settings';
		
				$.post(ajaxurl, data, function(response) {
					var message;
					var success;
		
					if (response == 'error_not_all_fields_filled_out') {
						success = false;
						message = 'Please fill out each of the fields above'
					} else if (response == 'error_subdomain_wrong') {
						success = false;
						message = 'The subdomain does not exist. Have you filled it out correctly?'
					} else if (response == 'error_subdomain_wrong_syntax') {
						success = false;
						message = 'The subdomain has a wrong syntax!'
					} else if (response == 'error_communication') {
						success = false;
						message = 'Could not contact API. Try selecting another connection method at the bottom'
					} else if (response == 'error_authentication_credentials_wrong') {
						success = false;
						message = 'The API credentials are wrong';
					} else if (response == 'error_communication_fgc_https'){
						success = false;
						message = 'No HTTPS Wrapper for fsocketopen found. Try using CURL';
					}	else {
						success = true;
						message = 'The settings are correct - do not forget to save your changes!';
					}
		
					$('#oa_social_login_api_test_result').html(message);
					if (success) {
						$('#oa_social_login_api_test_result').removeClass('error_message');
						$('#oa_social_login_api_test_result').addClass('success_message');
					} else {
						$('#oa_social_login_api_test_result').removeClass('success_message');
						$('#oa_social_login_api_test_result').addClass('error_message');
					}
				});
				return false;
			});
		});
	 });
})(jQuery);