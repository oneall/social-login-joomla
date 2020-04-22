<?php
/**
 * @package       OneAll Social Login Plugin
 * @copyright     Copyright 2011-Today http://www.oneall.com, all rights reserved
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
defined('_JEXEC') or die('Restricted access');
jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

// Directory Separator
if (!defined('DS'))
{
    define('DS', defined('DIRECTORY_SEPARATOR') ? DIRECTORY_SEPARATOR : '/');
}

// Check if plugin correctly installed
if (!JFile::exists(dirname(__FILE__) . DS . 'helper.php'))
{
    JError::raiseNotice('no_oneallsociallogin_plugin', JText::_('The OneAll Social Login plugin is not installed correctly. Plugin not executed'));

    return;
}
require_once dirname(__FILE__) . DS . 'helper.php';

define('OA_USERAGENT', 'SocialLogin/5.6.0 Joomla/3.0 (+http://www.oneall.com/)');
class plgSystemOneAllSocialLogin extends JPlugin
{
    /**
     * Authentication
     */
    private function doAuth($token)
    {
        // Settings
        $settings = plgSystemOneAllSocialLoginHelper::getSettings();

        // Check settings
        if (empty($settings['api_subdomain']) or empty($settings['api_key']) or empty($settings['api_secret']))
        {
            JError::raiseNotice('no_oneallsociallogin_plugin', JText::_('The OneAll Social Login API settings are missing. Please correct these in the Joomla administration area.'));

            return;
        }

        // Read user data
        $social_data = plgSystemOneAllSocialLoginHelper::makeTokenLookup($token);
        if (is_object($social_data))
        {
            $identity = $social_data->response->result->data->user->identity;
            $user_token = $social_data->response->result->data->user->user_token;

            // Identity
            $user_identity_id = $identity->id;
            $user_identity_provider = $identity->source->name;

            // ***** Firstname *****
            if (isset($identity->name->givenName) and !empty($identity->name->givenName))
            {
                $user_first_name = $identity->name->givenName;
            }
            elseif (isset($identity->preferredUsername))
            {
                $user_first_name = $identity->preferredUsername;
            }
            else
            {
                $user_first_name = 'noname';
            }

            // ***** Lastname *****
            if (isset($identity->name->familyName) and !empty($identity->name->familyName))
            {
                $user_last_name = $identity->name->familyName;
            }
            else
            {
                $user_last_name = '';
            }

            // ***** Fullname *****
            if (!empty($identity->name->formatted))
            {
                $user_full_name = $identity->name->formatted;
            }
            elseif (!empty($identity->name->displayName))
            {
                $user_full_name = $identity->name->displayName;
            }
            else
            {
                $user_full_name = trim($user_first_name . ' ' . $user_last_name);
            }

            // ***** Email *****
            $user_email = '';
            if (property_exists($identity, 'emails') and is_array($identity->emails))
            {
                foreach ($identity->emails as $email)
                {
                    $user_email = $email->value;
                    $user_email_is_verified = ($email->is_verified == '1');
                }
            }

            // ***** Thumbnail *****
            if (property_exists($identity, 'thumbnailUrl') and !empty($identity->thumbnailUrl))
            {
                $user_thumbnail = trim($identity->thumbnailUrl);
            }
            else
            {
                $user_thumbnail = '';
            }

            // ***** User Website *****
            if (property_exists($identity, 'profileUrl') and !empty($identity->profileUrl))
            {
                $user_website = $identity->profileUrl;
            }
            elseif (property_exists($identity, 'urls') and !empty($identity->urls[0]->value))
            {
                $user_website = $identity->urls[0]->value;
            }
            else
            {
                $user_website = '';
            }

            // ***** Preferred Username *****
            if (!empty($identity->preferredUsername))
            {
                $user_login = $identity->preferredUsername;
            }
            elseif (!empty($identity->displayName))
            {
                $user_login = $identity->displayName;
            }
            elseif (!empty($identity->name->formatted))
            {
                $user_login = $identity->name->formatted;
            }
            else
            {
                $user_login = '';
            }

            // Get user by token
            $user_id = plgSystemOneAllSocialLoginHelper::getUserIdForToken($user_token);

            // Not linked, try to link to existing account
            if (!is_numeric($user_id))
            {
                // Linking enabled?
                if (!empty($settings['link_verified_accounts']))
                {
                    // Only of email is verified
                    if (!empty($user_email) and $user_email_is_verified === true)
                    {
                        // Read existing user
                        if (($user_id_tmp = plgSystemOneAllSocialLoginHelper::getUserIdForEmail($user_email)) !== false)
                        {
                            // Link user to token
                            if (is_numeric($user_id_tmp))
                            {
                                if (plgSystemOneAllSocialLoginHelper::setUserIdForToken($user_token, $user_id_tmp))
                                {
                                    $user_id = $user_id_tmp;
                                }
                            }
                        }
                    }
                }
            }

            // ***** New User *****
            if (!is_numeric($user_id))
            {
                // Import libraries
                jimport('joomla.user.helper');
                jimport('joomla.application.component.helper');

                // New user
                $new_user = true;

                // Get the com_user params
                $usersParams = JComponentHelper::getParams('com_users');

                // Make sure user registration is allowed.
                if ($usersParams->get('allowUserRegistration') == '0' && !$usersParams->get('override_allow_user_registration', 0))
                {
                    JFactory::getApplication()->enqueueMessage(JText::_('Sorry, but your account could not be created as the registration of new users has been disabled by the administrator of this website.'), 'error');

                    return false;
                }

                // Replace characters that are now allowed by Joomla
                // See JLIB_DATABASE_ERROR_VALID_AZ09 in libraries/joomla/table/user.php
                $user_login = preg_replace('#[<>"\'%;()&\s\\\\]|\\.\\./#', "", $user_login);
                $user_login = trim(trim($user_login), '.');

                // Username must be at least 2 characters long
                if (strlen($user_login) < 2)
                {
                    $user_login = $user_identity_provider . 'User';
                }

                // Username must be unique
                if (plgSystemOneAllSocialLoginHelper::usernameExists($user_login))
                {
                    $i = 1;
                    $user_login_tmp = $user_login;
                    do
                    {
                        $user_login_tmp = $user_login . ($i++);
                    } while (plgSystemOneAllSocialLoginHelper::usernameExists($user_login_tmp));

                    // Unique user login
                    $user_login = $user_login_tmp;
                }

                // Email must be unique
                if (empty($user_email) or plgSystemOneAllSocialLoginHelper::useremailExists($user_email))
                {
                    $user_email = plgSystemOneAllSocialLoginHelper::getRandomUseremail();
                }

                // Get the ACL
                $acl = JFactory::getACL();

                // Ggenerate a new JUser Object
                $user = JFactory::getUser(0);

                // Array for all user settings
                $data = array();

                // Get the default usertype
                $defaultUserGroups = $usersParams->get('new_usertype', 2);
                if (!$defaultUserGroups)
                {
                    $defaultUserGroups = 'Registered';
                }

                // Setup the "main" user information
                $data['name'] = $user_full_name;
                $data['username'] = $user_login;
                $data['email'] = $user_email;
                $data['usertype'] = 'deprecated';
                $data['language'] = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
                $data['groups'] = array(
                    $defaultUserGroups
                );
                $data['registerDate'] = JFactory::getDate()->toSQL();
                $data['password'] = JUserHelper::genRandomPassword();
                $data['password2'] = $data['password'];
                $data['sendEmail'] = 0;
                $data['block'] = 0;

                // Bind the data to the JUser Object
                if (!$user->bind($data))
                {
                    JError::raiseWarning('', JText::_('Could not bind data to user') . ': ' . JText::_($user->getError()));

                    return false;
                }

                // Save the user
                if (!$user->save())
                {
                    JError::raiseWarning('', JText::_('Could not create user') . ': ' . JText::_($user->getError()));

                    return false;
                }

                // Store userid
                $user_id = $user->get('id');

                // Link to token
                plgSystemOneAllSocialLoginHelper::setUserIdForToken($user_token, $user_id);
            }
            // Returning user
            else
            {
                $new_user = false;
            }

            // Sucess
            if (isset($user_id) and is_numeric($user_id) and !empty($user_id))
            {
                // User exists
                $user = JFactory::getUser($user_id);
                if (is_object($user))
                {
                    // Get the application object.
                    $app = JFactory::getApplication();
                    $db = JFactory::getDBO();
                    $query = 'SELECT `id`, `username`, `password` FROM `#__users` WHERE id = ' . $db->Quote($user->get('id'));
                    $db->setQuery($query);
                    $result = $db->loadObject();

                    // Login user
                    if ($result and !empty($result->username))
                    {
                        JPluginHelper::importPlugin('user');

                        // Setup options
                        $options = array();
                        $options['action'] = 'core.login.site';

                        // Return to the original URL
                        /*
                         * if ( ! empty ($_REQUEST['return']))
                         * {
                         * $url = base64_decode (urldecode ($_REQUEST['return']));
                         *
                         * //Registration
                         * $settings['redirect_register_url'] = $url;
                         *
                         * //Login
                         * $settings['redirect_login_url'] = $url;
                         *
                         * }
                         */

                        // Setup return url for new users
                        if ($new_user === true)
                        {
                            if (isset($settings['redirect_register_url']) and strlen(trim($settings['redirect_register_url'])) > 0)
                            {
                                $session = JFactory::getSession();
                                $session->set('redirect_url', trim($settings['redirect_register_url']), 'plg_oneallsociallogin');
                            }
                        }
                        // Setup return url for returning users
                        elseif ($new_user === false)
                        {
                            if (isset($settings['redirect_login_url']) and strlen(trim($settings['redirect_login_url'])) > 0)
                            {
                                $session = JFactory::getSession();
                                $session->set('redirect_url', trim($settings['redirect_login_url']), 'plg_oneallsociallogin');
                            }
                        }

                        $response = new stdClass();
                        $response->username = $result->username;
                        $response->fullname = $result->username;
                        $response->password = $result->password;
                        $response->type = 'oneall-social-login';
                        $response->status = \JAuthentication::STATUS_SUCCESS;

                        $loginResult = $app->triggerEvent('onUserLogin', array((array) $response, $options));

                        $user = \JFactory::getUser();
                        if (in_array(false, $loginResult, true) == false)
                        {
                            $options['user'] = $user;
                            $options['responseType'] = $response->type;

                            // The user is successfully logged in. Run the after login events
                            $app->triggerEvent('onUserAfterLogin', array($options));
                        }

                        //Done

                        return true;
                    }
                }
            }
        }
    }

    /**
     * Check for token
     */
    public function onAfterInitialise()
    {
        // Check if we have a connection token
        if (isset($_POST) and !empty($_POST['oa_action']) and $_POST['oa_action'] == 'social_login' and !empty($_POST['connection_token']))
        {
            $this->doAuth($_POST['connection_token']);
        }
    }

    /**
     * Redirect if necessary
     */
    public function onAfterRoute()
    {
        // Read session
        $session = JFactory::getSession();

        // Check for uri
        $redirect_url = $session->get('redirect_url', null, 'plg_oneallsociallogin');
        if (!empty($redirect_url))
        {
            // Clear uri
            $session->clear('redirect_url', 'plg_oneallsociallogin');

            // Redirect
            $app = JFactory::getApplication();
            $app->redirect($redirect_url);
        }
    }
}
