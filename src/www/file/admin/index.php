<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 


require_once('pre.php');
require_once('www/file/file_utils.php');

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_error($Language->getText('file_file_utils', 'g_id_err'),$Language->getText('file_file_utils', 'g_id_err'));
}
if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}

file_utils_admin_header(array('title'=>$Language->getText('file_admin_editpackages','release_edit_f_rel'), 'help' => 'frs.html#delivery-manager-administration'));

?><h2>Files Administration</h2>
<h3><a href="manageprocessors.php?group_id=<?=$group_id?>"><?=$GLOBALS['Language']->getText('file_admin_manageprocessors', 'manage_proclist')?></a></h3>
<?php

file_utils_footer(array());
?>
