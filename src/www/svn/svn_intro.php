<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Originally written by Laurent Julliard 2001- 2003 Codendi Team, Xerox
 *
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/include/URL.class.php');
require_once('common/event/EventManager.class.php');

$vGroupId = new Valid_UInt('group_id');
$vGroupId->required();

if (!$request->valid($vGroupId)) {
    exit_no_group(); // need a group_id !!!
} else {
    $group_id = $request->get('group_id');
}

$hp =& Codendi_HTMLPurifier::instance();

svn_header(array ('title'=>$Language->getText('svn_intro','info')));

// Table for summary info
print '<TABLE width="100%"><TR valign="top"><TD width="65%">'."\n";

$project      = $request->getProject();
$svn_preamble = $project->getSVNpreamble();

// Show CVS access information
if ($svn_preamble != '') {
    echo $hp->purify(util_unconvert_htmlspecialchars($svn_preamble), CODENDI_PURIFIER_FULL);
} else {
    $host = $GLOBALS['sys_default_domain'];
    if ($GLOBALS['sys_force_ssl']) {
       $svn_url = 'https://'. $host;
    } else if (isset($GLOBALS['sys_disable_subdomains']) && $GLOBALS['sys_disable_subdomains']) {
      $svn_url = 'http://'.$host;
    } else {
       $svn_url = 'http://svn.'. $project->getUnixNameMixedCase() .'.'. $host;
    }
    // Domain name must be lowercase (issue with some SVN clients)
    $svn_url = strtolower($svn_url);
    $svn_url .= '/svnroot/'. $project->getUnixNameMixedCase();

    $event_manager       = EventManager::instance();
    $svn_intro_in_plugin = false;
    $svn_intro_info      = null;
    $user                = $request->getCurrentUser();

    $svn_params = array(
        'svn_intro_in_plugin' => &$svn_intro_in_plugin,
        'svn_intro_info'      => &$svn_intro_info,
        'group_id'            => $group_id,
        'user_id'             => $user->getId()
    );

    $event_manager->processEvent(Event::SVN_INTRO, $svn_params);

    $template_dir = ForgeConfig::get('codendi_dir') .'/src/templates/svn/';
    $renderer     = TemplateRendererFactory::build()->getRenderer($template_dir);

    $project_manager        = ProjectManager::instance();
    $token_manager          = new SVN_TokenUsageManager(new SVN_TokenDao(), $project_manager);
    $project_can_use_tokens = $token_manager->isProjectAuthorizingTokens($project);

    $presenter = new SVN_IntroPresenter(
        $user,
        $svn_intro_in_plugin,
        $svn_intro_info,
        $svn_url,
        $project_can_use_tokens
    );

    $renderer->renderToPage('intro', $presenter);
}

// Summary info
print '</TD><TD width="25%">';
print $HTML->box1_top($Language->getText('svn_intro','history'));

echo svn_utils_format_svn_history($group_id);

// SVN Browsing Box
print '<HR><B>'.$Language->getText('svn_intro','browse_tree').'</B>
<P>'.$Language->getText('svn_intro','browse_comment').'
<UL>
<LI><A HREF="/svn/viewvc.php/?roottype=svn&root='.$project->getUnixNameMixedCase().'"><B>'.$Language->getText('svn_intro','browse_tree').'</B></A></LI>';

print $HTML->box1_bottom();

print '</TD></TR></TABLE>';

svn_footer(array());