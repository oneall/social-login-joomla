<?php
/**
 * @package   	SocialLogin Module
 * @copyright 	Copyright 2012 http://www.oneall.com - All rights reserved.
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


//Only shown if logged out
if($user_status <> 'logout')
{
	//Check if the subdomain is set
	if (isset($widget_settings['api_subdomain']) AND ! empty ($widget_settings['api_subdomain']))
	{
		//Check if providers have been selected
		if (isset($widget_settings['providers']) AND is_array ($widget_settings['providers']))
		{
			//Check if we have a caption
			if (isset($widget_settings['mod_caption']) AND ! empty ($widget_settings['mod_caption']))
			{
				?>
					<p>
						<strong><?php echo JText::_($widget_settings['mod_caption']);?>:</strong>
					</p>
				<?php
			}
			?>
			<div id="oa_social_login_container"></div>
			<div id="branding" style="font-size: 10px;">
				Powered&nbsp;by <a target="_blank" href="http://www.oneall.com/" style="text-decoration:none">OneAll</a> <a href="http://www.oneall.com/services/single-sign-on/" style="text-decoration:none">Social&nbsp;Login</a>
			</div>
			<script type="text/javascript">
		 		oneall.api.plugins.social_login.build("oa_social_login_container",{
		  	providers : ["<?php echo implode ('", "', $widget_settings['providers']); ?>"],
		  	callback_uri: '<?php echo $return_url; ?>',
		  	css_theme_uri: (("https:" == document.location.protocol) ? "https://secure" : "http://public") + ".oneallcdn.com/css/api/socialize/themes/widget/joomla.css"
		 	});
			</script><!-- oneall.com / Social Login for Joomla! / v1.1 -->
			<?php
		}
	}
}