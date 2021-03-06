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

$em->processEvent('before_change_github', array());

$csrf = new CSRFSynchronizerToken('/account/change_github.php');

$user = $um->getCurrentUser();

if ($request->isPost() && $request->existAndNonEmpty('form_github')) {
    $csrf->check();

    $user->setGithub($request->get('form_github'));
    $um->updateDb($user);

    $GLOBALS['Response']->redirect("/account/");
    exit;
}

$HTML->header(array('title'=>"Change Github Account"));

$hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo "Change Github Account"; ?></h2>
<form action="change_github.php" method="post">
<?php 
echo $csrf->fetchHTMLInput();
echo "New Github Account"; ?>:
<br><input type="text" name="form_github" class="textfield_medium" value="<?php echo $hp->purify($user->getGithub(), CODENDI_PURIFIER_CONVERT_HTML) ?>" />
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
