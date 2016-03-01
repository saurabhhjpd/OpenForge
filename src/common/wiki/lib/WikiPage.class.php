<?php
/* 
 * Copyright 2005, STMicroelectronics
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('www/project/admin/permissions.php');

/**
 * Tuleap manipulation of WikiPages
 *
 * This class is Tuleap representation of wiki_page table in database.
 *
 * @package WikiService
 * @copyright STMicroelectronics, 2005
 * @author Manuel Vacelet <manuel.vacelet-abecedaire@st.com>
 * @license http://opensource.org/licenses/gpl-license.php GPL
 */
class WikiPage {
 /* private int */   var $id;       /* wiki_page.id */
 /* private string*/ var $pagename; /* wiki_page.pagename */
 /* private int */   var $gid;      /* wiki_page.group_id */
 /* private bool */  var $empty;    /* */

    /** @var WikiPageWrapper */
    private $wrapper;

    /** @var bool */
    private $referenced;

    /** @var WikiDao */
    private $wiki_dao;

    /*
     * Constructor
     */
    function WikiPage($id=0, $pagename='') {
        $this->empty = null;

        if($id != 0) {
          if(empty($pagename)) {
            //Given number is the WikiPage id from wiki_page table
            $this->id = (int) $id;
            $this->initFromDb();
          }
          else {
            //Given number is group_id from wiki_page table
            $this->gid      = (int) $id;
            $this->pagename = $pagename;
            $this->findPageId();
            $this->wrapper  = new WikiPageWrapper($this->gid);
          }
        }
        else {
          $this->id       = 0;
          $this->pagename = '';
          $this->gid      = 0;
          $this->wrapper  = null;
        }

        $this->referenced = $this->isWikiPageReferenced();

        $this->wiki_dao = new WikiDao();
    }

    private function setGid($project_id) {
        $this->gid     = $project_id;
        $this->wrapper = new WikiPageWrapper($project_id);
    }

    public function isReferenced() {
        return $this->referenced;
    }

    public function getLastVersionId() {
        $res = db_query(
            'SELECT version
            FROM wiki_version
            WHERE id='. db_ei($this->id) .' ORDER BY version DESC LIMIT 1'
        );

        if (db_numrows($res) !== 1) {
            return '';
        }

        $results = db_fetch_array($res);

        return $results['version'];
    }

    public function getMetadata() {
        if ($this->isEmpty()) {
            return array('mtime' => time());
        }

        $current_revision_metadata = $this->wrapper->getRequest()->getPage($this->pagename)
            ->getCurrentRevision()->getMetaData();

        $this->convertAuthorIdToUserId($current_revision_metadata);

        $content = array(
            'content' => $this->getLastVersionContent()
        );
        $summary = array(
            'summary' => $this->getSummaryForCurrentRevision()
        );

        return $current_revision_metadata + $content + $summary;
    }

    private function convertAuthorIdToUserId(array &$current_revision_metadata) {
        $last_author_id = null;

        if (isset($current_revision_metadata['author'])) {
            $user_manager = UserManager::instance();
            $author = $user_manager->getUserByUserName($current_revision_metadata['author']);

            if ($author) {
                $last_author_id = (int)$author->getId();
            }
        }

        $current_revision_metadata['author_id'] = $last_author_id;
    }

    public function getContent() {
        $metadata = $this->getMetadata();

        if (isset($metadata['content'])) {
            return $metadata['content'];
        }

        return "";
    }

    /**
     *
     * @return string
     */
    private function getSummaryForCurrentRevision() {
        $summary_content = $this->wrapper->getRequest()->getPage($this->pagename)
            ->getCurrentRevision()->get('summary');

        if ($summary_content) {
            return $summary_content;
        }

        return '';
    }

    /**
     * @return int
     */
    public function getCurrentVersion() {
        return $this->wrapper->getRequest()->getPage($this->pagename)->getCurrentRevision()->getVersion();
    }

