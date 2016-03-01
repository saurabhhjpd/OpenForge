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

$em->processEvent('before_change_website', array());

$csrf = new CSRFSynchronizerToken('/account/change_website.php');

$user = $um->getCurrentUser();

if ($request->isPost() && $request->existAndNonEmpty('form_website')) {
    $csrf->check();

    $user->setWebsite($request->get('form_website'));
    $um->updateDb($user);

    $GLOBALS['Response']->redirect("/account/");
    exit;
}

$HTML->header(array('title'=>"Change Website"));

$hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo "Change Website"; ?></h2>
<form action="change_website.php" method="post">
<?php 
echo $csrf->fetchHTMLInput();
echo "New Website"; ?>:
<br><input type="text" name="form_website" class="textfield_medium" value="<?php echo $hp->purify($user->getWebsite(), CODENDI_PURIFIER_CONVERT_HTML) ?>" />
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
