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
require_once(dirname (__FILE__) . '/classes/helper.php');
$params->def ('greeting', 1);

//Get settings
$widget_settings = mod_socialloginHelper::getSettings ();

//Add library
if (!empty ($widget_settings ['api_subdomain']))
{
	$document = JFactory::getDocument ();
	$document->addScript ((mod_socialloginHelper::is_https_on () ? 'https' : 'http') . '://' . $widget_settings ['api_subdomain'] . '.api.oneall.com/socialize/library.js');
}

//Get user status
$user = JFactory::getUser ();
$user_status = ((!$user->get ('guest')) ? 'logout' : 'login');

//Return URL
$return_url = JURI::getInstance ()->toString ();

//Get Module Class Suffix
$moduleclass_sfx = htmlspecialchars ($params->get ('moduleclass_sfx'));

//Show template
require JModuleHelper::getLayoutPath ('mod_sociallogin', $params->get('layout', 'default'));
