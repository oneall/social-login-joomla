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

/**
 * Supported Providers
 */
$social_login_providers = array(
    'facebook' => array(
        'name' => 'Facebook',
        'default_enabled' => true
    ),
    'twitter' => array(
        'name' => 'Twitter',
        'default_enabled' => true
    ),
    'google' => array(
        'name' => 'Google',
        'default_enabled' => true
    ),
    'linkedin' => array(
        'name' => 'LinkedIn',
        'default_enabled' => true
    ),
    'yahoo' => array(
        'name' => 'Yahoo',
        'default_enabled' => true
    ),
    'openid' => array(
        'name' => 'OpenID',
        'default_enabled' => true
    ),
    'wordpress' => array(
        'name' => 'Wordpress.com',
        'default_enabled' => true
    ),
    'paypal' => array(
        'name' => 'PayPal',
        'default_enabled' => false
    ),
    'livejournal' => array(
        'name' => 'LiveJournal',
        'default_enabled' => false
    ),
    'vkontakte' => array(
        'name' => 'VKontakte (Вконтактеµ)',
        'default_enabled' => false
    )
);
