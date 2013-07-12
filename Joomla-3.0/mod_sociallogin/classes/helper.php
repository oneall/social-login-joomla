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

class mod_socialloginHelper
{
	/**
	 * Is HTTPS enabled?
	 */
	public static function is_https_on()
	{
		if ( ! empty ($_SERVER ['SERVER_PORT']))
		{
			if (trim($_SERVER ['SERVER_PORT']) == '443')
			{
				return true;
			}
		}

		if ( ! empty ($_SERVER ['HTTP_X_FORWARDED_PROTO']))
		{
			if (strtolower(trim($_SERVER ['HTTP_X_FORWARDED_PROTO'])) == 'https')
			{
				return true;
			}
		}

		if ( ! empty ($_SERVER ['HTTPS']))
		{
			if (strtolower(trim($_SERVER ['HTTPS'])) == 'on' OR trim($_SERVER ['HTTPS']) == '1')
			{
				return true;
			}
		}

		return false;
	}


	/**
	 * Get settings
	 */
	public static function getSettings ()
	{
		//Container
		$settings = array ();

		//Get database handle
		$db = JFactory::getDBO ();

		//Read settings
		$sql = "SELECT * FROM #__oasl_settings";
		$db->setQuery ($sql);
		$rows = $db->LoadAssocList ();

		if (is_array ($rows))
		{
			foreach ($rows AS $key => $data)
			{
				if ($data['setting'] == 'providers')
				{
					$tmp = @unserialize ($data ['value']);
					if (is_array ($tmp))
					{
						$settings [$data['setting']] = $tmp;
					}
					else
					{
						$settings [$data['setting']] = array();
					}
				}
				else
				{
					$settings [$data['setting']] = $data ['value'];
				}
			}
		}

		return $settings;
	}
}