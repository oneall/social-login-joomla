<?php
/**
 * @package       SocialLogin Plugin
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

/**
 * SocialLogin Plugin Helper
 */
class plgSystemSocialLoginHelper
{
    const version = 'SocialLogin/3.1.122 Joomla/1.7 (+http://www.oneall.com/)';

    /**
     * Check if username exists
     */
    public static function usernameExists($username)
    {
        //Database handler
        $db = JFactory::getDBO();

        //Get user for username
        $sql = "SELECT id FROM #__users WHERE username = " . $db->quote($username);
        $db->setQuery($sql);
        $user_id = $db->loadResult();

        //Done

        return (!empty($user_id) and is_numeric($user_id));
    }

    /**
     * Check if email exists
     */
    public static function useremailExists($email)
    {
        //Database handler
        $db = JFactory::getDBO();

        //Get user for email
        $sql = "SELECT id FROM #__users WHERE email = " . $db->quote($email);
        $db->setQuery($sql);
        $user_id = $db->loadResult();

        //Done

        return (!empty($user_id) and is_numeric($user_id));
    }

    /**
     * Create random email
     */
    public static function getRandomUseremail()
    {
        //Create unique email
        do
        {
            $email = md5(uniqid(rand(10000, 99000))) . "@example.com";
        } while (self::useremailExists($email));

        //Done

        return $email;
    }

    /**
     * Link token to userid
     */
    public static function setUserIdForToken($token, $user_id)
    {
        //Database handler
        $db = JFactory::getDBO();

        //Remove
        $sql = "DELETE FROM #__oasl_user_mapping WHERE token = " . $db->quote($token);
        $db->setQuery($sql);
        if ($db->query())
        {
            //Add
            $sql = "INSERT INTO #__oasl_user_mapping SET token = " . $db->quote($token) . ",  user_id = " . $db->Quote($user_id);
            $db->setQuery($sql);
            if ($db->query())
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if we have userid for this token
     */
    public function getUserIdForToken($token)
    {
        //Database handler
        $db = JFactory::getDBO();

        //Read user
        $sql = "SELECT u.ID FROM #__oasl_user_mapping AS um	INNER JOIN  #__users AS u ON (um.user_id=u.ID) WHERE um.token = " . $db->quote($token);
        $db->setQuery($sql);
        $user_id = $db->loadResult();
        if ($user_id)
        {
            return $user_id;
        }

        return false;
    }

    /**
     * Get the userid for an email
     */
    public function getUserIdForEmail($email)
    {
        //Database handler
        $db = JFactory::getDBO();

        //Read user
        $sql = "SELECT id FROM #__users WHERE email = " . $db->quote($email);
        $db->setQuery($sql);
        $user_id = $db->loadResult();
        if ($user_id)
        {
            return $user_id;
        }

        return false;
    }

    /**
     * Token Lookup
     */
    public static function makeTokenLookup($token)
    {
        //Read settings
        $settings = self::getSettings();

        //Setup
        $social_data = null;

        //API Settings
        $api_subdomain = (!empty($settings['api_subdomain']) ? $settings['api_subdomain'] : '');
        $api_key = (!empty($settings['api_key']) ? $settings['api_key'] : '');
        $api_secret = (!empty($settings['api_secret']) ? $settings['api_secret'] : '');
        $api_use_curl = ((isset($settings['api_use_curl']) and empty($settings['api_use_curl'])) ? false : true);

        //Make request
        if ($api_use_curl)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://' . $api_subdomain . '.api.oneall.com/connections/' . $token . '.json');
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_USERPWD, $api_key . ":" . $api_secret);
            curl_setopt($curl, CURLOPT_TIMEOUT, 15);
            curl_setopt($curl, CURLOPT_VERBOSE, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_FAILONERROR, 0);
            curl_setopt($curl, CURLOPT_USERAGENT, self::version);

            //Process
            if (($json = curl_exec($curl)) !== false)
            {
                //Close connection
                curl_close($curl);

                //Decode
                $json_decoded = json_decode($json);

                //User Data
                if (is_object($json_decoded) and !empty($json_decoded->response->request->status->code) and $json_decoded->response->request->status->code == 200)
                {
                    $social_data = $json_decoded;
                }
            }
        }
        else
        {
            //Basic Auth
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'GET',
                    'header' =>
                    'Authorization: Basic ' . base64_encode($api_key . ':' . $api_secret) . '\r\n' .
                    'User-Agent: ' . self::version . '\r\n'
                )
            ));

            //Get Contents
            $json = @file_get_contents('https://' . $api_subdomain . '.api.oneall.com/connections/' . $token . '.json', false, $context);

            //Decode
            $json_decoded = @json_decode($json);

            //User Data
            if (is_object($json_decoded) and $json_decoded->response->result->status->code == 200)
            {
                $social_data = $json_decoded;
            }
        }

        return (is_object($social_data) ? $social_data : null);
    }

    /**
     * Get settings
     */
    public static function getSettings()
    {
        //Container
        $settings = array();

        //Get database handle
        $db = JFactory::getDBO();

        //Read settings
        $sql = "SELECT * FROM #__oasl_settings";
        $db->setQuery($sql);
        $rows = $db->LoadAssocList();

        if (is_array($rows))
        {
            foreach ($rows as $key => $data)
            {
                if ($data['setting'] == 'providers')
                {
                    $tmp = @unserialize($data['value']);
                    if ($tmp !== false and is_array($tmp))
                    {
                        $settings[$data['setting']] = $tmp;
                    }
                    else
                    {
                        $settings[$data['setting']] = array();
                    }
                }
                else
                {
                    $settings[$data['setting']] = $data['value'];
                }
            }
        }

        return $settings;
    }
}