    private function getLastVersionContent() {
        $res = db_query(
            'SELECT content
            FROM wiki_version
            WHERE id='. db_ei($this->id) .' ORDER BY version DESC LIMIT 1'
        );

        if (db_numrows($res) !== 1) {
            return '';
        }

        $results = db_fetch_array($res);

        return $results['content'];
    }

    private function findPageId() {
        $res = db_query(' SELECT id FROM wiki_page'.
                        ' WHERE group_id="'.db_ei($this->gid).'"'.
                        ' AND pagename="'.db_es($this->pagename).'"');
        if(db_numrows($res) > 1) {
            exit_error($GLOBALS['Language']->getText('global','error'),
                       $GLOBALS['Language']->getText('wiki_lib_wikipage',
                                                     'notunique_err'));
        }
        $row = db_fetch_array($res);
        $this->id =  $row['id'];
    }


    private function initFromDb() {
        $res = db_query(' SELECT id, pagename, group_id FROM wiki_page'.
                        ' WHERE id="'.db_ei($this->id).'"');
        if(db_numrows($res) > 1) {
            exit_error($GLOBALS['Language']->getText('global','error'),
                       $GLOBALS['Language']->getText('wiki_lib_wikipage',
                                                     'notunique_err'));
        }
        $row = db_fetch_array($res);

        $this->gid =  $row['group_id'];
        $this->pagename =  $row['pagename'];
    }


    /**
     * @todo transfer to Wrapper
     */
    function isEmpty() {
        // If this value is already computed, return now !
        if($this->empty != null) {
          return $this->empty;
        }

        // Else compute
        $this->empty=true;
        if($this->exist()) {
          $res = db_query(' SELECT wiki_page.id'
                          .' FROM wiki_page, wiki_nonempty'
                          .' WHERE wiki_page.group_id="'.db_ei($this->gid).'"'
                          .' AND wiki_page.id="'.db_ei($this->id).'"'
                          .' AND wiki_nonempty.id=wiki_page.id');
          if(db_numrows($res) == 1) {
            $this->empty = false;
          }
        }

        return $this->empty;
    }

    public function permissionExist() {
      if (permission_exist(Wiki_PermissionsManager::WIKI_PERMISSION_READ, $this->id))
        return true;
      else
        return false;
    }

    private function isWikiPageReferenced() {
        $referenced = false;

        //Check for Docman Perms
        $eM =& EventManager::instance();
        $eM->processEvent(
            'isWikiPageReferenced', array(
                'referenced' => &$referenced,
                'wiki_page'  => $this->pagename,
                'group_id'   => $this->gid
            )
        );

        return $referenced;
    }

