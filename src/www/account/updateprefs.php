<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('utils.php');
require_once('common/include/CookieManager.class.php');
require_once('common/include/CSRFSynchronizerToken.class.php');

$cookie_manager = new CookieManager();
$user = UserManager::instance()->getCurrentUser();

//
// Validate params
//

session_require(array('isloggedin'=>1));

$request = HTTPRequest::instance();

$csrf = new CSRFSynchronizerToken('/account/index.php');
$csrf->check();

$form_mail_site = 0;
if($request->existAndNonEmpty('form_mail_site')) {
    if($request->valid(new Valid_WhiteList('form_mail_site', array(0, 1)))) {
        $form_mail_site = (int) $request->get('form_mail_site');
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_form_mail_site'));
    }
}
$form_agree_site = 0;
if($request->existAndNonEmpty('form_agree_site')) {
    if($request->valid(new Valid_WhiteList('form_agree_site', array(0, 1)))) {
        $form_agree_site = (int) $request->get('form_agree_site');
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_form_agree_site'));
    }
}

$form_mail_va = 0;
if($request->existAndNonEmpty('form_mail_va')) {
    if($request->valid(new Valid_WhiteList('form_mail_va', array(0, 1)))) {
        $form_mail_va = (int) $request->get('form_mail_va');
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_form_mail_va'));
    }
}

// $theme_list is defined in /www/include/utils.php
$user_theme = $GLOBALS['sys_themedefault'];
$theme_list = util_get_theme_list();
if($request->existAndNonEmpty('user_theme')) {
    if($request->valid(new Valid_WhiteList('user_theme', $theme_list))) {
        $user_theme = $request->get('user_theme');
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_user_theme'));
    }
}

$user_theme_variant = null;
$requested_variant  = 'current_theme_variant';
$theme_variant      = new ThemeVariant();
if ($request->existAndNonEmpty($requested_variant)) {
    $validator = new Valid_WhiteList(
        'current_theme_variant',
        $theme_variant->getAllowedVariants()
    );
    if ($request->valid($validator)) {
        $user_theme_variant = $request->get($requested_variant);
    } else {
        $user_theme_variant = $theme_variant->getDefault();
    }
}

$form_sticky_login = 0;
if($request->existAndNonEmpty('form_sticky_login')) {
    if($request->valid(new Valid_WhiteList('form_sticky_login', array(0, 1)))) {
        $form_sticky_login = (int) $request->get('form_sticky_login');
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_form_sticky_login'));
    }
}

$language_id = $GLOBALS['sys_lang'];
if($request->existAndNonEmpty('language_id') && $GLOBALS['Language']->isLanguageSupported($request->get('language_id'))) {
    $language_id= $request->get('language_id');
} else {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_language_id'));
}

$user_csv_separator = PFUser::DEFAULT_CSV_SEPARATOR;
if($request->existAndNonEmpty('user_csv_separator')) {
   if($request->valid(new Valid_WhiteList('user_csv_separator', PFUser::$csv_separators))) {
        $user_csv_separator = $request->get('user_csv_separator');
   } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_user_csv_separator'));
    }
}

$user_csv_dateformat = PFUser::DEFAULT_CSV_DATEFORMAT;
if($request->existAndNonEmpty('user_csv_dateformat')) {
   if($request->valid(new Valid_WhiteList('user_csv_dateformat', PFUser::$csv_dateformats))) {
        $user_csv_dateformat = $request->get('user_csv_dateformat');
   } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_user_csv_dateformat'));
    }
}

$username_display = null;
if ($request->existAndNonEmpty('username_display')) {
    if($request->valid(new Valid_WhiteList('username_display', array(UserHelper::PREFERENCES_NAME_AND_LOGIN,
                                                                     UserHelper::PREFERENCES_LOGIN_AND_NAME,
                                                                     UserHelper::PREFERENCES_LOGIN,
                                                                     UserHelper::PREFERENCES_REAL_NAME)))) {
        $username_display = $request->get('username_display');
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_username_display'));
    }
}

$mailManager = new MailManager();
$user_tracker_mailformat = $mailManager->getMailPreferencesByUser($user);
if ($request->existAndNonEmpty(Codendi_Mail_Interface::PREF_FORMAT)) {
    if($request->valid(new Valid_WhiteList(Codendi_Mail_Interface::PREF_FORMAT, $mailManager->getAllMailFormats()))) {
        $user_tracker_mailformat = $request->get(Codendi_Mail_Interface::PREF_FORMAT);
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_user_tracker_mailformat'));
    }
}
//
// Perform the update
//

// User
db_query("UPDATE user SET "
         . "mail_siteupdates=" . $form_mail_site . ","
         . "agree_siteupdates=" . $form_agree_site . ","
         . "mail_va=" . $form_mail_va . ","
         . "theme='" . db_es($user_theme) . "',"
         . "sticky_login=" . $form_sticky_login . ","
         . "language_id='" . db_es($language_id) . "' WHERE "
         . "user_id=" . user_getid());

// Preferences
user_set_preference("user_csv_separator", $user_csv_separator);
user_set_preference("user_csv_dateformat", $user_csv_dateformat);
user_set_preference(Codendi_Mail_Interface::PREF_FORMAT, $user_tracker_mailformat);

if($username_display !== null) {
    user_set_preference("username_display", $username_display);
}
$user = UserManager::instance()->getCurrentUser();
$user->setLabFeatures($request->existAndNonEmpty('form_lab_features'));

if ($user_theme_variant) {
    $user->setPreference('theme_variant', $user_theme_variant);
}

//plugins specific preferences
$em = EventManager::instance();
$em->processEvent('update_user_preferences_appearance', array('request' => $request));

//
// Output
//

session_redirect("/account/index.php");

?>