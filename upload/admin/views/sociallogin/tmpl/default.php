<?php
/**
 * @package       SocialLogin
 * @copyright     Copyright 2012 http://www.oneall.com - All rights reserved.
 * @license       GNU/GPL 2 or later
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,USA.
 *
 * The "GNU General Public License" (GPL) is available at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');
JHtml::_('behavior.tooltip');

?>
<form action="<?php echo JRoute::_('index.php?option=com_sociallogin&view=sociallogin&layout=default'); ?>" method="post" name="adminForm">
	<fieldset class="social_login_form social_login_form_welcome">
		<?php
if (empty($this->settings['api_settings_verified']))
{
    ?>
					<div class="row row_title">
						Make your Joomla Portal social!
					</div>
					<div class="row">
						Allow your users to comment, login and register with social networks like Twitter, Facebook, LinkedIn,  Вконтакте, Google or Yahoo.
						<strong>Draw a larger audience and increase user engagement in a  few simple steps.</strong>
					</div>
					<div class="row">
						To be able to use this plugin you first of all need to create a free account at <a href="https://app.oneall.com/signup/" target="_blank">http://www.oneall.com</a>
						and setup a Site. After having created your account and setup your Site, please enter the Site settings in the form below.
					</div>
					<div class="row row_description">
						You are in good company, 10000+ websites already trust us!
					</div>
					<div class="row row_button">
						<div class="button2-left">
							<div class="blank">
								<a class="modal" href="https://app.oneall.com/signup/" target="_blank">Get started in 60 seconds. Click here to create your free account!</a>
							</div>
						</div>
					</div>
				<?php
}
else
{
    ?>
					<div class="row row_title">
							Your API Account is setup correctly
					</div>
					<div class="row">
							<a href="https://app.oneall.com/signin/" target="_blank">Login to your account</a> to manage your providers and access your <a href="https://app.oneall.com/insights/"  target="_blank">Social Insights</a>.
							Determine which social networks are popular amongst your users and tailor your registration experience to increase your users' engagement.
					</div>
					<div class="row row_button">
						<div class="button2-left">
							<div class="blank">
								<a class="modal" href="https://app.oneall.com/signin/" target="_blank"><strong>Signin to your account</strong></a>
							</div>
						</div>
					</div>
				<?php
}
?>
	</fieldset>
	<fieldset class="social_login_form social_login_form_info">
		<legend><?php echo JText::_('Help, Updates &amp; Documentation'); ?></legend>
		<div class="row">
			<ul>
				<li>
					<a target="_blank" href="http://www.twitter.com/oneall">Follow us on Twitter</a> to stay informed about updates;
				</li>
				<li>
					<a target="_blank" href="http://docs.oneall.com/plugins/">Read the online documentation</a> for more information about this plugin;</li>
				<li>
					<a target="_blank" href="http://www.oneall.com/company/contact-us/">Contact us</a> if you have feedback or need assistance.
				</li>
			</ul>
		</div>
	</fieldset>



	<fieldset class="social_login_form">
		<legend> API Settings</legend>
		<div class="row row_description">
			<strong><a href="https://app.oneall.com/" target="_blank">Click here to create and view your API Credentials</a></strong>
		</div>
		<div class="row row_odd">
			<?php
$api_use_curl = (!isset($this->settings['api_use_curl']) or !empty($this->settings['api_use_curl']));
?>
			<input id="api_use_curl_yes" type="radio" name="settings[api_use_curl]" value="1" <?php echo ($api_use_curl ? 'checked="checked"' : ''); ?> />
			<label for="api_use_curl_yes" style="clear:none">Use CURL to communicate with the API <strong>(Recommended)</strong></label>
			<div class="clr"></div>
			<input id="api_use_curl_no" type="radio" name="settings[api_use_curl]" value="0" <?php echo (!$api_use_curl ? 'checked="checked"' : ''); ?> />
			<label for="api_use_curl_no" style="clear:none">Use FSOCKOPEN to communicate with the API</label>
		</div>
		<div class="row row_even">
			<label for="oneall_api_subdomain"  style="width: 200px;">API Subdomain:</label>
			<input type="text" id="settings_api_subdomain" name="settings[api_subdomain]" size="60" value="<?php echo (isset($this->settings['api_subdomain']) ? htmlspecialchars($this->settings['api_subdomain']) : ''); ?>" />
		</div>
		<div class="row row_even">
			<label for="oneall_api_public_key" style="width: 200px;">API Public Key:</label>
			<input type="text" id="settings_api_key" name="settings[api_key]" size="60" value="<?php echo (isset($this->settings['api_key']) ? htmlspecialchars($this->settings['api_key']) : ''); ?>" />
		</div>
		<div class="row row_even">
			<label for="oneall_api_private_key" style="width: 200px;">API Private Key:</label>
			<input type="text" id="settings_api_secret"  name="settings[api_secret]" size="60" value="<?php echo (isset($this->settings['api_secret']) ? htmlspecialchars($this->settings['api_secret']) : ''); ?>" />
		</div>
		<div class="row row_button">
			<div class="button2-left">
				<div class="blank">
					<a  href="#" id="oa_social_login_test_api_settings">Verify my API Settings</a>
				</div>
			</div>
			<div id="oa_social_login_api_test_result" style="float: left; padding-left: 35px;"></div>
		</div>
	</fieldset>
	<fieldset class="social_login_form">
		<legend>Enable the social networks/identity providers of your choice</legend>
			<?php
$i = 0;
foreach ($this->providers as $key => $provider_data)
{
    ?>
						<div class="row <?php echo ((($i++) % 2) == 0) ? 'row_even' : 'row_odd' ?> row_provider">
							<label class="provider_icon" for="oneall_social_login_provider_<?php echo $key; ?>"><span class="oa_provider oa_social_login_provider_<?php echo $key; ?>" title="<?php echo htmlspecialchars($provider_data['name']); ?>"><?php echo htmlspecialchars($provider_data['name']); ?></span></label>
							<input class="provider_check" type="checkbox" id="oneall_social_login_provider_<?php echo $key; ?>" name="settings[providers][<?php echo $key; ?>]" value="1" <?php echo (in_array($key, $this->settings['providers']) ? 'checked="checked"' : ''); ?> />
							<label class="provider_name" for="oneall_social_login_provider_<?php echo $key; ?>"><?php echo htmlspecialchars($provider_data['name']); ?></label>
							<div class="clr"></div>
						</div>
					<?php
}
?>
	</fieldset>
	<fieldset class="social_login_form">
		<legend>Settings</legend>
			<div class="row row_even">
				Enter the caption to be displayed above the social network login buttons:
			</div>
			<div class="row row_even">
				<input type="text" name="settings[mod_caption]" size="118" value="<?php echo (isset($this->settings['mod_caption']) ? htmlspecialchars($this->settings['mod_caption']) : 'Connect with:'); ?>" />
			</div>
			<div class="row row_odd">
				Should social network profiles with verified email addresses be linked to existing account?
			</div>
			<div class="row row_odd">
			<?php
$link_verified_accounts = (!isset($this->settings['link_verified_accounts']) or !empty($this->settings['link_verified_accounts']));
?>
			<input id="link_verified_accounts_yes" type="radio" name="settings[link_verified_accounts]" value="1" <?php echo ($link_verified_accounts ? 'checked="checked"' : ''); ?> />
			<label for="link_verified_accounts_yes" style="clear:none">Yes, try to link verified social network profiles to existing accounts <strong>(Default)</strong></label>
			<div class="clr"></div>
			<input id="link_verified_accounts_no" type="radio" name="settings[link_verified_accounts]" value="0" <?php echo (!$link_verified_accounts ? 'checked="checked"' : ''); ?> />
			<label for="$link_verified_accounts_no" style="clear:none">No, disable account linking </label>
		</div>
	</fieldset>
	<input type="hidden" name="task" value="" />
</form>