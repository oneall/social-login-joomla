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
jimport('joomla.application.component.controller');

/**
 * General Controller of SocialLogin component
 */
class SocialLoginController extends JController
{
    const version = 'SocialLogin/1.1.122 Joomla/1.5 (+http://www.oneall.com/)';

    /**
     * Display task
     */
    public function display($cachable = false)
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
        $model = &$this->getModel();
        $model->saveSettings();
        $this->setRedirect(JRoute::_('index.php?option=com_sociallogin&view=sociallogin&layout=default', false));
    }

    /**
     * Check API Settings
     */
    public function check_api_settings()
    {
        $model = &$this->getModel();

        //Check if all fields have been filled out
        if (empty($_POST['api_subdomain']) or empty($_POST['api_key']) or empty($_POST['api_secret']))
        {
            echo 'error_not_all_fields_filled_out';
            $model->setSetting('api_settings_verified', 0);
            die();
        }

        //Use CURL?
        $api_use_curl = (!empty($_POST['api_use_curl']));

        //Subdomain
        $api_subdomain = trim(strtolower($_POST['api_subdomain']));

        //Full domain entered
        if (preg_match("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
        {
            $api_subdomain = trim($matches[1]);
        }

        //Check subdomain format
        if (!preg_match("/^[a-z0-9\-]+$/i", $api_subdomain))
        {
            echo 'error_subdomain_wrong_syntax';
            $model->setSetting('api_settings_verified', 0);
            die();
        }

        //Domain
        $api_domain = trim($api_subdomain) . '.api.oneall.com';

        //Key
        $api_key = $_POST['api_key'];

        //Secret
        $api_secret = $_POST['api_secret'];

        //Resource URI
        $resource_uri = 'https://' . $api_domain . '/tools/ping.json';

        //Ping
        if ($api_use_curl)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $resource_uri);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_USERPWD, $api_key . ":" . $api_secret);
            curl_setopt($curl, CURLOPT_TIMEOUT, 15);
            curl_setopt($curl, CURLOPT_VERBOSE, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_FAILONERROR, 0);
            curl_setopt($curl, CURLOPT_USERAGENT, SocialLoginController::version);

            if (($json = curl_exec($curl)) === false)
            {
                curl_close($curl);

                echo 'error_communication';
                $model->setSetting('api_settings_verified', 0);
                die();
            }

            //Success
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
        }
        else
        {
            //Check if file_get_contents can handle https
            $stream_wrappers = stream_get_wrappers();
            if (!in_array('https', $stream_wrappers))
            {
                echo 'error_communication_fgc_https';
                $model->setSetting('api_settings_verified', 0);
                die();
            }

            //Basic Auth
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'GET',
                    'header' =>
                    "Authorization: Basic " . base64_encode($api_key . ':' . $api_secret) . "\r\n" .
                    "User-Agent: " . SocialLoginController::version . "\r\n"
                )
            ));

            //Get Contents
            if (($data = file_get_contents($resource_uri, false, $context)) === false)
            {
                echo 'error_communication';
                $model->setSetting('api_settings_verified', 0);
                die();
            }

            if (isset($http_response_header) and is_array($http_response_header))
            {
                if (preg_match('/ ([0-9]{3}) /', $http_response_header[0], $matches))
                {
                    $http_code = $matches[1];
                }
            }
        }

        //Authentication Error
        if (!isset($http_code))
        {
            echo 'error_communication';
            $model->setSetting('api_settings_verified', 0);
            die();
        }
        elseif ($http_code == 401)
        {
            echo 'error_authentication_credentials_wrong';
            $model->setSetting('api_settings_verified', 0);
            die();
        }
        elseif ($http_code == 404)
        {
            echo 'error_subdomain_wrong';
            $model->setSetting('api_settings_verified', 0);
            die();
        }
        elseif ($http_code == 200)
        {
            echo 'success';
            $model->setSetting('api_settings_verified', 1);
            die();
        }

        die();
    }
}
