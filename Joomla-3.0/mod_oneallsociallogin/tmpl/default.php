<?php
/**
 * @package   	Oneall Social Login Module
 * @copyright 	Copyright 2011-2015 http://www.oneall.com, all rights reserved
 * @license   	GNU/GPL 2 or later
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
defined ('_JEXEC') or die ('Direct Access to this location is not allowed.');


// User is logged in
if($user_status == 'logout')
{
	//Display a Logout button?
	if (isset($widget_settings['show_logout_button']) AND $widget_settings['show_logout_button'] == '1')
	{
		?>
			<div class="oa_social_login<?php echo $moduleclass_sfx; ?>">
				<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="login-form">
					<?php
						//Display text above the Logout button?
						if (isset ($widget_settings['logout_button_text']) AND strlen (trim ($widget_settings['logout_button_text'])) > 0)
						{
							?>
								<div class="login-greeting">
									<?php
										$username = htmlspecialchars(($params->get('name') == 0) ? $user->get('name') : $user->get('username'));
										echo str_replace ('%s', $username, $widget_settings['logout_button_text']);
									?>
								</div>
							<?php
						}
					?>
			  	  <div class="logout-button">
			    	    <input type="submit" name="Submit" class="btn btn-primary" value="<?php echo JText::_('JLOGOUT'); ?>" />
			      	  <input type="hidden" name="option" value="com_users" />
			        	<input type="hidden" name="task" value="user.logout" />
			        	<?php echo JHtml::_('form.token'); ?>
			    	</div>
				</form>
			</div>
		<?php
	}
}
//User is logged out
else
{
	//Custom CSS
	$widget_settings['css_theme_uri'] = (! isset ($widget_settings['css_theme_uri']) ? '' : trim ($widget_settings['css_theme_uri']));

	//Check if the subdomain is set
	if (isset($widget_settings['api_subdomain']) AND strlen (trim ($widget_settings['api_subdomain'])) > 0)
	{
		//Check if providers have been selected
		if (isset($widget_settings['providers']) AND is_array ($widget_settings['providers']))
		{
			//Random integer for unique dom element ids;
			$rnd = rand ();

			?>
				<div class="oa_social_login<?php echo $moduleclass_sfx ?>">

					<?php
						//Check if we have a caption
						if (isset($widget_settings['mod_caption']) AND strlen (trim ($widget_settings['mod_caption'])) > 0)
						{
							?>
								<p class="oa_social_login_caption<?php echo $moduleclass_sfx ?>">
									<strong><?php echo JText::_($widget_settings['mod_caption']);?></strong>
								</p>
							<?php
						}
					?>
						<div id="oa_social_login_container<?php echo $rnd.$moduleclass_sfx ?>"></div>
						<script type="text/javascript">
							var _oneall = _oneall || [];
							_oneall.push(['social_login', 'set_providers', ['<?php echo implode ("','", $widget_settings['providers']); ?>']]);
							_oneall.push(['social_login', 'set_callback_uri', '<?php echo $return_url; ?>']);
							_oneall.push(['social_login', 'set_custom_css_uri', '<?php echo $widget_settings['css_theme_uri']; ?>']);
							_oneall.push(['social_login', 'do_render_ui', 'oa_social_login_container<?php echo $rnd.$moduleclass_sfx ?>']);
						</script><!-- http://www.oneall.com / OneAll Social Login for Joomla! -->
					<?php
					?>
				</div>
			<?php
		}
		else
		{
			?>
				<div style="background-color:red;color:white;padding:5px;text-align:center">[<strong>Social Login</strong>] Please select at least one Social Network (Admin: Components\OneAll Social Login)</div>
			<?php
		}
	}
	else
	{
		?>
			<div style="background-color:red;color:white;padding:5px;text-align:center">[<strong>Social Login</strong>] Please complete your API Settings (Admin: Components\OneAll Social Login)</div>
		<?php
	}
}