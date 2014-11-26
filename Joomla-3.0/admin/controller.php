<?php
/**
 * @package   	OneAll Social Login
 * @copyright 	Copyright 2011-2014 http://www.oneall.com, all rights reserved
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
jimport ('joomla.application.component.controller');

/**
 * General Controller of OneAllSocialLogin component
 */
class OneAllSocialLoginController extends JControllerLegacy
{
	/**
	 * Display task
	 */
	public function display ($cachable = false, $urlparams = array())
	{
		// Set default view if not set
		JRequest::setVar ('view', JRequest::getCmd ('view', 'OneAllSocialLogin'));

		// Call parent behavior
		parent::display ($cachable);
	}

	/**
	 * Save settings
	 */
	public function apply ()
	{
		$model = $this->getModel ();
		$model->saveSettings ();
		$this->setRedirect (JRoute::_ ('index.php?option=com_oneallsociallogin&action=save', false));
	}


	/**
	 * Autodetect API Connection Handler
	 */
	public function autodetect_api_connection_handler ()
	{
		//CURL Works on port 443
		if ($this->is_curl_available (true) === true)
		{
			die ('success_autodetect_api_curl_443');
		}

		// FSOCKOPEN works on port 443
		if ($this->is_fsockopen_available (true) == true)
		{
			die ('success_autodetect_api_fsockopen_443');
		}

		//CURL Works on port 80
		if ($this->is_curl_available (false) === true)
		{
			die ('success_autodetect_api_curl_80');
		}

		// FSOCKOPEN works on port 80
		if ($this->is_fsockopen_available (false) == true)
		{
			die ('success_autodetect_api_fsockopen_80');
		}

		//No working handler found
		die ('error_autodetect_api_no_handler');
	}


	/**
	 * Check API Settings
	 */
	public function check_api_settings ()
	{
		$model = $this->getModel ();

		//Check if all fields have been filled out
		if (empty ($_POST ['api_subdomain']) OR empty ($_POST ['api_key']) OR empty ($_POST ['api_secret']))
		{
			$model->setSetting ('api_settings_verified', 0);
			die ('error_not_all_fields_filled_out');
		}

		//Check the handler
		$api_connection_handler = ((!empty ($_POST ['api_connection_handler']) AND $_POST ['api_connection_handler'] == 'fsockopen') ? 'fsockopen' : 'curl');
		$api_connection_port = ((!empty ($_POST ['api_connection_port']) AND $_POST ['api_connection_port'] == 80) ? 80 : 443);
		$api_connection_secure = ($api_connection_port == 443);

		//FSOCKOPEN
		if ($api_connection_handler == 'fsockopen')
		{
			if ($this->is_fsockopen_available($api_connection_secure) !== true)
			{
				$model->setSetting ('api_settings_verified', 0);
				die('error_selected_handler_faulty');
			}
		}
		//CURL
		else
		{
			if ($this->is_curl_available($api_connection_secure) !== true)
			{
				$model->setSetting ('api_settings_verified', 0);
				die('error_selected_handler_faulty');
			}
		}

		//Parameters
		$api_subdomain = trim (strtolower ($_POST ['api_subdomain']));
		$api_key = $_POST ['api_key'];
		$api_secret = $_POST ['api_secret'];

		//Full domain entered
		if (preg_match ("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
		{
			$api_subdomain = trim($matches [1]);
		}

		//Check subdomain format
		if (!preg_match ("/^[a-z0-9\-]+$/i", $api_subdomain))
		{
			$model->setSetting ('api_settings_verified', 0);
			die ('error_subdomain_wrong_syntax');
		}

		//Domain
		$api_domain = $api_subdomain . '.api.oneall.com';

		//Resource URI
		$api_resource_url = ($api_connection_secure ? 'https' : 'http') . '://' . $api_domain . '/tools/ping.json';

		//Get connection details
		$result = $this->make_api_request ($api_connection_handler, $api_resource_url, array ('api_key' => $api_key, 'api_secret' => $api_secret), 15);

		//Parse result
		if (is_object ($result) AND property_exists ($result, 'http_code') AND property_exists ($result, 'http_data'))
		{
			switch ($result->http_code)
			{
				//Success
				case 200:
					$model->setSetting ('api_settings_verified', 1);
					die ('success');
				break;

				//Authentication Error
				case 401:
					$model->setSetting ('api_settings_verified', 0);
					die ('error_authentication_credentials_wrong');
				break;

				//Wrong Subdomain
				case 404:
					$model->setSetting ('api_settings_verified', 0);
					die ('error_subdomain_wrong');
				break;

				//Other error
				default:
					$model->setSetting ('api_settings_verified', 0);
					die ('error_communication');
				break;

			}
		}

		$model->setSetting ('api_settings_verified', 0);
		die ('error_communication');
	}

	/**
	 * Send an API request by using the given handler
	 */
	function make_api_request ($handler, $url, $options = array (), $timeout = 15)
	{
		//FSOCKOPEN
		if ($handler == 'fsockopen')
		{
			return $this->make_fsockopen_request($url, $options, $timeout);
		}
		//CURL
		else
		{
			return $this->make_curl_request($url, $options, $timeout);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// CURL
	/////////////////////////////////////////////////////////////////////////////

	/**
	 * Check if CURL can be used
	 */
	public function is_curl_available ($secure = true)
	{
		if (in_array ('curl', get_loaded_extensions ()) AND function_exists('curl_exec'))
		{
			$result = $this->make_curl_request (($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
			if (is_object ($result) AND property_exists ($result, 'http_code') AND $result->http_code == 200)
			{
				if (property_exists ($result, 'http_data'))
				{
					if (strtolower ($result->http_data) == 'ok')
					{
						return true;
					}
				}
			}
		}
		return false;
	}


	/**
	 * Send a CURL request
	 */
	public function make_curl_request ($url, $options = array (), $timeout = 15)
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
		curl_setopt ($curl, CURLOPT_USERAGENT, 'OneAll Social Login Joomla 3 (+http://www.oneall.com/)');

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

	/////////////////////////////////////////////////////////////////////////////
	// FSOCKOPEN
	/////////////////////////////////////////////////////////////////////////////

	/**
	 * Check if fsockopen can be used
	 */
	public function is_fsockopen_available ($secure = true)
	{
		$result = $this->make_fsockopen_request (($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
		if (is_object ($result) AND property_exists ($result, 'http_code') AND $result->http_code == 200)
		{
			if (property_exists ($result, 'http_data'))
			{
				if (strtolower ($result->http_data) == 'ok')
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Send a FSOCKOPEN request
	 */
	public function make_fsockopen_request ($url, $options = array (), $timeout = 15)
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
				'User-Agent' => 'User-Agent: OneAll Social Login Joomla 3 (+http://www.oneall.com/)'
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
}