    public function isAutorized($uid) {
        if($this->referenced == true) {
            $userCanAccess = false;
            $eM =& EventManager::instance();
            $eM->processEvent('userCanAccessWikiDocument', array(
                            'canAccess' => &$userCanAccess,
                            'wiki_page'  => $this->pagename,
                            'group_id' => $this->gid
                            ));
            if(!$userCanAccess) {
                return false;
            }
        } else {
            // Check if user is authorized.
            if($this->permissionExist()) {
                if (!permission_is_authorized(Wiki_PermissionsManager::WIKI_PERMISSION_READ, $this->id, $uid, $this->gid)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function setPermissions($groups) {
        global $feedback;

        list ($ret, $feedback) = permission_process_selection_form(
            $this->gid,
            Wiki_PermissionsManager::WIKI_PERMISSION_READ,
            $this->id,
            $groups
        );

        return $ret;
    }

    public function resetPermissions() {
        return permission_clear_all(
            $this->gid,
            Wiki_PermissionsManager::WIKI_PERMISSION_READ,
            $this->id
        );
    }

    /**
     * @todo transfer to Wrapper
     */
    function exist() {
      return($this->id != 0);
    }

    public function log($user_id) {
      $sql = "INSERT INTO wiki_log(user_id,group_id,pagename,time) "
            ."VALUES ('".db_ei($user_id)."','".db_ei($this->gid)."','".db_es($this->pagename)."','".db_ei(time())."')";
      db_query($sql);
    }

    public function render($lite=false, $full_screen=false) {
      $wpw = new WikiPageWrapper($this->gid);
      $wpw->render($lite, $full_screen);
    }

    /**
     * @return int Page identifier
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string Page name
     */
    public function getPagename() {
        return $this->pagename;
    }

    /**
     * @return int Group Identifier
     */
    public function getGid() {
        return $this->gid;
    }

    /**
     * @return bool
     */
    public function delete() {
        if ($this->exist()) {
            if($this->wiki_dao->deleteWikiPage($this->id)
                && $this->wiki_dao->deleteWikiPageVersion($this->id)
                && $this->wiki_dao->deleteLinksFromToWikiPage($this->id)
                && $this->wiki_dao->deleteWikiPageFromNonEmptyList($this->id)
                && $this->wiki_dao->deleteWikiPageRecentInfos($this->id)) {
                $this->id = 0;
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @return string[] List of pagename
     */
    public function &getAllAdminPages() {
        $WikiPageAdminPages = WikiPage::getAdminPages();

        $allPages = array();

        $res = db_query(' SELECT pagename'
                        .' FROM wiki_page, wiki_nonempty'
                        .' WHERE wiki_page.group_id="'.db_ei($this->gid).'"'
                        .' AND wiki_nonempty.id=wiki_page.id'
                        .' AND wiki_page.pagename IN ("'.implode('","', $WikiPageAdminPages).'")');
        while($row = db_fetch_array($res)) {
          $allPages[]=$row[0];
        }

        return $allPages;
    }


    /**
     * @return string[] List of pagename
     */
    public function &getAllInternalPages() {
        $WikiPageDefaultPages = WikiPage::getDefaultPages();

        $allPages = array();

        $res = db_query(' SELECT pagename'
                        .' FROM wiki_page, wiki_nonempty'
                        .' WHERE wiki_page.group_id="'.db_ei($this->gid).'"'
                        .' AND wiki_nonempty.id=wiki_page.id'
                        .' AND wiki_page.pagename IN ("'.implode('","', $WikiPageDefaultPages).'")');
        while($row = db_fetch_array($res)) {
          $allPages[]=$row[0];
        }

        return $allPages;
    }


    /**
     * @return string[] List of pagename
     */
    public function &getAllUserPages() {
        $WikiPageAdminPages = WikiPage::getAdminPages();
        $WikiPageDefaultPages = WikiPage::getDefaultPages();

        $allPages = array();

        $res = db_query(' SELECT pagename'
                        .' FROM wiki_page, wiki_nonempty'
                        .' WHERE wiki_page.group_id="'.db_ei($this->gid).'"'
                        .' AND wiki_nonempty.id=wiki_page.id'
                        .' AND wiki_page.pagename NOT IN ("'.implode('","', $WikiPageDefaultPages).'",
                                                          "'.implode('","', $WikiPageAdminPages).'")');
        while($row = db_fetch_array($res)) {
          $allPages[]=$row[0];
        }

        return $allPages;
    }

    public function getAllIndexablePages($project_id) {
        $this->setGid($project_id);

        $indexable_pages = array();
        $this->getIndexablePageFromAllUserPages($indexable_pages);
        $this->getIndexablePageFromDefaultAndAdminPages($indexable_pages);

        return $indexable_pages;
    }

    private function getIndexablePageFromAllUserPages(array &$indexable_pages) {
        $all_internal_pages = array_merge($this->getAllUserPages(), $this->wrapper->getProjectEmptyLinks());

        foreach ($all_internal_pages as $internal_page_name) {
            $wiki_page = new WikiPage($this->gid, $internal_page_name);

            if (! $wiki_page->isReferenced()) {
                $indexable_pages[] = $wiki_page;
            }
        }
    }

    private function getIndexablePageFromDefaultAndAdminPages(array &$indexable_pages) {
        $default_pages_used = array_merge($this->getAllInternalPages(), $this->getAdminPages());

        foreach ($default_pages_used as $default_page_name) {
            $wiki_page = new WikiPage($this->gid, $default_page_name);
            $version   = $wiki_page->getCurrentVersion();
            if ($version > 1) {
                $indexable_pages[] = $wiki_page;
            }
        }
    }

  /**
   * List all default PhpWiki pages
   *
   * Following list include all pages (excepted Admin pages) created by PhpWiki
   * out-of-the-box during initialisation.
   *
   * @return string[] List of pagename 
   */
    public static function getDefaultPages() {
        return array
            ( // Plugin documentation pages
             "AddCommentPlugin","AppendTextPlugin","AuthorHistoryPlugin"
             ,"CalendarListPlugin","CommentPlugin","CreatePagePlugin"
             ,"CreateTocPlugin","EditMetaDataPlugin","FrameIncludePlugin"
             ,"HelloWorldPlugin","IncludePagePlugin","ListPagesPlugin"
             ,"PhotoAlbumPlugin","PhpHighlightPlugin","RedirectToPlugin"
             ,"RichTablePlugin","RssFeedPlugin","SearchHighlightPlugin"
             ,"SyntaxHighlighterPlugin","TemplateExample","TemplatePlugin"
             ,"TranscludePlugin","UnfoldSubpagesPlugin","WikiBlogPlugin"
             ,"CalendarPlugin","PhpWikiPoll"
             
             // Wiki doc page
             ,"WikiPlugin","OldStyleTablePlugin","OldTextFormattingRules"
             ,"PhpWikiDocumentation","TextFormattingRules"

             // Action Pages
             ,"DebugInfo","AppendText","CreatePage","EditMetaData","LikePages"
             ,"PluginManager","SearchHighlight","UpLoad","AllPages","BackLinks"
             ,"FindPage","FullRecentChanges","FullTextSearch","FuzzyPages"
             ,"InterWikiMap","InterWikiSearch","MostPopular","OrphanedPages"
             ,"PageDump","PageHistory","PageInfo","RandomPage","RecentChanges"
             ,"RecentComments","RecentEdits","RecentReleases","RelatedChanges"
             ,"TitleSearch","UserPreferences","WantedPages"

             // Collection Pages
             ,"CategoryCategory","CategoryGroup"

             // French pages
             ,"PluginCommenter" ,"CréerUnePage" ,"DéposerUnFichier" ,"DernièresModifsComplètes"
             ,"AjouterDesCommentaires" ,"AjouterDesPages" ,"AliasAccueil"
             ,"AnciennesRèglesDeFormatage" ,"ÉditerLeContenu" ,"ÉditionsRécentes"
             ,"CarteInterWiki" ,"CatégorieCatégorie " ,"CatégoriePagesAccueil"
             ,"ChangementsLiés" ,"ChercherUnePage" ,"ClassezLa" ,"CommentairesRécents" 
             ,"CommentUtiliserUnWiki" ,"DerniersVisiteurs" ,"DocumentationDePhpWiki" 
             ,"EditerLesMetaDonnées" ,"GestionDesPlugins" ,"HistoriqueDeLaPage" 
             ,"IcônesDeLien" ,"InfosAuthentification" ,"InfosDeDéboguage" ,"InfosSurLaPage"
             ,"InterWiki" ,"JoindreUnFichier" ,"LesPlusVisitées" ,"LienGoogle" 
             ,"ListeDePages" ,"ModifsRécentesPhpWiki" ,"NotesDeVersion" ,"PageAléatoire" 
             ,"PagesRecherchées" ,"PagesSemblables" ,"PhpWiki" ,"PierrickMeignen" ,"PluginAlbumPhotos"
             ,"PluginBeauTableau" ,"PluginBonjourLeMonde" ,"PluginÉditerMetaData" 
             ,"PluginCalendrier" ,"PluginColorationPhp" ,"PluginCréerUnePage" 
             ,"PluginCréerUneTdm" ,"PluginHistoriqueAuteur" ,"PluginHtmlPur" 
             ,"PluginInclureUnCadre" ,"PluginInclureUnePage" ,"PluginListeDesSousPages" 
             ,"PluginListeDuCalendrier" ,"PluginMétéoPhp" ,"PluginRechercheExterne"
             ,"PluginRedirection" ,"PluginRessourcesRss" ,"PluginTableauAncienStyle" 
             ,"PluginTeX2png" ,"PluginWiki" ,"PluginWikiBlog" ,"PréférencesUtilisateurs" 
             ,"QuiEstEnLigne" ,"RèglesDeFormatageDesTextes" ,"RécupérationDeLaPage"
             ,"RétroLiens" ,"RechercheEnTexteIntégral" ,"RechercheInterWiki"
             ,"RechercheParTitre" ,"SommaireDuProjet" ,"TestDeCache" ,"TestGroupeDePages"
             ,"TestGroupeDePages/Deux" ,"TestGroupeDePages/Trois" ,"TestGroupeDePages/Un" 
             ,"TousLesUtilisateurs" ,"ToutesLesPages" ,"TraduireUnTexte" 
             ,"URLMagiquesPhpWiki" ,"VersionsRécentes" ,"VisiteursRécents"
             ,"WabiSabi" ,"WikiWikiWeb" ,"DernièresModifs" ,"CatégorieGroupes" 
             ,"SteveWainstead" ,"PluginInsérer" ,"StyleCorrect" ,"DétailsTechniques" 
             ,"PagesFloues" ,"PluginInfosSystème", "PagesOrphelines" ,"SondagePhpWiki"
             
             // Old projects initialised their wiki with the old set of internal pages (pgsrc folder)
             // In the current version of PHPWiki, we initialise wiki with a different folder. 
             // The following pages are added in order not to consider them as user pages.
             ,"AddingPages", "AllUsers","TranslateText","WhoIsOnline"
             ,"_AuthInfo","CategoryHomePages","EditText","ExternalSearchPlugin"
             ,"GoodStyle","GoogleLink","HowToUseWiki","LinkIcons"
             ,"MagicPhpWikiURLs","MoreAboutMechanics","NewMarkupTestPage"
             ,"OldMarkupTestPage","PageGroupTest","PageGroupTest/One"
             ,"PageGroupTest/Two","PageGroupTest/Three","PageGroupTest/Four"
             ,"PgsrcRefactoring","PgsrcTranslation","PhpWikiRecentChanges"
             ,"ProjectSummary","RecentVisitors","ReleaseNotes","SystemInfoPlugin"
             ,"HomePageAlias","PhpWeatherPlugin","RateIt","RawHtmlPlugin"
             
             );
  }

  /**
   * List all PhpWiki Admin pages 
   *
   * @see getDefaultPages
   * @return string[] List of pagename 
   */
    public static function getAdminPages() {
        return array(
            "HomePage" ,"PhpWikiAdministration","WikiAdminSelect"
            ,"PhpWikiAdministration/Remove"
            ,"PhpWikiAdministration/Rename", "PhpWikiAdministration/Replace"
            ,"PhpWikiAdministration/Chmod","PhpWikiAdministration/Chown"
            ,"PhpWikiAdministration/SetAcl" ,"SandBox", "ProjectWantedPages",

            "PageAccueil" ,"AdministrationDePhpWiki","AdministrationDePhpWiki/Supprimer"
            ,"AdministrationDePhpWiki/Remplacer"
            ,"AdministrationDePhpWiki/Renommer", "AdministrationDePhpWiki/Droits"
            ,"BacÀSable",
        );
    }
}
