<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    

$em      = EventManager::instance();
$um      = UserManager::instance();
$request = HTTPRequest::instance();

$em->processEvent('before_change_user_type', array());

$csrf = new CSRFSynchronizerToken('/account/change_user_type.php');

$user = $um->getCurrentUser();

if ($request->isPost() && $request->existAndNonEmpty('form_user_type')) {
    $csrf->check();

    $user->setUserType($request->get('form_user_type'));
    $um->updateDb($user);

    $GLOBALS['Response']->redirect("/account/");
    exit;
}

$HTML->header(array('title'=>"Change User Type"));

$hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo "Change UserType"; ?></h2>
<form action="change_user_type.php" method="post">
<?php 
echo $csrf->fetchHTMLInput();
echo "New User Type"; ?>:
<br><input type="text" name="form_user_type" class="textfield_medium" value="<?php echo $hp->purify($user->getUserType(), CODENDI_PURIFIER_CONVERT_HTML) ?>" />
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
