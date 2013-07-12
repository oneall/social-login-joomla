<?php
/**
 * @package   	SocialLogin Plugin
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


/**
 * SocialLogin Plugin Helper
 */
class plgSystemSocialLoginHelper
{

	/**
	 * Check if the given username exists
	 */
	public static function usernameExists ($username)
	{
		//Database handler
		$db = JFactory::getDBO ();

		//Get user for username
		$sql = "SELECT id FROM #__users WHERE username = " . $db->quote ($username);
		$db->setQuery ($sql);
		$user_id = $db->loadResult ();

		//Done
		return (!empty ($user_id) AND is_numeric ($user_id));
	}


	/**
	 * Check if the given email exists
	 */
	public static function useremailExists ($email)
	{
		//Database handler
		$db = JFactory::getDBO ();

		//Get user for email
		$sql = "SELECT id FROM #__users WHERE email = " . $db->quote ($email);
		$db->setQuery ($sql);
		$user_id = $db->loadResult ();

		//Done
		return (!empty ($user_id) AND is_numeric ($user_id));
	}


	/**
	 * Create random email
	 */
	public static function getRandomUseremail ()
	{
		//Create unique email
		do
		{
			$email = md5 (uniqid (rand (10000, 99000))) . "@example.com";
		}
		while (self::useremailExists ($email));

		//Done
		return $email;
	}


	/**
	 * Link token to userid
	 */
	public static function setUserIdForToken ($token, $user_id)
	{
		//Database handler
		$db = JFactory::getDBO ();

		//Remove
		$sql = "DELETE FROM #__oasl_user_mapping WHERE token = " . $db->quote ($token);
		$db->setQuery ($sql);
		if ($db->query ())
		{
			//Add
			$sql = "INSERT INTO #__oasl_user_mapping SET token = " . $db->quote ($token) . ",  user_id = " . $db->Quote ($user_id);
			$db->setQuery ($sql);
			if ($db->query ())
			{
				return true;
			}
		}
		return false;
	}


	/**
	 * Check if we have a userid for the given token
	 */
	public static function getUserIdForToken ($token)
	{
		//Database handler
		$db = JFactory::getDBO ();

		//Read user
		$sql = "SELECT u.ID FROM #__oasl_user_mapping AS um	INNER JOIN  #__users AS u ON (um.user_id=u.ID) WHERE um.token = " . $db->quote ($token);
		$db->setQuery ($sql);
		$user_id = $db->loadResult ();
		if ($user_id)
		{
			return $user_id;
		}
		return false;
	}


	/**
	 * Get the userid for a given email
	 */
	public static function getUserIdForEmail ($email)
	{
		//Database handler
		$db = JFactory::getDBO ();

		//Read user
		$sql = "SELECT id FROM #__users WHERE email = " . $db->quote ($email);
		$db->setQuery ($sql);
		$user_id = $db->loadResult ();
		if ($user_id)
		{
			return $user_id;
		}
		return false;
	}


	/**
	 * Make an API Request to obtain the data for a given connection_token
	 */
	public static function makeTokenLookup ($token)
	{
		//Read settings
		$settings = self::getSettings ();

		//API Settings
		$api_subdomain = (!empty ($settings ['api_subdomain']) ? $settings ['api_subdomain'] : '');
		$api_key = (!empty ($settings ['api_key']) ? $settings ['api_key'] : '');
		$api_secret = (!empty ($settings ['api_secret']) ? $settings ['api_secret'] : '');

		//API Connection
		$api_connection_handler = ((!empty ($settings ['api_connection_handler']) AND $settings ['api_connection_handler'] == 'fsockopen') ? 'fsockopen' : 'curl');
		$api_resource = 'https://' . $api_subdomain . '.api.oneall.com/connections/' . $token . '.json';

		//Send request to the API
		$result = self::makeHttpRequest ($api_connection_handler, $api_resource, array (
			'api_key' => $api_key,
			'api_secret' => $api_secret
		));

		//Parse result
		if (is_object ($result) AND property_exists ($result, 'http_data') AND property_exists ($result, 'http_code') AND $result->http_code == 200)
		{
			//Result
			$json = $result->http_data;

			//Decode
			$json_decoded = @json_decode ($json);

			//Check format
			if (is_object ($json_decoded) AND !empty ($json_decoded->response->request->status->code) AND $json_decoded->response->request->status->code == 200)
			{
				$social_data = $json_decoded;
			}
		}

		return ((isset ($social_data) AND is_object ($social_data)) ? $social_data : null);
	}


