<?php
/**
 * @package       OneAll Social Login Component
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
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
class Com_OneAllSocialLoginInstallerScript
{
    /**
     * The component's name
     */
    protected $_oneallsociallogin_extension = 'com_oneallsociallogin';

    /**
     * The list of extra modules and plugins to install
     */
    private $installation_queue = array(
        'modules' => array(
            'site' => array(
                'oneallsociallogin' => array(
                    'title' => 'Social Login',
                    'position' => 'position-7',
                    'order' => 8,
                    'published' => 1,
                    'language' => '*',
                    'showtitle' => 0
                )
            )
        ),
        'plugins' => array(
            'system' => array(
                'oneallsociallogin' => array(
                    'published' => 1
                )
            )
        )
    );

    /**
     * Runs before install, update or discover_install
     */
    public function preflight($type, $parent)
    {
        switch ($type)
        {
            case 'install':
            case 'discover_install':
                $this->bugfix_db_function_returned_no_error();
                break;

            case 'update':
                $this->bugfix_cannot_build_admin_menus();
                break;
        }

        // Only allow to install on Joomla! 3 or later

        return version_compare(JVERSION, '3', 'ge');
    }

    /**
     * Runs after install, update or discover_update
     */
    public function postflight($type, $parent)
    {
        // Install sub-extensions
        $status = $this->install_sub_extensions($parent);

        // Show the post-installation page
        $this->render_post_installation($status, $parent);
    }

    /**
     * Runs on uninstallation
     */
    public function uninstall($parent)
    {
        // Uninstall subextensions
        $status = $this->uninstall_sub_extensions($parent);

        // Show the post-uninstallation page
        $this->render_post_uninstallation($status, $parent);
    }

    /**
     * Renders the post-installation message
     */
    private function render_post_installation($status, $parent)
    {
        $message = '<br/>Thank you very much for having installed <strong>Social Login</strong>!<br />';
        $message .= 'Please open the <a href="index.php?option=com_oneallsociallogin">Social Login Configuration</a> to enable this component.<br/><br/>';
        echo $message;
    }

    /**
     * Renders the post-uninstallation message
     */
    private function render_post_uninstallation($status, $parent)
    {
        $message = '<strong>Social Login</strong> has been uninstalled successfully.';
        echo $message;
    }

    /**
     * Joomla! bugfix for "DB function returned no error"
     */
    private function bugfix_db_function_returned_no_error()
    {
        $db = JFactory::getDbo();

        // Fix broken #__assets records
        $query = $db->getQuery(true);
        $query->select('id')->from('#__assets')->where($db->qn('name') . ' = ' . $db->q($this->_oneallsociallogin_extension));
        $db->setQuery($query);
        $ids = $db->loadColumn();

        if (is_array($ids))
        {
            foreach ($ids as $id)
            {
                $query = $db->getQuery(true);
                $query->delete('#__assets')->where($db->qn('id') . ' = ' . $db->q($id));
                $db->setQuery($query);
                $db->execute();
            }
        }

        // Fix broken #__extensions records
        $query = $db->getQuery(true);
        $query->select('extension_id')->from('#__extensions')->where($db->qn('element') . ' = ' . $db->q($this->_oneallsociallogin_extension));
        $db->setQuery($query);
        $ids = $db->loadColumn();

        if (is_array($ids))
        {
            foreach ($ids as $id)
            {
                $query = $db->getQuery(true);
                $query->delete('#__extensions')->where($db->qn('extension_id') . ' = ' . $db->q($id));
                $db->setQuery($query);
                $db->execute();
            }
        }

        // Fix broken #__menu records
        $query = $db->getQuery(true);
        $query->select('id')->from('#__menu')->where($db->qn('type') . ' = ' . $db->q('component'))->where($db->qn('menutype') . ' = ' . $db->q('main'))->where($db->qn('link') . ' LIKE ' . $db->q('index.php?option=' . $this->_oneallsociallogin_extension));
        $db->setQuery($query);
        $ids = $db->loadColumn();

        if (is_array($ids))
        {
            foreach ($ids as $id)
            {
                $query = $db->getQuery(true);
                $query->delete('#__menu')->where($db->qn('id') . ' = ' . $db->q($id));
                $db->setQuery($query);
                $db->execute();
            }
        }
    }

    /**
     * Joomla! bugfix for "Can not build admin menus"
     */
    private function bugfix_cannot_build_admin_menus()
    {
        $db = JFactory::getDbo();

        // If there are multiple #__extensions record, keep one of them
        $query = $db->getQuery(true);
        $query->select('extension_id')->from('#__extensions')->where($db->qn('element') . ' = ' . $db->q($this->_oneallsociallogin_extension));
        $db->setQuery($query);
        $ids = $db->loadColumn();
        if (count($ids) > 1)
        {
            asort($ids);
            $extension_id = array_shift($ids); // Keep the oldest id

            foreach ($ids as $id)
            {
                $query = $db->getQuery(true);
                $query->delete('#__extensions')->where($db->qn('extension_id') . ' = ' . $db->q($id));
                $db->setQuery($query);
                $db->execute();
            }
        }

        // If there are multiple assets records, delete all except the oldest one
        $query = $db->getQuery(true);
        $query->select('id')->from('#__assets')->where($db->qn('name') . ' = ' . $db->q($this->_oneallsociallogin_extension));
        $db->setQuery($query);
        $ids = $db->loadObjectList();
        if (count($ids) > 1)
        {
            asort($ids);
            $asset_id = array_shift($ids); // Keep the oldest id

            foreach ($ids as $id)
            {
                $query = $db->getQuery(true);
                $query->delete('#__assets')->where($db->qn('id') . ' = ' . $db->q($id));
                $db->setQuery($query);
                $db->execute();
            }
        }

        // Remove #__menu records for good measure!
        $query = $db->getQuery(true);
        $query->select('id')->from('#__menu')->where($db->qn('type') . ' = ' . $db->q('component'))->where($db->qn('menutype') . ' = ' . $db->q('main'))->where($db->qn('link') . ' LIKE ' . $db->q('index.php?option=' . $this->_oneallsociallogin_extension));
        $db->setQuery($query);
        $ids1 = $db->loadColumn();
        if (empty($ids1))
        {
            $ids1 = array();
        }

        $query = $db->getQuery(true);
        $query->select('id')->from('#__menu')->where($db->qn('type') . ' = ' . $db->q('component'))->where($db->qn('menutype') . ' = ' . $db->q('main'))->where($db->qn('link') . ' LIKE ' . $db->q('index.php?option=' . $this->_oneallsociallogin_extension . '&%'));
        $db->setQuery($query);
        $ids2 = $db->loadColumn();
        if (empty($ids2))
        {
            $ids2 = array();
        }

        $ids = array_merge($ids1, $ids2);
        if (!empty($ids))
        {
            foreach ($ids as $id)
            {
                $query = $db->getQuery(true);
                $query->delete('#__menu')->where($db->qn('id') . ' = ' . $db->q($id));
                $db->setQuery($query);
                $db->execute();
            }
        }
    }

    /**
     * Installs subextensions (modules, plugins) bundled with the main extension
     */
    private function install_sub_extensions($parent)
    {
        $src = $parent->getParent()->getPath('source');

        $db = JFactory::getDbo();

        // The subextension installation status
        $status = new JObject();
        $status->modules = array();
        $status->plugins = array();

        // Modules installation
        if (isset($this->installation_queue['modules']) and is_array($this->installation_queue['modules']))
        {
            foreach ($this->installation_queue['modules'] as $folder => $modules)
            {
                if (is_array($modules))
                {
                    foreach ($modules as $module => $module_preferences)
                    {
                        // Look for the temporary installation folder
                        $path = $src . '/modules/' . $folder . '/' . $module;

                        if (!is_dir($path))
                        {
                            $path = $src . '/modules/' . $folder . '/mod_' . $module;
                        }

                        if (!is_dir($path))
                        {
                            $path = $src . '/modules/' . $module;
                        }

                        if (!is_dir($path))
                        {
                            $path = $src . '/modules/mod_' . $module;
                        }

                        if (!is_dir($path))
                        {
                            $path = $src . '/mod_' . $module;
                        }

                        if (!is_dir($path))
                        {
                            continue;
                        }

                        // Was the module already installed?
                        $sql = $db->getQuery(true)->select('COUNT(*)')->from('#__modules')->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
                        $db->setQuery($sql);
                        $count = $db->loadResult();

                        // Install
                        $installer = new JInstaller();
                        $installer->setOverwrite(true);
                        $result = $installer->install($path);

                        // Store status
                        $status->modules[] = array(
                            'name' => 'mod_' . $module,
                            'client' => $folder,
                            'result' => $result
                        );

                        // Modify where it's published and its published state
                        if (!$count)
                        {
                            // Flags
                            $module_position = (isset($module_preferences['position']) ? $module_preferences['position'] : 1);
                            $module_published = (!empty($module_preferences['published']));
                            $module_showtitle = (!empty($module_preferences['showtitle']));

                            // Position
                            $sql = $db->getQuery(true)->update($db->qn('#__modules'))->set($db->qn('position') . ' = ' . $db->q($module_position))->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));

                            // Published
                            if ($module_published)
                            {
                                $sql->set($db->qn('published') . ' = ' . $db->q('1'));
                            }

                            // Do not display the title?
                            if (!$module_showtitle)
                            {
                                $sql->set($db->qn('showtitle') . ' = ' . $db->q('0'));
                            }

                            $db->setQuery($sql);
                            $db->execute();

                            // Change the ordering of back-end modules to 1 + max ordering
                            if ($folder == 'admin')
                            {
                                $query = $db->getQuery(true);
                                $query->select('MAX(' . $db->qn('ordering') . ')')->from($db->qn('#__modules'))->where($db->qn('position') . '=' . $db->q($module_position));
                                $db->setQuery($query);
                                $position = $db->loadResult();
                                $position++;

                                $query = $db->getQuery(true);
                                $query->update($db->qn('#__modules'))->set($db->qn('ordering') . ' = ' . $db->q($position))->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
                                $db->setQuery($query);
                                $db->execute();
                            }

                            // Link to all pages
                            $query = $db->getQuery(true);
                            $query->select('id')->from($db->qn('#__modules'))->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
                            $db->setQuery($query);
                            $moduleid = $db->loadResult();

                            $query = $db->getQuery(true);
                            $query->select('*')->from($db->qn('#__modules_menu'))->where($db->qn('moduleid') . ' = ' . $db->q($moduleid));
                            $db->setQuery($query);
                            $assignments = $db->loadObjectList();

                            $isAssigned = !empty($assignments);
                            if (!$isAssigned)
                            {
                                $o = (object) array(
                                    'moduleid' => $moduleid,
                                    'menuid' => 0
                                );
                                $db->insertObject('#__modules_menu', $o);
                            }
                        }
                    }
                }
            }
        }

        // Plugins installation
        if (isset($this->installation_queue['plugins']) and is_array($this->installation_queue['plugins']))
        {
            foreach ($this->installation_queue['plugins'] as $folder => $plugins)
            {
                if (is_array($plugins))
                {
                    foreach ($plugins as $plugin => $plugin_preferences)
                    {
                        // Look for the temporary installation folder
                        $path = $src . '/plugins/' . $folder . '/$plugin';

                        if (!is_dir($path))
                        {
                            $path = $src . '/plugins/' . $folder . '/plg_' . $plugin;
                        }

                        if (!is_dir($path))
                        {
                            $path = $src . '/plugins/' . $plugin;
                        }

                        if (!is_dir($path))
                        {
                            $path = $src . '/plugins/plg_' . $plugin;
                        }

                        if (!is_dir($path))
                        {
                            $path = $src . '/plg_' . $plugin;
                        }

                        if (!is_dir($path))
                        {
                            continue;
                        }

                        // Was the plugin already installed?
                        $query = $db->getQuery(true)->select('COUNT(*)')->from($db->qn('#__extensions'))->where($db->qn('element') . ' = ' . $db->q($plugin))->where($db->qn('folder') . ' = ' . $db->q($folder));
                        $db->setQuery($query);
                        $count = $db->loadResult();

                        // Install
                        $installer = new JInstaller();
                        $result = $installer->install($path);

                        // Store status
                        $status->plugins[] = array(
                            'name' => 'plg_' . $plugin,
                            'group' => $folder,
                            'result' => $result
                        );

                        // Publish plugin
                        if (!empty($plugin_preferences['published']) && !$count)
                        {
                            $query = $db->getQuery(true)->update($db->qn('#__extensions'))->set($db->qn('enabled') . ' = ' . $db->q('1'))->where($db->qn('element') . ' = ' . $db->q($plugin))->where($db->qn('folder') . ' = ' . $db->q($folder));
                            $db->setQuery($query);
                            $db->execute();
                        }
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Uninstalls subextensions (modules, plugins) bundled with the main extension
     */
    private function uninstall_sub_extensions($parent)
    {
        jimport('joomla.installer.installer');

        // Database handler
        $db = JFactory::getDBO();

        // Uninstall status
        $status = new JObject();
        $status->modules = array();
        $status->plugins = array();

        // Modules uninstallation
        if (isset($this->installation_queue['modules']) and is_array($this->installation_queue['modules']))
        {
            foreach ($this->installation_queue['modules'] as $folder => $modules)
            {
                if (is_array($modules))
                {
                    foreach ($modules as $module => $module_preferences)
                    {
                        // Find the module ID
                        $sql = $db->getQuery(true)->select($db->qn('extension_id'))->from($db->qn('#__extensions'))->where($db->qn('element') . ' = ' . $db->q('mod_' . $module))->where($db->qn('type') . ' = ' . $db->q('module'));
                        $db->setQuery($sql);
                        $id = $db->loadResult();

                        // Uninstall the module
                        if ($id)
                        {
                            $installer = new JInstaller();
                            $result = $installer->uninstall('module', $id, 1);
                            $status->modules[] = array(
                                'name' => 'mod_' . $module,
                                'client' => $folder,
                                'result' => $result
                            );
                        }
                    }
                }
            }
        }

        // Plugins uninstallation
        if (isset($this->installation_queue['plugins']) and is_array($this->installation_queue['plugins']))
        {
            foreach ($this->installation_queue['plugins'] as $folder => $plugins)
            {
                if (is_array($plugins))
                {
                    foreach ($plugins as $plugin => $plugin_preferences)
                    {
                        // Find the plugin ID
                        $sql = $db->getQuery(true)->select($db->qn('extension_id'))->from($db->qn('#__extensions'))->where($db->qn('type') . ' = ' . $db->q('plugin'))->where($db->qn('element') . ' = ' . $db->q($plugin))->where($db->qn('folder') . ' = ' . $db->q($folder));
                        $db->setQuery($sql);
                        $id = $db->loadResult();

                        // Uninstall the plugin
                        if ($id)
                        {
                            $installer = new JInstaller();
                            $result = $installer->uninstall('plugin', $id, 1);
                            $status->plugins[] = array(
                                'name' => 'plg_' . $plugin,
                                'group' => $folder,
                                'result' => $result
                            );
                        }
                    }
                }
            }
        }

        return $status;
    }
}
