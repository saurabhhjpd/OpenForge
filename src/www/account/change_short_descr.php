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

$em->processEvent('before_change_short_descr', array());

$csrf = new CSRFSynchronizerToken('/account/change_short_descr.php');

$user = $um->getCurrentUser();

if ($request->isPost() && $request->existAndNonEmpty('form_short_descr')) {
    $csrf->check();

    $user->setShortDescr($request->get('form_short_descr'));
    $um->updateDb($user);

    $GLOBALS['Response']->redirect("/account/");
    exit;
}

$HTML->header(array('title'=>"Change Description"));

$hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo "Change Description"; ?></h2>
<form action="change_short_descr.php" method="post">
<?php 
echo $csrf->fetchHTMLInput();
echo "New Description"; ?>:
<br><input type="text" name="form_short_descr" class="textfield_medium" value="<?php echo $hp->purify($user->getShortDescr(), CODENDI_PURIFIER_CONVERT_HTML) ?>" />
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
