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

$em->processEvent('before_change_country', array());

$csrf = new CSRFSynchronizerToken('/account/change_country.php');

$user = $um->getCurrentUser();

if ($request->isPost() && $request->existAndNonEmpty('form_country')) {
    $csrf->check();

    $user->setCountry($request->get('form_country'));
    $um->updateDb($user);

    $GLOBALS['Response']->redirect("/account/");
    exit;
}

$HTML->header(array('title'=>"Change Country"));

$hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo "Change Country"; ?></h2>
<form action="change_country.php" method="post">
<?php 
echo $csrf->fetchHTMLInput();
echo "New Country"; ?>:
<br><input type="text" name="form_country" class="textfield_medium" value="<?php echo $hp->purify($user->getCountry(), CODENDI_PURIFIER_CONVERT_HTML) ?>" />
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
