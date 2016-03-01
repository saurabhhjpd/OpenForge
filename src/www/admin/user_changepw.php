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


session_require(array('group'=>'1','admin_flags'=>'A'));

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid(Codendi_Request $request) {
    global $Language;

    if (! $request->existAndNonEmpty('Update')) {
        return false;
    }
    if (! $request->existAndNonEmpty('user_id')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_user_changepw','error_userid'));
        return false;
    }
    if (! $request->existAndNonEmpty('form_pw')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_user_changepw','error_nopasswd'));
        return false;
    }
    if ($request->get('form_pw') != $request->get('form_pw2')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_user_changepw','error_passwd'));
        return false;
    }
    $errors = array();
    if (! account_pwvalid($request->get('form_pw'), $errors)) {
        foreach($errors as $e) {
            $GLOBALS['Response']->addFeedback('error', $e);
        }
        return false;
    }
	
    // if we got this far, it must be good
    $user_manager = UserManager::instance();
    $user         = $user_manager->getUserById($request->get('user_id'));
    $user->setPassword($request->get('form_pw'));
    if (!$user_manager->updateDb($user)) {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_user_changepw','error_update'));
        return false;
    }
    return true;
}

// ###### first check for valid login, if so, congratulate
$HTML->includeJavascriptFile('/scripts/check_pw.js');
if (register_valid($request)) {
    $HTML->header(array('title'=>$Language->getText('admin_user_changepw','title_changed')));
?>
<h3><?php echo $Language->getText('admin_user_changepw','header_changed'); ?></h3>
<p><?php echo $Language->getText('admin_user_changepw','msg_changed'); ?></h3>

<p><a href="/admin"><?php echo $Language->getText('global','back'); ?></a>.
<?php
} else { // not valid registration, or first time to page
    $HTML->header(array('title'=>$Language->getText('admin_user_changepw','title')));

    require_once('common/event/EventManager.class.php');
    $em =& EventManager::instance();
    $em->processEvent('before_admin_change_pw', array());

?>
<h3><?php echo $Language->getText('admin_user_changepw','header'); ?></h3>
<form action="user_changepw.php" method="post">
<?php user_display_choose_password('',$user_id); ?>
<p><input type="submit" class="btn btn-primary" name="Update" value="<?php echo $Language->getText('global','btn_update'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
