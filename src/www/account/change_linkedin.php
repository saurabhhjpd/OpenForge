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

$em->processEvent('before_change_linkedin', array());

$csrf = new CSRFSynchronizerToken('/account/change_linkedin.php');

$user = $um->getCurrentUser();

if ($request->isPost() && $request->existAndNonEmpty('form_linkedin')) {
    $csrf->check();

    $user->setLinkedin($request->get('form_linkedin'));
    $um->updateDb($user);

    $GLOBALS['Response']->redirect("/account/");
    exit;
}

$HTML->header(array('title'=>"Change Linkedin Account"));

$hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo "Change Linkedin Account"; ?></h2>
<form action="change_linkedin.php" method="post">
<?php 
echo $csrf->fetchHTMLInput();
echo "New Linkedin Account"; ?>:
<br><input type="text" name="form_linkedin" class="textfield_medium" value="<?php echo $hp->purify($user->getLinkedin(), CODENDI_PURIFIER_CONVERT_HTML) ?>" />
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
