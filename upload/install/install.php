<?php
/**
 * @package   	SocialLogin Component
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

//Setup database handler
$db = JFactory::getDBO ();

//Read manifest
$manifest = $this->manifest;

//Installed modules
$installed = array ();

//Parse manifest and install modules
if ($manifest instanceof JXMLElement AND property_exists ($manifest, 'modules'))
{
	//Check of modules child
	if ($manifest->modules instanceof JXMLElement)
	{
		//Lopp trough modules
		foreach ($manifest->modules->children () AS $module)
		{
			//Setup module data
			$mod_data = array ();
			foreach ($module->attributes () as $key => $value)
			{
				$mod_data [$key] = strval ($value);
			}
			$mod_data ['client'] = JApplicationHelper::getClientInfo ($mod_data ['client'], true);

			//Set the installation path
			$this->parent->setPath ('extension_root', $mod_data ['client']->path . DS . 'modules' . DS . $mod_data ['module']);

			//If the directory exists, we assume that the module is already installed or another module is using that directory.
			if (file_exists ($this->parent->getPath ('extension_root')))
			{
				if (!$this->parent->getOverwrite ())
				{
					$this->parent->abort (JText::_ ('Module') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('Another module is already using the directory') . ': "' . $this->parent->getPath ('extension_root') . '"');
					return false;
				}
			}

			//If the module directory does not exist, lets create it
			$mod_created = false;
			if (!file_exists ($this->parent->getPath ('extension_root')))
			{
				if (!($mod_created = JFolder::create ($this->parent->getPath ('extension_root'))))
				{
					$this->parent->abort (JText::_ ('Module') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('Failed to create directory') . ': "' . $this->parent->getPath ('extension_root') . '"');
					return false;
				}
			}

			//Since we created the module directory and will want to remove it if we have to roll back the installation, lets add it to the installation step stack
			if ($mod_created)
			{
				$this->parent->pushStep (array (
					'type' => 'folder',
					'path' => $this->parent->getPath ('extension_root')
				));
			}

			//Copy all necessary files
			if ($this->parent->parseFiles ($module->files, -1) === false)
			{
				// Install failed, roll back changes
				$this->parent->abort ();
				return false;
			}

			//Build Manifest Cache
			$mod_data ['manifest_cache'] = json_encode (JApplicationHelper::parseXMLInstallFile ((string) $mod_data ['client']->path . DS . 'modules' . DS . $mod_data ['module'] . DS . $mod_data ['module'] . '.xml'));


			//If extension already installed do not create a new instance
			$query = "SELECT `extension_id` FROM `#__extensions` WHERE type='module' AND element = " . $db->Quote ($mod_data ['module']);
			$db->setQuery ($query);
			if (!$db->Query ())
			{
				// Install failed, roll back changes
				$this->parent->abort (JText::_ ('Extension') . ' ' . JText::_ ('Install') . ': ' . $db->stderr (true));
				return false;
			}
			$extension_id = $db->loadResult ();

			//Does not exist
			if (!$extension_id)
			{
				//Extension Data
				$data = array ();
				$data ['name'] = $mod_data ['title'];
				$data ['type'] = 'module';
				$data ['element'] = $mod_data ['module'];
				$data ['folder'] = '';
				$data ['client_id'] = (int) $mod_data ['client']->id;
				$data ['enabled'] = 1;
				$data ['access'] = 1;
				$data ['protected'] = 0;
				$data ['manifest_cache'] = $mod_data ['manifest_cache'];
				$data ['params'] = '{}';

				//Create Extension
				$table = JTable::getInstance ('extension');
				if (!$table->bind ($data))
				{
					// Install failed, roll back changes
					$this->parent->abort (JText::_ ('Extension') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('table->bind throws error'));
					return false;
				}
				if (!$table->check ($data))
				{
					// Install failed, roll back changes
					$this->parent->abort (JText::_ ('Extension') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('table->check throws error'));
					return false;
				}
				if (!$table->store ($data))
				{
					// Install failed, roll back changes
					$this->parent->abort (JText::_ ('Extension') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('table->store throws error'));
					return false;
				}

				// Add it to the installation step stack  so that if we have to rollback the changes we can undo it.
				$this->parent->pushStep (array (
					'type' => 'extension',
					'extension_id' => $table->extension_id
				));
			}

			// If module already installed do not create a new instance
			$query = 'SELECT `id` FROM `#__modules` WHERE module = ' . $db->Quote ($mod_data ['module']);
			$db->setQuery ($query);
			if (!$db->Query ())
			{
				// Install failed, roll back changes
				$this->parent->abort (JText::_ ('Module') . ' ' . JText::_ ('Install') . ': ' . $db->stderr (true));
				return false;
			}
			$mod_id = $db->loadResult ();

			//Does not exist
			if (!$mod_id)
			{
				//Module Data
				$data = array ();
				$data ['title'] = $mod_data ['title'];
				$data ['ordering'] = $mod_data ['order'];
				$data ['position'] = $mod_data ['position'];
				$data ['showtitle'] = (!empty ($mod_data ['showtitle']) ? 1 : 0);
				$data ['published'] = 1;
				$data ['module'] = $mod_data ['module'];
				$data ['access'] = 1;
				$data ['params'] = '';
				$data ['client_id'] = $mod_data ['client']->id;

				//Create Module
				$table = JTable::getInstance ('module');
				if (!$table->bind ($data))
				{
					// Install failed, roll back changes
					$this->parent->abort (JText::_ ('Module') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('table->bind throws error'));
					return false;
				}
				if (!$table->check ($data))
				{
					// Install failed, roll back changes
					$this->parent->abort (JText::_ ('Module') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('table->check throws error'));
					return false;
				}
				if (!$table->store ($data))
				{
					// Install failed, roll back changes
					$this->parent->abort (JText::_ ('Module') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('table->store throws error'));
					return false;
				}

				//User below
				$mod_id = $table->id;
			}

			// Make visible everywhere if site module
			if ((int) $mod_data ['client']->id == 0)
			{
				$query = 'REPLACE INTO `#__modules_menu` (moduleid,menuid) values (' . $db->Quote ($mod_id) . ',0)';
				$db->setQuery ($query);
				if (!$db->query ())
				{
					// Install failed, roll back changes
					$this->parent->abort (JText::_ ('Module') . ' ' . JText::_ ('Install') . ': ' . $db->stderr (true));
					return false;
				}
			}

			//Installed
			$installed [] = array (
				'type' => 'module',
				'title' => $mod_data ['module']
			);
		}
	}
}

//Parse manifest and install plugins
if ($manifest instanceof JXMLElement AND property_exists ($manifest, 'plugins'))
{
	//Check for plugins child
	if ($manifest->plugins instanceof JXMLElement)
	{
		//Lopp trough modules
		foreach ($manifest->plugins->children () AS $plugin)
		{
			//Setup plugin data
			$plg_data = array ();
			foreach ($plugin->attributes () as $key => $value)
			{
				$plg_data [$key] = strval ($value);
			}

			// Set the installation path
			$this->parent->setPath ('extension_root', JPATH_ROOT . DS . 'plugins' . DS . $plg_data ['group'] . DS . $plg_data ['plugin']);

			// If the plugin directory does not exist, lets create it
			$created = false;
			if (!file_exists ($this->parent->getPath ('extension_root')))
			{
				if (!$created = JFolder::create ($this->parent->getPath ('extension_root')))
				{
					$this->parent->abort (JText::_ ('Plugin') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('Failed to create directory') . ': "' . $this->parent->getPath ('extension_root') . '"');
					return false;
				}
			}

			//Remove it if we have to roll back the installation, lets add it to the installation step stack
			if ($created)
			{
				$this->parent->pushStep (array (
					'type' => 'folder',
					'path' => $this->parent->getPath ('extension_root')
				));
			}

			// Copy all necessary files
			if ($this->parent->parseFiles ($plugin->files, -1) === false)
			{
				// Install failed, roll back changes
				$this->parent->abort ();
				return false;
			}

			//Build Manifest Cache
			$plg_data ['manifest_cache'] = json_encode (JApplicationHelper::parseXMLInstallFile (JPATH_ROOT . DS . 'plugins' . DS . $plg_data ['group'] . DS . $plg_data ['plugin'] . DS . $plg_data ['plugin'] . '.xml'));

			// Check to see if a plugin by the same name is already installed
			$query = 'SELECT `extension_id` FROM `#__extensions` WHERE folder = ' . $db->Quote ($plg_data ['group']) . ' AND type=\'plugin\' AND element = ' . $db->Quote ($plg_data ['plugin']);
			$db->setQuery ($query);
			if (!$db->Query ())
			{
				// Install failed, roll back changes
				$this->parent->abort (JText::_ ('Plugin') . ' ' . JText::_ ('Install') . ': ' . $db->stderr (true));
				return false;
			}
			$plugin_id = $db->loadResult ();

			// Was there a plugin already installed with the same name?
			if (empty ($plugin_id))
			{
				//Extension Data
				$data = array ();
				$data ['name'] = $plg_data ['title'];
				$data ['type'] = 'plugin';
				$data ['element'] = $plg_data ['plugin'];
				$data ['folder'] = $plg_data ['group'];
				$data ['client_id'] = 0;
				$data ['enabled'] = 1;
				$data ['access'] = 1;
				$data ['protected'] = 0;
				$data ['manifest_cache'] = $plg_data ['manifest_cache'];
				$data ['params'] = '{}';

				//Create Extension
				$table = JTable::getInstance ('extension');
				if (!$table->bind ($data))
				{
					// Install failed, roll back changes
					$this->parent->abort (JText::_ ('Plugin') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('table->bind throws error'));
					return false;
				}
				if (!$table->check ($data))
				{
					// Install failed, roll back changes
					$this->parent->abort (JText::_ ('Plugin') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('table->check throws error'));
					return false;
				}
				if (!$table->store ($data))
				{
					// Install failed, roll back changes
					$this->parent->abort (JText::_ ('Plugin') . ' ' . JText::_ ('Install') . ': ' . JText::_ ('table->store throws error'));
					return false;
				}

				// Add it to the installation step stack  so that if we have to rollback the changes we can undo it.
				$this->parent->pushStep (array (
					'type' => 'extension',
					'extension_id' => $table->extension_id
				));
			}

			//Installed
			$installed [] = array (
				'type' => 'plugin',
				'title' => $plg_data ['plugin']
			);
		}
	}
}

//Success!
if (count ($installed) > 0)
{
	?>
	<h2><?php echo JText::_ ('Social Login Installation'); ?></h2>
	<p class="nowarning">
		Thank you very much for having installed <strong>Social Login</strong>!<br />
		In order to enable the component, it has to be <a href="index.php?option=com_sociallogin">configured</a> first.<br /><br />
		Feel free to <a href="http://www.oneall.com/company/contact-us/" target="_blank">contact us</a> if you need any assistance.
		<strong>Thank you!</strong>
	</p>
	<?php
}