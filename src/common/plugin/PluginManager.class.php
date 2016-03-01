<?php
/**
 * Copyright (c) Enalean SAS, 2015. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * PluginManager
 */
class PluginManager {


    const PLUGIN_HOOK_CACHE_FILE = 'hooks.json';

    /** @var EventManager */
    private $event_manager;

    /** @var PluginFactory */
    private $plugin_factory;

    /** @var SiteCache */
    private $site_cache;

    /** @var PluginManager */
    private static $instance = null;

    /** @var ForgeUpgradeConfig */
    private $forgeupgrade_config;

    var $pluginHookPriorityManager;

    public function __construct(PluginFactory $plugin_factory, EventManager $event_manager, SiteCache $site_cache, ForgeUpgradeConfig $forgeupgrade_config) {
        $this->plugin_factory      = $plugin_factory;
        $this->event_manager       = $event_manager;
        $this->site_cache          = $site_cache;
        $this->forgeupgrade_config = $forgeupgrade_config;
    }

    public static function instance() {
        if (! self::$instance) {
            self::$instance = new PluginManager(
                PluginFactory::instance(),
                EventManager::instance(),
                new SiteCache(),
                new ForgeUpgradeConfig(
                    new System_Command()
                )
            );
        }
        return self::$instance;
    }

    public static function setInstance(PluginManager $plugin_manager) {
        self::$instance = $plugin_manager;
    }

    public static function clearInstance() {
        self::$instance = null;
    }

    public function loadPlugins() {
        foreach ($this->getHooksCache() as $plugin) {
            $this->loadPluginFiles($plugin['path']);
            $proxy = new PluginProxy($plugin['class'], $plugin['id']);
            foreach ($plugin['hooks'] as $hook) {
                $this->addListener($hook, $proxy);
            }
        }
    }

    private function loadPluginFiles($path) {
        include_once $path;
        $autoload = dirname($path).'/autoload.php';
        if (file_exists($autoload)) {
            include_once $autoload;
        }
    }

    private function addListener($hook, PluginProxy $proxy) {
        $proxy->addListener(
            $hook['event'],
            $hook['callback'],
            $hook['recall_event']
        );
        $this->event_manager->addListener(
            $hook['event'],
            $proxy,
            'processEvent',
            true
        );
    }

    private function getHooksCache() {
        $hooks_cache_file = $this->getCacheFile();
        if (! file_exists($hooks_cache_file)) {
            $hooks_cache = $this->getHooksOfAvailablePlugins();
            file_put_contents($hooks_cache_file, json_encode($hooks_cache));
            return $hooks_cache;
        }
        return json_decode(file_get_contents($hooks_cache_file), true);
    }

    public function getCacheFile() {
        return ForgeConfig::get('codendi_cache_dir').'/'.self::PLUGIN_HOOK_CACHE_FILE;
    }

    private function getHooksOfAvailablePlugins() {
        $hooks_cache = array();
        foreach($this->plugin_factory->getAvailablePlugins() as $plugin) {
            $hooks_cache[$plugin->getName()] = array(
                'id'    => $plugin->getId(),
                'class' => $this->plugin_factory->getClassName($plugin->getName()),
                'path'  => $this->plugin_factory->getClassPath($plugin->getName()),
                'hooks' => array()
            );
            foreach ($plugin->getHooksAndCallbacks()->iterator() as $hook) {
                $hooks_cache[$plugin->getName()]['hooks'][] = array(
                    'event'        => $hook['hook'],
                    'callback'     => $hook['callback'],
                    'recall_event' => $hook['recallHook'],
                );
            }
        }
        return $hooks_cache;
    }

    public function getAvailablePlugins() {
        return $this->plugin_factory->getAvailablePlugins();
    }

    function getAllPlugins() {
        return $this->plugin_factory->getAllPlugins();
    }

    function isPluginAvailable($plugin) {
        return $this->plugin_factory->isPluginAvailable($plugin);
    }

    function availablePlugin($plugin) {
        if ($plugin->canBeMadeAvailable()) {
            $this->plugin_factory->availablePlugin($plugin);

            $plugin->setAvailable(true);
            $this->site_cache->invalidatePluginBasedCaches();
        }
    }

    function unavailablePlugin($plugin) {
        $this->plugin_factory->unavailablePlugin($plugin);

        $plugin->setAvailable(false);
        $this->site_cache->invalidatePluginBasedCaches();
    }

    public function installAndActivate($name) {
        $plugin = $this->plugin_factory->getPluginByName($name);
        if (! $plugin) {
            $plugin = $this->installPlugin($name);
        }
        if (! $this->plugin_factory->isPluginAvailable($plugin)) {
            $this->plugin_factory->availablePlugin($plugin);
        }
        $this->site_cache->invalidatePluginBasedCaches();
    }

