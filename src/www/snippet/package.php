<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// Copyright (c) Enalean, 2015. All Rights Reserved.
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../snippet/snippet_utils.php');


if (user_isloggedin()) {

    if ($post_changes) {
        $csrf->check();
        /*
			Create a new snippet entry, then create a new snippet version entry
        */
        if ($name && $description && $language != 0 && $category != 0 && $version) {
            if ($category==100) {
                $feedback .= ' '.$Language->getText('snippet_details','select_category').' ';
            } else if ($language==100) {
                $feedback .= ' '.$Language->getText('snippet_details','select_lang').' ';
            } else {
                $category = (int)$category;
                $language = (int)$language;
                /*
				Create the new package
                */
                $sql="INSERT INTO snippet_package (category,created_by,name,description,language) ".
                    "VALUES ('". db_ei($category) ."','". db_ei(user_getid()) ."','". db_es(htmlspecialchars($name)) ."','". db_es(htmlspecialchars($description)) ."','" . db_ei($language) ."')";
                $result=db_query($sql);
                if (!$result) {
                    //error in database
                    $feedback .= ' '.$Language->getText('snippet_package','error_p_insert').' ';
                    snippet_header(array('title'=>$Language->getText('snippet_addversion','submit_p')));
                    echo db_error();
                    snippet_footer(array());
                    exit;
                } else {
                    $feedback .= ' '.$Language->getText('snippet_package','p_add_success').' ';
                    $snippet_package_id=db_insertid($result);
                    /*
					create the snippet package version
                    */
                    $sql="INSERT INTO snippet_package_version ".
                        "(snippet_package_id,changes,version,submitted_by,date) ".
                        "VALUES ('". db_ei($snippet_package_id) ."','". db_es(htmlspecialchars($changes)) ."','".
                        db_es(htmlspecialchars($version)) ."','". db_ei(user_getid()) ."','".time()."')";
                    $result=db_query($sql);
                    if (!$result) {
                        //error in database
                        $feedback .= ' '.$Language->getText('snippet_addversion','error_insert').' ';
                        snippet_header(array('title'=>$Language->getText('snippet_addversion','submit_p')));
                        echo db_error();
                        snippet_footer(array());
                        exit;
                    } else {
                        //so far so good - now add snippets to the package
                        $feedback .= ' '.$Language->getText('snippet_addversion','p_add_success').' ';

                        //id for this snippet_package_version
                        $snippet_package_version_id=db_insertid($result);
                        snippet_header(array('title'=>$Language->getText('snippet_addversion','add')));

                        /*
                        This raw HTML allows the user to add snippets to the package
                        */

echo '
<SCRIPT LANGUAGE="JavaScript">
<!--
function show_add_snippet_box() {
	newWindow = open("","occursDialog","height=500,width=300,scrollbars=yes,resizable=yes");
	newWindow.location=(\'/snippet/add_snippet_to_package.php?suppress_nav=1&snippet_package_version_id='.$snippet_package_version_id.'\');
}
// -->
</script>
<BODY onLoad="show_add_snippet_box()">

<H2>'.$Language->getText('snippet_addversion','now_add').'</H2>
<hr size="1" noshade="">
<P>
<span class="highlight"><B>'.$Language->getText('snippet_addversion','important').'</B></span>
<P>
'.$Language->getText('snippet_addversion','important_comm').'
<P>
<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id='.$snippet_package_version_id.'" TARGET="_blank">'.$Language->getText('snippet_addversion','add').'</A>
<P>
'.$Language->getText('snippet_addversion','browse_lib').'
<P>';

                        snippet_footer(array());
                        exit;
                    }
                }
            }
        } else {
            exit_error($Language->getText('global','error'),$Language->getText('snippet_add_snippet_to_package','error_fill_all_info'));
        }

    }
    snippet_header(array('title'=>$Language->getText('snippet_addversion','submit_p'),
			     'header'=>$Language->getText('snippet_package','create_p'),
			     'help' => 'overview.html#grouping-code-snippets'));


    echo '
	<P>
	'.$Language->getText('snippet_package','group_s_into_p').'
	<P>
	<FORM ACTION="?" METHOD="POST">'.
        $csrf->fetchHTMLInput() .'
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<INPUT TYPE="HIDDEN" NAME="changes" VALUE="'.$Language->getText('snippet_package','first_posted_v').'">

	<TABLE>

	<TR><TD COLSPAN="2"><B>'.$Language->getText('snippet_browse','title').':</B><BR>
		<INPUT TYPE="TEXT" NAME="name" SIZE="45" MAXLENGTH="60">
	</TD></TR>

	<TR><TD COLSPAN="2"><B>'.$Language->getText('snippet_package','description').'</B><BR>
		<TEXTAREA NAME="description" ROWS="5" COLS="45" WRAP="SOFT"></TEXTAREA>
	</TD></TR>

	<TR>
	<TD><B>'.$Language->getText('snippet_package','language').'</B><BR>
		'.html_build_select_box (snippet_data_get_all_languages(),'language').'
	</TD>

	<TD><B>'.$Language->getText('snippet_package','category').'</B><BR>
		'.html_build_select_box (snippet_data_get_all_categories(),'category').'
	</TD>
	</TR>
 
	<TR><TD COLSPAN="2"><B>'.$Language->getText('snippet_addversion','version').'</B><BR>
		<INPUT TYPE="TEXT" NAME="version" SIZE="10" MAXLENGTH="15">
	</TD></TR>
  
	<TR><TD COLSPAN="2">
		<B>'.$Language->getText('snippet_add_snippet_to_package','all_info_complete').'</B>
		<BR><BR>
		<INPUT CLASS="btn btn-primary" TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
	</TD></TR>

	</TABLE>';

    snippet_footer(array());

} else {

	exit_not_logged_in();

}
