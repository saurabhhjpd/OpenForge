<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('../news/news_utils.php');

$request =& HTTPRequest::instance();

$validGroupId = new Valid_GroupId();
$validGroupId->required();
if($request->valid($validGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

if (user_isloggedin()) {

    if (user_ismember($group_id,'A') || user_ismember($group_id,'N1') || user_ismember($group_id,'N2')) {
        
        if ($request->get('post_changes')) {
            
            $validSummary = new Valid_String('summary');
            $validSummary->setErrorMessage('Summary is required');
            $validSummary->required();
            
            $validDetails = new Valid_Text('details');
            
            $validPrivateNews = new Valid_WhiteList('private_news', array('0', '1'));
            $validSummary->required();
            
            $validPromoteNews = new Valid_WhiteList('promote_news', array('0', '3'));
            $validSummary->required();
            
            if ($request->valid($validSummary)
                && $request->valid($validDetails)
                && $request->valid($validPrivateNews)
                && $request->valid($validPromoteNews)
                ) 
            {
                
                /*
                 Insert the row into the db if it's a generic message
                 OR this person is an admin for the group involved
                */
                /*
                 create a new discussion forum without a default msg
                 if one isn't already there
                */
                
                //if news is declared as private, force the $promote_news to '0' value (not to be promoted)
                $promote_news = $request->get('promote_news');
                if ($promote_news == '3' && $request->get('private_news')) {
                    $promote_news = "0";
                }
                
                news_submit($group_id, $request->get('summary'), $request->get('details'), $request->get('private_news'), $request->get('send_news_to'), $promote_news);
            }
        }
        
        /*
             Show the submit form
        */
        news_header(array('title'=>$Language->getText('news_index','news'),
              'help'=>'communication.html#news-service'));

        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        /*
         create a new discussion forum without a default msg
         if one isn't already there
        */
        echo '
        <H3>'.$Language->getText('news_submit','submit_news_for',$project->getPublicName()).'</H3>
        <P>
        '.$Language->getText('news_submit','post_explain',$GLOBALS['sys_name']).'
        <P>
        <FORM ACTION="" METHOD="POST">
        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
        <B>'.$Language->getText('news_submit','for_project',$project->getPublicName()) .'</B>
        <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="1">
        <br><br>
        <div class="control-group">
            <label for="summary">'.$Language->getText('news_admin_index','subject').':</label>
            <div class="controls">
                <INPUT TYPE="TEXT" NAME="summary" id="summary" VALUE="" CLASS="textfield_medium" required>
            </div>
        </div>

        <div class="control-group">
            <label for="details">'.$Language->getText('news_admin_index','details').':</label>
            <div class="controls">
                <TEXTAREA NAME="details" id="details" ROWS="8" COLS="50" WRAP="SOFT"></TEXTAREA>
            </div>
        </div>

        '.$Language->getText('news_submit','news_privacy').':
        <label class="radio">
            <INPUT TYPE="RADIO" NAME="private_news" VALUE="0" CHECKED>
            '.$Language->getText('news_submit','public_news').'
        </label>
        <label class="radio">
            <INPUT TYPE="RADIO" NAME="private_news" VALUE="1">
            '.$Language->getText('news_submit','private_news').'
        </label>

        <br>
        '.$Language->getText('news_submit','news_promote',$GLOBALS['sys_name']).'
        <label class="radio">
            <INPUT TYPE="RADIO" NAME="promote_news" VALUE="3">
            '.$Language->getText('global','yes').'
        </label>
        <label class="radio">
            <INPUT TYPE="RADIO" NAME="promote_news" VALUE="0" CHECKED>
            '.$Language->getText('global','no').'
        </label>

        '.$Language->getText('news_submit','promote_warn',$GLOBALS['sys_name']);

        if ($project->getId() != Project::SITE_NEWS_PROJECT_ID) {
            echo '<br><br>'.$Language->getText('news_submit','send_news_by_email',$GLOBALS['sys_name']).':<br>';
            echo news_fetch_ugroups($project);
        }

        echo '<br><br><INPUT CLASS="btn btn-primary" TYPE="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
        </FORM>';

        news_footer(array());

    } else {
        exit_error($Language->getText('news_admin_index','permission_denied'),$Language->getText('news_submit','only_writer_submits'));
    }
} else {
    exit_not_logged_in();
}
?>
