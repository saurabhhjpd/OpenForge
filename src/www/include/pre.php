<?php
//
// Copyright 2011-2015 (c) Enalean
// Copyright 1999-2000 (c) The SourceForge Crew
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// http://sourceforge.net
//
//


if (version_compare(phpversion(), '5.3', '<') && version_compare(phpversion(), '7', '>=')) {
    die('Tuleap must be run on a PHP 5.3 (or greater) engine.  PHP 7 is not yet supported.');
}


require_once('common/autoload_libs.php');
require_once('common/autoload.php');

if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Paris');
}

// Defines all of the settings first (hosts, databases, etc.)
$locar_inc_finder = new Config_LocalIncFinder();
$local_inc = $locar_inc_finder->getLocalIncPath();

require($local_inc);
require($GLOBALS['db_config_file']);
ForgeConfig::loadFromFile($GLOBALS['codendi_dir'] .'/src/etc/local.inc.dist'); //load the default settings
ForgeConfig::loadFromFile($local_inc);
ForgeConfig::loadFromFile($GLOBALS['db_config_file']);
if (isset($GLOBALS['DEBUG_MODE'])) {
    ForgeConfig::loadFromFile($GLOBALS['codendi_dir'] .'/src/etc/development.inc.dist');
    ForgeConfig::loadFromFile(dirname($local_inc).'/development.inc');
}
ForgeConfig::loadFromDatabase();

// Fix path if needed
if (isset($GLOBALS['htmlpurifier_dir'])) {
    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$GLOBALS['htmlpurifier_dir']);
}
if (isset($GLOBALS['jpgraph_dir'])) {
    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$GLOBALS['jpgraph_dir']);
}

define('TTF_DIR',isset($GLOBALS['ttf_font_dir']) ? $GLOBALS['ttf_font_dir'] : '/usr/share/fonts/');

$xml_security = new XML_Security();
$xml_security->disableExternalLoadOfEntities();

// Detect whether this file is called by a script running in cli mode, or in normal web mode
if (!defined('IS_SCRIPT')) {
    if (php_sapi_name() == "cli") {
        // Backend scripts should never ends because of lack of time or memory
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);

        define('IS_SCRIPT', true);
    } else {
        define('IS_SCRIPT', false);
    }
}

if (!IS_SCRIPT) {
    // Protection against clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    $csp_rules = "frame-ancestors 'self'; ";

    // XSS prevention
    header('X-XSS-Protection: 1; mode=block');
    $csp_whitelist_script_scr = ForgeConfig::get('sys_csp_script_scr_whitelist');
    $csp_rules               .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' $csp_whitelist_script_scr; "
                              . "reflected-xss block;";

    header('Content-Security-Policy: ' . $csp_rules);
}

//{{{ Sanitize $_REQUEST : remove cookies
while(count($_REQUEST)) {
    array_pop($_REQUEST);
}
if (!ini_get('variables_order')) {
        $_REQUEST = array_merge($_GET, $_POST);
} else {
    $g_pos = strpos(strtolower(ini_get('variables_order')), 'g');
    $p_pos = strpos(strtolower(ini_get('variables_order')), 'p');
    if ($g_pos === FALSE) {
        if ($p_pos !== FALSE) {
            $_REQUEST = $_POST;
        }
    } else {
        if ($p_pos === FALSE) {
            $_REQUEST = $_GET;
        } else {
            if ($g_pos < $p_pos) {
                $first = '_GET';
                $second = '_POST';
            } else {
                $first = '_POST';
                $second = '_GET';
            }
            $_REQUEST = array_merge($$first, $$second);
        }
    }
}
//Cast group_id as int.
foreach(array(
        'group_id',
        'atid',
        'pv',
    ) as $variable) {
    if (isset($_REQUEST[$variable])) {
        $$variable = $_REQUEST[$variable] = $_GET[$variable] = $_POST[$variable] = (int)$_REQUEST[$variable];
    }
}
//}}}

