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

$em->processEvent('before_change_twitter', array());

$csrf = new CSRFSynchronizerToken('/account/change_twitter.php');

$user = $um->getCurrentUser();

if ($request->isPost() && $request->existAndNonEmpty('form_twitter')) {
    $csrf->check();

    $user->setTwitter($request->get('form_twitter'));
    $um->updateDb($user);

    $GLOBALS['Response']->redirect("/account/");
    exit;
}

$HTML->header(array('title'=>"Change Twitter Account"));

$hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo "Change Twitter Account"; ?></h2>
<form action="change_twitter.php" method="post">
<?php 
echo $csrf->fetchHTMLInput();
echo "New Twitter Account"; ?>:
<br><input type="text" name="form_twitter" class="textfield_medium" value="<?php echo $hp->purify($user->getTwitter(), CODENDI_PURIFIER_CONVERT_HTML) ?>" />
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
