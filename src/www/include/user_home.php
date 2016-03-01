<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
// Copyright (c) Enalean, 2015. All rights reserved
// 

/*
	Developer Info Page
	Written by dtype Oct 1999
*/


/*


	Assumes $res_user result handle is present


*/


$HTML->header(array('title'=>$Language->getText('include_user_home','devel_profile')));
$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tiny_mce/tiny_mce.js');

if (!$user) {
	exit_error($Language->getText('include_user_home','no_such_user'),$Language->getText('include_user_home','no_such_user'));
}

$hp = Codendi_HTMLPurifier::instance();

echo '
<H3>'.$hp->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML).'&#39;s Profile</H3><hr size="1" noshade="">
<P>
<TABLE width=100% cellpadding=2 cellspacing=2 border=0 class="personal_info_tbl"><TR valign=top>
<TD width=50% class="box_back">';

$HTML->box1_top($Language->getText('include_user_home','perso_info'));
echo '
&nbsp;
<BR>
<TABLE width=100% cellpadding=0 cellspacing=0 border=0>
<TR valign=top>
	<TD>Login: <span class="normal_font">'.$hp->purify($user->getUserName()).'</span></TD>
	<TD>Twitter: <span class="normal_font">'. $hp->purify($user->getTwitter(), CODENDI_PURIFIER_CONVERT_HTML) .'</span></TD>
</TR>
<TR valign=top>
	<TD>Name: <span class="normal_font">'. $hp->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML) .'</span></TD>
	<TD>Linkedin: <span class="normal_font">'. $hp->purify($user->getLinkedin(), CODENDI_PURIFIER_CONVERT_HTML) .'</span></TD>
</TR>
';
// addiing more fields  user_type, short_descr, twitter, linkedin, website, github, city, country,
echo '
<TR>
	<TD>Member since: <span class="normal_font">'.date("M d, Y",$user->getAddDate()).'</span></TD>
	<TD>Website: <span class="normal_font">'. $hp->purify($user->getWebsite(), CODENDI_PURIFIER_CONVERT_HTML) .'</span></TD>
</TR>

<TR>
	<TD>'.$Language->getText('include_user_home','user_status').': <span class="normal_font">';
        switch($user->getStatus()) {
        case 'A':
            echo $Language->getText('include_user_home','active');
            break;
        case 'R':
            echo $Language->getText('include_user_home','restricted');
            break;
        case 'P':
            echo $Language->getText('include_user_home','pending');
            break;
        case 'D':
            echo $Language->getText('include_user_home','deleted');
            break;
        case 'S':
            echo $Language->getText('include_user_home','suspended');
            break;
        default:
            echo $Language->getText('include_user_home','unkown');
        }

	
echo '</span></TD>	
	<TD>City: <span class="normal_font">'.$hp->purify($user->getCity(), CODENDI_PURIFIER_CONVERT_HTML) .'</span></TD>
</TR>
<TR>
	<TD>User Type: <span class="normal_font">'.$hp->purify($user->getUserType()).'</span></TD>
	<TD>Country: <span class="normal_font">'.$hp->purify($user->getCountry(), CODENDI_PURIFIER_CONVERT_HTML) .'</span></TD>
</TR>
<TR>
	<TD COLSPAN="2">About: <span class="normal_font">'.$hp->purify($user->getShortDescr(), CODENDI_PURIFIER_CONVERT_HTML) .'</span></TD>
</TR>';

$entry_label = array();
$entry_value = array();

$em =& EventManager::instance();
$eParams = array();
$eParams['user_id']     =  $user->getId();
$eParams['entry_label'] =& $entry_label;
$eParams['entry_value'] =& $entry_value;
$em->processEvent('user_home_pi_entry', $eParams);

foreach($entry_label as $key => $label) {
    $value = $entry_value[$key];
    print '
<TR valign=top>
	<TD>'.$label.'</TD>
	<TD><B>'.$value.'</B></TD>
</TR>
';
}

$hooks_output = "";

$em =& EventManager::instance();
$eParams = array();
$eParams['showdir']   =  isset($_REQUEST['showdir'])?$_REQUEST['showdir']:"";
$eParams['user_name'] =  $user->getUnixName();
$eParams['ouput']     =& $hooks_output;
$em->processEvent('user_home_pi_tail', $eParams);

echo $hooks_output;
?>

</TR>

</TABLE>
<?php $HTML->box1_bottom(); ?>

</TD>
<TD>&nbsp;</TD>
<TD width=50% class="box_back">
<?php $HTML->box1_top("Projects"); 
// now get listing of groups for that user
$res_cat = db_query("SELECT groups.group_name, "
	. "groups.unix_group_name, "
	. "groups.group_id, "
	. "user_group.admin_flags, "
	. "user_group.bug_flags FROM "
	. "groups,user_group WHERE user_group.user_id='".$user->getId()."' AND "
	. "groups.group_id=user_group.group_id AND groups.access != '".db_es(Project::ACCESS_PRIVATE)."' AND groups.status='A' AND groups.type='1'");

// see if there were any groups
if (db_numrows($res_cat) < 1) {
	echo '
	<p>'.$Language->getText('include_user_home','not_member');
} else { // endif no groups
	print '<p>'.$Language->getText('include_user_home','is_member').":<BR>&nbsp;";
	while ($row_cat = db_fetch_array($res_cat)) {
        print ('<BR><A href="/projects/'.urlencode($row_cat['unix_group_name']).'/">'.$hp->purify($row_cat['group_name'])."</A>\n");
    }
	print "</ul>";
} // end if groups

$HTML->box1_bottom(); ?>
</TD></TR>

<TR></TR>
<td></td>
<TR>

<TD class="box_back">

<?php 

if (user_isloggedin()) {
    $csrf_token = new CSRFSynchronizerToken('sendmessage.php');

    $HTML->box1_top($Language->getText('include_user_home','send_message_to').' '. $hp->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML));

    echo '
	<FORM ACTION="/sendmessage.php" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="touser" VALUE="'.$user->getId().'">';
    echo $csrf_token->fetchHTMLInput();

	$my_name = $hp->purify(user_getrealname(user_getid()));
    $cc      = (isset($_REQUEST['cc'])?$hp->purify(trim($_REQUEST['cc'])):"");
	echo  '
    
	<P>
	<B>'.$Language->getText('include_user_home','subject').':</B><BR>
	<INPUT TYPE="TEXT" NAME="subject" VALUE="" STYLE="width: 99%;">
    </P>

    <P>
	<B>'.$Language->getText('include_user_home','message').':</B><BR>
	
	<TEXTAREA ID="body" NAME="body" ROWS="15" WRAP="HARD" STYLE="width: 99%;"></TEXTAREA>
	</P>

	
	<INPUT TYPE="SUBMIT" NAME="send_mail" VALUE="'.$Language->getText('include_user_home','send_message').'">
	
	</FORM>';

    $HTML->box1_bottom();

} else {

	echo '<H3>'.$Language->getText('include_user_home','send_message_if_logged').'</H3>';

}

?>

</TD></TR>
</TABLE>

<?php
$js = "new UserAutoCompleter('cc','".util_get_dir_image_theme()."', true);";
$GLOBALS['Response']->includeFooterJavascriptSnippet($js);

$rte = "
var useLanguage = '". substr(UserManager::instance()->getCurrentUser()->getLocale(), 0, 2) ."';
document.observe('dom:loaded', function() {
            new Codendi_RTE_Send_HTML_MAIL('body');
        });";

$GLOBALS['HTML']->includeFooterJavascriptSnippet($rte);
$HTML->footer(array());

?>
