<?php
//
// Copyright (c) Enalean, 2015. All Rights Reserved.
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('account.php');
require_once('common/include/CSRFSynchronizerToken.class.php');
$request = HTTPRequest::instance();
$csrf    = new CSRFSynchronizerToken('/account/change_pw.php');

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid($user_id, CSRFSynchronizerToken $csrf, EventManager $event_manager)	{
    $request = HTTPRequest::instance();

    if (!$request->isPost() || !$request->exist('Update')) {
		return 0;
	}
    $csrf->check();

	// check against old pw
    $user_manager = UserManager::instance();
    $user         = $user_manager->getUserById($user_id);
	if ($user === null) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'user_not_found'));
        return 0;
	}

    $password_expiration_checker = new User_PasswordExpirationChecker();
    $password_handler            = PasswordHandlerFactory::getPasswordHandler();
    $login_manager               = new User_LoginManager(
        $event_manager,
        $user_manager,
        $password_expiration_checker,
        $password_handler
    );
    if (!$login_manager->verifyPassword($user, $request->get('form_oldpw'))) {
		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'incorrect_old_password'));
		return 0;
	}

    try {
        $status_manager = new User_UserStatusManager();
        $status_manager->checkStatus($user);
    } catch (User_StatusInvalidException $exception) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'account_inactive'));
        return 0;
    }

	if (!$request->exist('form_pw')) {
		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'password_needed'));
		return 0;
	}
	if ($request->get('form_pw') != $request->get('form_pw2')) {
		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'password_not_match'));
		return 0;
	}
    if ($request->get('form_pw') === $request->get('form_oldpw')) {
        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('account_change_pw', 'identical_password'));
        return 0;
    }
	if (!account_pwvalid($request->get('form_pw'), $errors)) {
        foreach($errors as $e) {
            $GLOBALS['Response']->addFeedback('error', $e);
        }
		return 0;
	}
	
	// if we got this far, it must be good
    $user->setPassword($request->get('form_pw'));
    if (!$user_manager->updateDb($user)) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'internal_error_update'));
        return 0;
	}

	return 1;
}

require_once('common/event/EventManager.class.php');
$em = EventManager::instance();
$em->processEvent('before_change_pw', array());

// ###### first check for valid login, if so, congratulate
$user_id = is_numeric($request->get('user_id')) ? (int)$request->get('user_id') : user_getid();
if (register_valid($user_id, $csrf, $em)) {
    $HTML->header(array('title'=>$Language->getText('account_change_pw', 'title_success')));
?>
<p><b><? echo $Language->getText('account_change_pw', 'title_success'); ?></b>
<p><? echo $Language->getText('account_change_pw', 'message', array($GLOBALS['sys_name'])); ?>

<p><a href="/">[ <? echo $Language->getText('global', 'back_home');?> ]</a>
<?php
} else { // not valid registration, or first time to page
	$HTML->includeJavascriptFile('/scripts/check_pw.js');
	$HTML->header(array('title'=>$Language->getText('account_options', 'change_password')));

?>
<h2><? echo $Language->getText('account_change_pw', 'title'); ?></h2>
<form action="change_pw.php" method="post" autocomplete="off" >
<p><?
echo $csrf->fetchHTMLInput();
echo $Language->getText('account_change_pw', 'old_password'); ?>:
<br><input type="password" value="" name="form_oldpw">
<?php user_display_choose_password('',is_numeric($request->get('user_id')) ? $request->get('user_id') : 0); ?>
<p><input type="submit" class="btn btn-primary" name="Update" value="<? echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
