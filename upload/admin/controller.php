<?php
/**
 * @package       SocialLogin
 * @copyright     Copyright 2011-2017 http://www.oneall.com - All rights reserved.
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
jimport('joomla.application.component.controller');

/**
 * General Controller of SocialLogin component
 */
class SocialLoginController extends JController
{
    const USER_AGENT = 'SocialLogin/4.4.0 Joomla/2.5 (+http://www.oneall.com/)';

    /**
     * Display task
     */
    public function display($cachable = false, $urlparams = false)
    {
        // Set default view if not set
        JRequest::setVar('view', JRequest::getCmd('view', 'SocialLogin'));

        // Call parent behavior
        parent::display($cachable);
    }

    /**
     * Save settings
     */
    public function apply()
    {
        $model = $this->getModel();
        $model->saveSettings();
        $this->setRedirect(JRoute::_('index.php?option=com_sociallogin&view=sociallogin&layout=default', false));
    }

    /**
     * Autoderect API Connection Handler
     */
    public function autodetect_api_connection_handler()
    {
        //CURL Works
        if ($this->is_curl_available() === true)
        {
            echo 'success_autodetect_api_curl';
            die();
        }
        //CURL does not work
        else
        {
            // FSOCKOPEN works
            if ($this->is_fsockopen_available() == true)
            {
                echo 'success_autodetect_api_fsockopen';
                die();
            }
        }

        //No working handler found
        echo 'error_autodetect_api_no_handler';
        die();
    }

