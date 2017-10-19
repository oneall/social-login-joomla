<?php
/**
 * @package   	SocialLogin
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
defined ('_JEXEC') or die ('Restricted access');
jimport ('joomla.application.component.view');

/**
 * SocialLogin View
 */
class SocialLoginViewSocialLogin extends JView
{
	//Config
	public $settings;
	public $providers;

	/**
	 * SocialLogin - Display administration area
	 * @return void
	 */
	public function display ($tpl = null)
	{
		//Require settings
		require_once(JPATH_BASE . '/components/com_sociallogin/assets/cfg.php');

		//Build document
		$document = &JFactory::getDocument ();
		$document->addStyleSheet ('components/com_sociallogin/assets/css/sociallogin.css');
		$document->addScript ('components/com_sociallogin/assets/js/jquery.js');
		$document->addScript ('components/com_sociallogin/assets/js/sociallogin.js');

		//Read settings
		$model = &$this->getModel ();
		$this->settings = $model->getSettings ();

		//Read providers
		$this->providers = $social_login_providers;

		//Build page
		$this->form = $this->get ('Form');
		$this->addToolbar ();

		// Display the template
		parent::display ($tpl);
	}

	/**
	 * SocialLogin - Add Toolbar
	 * @return void
	 */
	protected function addToolbar ()
	{
		JRequest::setVar ('hidemainmenu', false);
		JToolBarHelper::title (JText::_ ('Social Login Configuration'), 'weblinks.png');
		JToolBarHelper::apply ('apply');
	}
}