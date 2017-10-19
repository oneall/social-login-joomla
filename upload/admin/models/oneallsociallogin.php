<?php
/**
 * @package   	OneAll Social Login
 * @copyright 	Copyright 2011-Today http://www.oneall.com, all rights reserved
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
defined ('_JEXEC') or die ('Direct access to this location is not allowed.');
jimport ('joomla.application.component.modellist');

/**
 * OneAllSocialLogin Model
 */
class OneAllSocialLoginModelOneAllSocialLogin extends JModelList
{

	/**
	 * Save Settings
	 */
	public function saveSettings ()
	{
		// Get database handle
		$db = $this->getDbo ();

		// Read Settings
		$settings = JRequest::getVar ('settings');

		// Cleanup subdomain
		if (!empty ($settings ['api_subdomain']))
		{
			$settings ['api_subdomain'] = strtolower (trim ($settings ['api_subdomain']));

			// Full domain entered
			if (preg_match ("/([a-z0-9\-]+)\.api\.oneall\.com/i", $settings ['api_subdomain'], $matches))
			{
				$settings ['api_subdomain'] = trim ($matches [1]);
			}
		}

		// Save providers
		$providers = array();
		if (isset ($settings ['providers']) and is_array ($settings ['providers']))
		{
			foreach ($settings ['providers'] as $key => $value)
			{
				if (!empty ($value))
				{
					$providers [] = $key;
				}
			}
		}
		$settings ['providers'] = serialize ($providers);

		// Remove current settings
		$sql = "DELETE FROM #__oasl_settings WHERE setting <> 'api_settings_verified'";
		$db->setQuery ($sql);
		$db->query ();

		// Insert new settings
		foreach ($settings as $k => $v)
		{
			$sql = "INSERT INTO #__oasl_settings ( setting, value )" . " VALUES ( " . $db->Quote ($k) . ", " . $db->Quote ($v) . " )";
			$db->setQuery ($sql);
			$db->query ();
		}
	}

	/**
	 * Read Settings
	 */
	public function getSettings ()
	{
		// Container
		$settings = array();

		// Get database handle
		$db = $this->getDbo ();

		// Read settings
		$sql = "SELECT * FROM #__oasl_settings";
		$db->setQuery ($sql);
		$rows = $db->LoadAssocList ();

		if (is_array ($rows))
		{
			foreach ($rows as $key => $data)
			{
				if ($data ['setting'] == 'providers')
				{
					$tmp = @unserialize ($data ['value']);
					if (is_array ($tmp))
					{
						$settings [$data ['setting']] = $tmp;
					}
					else
					{
						$settings [$data ['setting']] = array();
					}
				}
				else
				{
					$settings [$data ['setting']] = $data ['value'];
				}
			}
		}

		return $settings;
	}

	/**
	 * Insert a given setting
	 */
	public function setSetting ($key, $value)
	{
		// Get database handle
		$db = $this->getDbo ();

		// Delete setting
		$sql = "DELETE FROM #__oasl_settings WHERE setting = " . $db->Quote ($key) . "";
		$db->setQuery ($sql);
		$db->query ();

		// Insert new value
		$sql = "INSERT INTO #__oasl_settings ( setting, value )" . " VALUES ( " . $db->Quote ($key) . ", " . $db->Quote ($value) . " )";
		$db->setQuery ($sql);
		$db->query ();
	}
}