    /**
     * Check API Settings
     */
    public function check_api_settings()
    {
        $model = $this->getModel();

        //Check if all fields have been filled out
        if (empty($_POST['api_subdomain']) or empty($_POST['api_key']) or empty($_POST['api_secret']))
        {
            echo 'error_not_all_fields_filled_out';
            $model->setSetting('api_settings_verified', 0);
            die();
        }

        //Check the handler
        $api_connection_handler = ((!empty($_POST['api_connection_handler']) and $_POST['api_connection_handler'] == 'fsockopen') ? 'fsockopen' : 'curl');

        //FSOCKOPEN
        if ($api_connection_handler == 'fsockopen')
        {
            if ($this->is_fsockopen_available() !== true)
            {
                echo 'error_selected_handler_faulty';
                $model->setSetting('api_settings_verified', 0);
                die();
            }
        }
        //CURL
        else
        {
            if ($this->is_curl_available() !== true)
            {
                echo 'error_selected_handler_faulty';
                $model->setSetting('api_settings_verified', 0);
                die();
            }
        }

        //Parameters
        $api_subdomain = trim(strtolower($_POST['api_subdomain']));
        $api_key = $_POST['api_key'];
        $api_secret = $_POST['api_secret'];

        //Full domain entered
        if (preg_match("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
        {
            $api_subdomain = $matches[1];
        }

        //Check subdomain format
        if (!preg_match("/^[a-z0-9\-]+$/i", $api_subdomain))
        {
            echo 'error_subdomain_wrong_syntax';
            $model->setSetting('api_settings_verified', 0);
            die();
        }

        //Domain
        $api_domain = $api_subdomain . '.api.oneall.com';

        //Resource URI
        $api_resource_url = 'https://' . $api_domain . '/tools/ping.json';

        //Get connection details
        $result = $this->make_api_request($api_connection_handler, $api_resource_url, array('api_key' => $api_key, 'api_secret' => $api_secret), 15);

        //Parse result
        if (is_object($result) and property_exists($result, 'http_code') and property_exists($result, 'http_data'))
        {
            switch ($result->http_code)
            {
                //Success
                case 200:
                    echo 'success';
                    $model->setSetting('api_settings_verified', 1);
                    die();
                    break;

                //Authentication Error
                case 401:
                    echo 'error_authentication_credentials_wrong';
                    $model->setSetting('api_settings_verified', 0);
                    die();
                    break;

                //Wrong Subdomain
                case 404:
                    echo 'error_subdomain_wrong';
                    $model->setSetting('api_settings_verified', 0);
                    die();
                    break;

                //Other error
                default:
                    echo 'error_communication';
                    $model->setSetting('api_settings_verified', 0);
                    die();
                    break;
            }
        }
        else
        {
            echo 'error_communication';
            $model->setSetting('api_settings_verified', 0);
            die();
        }
        die();
    }

    /**
     * Send an API request by using the given handler
     */
    public function make_api_request($handler, $url, $options = array(), $timeout = 15)
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
    public function is_curl_available()
    {
        if (in_array('curl', get_loaded_extensions()) and function_exists('curl_exec'))
        {
            $result = $this->make_curl_request('https://www.oneall.com/ping.html');
            if (is_object($result) and property_exists($result, 'http_code') and $result->http_code == 200)
            {
                if (property_exists($result, 'http_data'))
                {
                    if (strtolower($result->http_data) == 'ok')
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
    public function make_curl_request($url, $options = array(), $timeout = 15)
    {
        //Store the result
        $result = new stdClass();

        //Send request
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, self::USER_AGENT);

        // BASIC AUTH?
        if (isset($options['api_key']) and isset($options['api_secret']))
        {
            curl_setopt($curl, CURLOPT_USERPWD, $options['api_key'] . ":" . $options['api_secret']);
        }

        //Make request
        if (($http_data = curl_exec($curl)) !== false)
        {
            $result->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $result->http_data = $http_data;
            $result->http_error = null;
        }
        else
        {
            $result->http_code = -1;
            $result->http_data = null;
            $result->http_error = curl_error($curl);
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
    public function is_fsockopen_available()
    {
        $result = $this->make_fsockopen_request('https://www.oneall.com/ping.html');
        if (is_object($result) and property_exists($result, 'http_code') and $result->http_code == 200)
        {
            if (property_exists($result, 'http_data'))
            {
                if (strtolower($result->http_data) == 'ok')
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
    public function make_fsockopen_request($url, $options = array(), $timeout = 15)
    {
        //Store the result
        $result = new stdClass();

        //Make that this is a valid URL
        if (($uri = parse_url($url)) == false)
        {
            $result->http_code = -1;
            $result->http_data = null;
            $result->http_error = 'invalid_uri';

            return $result;
        }

        //Make sure we can handle the schema
        switch ($uri['scheme'])
        {
            case 'http':
                $port = (isset($uri['port']) ? $uri['port'] : 80);
                $host = ($uri['host'] . ($port != 80 ? ':' . $port : ''));
                $fp = @fsockopen($uri['host'], $port, $errno, $errstr, $timeout);
                break;

            case 'https':
                $port = (isset($uri['port']) ? $uri['port'] : 443);
                $host = ($uri['host'] . ($port != 443 ? ':' . $port : ''));
                $fp = @fsockopen('ssl://' . $uri['host'], $port, $errno, $errstr, $timeout);
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
            $result->http_error = trim($errstr);

            return $result;
        }

        //Construct the path to act on
        $path = (isset($uri['path']) ? $uri['path'] : '/');
        if (isset($uri['query']))
        {
            $path .= '?' . $uri['query'];
        }

        //Create HTTP request
        $defaults = array(
            'Host' => "Host: $host",
            'User-Agent' => 'User-Agent: ' . self::USER_AGENT
        );

        // BASIC AUTH?
        if (isset($options['api_key']) and isset($options['api_secret']))
        {
            $defaults['Authorization'] = 'Authorization: Basic ' . base64_encode($options['api_key'] . ":" . $options['api_secret']);
        }

        //Build and send request
        $request = 'GET ' . $path . " HTTP/1.0\r\n";
        $request .= implode("\r\n", $defaults);
        $request .= "\r\n\r\n";
        fwrite($fp, $request);

        //Fetch response
        $response = '';
        while (!feof($fp))
        {
            $response .= fread($fp, 1024);
        }

        //Close connection
        fclose($fp);

        //Parse response
        list($response_header, $response_body) = explode("\r\n\r\n", $response, 2);

        //Parse header
        $response_header = preg_split("/\r\n|\n|\r/", $response_header);
        list($header_protocol, $header_code, $header_status_message) = explode(' ', trim(array_shift($response_header)), 3);

        //Build result
        $result->http_code = $header_code;
        $result->http_data = $response_body;

        //Done

        return $result;
    }
}
