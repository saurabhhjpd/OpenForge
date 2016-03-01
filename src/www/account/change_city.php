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

$em->processEvent('before_change_city', array());

$csrf = new CSRFSynchronizerToken('/account/change_city.php');

$user = $um->getCurrentUser();

if ($request->isPost() && $request->existAndNonEmpty('form_city')) {
    $csrf->check();

    $user->setCity($request->get('form_city'));
    $um->updateDb($user);

    $GLOBALS['Response']->redirect("/account/");
    exit;
}

$HTML->header(array('title'=>"Change City"));

$hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo "Change City"; ?></h2>
<form action="change_city.php" method="post">
<?php 
echo $csrf->fetchHTMLInput();
echo "New City"; ?>:
<br><input type="text" name="form_city" class="textfield_medium" value="<?php echo $hp->purify($user->getCity(), CODENDI_PURIFIER_CONVERT_HTML) ?>" />
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