//{{{ define undefined variables
if (!isset($GLOBALS['feedback'])) {
    $GLOBALS['feedback'] = "";  //By default the feedbak is empty
}

$cookie_manager = new CookieManager();
if (!IS_SCRIPT) {
    // Prevent "Pragma: no-cache" to be sent to user (break https & IE)
    session_cache_limiter(false);
    session_start();
    $GLOBALS['session_hash'] = $cookie_manager->isCookie('session_hash') ? $cookie_manager->getCookie('session_hash') : false;
}
//}}}

// Create cache directory if needed
if (!file_exists($GLOBALS['codendi_cache_dir'])) {
      // This directory must be world reachable, but writable only by the web-server
      mkdir($GLOBALS['codendi_cache_dir'], 0755);
}

// Instantiate System Event listener
$system_event_manager = SystemEventManager::instance();

//Load plugins
$plugin_manager =& PluginManager::instance();
$plugin_manager->loadPlugins();

$feedback=''; // Initialize global var

//library to determine browser settings
if(!IS_SCRIPT) {
    require_once('browser.php');
}

//Language
if (!$GLOBALS['sys_lang']) {
    $GLOBALS['sys_lang']="en_US";
}
$Language = new BaseLanguage($GLOBALS['sys_supported_languages'], $GLOBALS['sys_lang']);

//various html utilities
require_once('utils.php');

//database abstraction
require_once('database.php');
db_connect();

//security library
require_once('session.php');

//user functions like get_name, logged_in, etc
require_once('user.php');
$user_manager = UserManager::instance();
$current_user = $user_manager->getCurrentUser();

//Pass username in order to be written in Apache access_log
if(!IS_SCRIPT) {
    apache_note('username', $current_user->getUnixName());
}

//library to set up context help
require_once('help.php');

//exit_error library
require_once('exit.php');

//various html libs like button bar, themable
require_once('html.php');

//left-hand nav library, themable
require_once('menu.php');



//insert this page view into the database
if(!IS_SCRIPT) {
    require_once('logger.php');
}

/*

	Timezone must come after logger to prevent messups


*/
if ($current_user->isLoggedIn()) {
    date_default_timezone_set($current_user->getTimezone());

    if (! $cookie_manager->isCookie(CookieManager::USER_TOKEN) ) {
        $user_manager->setUserTokenCookie($current_user);
    }

    if (! $cookie_manager->isCookie(CookieManager::USER_ID) ) {
        $user_manager->setUserIdCookie($current_user);
    }
}

$theme_manager = new ThemeManager();
$HTML = $theme_manager->getTheme($current_user);

// If the Software license was declined by the site admin
// so stop all accesses to the site. Use exlicit path to avoid
// loading the license.php file in the register directory when
// invoking project/register.php
if(!IS_SCRIPT) {
require_once(dirname(__FILE__).'/license.php');
if (license_already_declined()) {
    exit_error($Language->getText('global','error'),$Language->getText('include_pre','site_admin_declines_license',$GLOBALS['sys_email_admin']));
}
}
// Check if anonymous user is allowed to browse the site
// Bypass the test for:
// a) all scripts where you are not logged in by definition
// b) if it is a local access from localhost

// Check URL for valid hostname and valid protocol

if (!IS_SCRIPT) {
    $urlVerifFactory = new URLVerificationFactory();
    $urlVerif = $urlVerifFactory->getURLVerification($_SERVER);
    $urlVerif->assertValidUrl($_SERVER);
}
$request = HTTPRequest::instance();
$request->setTrustedProxies(array_map('trim', explode(',', ForgeConfig::get('sys_trusted_proxies'))));

//Check post max size
if ($request->exist('postExpected') && !$request->exist('postReceived')) {
    $e = 'You tried to upload a file that is larger than the Codendi post_max_size setting.';
    exit_error('Error', $e);
}
if (ForgeConfig::get('DEBUG_MODE')) {
    $GLOBALS['DEBUG_TIME_IN_PRE'] = microtime(1) - $GLOBALS['debug_time_start'];
}