    function installPlugin($name) {
        $plugin = false;
        if ($this->isNameValid($name)) {
            if (!$this->plugin_factory->isPluginInstalled($name)) {
                if (!$this->_executeSqlStatements('install', $name)) {
                    $plugin = $this->plugin_factory->createPlugin($name);
                    if ($plugin instanceof Plugin) {
                        $this->_createEtc($name);
                        $this->configureForgeUpgrade($name);
                        $plugin->postInstall();
                    } else {
                        $GLOBALS['Response']->addFeedback('error', 'Unable to create plugin');
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', 'DB may be corrupted');
                }
            }
        }
        return $plugin;
    }

    function uninstallPlugin($plugin) {
        $name = $this->plugin_factory->getNameForPlugin($plugin);
        if (!$this->_executeSqlStatements('uninstall', $name)) {
            $this->uninstallForgeUpgrade($name);
            return $this->plugin_factory->removePlugin($plugin);
        } else {
            return false;
        }
    }
    function getPostInstall($name) {
        $path_to_file = '/'.$name.'/POSTINSTALL.txt';
        return file_exists($GLOBALS['sys_pluginsroot'].$path_to_file) ?
            file_get_contents($GLOBALS['sys_pluginsroot'].$path_to_file) :
            false;
    }

    function getInstallReadme($name) {
        foreach ($this->plugin_factory->getAllPossiblePluginsDir() as $dir) {
            $path = $dir.'/'.$name;
            if (file_exists($path.'/README.mkd') || file_exists($path.'/README')) {
                return $path.'/README';
            }
        }
        return false;
    }

    /**
     * Format the readme file of a plugin
     *
     * Use markdown formatter if installed and if README.mkd exists
     * Otherwise assume text/plain and put it in <pre> tags
     * If README file doesn't exist, return empty string.
     *
     * For Markdown, the following is needed:
     * <code>
     * pear channel-discover pear.michelf.com
     * pear install michelf/package
     * </code>
     *
     * @return string html
     */
    function fetchFormattedReadme($file) {
        if (is_file("$file.mkd")) {
            $content = file_get_contents("$file.mkd");
            if (@include_once "markdown.php") {
                return Markdown($content);
            }
            return $this->getEscapedReadme($content);
        }

        if (is_file("$file.txt")) {
            return $this->getEscapedReadme(file_get_contents("$file.txt"));
        }

        if (is_file($file)) {
            return $this->getEscapedReadme(file_get_contents($file));
        }

        return '';
    }

    private function getEscapedReadme($content) {
        return '<pre>'.Codendi_HTMLPurifier::instance()->purify($content).'</pre>';
    }

    /**
     * Initialize ForgeUpgrade configuration for given plugin
     *
     * Add in configuration and record existing migration scripts as 'skipped'
     * because the 'install.sql' script is up-to-date with latest DB modif.
     *
     * @param String $name Plugin's name
     */
    protected function configureForgeUpgrade($name) {
        try {
            $plugin_path = $GLOBALS['sys_pluginsroot'].$name;
            $this->forgeupgrade_config->loadDefaults();
            $this->forgeupgrade_config->addPath($GLOBALS['sys_pluginsroot'].$name);
            $this->forgeupgrade_config->recordOnlyPath($plugin_path);
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback('warning', "ForgeUpgrade configuration update failed: ".$e->getMessage());
        }
    }

    /**
     * Remove plugin from ForgeUpgrade configuration
     *
     * Keep migration scripts in DB, it doesn't matter.
     *
     * @param String $name Plugin's name
     */
    protected function uninstallForgeUpgrade($name) {
        try {
            $this->forgeupgrade_config->loadDefaults();
            $this->forgeupgrade_config->removePath($GLOBALS['sys_pluginsroot'].$name);
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback('warning', "ForgeUpgrade configuration update failed: ".$e->getMessage());
        }
    }

    function _createEtc($name) {
        if (!is_dir($GLOBALS['sys_custompluginsroot'] .'/'. $name)) {
            mkdir($GLOBALS['sys_custompluginsroot'] .'/'. $name, 0700);
        }
        if (is_dir($GLOBALS['sys_pluginsroot'] .'/'. $name .'/etc')) {
            if (!is_dir($GLOBALS['sys_custompluginsroot'] .'/'. $name .'/etc')) {
                mkdir($GLOBALS['sys_custompluginsroot'] .'/'. $name . '/etc', 0700);
            }
            $etcs = glob($GLOBALS['sys_pluginsroot'] .'/'. $name .'/etc/*');
            foreach($etcs as $etc) {
                if(is_dir($etc)) {
                    $this->copyDirectory($etc, $GLOBALS['sys_custompluginsroot'] .'/'. $name . '/etc/' . basename($etc));
                } else {
                    copy($etc, $GLOBALS['sys_custompluginsroot'] .'/'. $name . '/etc/' . basename($etc));
                }
            }
            $incdists = glob($GLOBALS['sys_custompluginsroot'] .'/'. $name .'/etc/*.dist');
            foreach($incdists as $incdist) {
                rename($incdist,  $GLOBALS['sys_custompluginsroot'] .'/'. $name . '/etc/' . basename($incdist, '.dist'));
            }
        }
    }

    function _executeSqlStatements($file, $name) {
        $db_corrupted = false;
        $path_to_file = '/'.$name.'/db/'.$file.'.sql';

        foreach ($this->plugin_factory->getAllPossiblePluginsDir() as $dir) {
            $sql_filename = $dir.$path_to_file;
            if (file_exists($sql_filename)) {
                $dbtables = new DBTablesDAO();
                if (!$dbtables->updateFromFile($sql_filename)) {
                    $db_corrupted = true;
                }
            }
        }

        return $db_corrupted;
    }

    function getNotYetInstalledPlugins() {
        return $this->plugin_factory->getNotYetInstalledPlugins();
    }

    function isNameValid($name) {
        return (0 === preg_match('/[^a-zA-Z0-9_-]/', $name));
    }

    function getPluginByName($name) {
        return $this->plugin_factory->getPluginByName($name);
    }

    function getAvailablePluginByName($name) {
        $plugin = $this->getPluginByName($name);
        if ($plugin && $this->isPluginAvailable($plugin)) {
            return $plugin;
        }
    }
    function getPluginById($id) {
        return $this->plugin_factory->getPluginById($id);
    }
    function pluginIsCustom($plugin) {
        return $this->plugin_factory->pluginIsCustom($plugin);
    }

    var $plugins_name;
    function getNameForPlugin($plugin) {
        if (!$this->plugins_name) {
            $this->plugins_name = array();
        }
        if (!isset($this->plugins_name[$plugin->getId()])) {
            $this->plugins_name[$plugin->getId()] = $this->plugin_factory->getNameForPlugin($plugin);
        }
        return $this->plugins_name[$plugin->getId()];
    }

    function getAllowedProjects($plugin) {
        return $this->plugin_factory->getProjectsByPluginId($plugin);
    }

    function _updateProjectForPlugin($action, $plugin, $projectIds) {
        $success     = true;
        $successOnce = false;

        if(is_array($projectIds)) {
            foreach($projectIds as $prjId) {
                switch($action){
                case 'add':
                    $success = $success && $this->plugin_factory->addProjectForPlugin($plugin, $prjId);
                    break;
                case 'del':
                    $success = $success && $this->plugin_factory->delProjectForPlugin($plugin, $prjId);
                    break;
                }

                if($success === true)
                    $successOnce = true;
            }
        }
        elseif(is_numeric($projectIds)) {
            switch($action){
            case 'add':
                $success = $success && $this->plugin_factory->addProjectForPlugin($plugin, $prjId);
                break;
            case 'del':
                $success = $success && $this->plugin_factory->delProjectForPlugin($plugin, $prjId);
                break;
            }
            $successOnce = $success;
        }

        if($successOnce && ($action == 'add')) {
            $this->plugin_factory->restrictProjectPluginUse($plugin, true);
        }
    }

    function addProjectForPlugin($plugin, $projectIds) {
        $this->_updateProjectForPlugin('add', $plugin, $projectIds);
    }

    function delProjectForPlugin($plugin, $projectIds) {
        $this->_updateProjectForPlugin('del', $plugin, $projectIds);
    }

    function isProjectPluginRestricted($plugin) {
        return $this->plugin_factory->isProjectPluginRestricted($plugin);
    }

    function updateProjectPluginRestriction($plugin, $restricted) {
        $this->plugin_factory->restrictProjectPluginUse($plugin, $restricted);
        if($restricted == false) {
            $this->plugin_factory->truncateProjectPlugin($plugin);
        }
    }

    function isPluginAllowedForProject($plugin, $projectId) {
        if($this->isProjectPluginRestricted($plugin)) {
            return $this->plugin_factory->isPluginAllowedForProject($plugin, $projectId);
        }
        else {
            return true;
        }
    }

    /**
     * This method instantiate a plugin that should not be used outside
     * of installation use case. It bypass all caches and do not check availability
     * of the plugin.
     *
     * @param string $name The name of the plugin (docman, tracker, …)
     * @return Plugin
     */
    public function getPluginDuringInstall($name) {
        return $this->plugin_factory->instantiatePlugin(0, $name);
    }

    private function copyDirectory($source, $destination) {

        if(!is_dir($destination)) {
            if(!mkdir($destination)) {
                return false;
            }
        }

        $iterator = new DirectoryIterator($source);
        foreach($iterator as $file) {
            if($file->isFile()) {
                copy($file->getRealPath(), "$destination/" . $file->getFilename());
            } else if(!$file->isDot() && $file->isDir()) {
                $this->copyDirectory($file->getRealPath(), "$destination/$file");
            }
        }
    }

    public function invalidateCache() {
        if (file_exists($this->getCacheFile())) {
            unlink($this->getCacheFile());
        }
    }
}