	/**
	 * Send a HTTP request by using the given handler
	 */
	public static function makeHttpRequest ($handler, $url, $options = array (), $timeout = 15)
	{
		//FSOCKOPEN
		if ($handler == 'fsockopen')
		{
			return self::makeFsockopenRequest ($url, $options, $timeout);
		}
		//CURL
		else
		{
			return self::makeCurlRequest ($url, $options, $timeout);
		}
	}

	/**
	 * Send a HTTP request by using CURL
	 */
	public static function makeCurlRequest ($url, $options = array (), $timeout = 15)
	{
		//Store the result
		$result = new stdClass ();

		//Send request
		$curl = curl_init ();
		curl_setopt ($curl, CURLOPT_URL, $url);
		curl_setopt ($curl, CURLOPT_HEADER, 0);
		curl_setopt ($curl, CURLOPT_TIMEOUT, $timeout);
		curl_setopt ($curl, CURLOPT_VERBOSE, 0);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);

		// BASIC AUTH?
		if (isset ($options ['api_key']) AND isset ($options ['api_secret']))
		{
			curl_setopt ($curl, CURLOPT_USERPWD, $options ['api_key'] . ":" . $options ['api_secret']);
		}

		//Make request
		if (($http_data = curl_exec ($curl)) !== false)
		{
			$result->http_code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);
			$result->http_data = $http_data;
			$result->http_error = null;
		}
		else
		{
			$result->http_code = -1;
			$result->http_data = null;
			$result->http_error = curl_error ($curl);
		}

		//Done
		return $result;
	}

	/**
	 * Send a HTTP request by using FSOCKOPEN
	 */
	public static function makeFsockopenRequest ($url, $options = array (), $timeout = 15)
	{
		//Store the result
		$result = new stdClass ();

		//Make that this is a valid URL
		if (($uri = parse_url ($url)) == false)
		{
			$result->http_code = -1;
			$result->http_data = null;
			$result->http_error = 'invalid_uri';
			return $result;
		}

		//Make sure we can handle the schema
		switch ($uri ['scheme'])
		{
			case 'http':
				$port = (isset ($uri ['port']) ? $uri ['port'] : 80);
				$host = ($uri ['host'] . ($port != 80 ? ':' . $port : ''));
				$fp = @fsockopen ($uri ['host'], $port, $errno, $errstr, $timeout);
				break;

			case 'https':
				$port = (isset ($uri ['port']) ? $uri ['port'] : 443);
				$host = ($uri ['host'] . ($port != 443 ? ':' . $port : ''));
				$fp = @fsockopen ('ssl://' . $uri ['host'], $port, $errno, $errstr, $timeout);
				break;

			default:
				$result->http_code = -1;
				$result->http_data = null;
				$result->http_error = 'invalid_schema';
				return $result;
				break;
		}

		//Make sure the socket opened properly
		if (!$fp)
		{
			$result->http_code = -$errno;
			$result->http_data = null;
			$result->http_error = trim ($errstr);
			return $result;
		}

		//Construct the path to act on
		$path = (isset ($uri ['path']) ? $uri ['path'] : '/');
		if (isset ($uri ['query']))
		{
			$path .= '?' . $uri ['query'];
		}

		//Create HTTP request
		$defaults = array (
			'Host' => "Host: $host",
			'User-Agent' => 'User-Agent: OneAll Social Login Joomla (+http://www.oneall.com/)',
		);

		// BASIC AUTH?
		if (isset ($options ['api_key']) AND isset ($options ['api_secret']))
		{
			$defaults ['Authorization'] = 'Authorization: Basic ' . base64_encode ($options ['api_key'] . ":" . $options ['api_secret']);
		}

		//Build and send request
		$request = 'GET ' . $path . " HTTP/1.0\r\n";
		$request .= implode ("\r\n", $defaults);
		$request .= "\r\n\r\n";
		fwrite ($fp, $request);

		//Fetch response
		$response = '';
		while (!feof ($fp))
		{
			$response .= fread ($fp, 1024);
		}

		//Close connection
		fclose ($fp);

		//Parse response
		list($response_header, $response_body) = explode ("\r\n\r\n", $response, 2);

		//Parse header
		$response_header = preg_split ("/\r\n|\n|\r/", $response_header);
		list($header_protocol, $header_code, $header_status_message) = explode (' ', trim (array_shift ($response_header)), 3);

		//Build result
		$result->http_code = $header_code;
		$result->http_data = $response_body;

		//Done
		return $result;
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
				if ($data ['setting'] == 'providers')
				{
					$tmp = @unserialize ($data ['value']);
					if ($tmp !== false AND is_array ($tmp))
					{
						$settings [$data ['setting']] = $tmp;
					}
					else
					{
						$settings [$data ['setting']] = array ();
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
}
