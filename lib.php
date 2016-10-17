<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 
/**
 * Library functions
 *
 * @package   local_wikiexport
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/pdflib.php');
require_once($CFG->dirroot.'/local/wikiexport/luciepub/LuciEPUB.php');

class local_wikiexport {
    /** @var object */
    protected $cm;
    /** @var object */
    protected $wiki;
    /** @var local_wikiexport_info */
    protected $wikiinfo;
    /** @var string */
    protected $exporttype;
    /** @var object */
    protected $userid;
    /** @var object */
    protected $groupid;

    const EXPORT_EPUB = 'epub';
    const EXPORT_PDF = 'pdf';

    const MAX_EXPORT_ATTEMPTS = 2;

    protected static $exporttypes = array(self::EXPORT_EPUB, self::EXPORT_PDF);

    public function __construct($cm, $wiki, $exporttype, $userid = 0, $groupid = 0) {
        $this->cm = $cm;
        $this->wiki = $wiki;
        if (in_array($exporttype, self::$exporttypes)) {
            $this->exporttype = $exporttype;
        } else {
            $this->exporttype = reset(self::$exporttypes); // Default to first type in the list.
        }
        $this->userid = $userid;
        if (is_object($this->userid)) {
            $this->userid = $this->userid->id;
        } else if ($this->userid === null) {
            $this->userid = 0;
        }
        $this->groupid = $groupid;
        if (is_object($this->groupid)) {
            $this->groupid = $this->groupid->id;
        } else if ($this->groupid === null) {
            $this->groupid = 0;
        }
        $this->wikiinfo = new local_wikiexport_info();
    }

    public static function get_links($cm, $userid = null, $groupid = null) {
        $ret = array();
        $context = context_module::instance($cm->id);

        // Add links for the different export types.
        foreach (self::$exporttypes as $exporttype) {
            $capability = 'local/wikiexport:export'.$exporttype;
            if (has_capability($capability, $context)) {
                $name = get_string('export'.$exporttype, 'local_wikiexport');
                $url = new moodle_url('/local/wikiexport/export.php', array('id' => $cm->id, 'type' => $exporttype));
                if ($userid) {
                    $url->param('userid', $userid);
                }
                if ($groupid) {
                    $url->param('groupid', $groupid);
                }
                $ret[$name] = $url;
            }
        }

        // Add the 'sort pages' link.
        if (has_capability('mod/wiki:managewiki', $context)) {
            $name = get_string('sortpages', 'local_wikiexport');
            $url = new moodle_url('/local/wikiexport/sortpages.php', array('id' => $cm->id));
            if ($userid) {
                $url->param('userid', $userid);
            }
            if ($groupid) {
                $url->param('groupid', $groupid);
            }
            $ret[$name] = $url;
        }
        return $ret;
    }

    public function check_access() {
        global $USER;
        $context = context_module::instance($this->cm->id);
        $capability = 'local/wikiexport:export'.$this->exporttype;
        require_capability($capability, $context);

        $groupmode = groups_get_activity_groupmode($this->cm);
        if ($this->groupid) {
            if ($groupmode == SEPARATEGROUPS) {
                if (!groups_is_member($this->groupid)) {
                    if (!has_capability('mod/wiki:managewiki', $context)) {
                        require_capability('moodle/site:accessallgroups', $context);
                    }
                }
            }
        }
        if ($this->userid) {
            if ($this->userid !== $USER->id) {
                if ($this->cm->groupmode != VISIBLEGROUPS) {
                    require_capability('mod/wiki:managewiki', $context);
                }
            }
        }
    }

    /**
     * Generate the export file and (optionally) send direct to the user's browser.
     *
     * @param bool $download (optional) true to send the file directly to the user's browser
     * @return string the path to the generated file, if not downloading directly
     */
    public function export($download = true) {
        // Raise the max execution time to 5 min, not 30 seconds.
        @set_time_limit(300);

        $pages = $this->load_pages();
        $exp = $this->start_export($download);
        $this->add_coversheet($exp);
        foreach ($pages as $page) {
            $this->export_page($exp, $page);
        }
        return $this->end_export($exp, $download);
    }

    public static function cron() {
        $config = get_config('local_wikiexport');
        if (empty($config->publishemail)) {
            return; // No email specified.
        }
        if (!$destemail = trim($config->publishemail)) {
            return; // Email is empty.
        }
        if (empty($config->lastcron)) {
            return; // Don't export every wiki on the site the first time cron runs.
        }

        // Update the list of wikis waiting to be exported.
        self::update_queue($config);

        $touser = (object)array(
            'id' => -1,
            'email' => $destemail,
            'maildisplay' => 0,
        );
        foreach (get_all_user_name_fields(false) as $fieldname) {
            $touser->$fieldname = '';
        }

        $msg = get_string('wikiupdated_body', 'local_wikiexport');
        while ($subwiki = self::get_next_from_queue()) {
            if ($subwiki->exportattempts == self::MAX_EXPORT_ATTEMPTS) {
                // Already failed to export the maximum allowed times - drop an email to the user to let them know, then move on
                // to the next wiki to export.
                $wikiurl = new moodle_url('/mod/wiki/view.php', array('id' => $subwiki->cm->id));
                $info = (object)array(
                    'name' => $subwiki->wiki->name,
                    'url' => $wikiurl->out(false),
                    'exportattempts' => $subwiki->exportattempts,
                );
                $failmsg = get_string('wikiexportfailed_body', 'local_wikiexport', $info);
                email_to_user($touser, $touser, get_string('wikiexportfailed', 'local_wikiexport', $subwiki->wiki->name), $failmsg);
            }

            // Attempt the export.
            try {
                $export = new local_wikiexport($subwiki->cm, $subwiki->wiki, self::EXPORT_PDF, $subwiki->userid, $subwiki->groupid);
                $filepath = $export->export(false);
                $filename = basename($filepath);
                email_to_user($touser, $touser, get_string('wikiupdated', 'local_wikiexport', $subwiki->wiki->name), $msg, '',
                              $filepath, $filename, false);
                @unlink($filepath);

                // Export successful - update the queue.
                self::remove_from_queue($subwiki);
            } catch(Exception $e) {
                print_r($e);
                print_r($subwiki);
            }
        }
    }

    /**
     * Find any subwikis that have been updated since we last refeshed the export queue.
     * Any subwikis that have been updated will have thier export attempt count reset.
     *
     * @param $config
     */
    protected static function update_queue($config) {
        global $DB;

        if (empty($config->lastqueueupdate)) {
            $config->lastqueueupdate = $config->lastcron;
        }

        // Get a list of any wikis that have been changed since the last queue update.
        $sql = "SELECT DISTINCT s.id, s.wikiid
                  FROM {wiki_subwikis} s
                  JOIN {wiki_pages} p ON p.subwikiid = s.id AND p.timemodified > :lastqueueupdate
                 ORDER BY s.wikiid, s.id";
        $params = array('lastqueueupdate' => $config->lastqueueupdate);
        $subwikis = $DB->get_records_sql($sql, $params);

        // Save a list of all subwikis to be exported.
        $currentqueue = $DB->get_records('local_wikiexport_queue');
        foreach ($subwikis as $subwiki) {
            if (isset($currentqueue[$subwiki->id])) {
                // A subwiki already in the queue has been updated - reset the export attempts (if non-zero).
                $queueitem = $currentqueue[$subwiki->id];
                if ($queueitem->exportattempts != 0) {
                    $DB->set_field('local_wikiexport_queue', 'exportattempts', 0, array('id' => $queueitem->id));
                }
            } else {
                $ins = (object)array(
                    'subwikiid' => $subwiki->id,
                    'exportattempts' => 0,
                );
                $DB->insert_record('local_wikiexport_queue', $ins, false);
            }
        }

        // Save the timestamp to detect any future wiki export changes.
        set_config('lastqueueupdate', time(), 'local_wikiexport');
    }

    /**
     * Get the next subwiki in the queue - ignoring those that have already had too many export attempts.
     * The return object includes the wiki and cm as sub-objects.
     *
     * @return object|null null if none left to export
     */
    protected static function get_next_from_queue() {
        global $DB;

        static $cm = null;
        static $wiki = null;

        $sql = "SELECT s.id, s.groupid, s.userid, s.wikiid, q.id AS queueid, q.exportattempts
                  FROM {local_wikiexport_queue} q
                  JOIN {wiki_subwikis} s ON s.id = q.subwikiid
                 WHERE q.exportattempts <= :maxexportattempts
                 ORDER BY s.wikiid";
        $params = array('maxexportattempts' => self::MAX_EXPORT_ATTEMPTS);
        $nextitems = $DB->get_records_sql($sql, $params, 0, 1); // Retrieve the first record found.
        $nextitem = reset($nextitems);
        if (!$nextitem) {
            return null;
        }

        // Update the 'export attempts' in the database.
        $DB->set_field('local_wikiexport_queue', 'exportattempts', $nextitem->exportattempts + 1, ['id' => $nextitem->queueid]);

        // Add the wiki + cm objects to the return object.
        if (!$wiki || $wiki->id != $nextitem->wikiid) {
            if (!$wiki = $DB->get_record('wiki', array('id' => $nextitem->wikiid))) {
                mtrace("Page updated for wiki ID {$nextitem->wikiid}, which does not exist\n");
                return self::get_next_from_queue();
            }
            if (!$cm = get_coursemodule_from_instance('wiki', $wiki->id)) {
                mtrace("Missing course module for wiki ID {$wiki->id}\n");
                return self::get_next_from_queue();
            }
        }
        $nextitem->wiki = $wiki;
        $nextitem->cm = $cm;

        return $nextitem;
    }

    /**
     * Remove the subwiki from the export queue, after it has been successfully exported.
     *
     * @param object $subwiki
     */
    protected static function remove_from_queue($subwiki) {
        global $DB;
        $DB->delete_records('local_wikiexport_queue', array('id' => $subwiki->queueid));
    }

    protected function load_pages() {
        global $DB;
        $subwiki = $DB->get_record('wiki_subwikis', array('wikiid' => $this->wiki->id, 'groupid' => $this->groupid,
                                                          'userid' => $this->userid), '*', MUST_EXIST);
        $sql = "SELECT p.id, p.title, p.cachedcontent AS content, p.timecreated, p.timemodified, p.userid
                  FROM {wiki_pages} p
                  LEFT JOIN {local_wikiexport_order} xo ON xo.pageid = p.id
                 WHERE p.subwikiid = :subwikiid
                 ORDER BY xo.sortorder, p.title";
        $params = array('subwikiid' => $subwiki->id);
        $pages = $DB->get_records_sql($sql, $params);
        $pageids = array_keys($pages);
        foreach ($pages as $id => $page) {
            if ($page->title == $this->wiki->firstpagetitle) {
                unset($pages[$id]);
                $pages = array($id => $page) + $pages; // Move the first page to the start of the list.
                break;
            }
        }

        $context = context_module::instance($this->cm->id);
        foreach ($pages as $page) {
            // Fix pluginfile urls.
            $page->content = file_rewrite_pluginfile_urls($page->content, 'pluginfile.php', $context->id,
                                                          'mod_wiki', 'attachments', $subwiki->id);
            $page->content = format_text($page->content, FORMAT_MOODLE, array('overflowdiv' => true, 'allowid' => true));

            // Fix internal links.
            $this->fix_internal_links($page, $pageids);

            // Note created/modified time (if earlier / later than already recorded).
            $this->wikiinfo->update_times($page->timecreated, $page->timemodified, $page->userid);
        }

        return $pages;
    }

    protected function fix_internal_links($page, $pageids) {
        if ($this->exporttype == self::EXPORT_PDF) {
            // Fix internal TOC links to include the pageid (to make them unique across all pages).
            if (preg_match_all('|<a href="#([^"]+)"|', $page->content, $matches)) {
                $anchors = $matches[1];
                foreach ($anchors as $anchor) {
                    $page->content = str_replace($anchor, $anchor.'-'.$page->id, $page->content);
                }
            }
        }

        // Replace links to other pages with anchor links to '#pageid-[page id]' (PDF)
        // or links to page 'pageid-[page id].html' (EPUB).
        $baseurl = new moodle_url('/mod/wiki/view.php', array('pageid' => 'PAGEID'));
        $baseurl = $baseurl->out(false);
        $baseurl = preg_quote($baseurl);
        $baseurl = str_replace(array('&', 'PAGEID'), array('(&|&amp;)', '(\d+)'), $baseurl);
        if (preg_match_all("|$baseurl|", $page->content, $matches)) {
            $ids = $matches[count($matches) - 1];
            $urls = $matches[0];
            foreach ($ids as $idx => $pageid) {
                if (in_array($pageid, $pageids)) {
                    $find = $urls[$idx];
                    if ($this->exporttype == self::EXPORT_PDF) {
                        $replace = '#pageid-'.$pageid;
                    } else { // Epub - link to correct page in export.
                        $replace = 'pageid-'.$pageid.'.html';
                    }
                    $page->content = str_replace($find, $replace, $page->content);
                }
            }
        }

        // Replace any 'create' links with blank links.
        $baseurl = new moodle_url('/mod/wiki/create.php');
        $baseurl = $baseurl->out(false);
        $baseurl = preg_quote($baseurl);
        $baseurl = str_replace(array('&'), array('(&|&amp;)'), $baseurl);
        if (preg_match_all('|href="'.$baseurl.'[^"]*"|', $page->content, $matches)) {
            foreach ($matches[0] as $createurl) {
                $page->content = str_replace($createurl, '', $page->content);
            }
        }

        // Remove any 'edit' links.
        $page->content = preg_replace('|<a href="edit\.php.*?\[edit\]</a>|', '', $page->content);
    }

    protected function start_export($download) {
        global $CFG;
        $exp = null;
        if ($this->exporttype == self::EXPORT_EPUB) {
            $exp = new wikiexport_epub();
            $exp->set_title($this->wiki->name);
            $exp->set_uid();
            $exp->set_date();
            if ($CFG->lang) {
                $exp->add_language($CFG->lang);
            }
            $exp->set_publisher(get_string('publishername', 'local_wikiexport'));
        } else { // PDF.
            $exp = new wikiexport_pdf();
            $restricttocontext = false;
            if ($download) {
                $restricttocontext = context_module::instance($this->cm->id);
            }
            $exp->use_direct_image_load($restricttocontext);
            $exp->SetMargins(20, 10, -1, true); // Set up wider left margin than default.
        }

        return $exp;
    }

    protected function export_page($exp, $page) {
        if ($this->exporttype == self::EXPORT_EPUB) {
            /** @var LuciEPUB $exp */
            $content = '<h1>'.$page->title.'</h1>'.$page->content;
            $href = 'pageid-'.$page->id.'.html';
            $exp->add_html($content, $page->title, array('tidy' => false, 'href' => $href, 'toc' => true));

        } else { // PDF.
            /** @var wikiexport_pdf $exp */
            $exp->addPage();
            $exp->setDestination('pageid-'.$page->id);
            $exp->writeHTML('<h2>'.$page->title.'</h2>');
            $exp->writeHTML($page->content);
        }
    }

    protected function end_export($exp, $download) {
        global $CFG;

        $filename = $this->get_filename($download);

        if ($this->exporttype == self::EXPORT_EPUB) {
            /** @var LuciEPUB $exp */
            $exp->generate_nav();
            $out = $exp->generate();
            if ($download) {
                $out->sendZip($filename, 'application/epub+zip');
            } else {
                $out->setZipFile($filename);
            }

        } else { // PDF
            /** @var pdf $exp */
            if ($download) {
                $exp->Output($filename, 'D');
            } else {
                $exp->Output($filename, 'F');
            }
        }

        // Remove 'dataroot' from the filename, so the email sending can put it back again.
        $filename = str_replace($CFG->dataroot.'/', '', $filename);

        return $filename;
    }

    protected function get_filename($download) {
        $info = (object)array(
            'timestamp' => userdate(time(), '%Y-%m-%d %H:%M'),
            'wikiname' => format_string($this->wiki->name),
        );
        $filename = get_string('filename', 'local_wikiexport', $info);
        if ($this->exporttype == self::EXPORT_EPUB) {
            $filename .= '.epub';
        } else { // PDF.
            $filename .= '.pdf';
        }

        $filename = clean_filename($filename);

        if (!$download) {
            $filename = str_replace(' ', '_', $filename);
            $path = make_temp_directory('local_wikiexport');
            $filename = $path.'/'.$filename;
        }

        return $filename;
    }

    protected function add_coversheet($exp) {
        if ($this->exporttype == self::EXPORT_EPUB) {
            $this->add_coversheet_epub($exp);
        } else {
            $this->add_coversheet_pdf($exp);
        }
    }

    protected function add_coversheet_epub(LuciEPUB $exp) {
        global $CFG;

        $title = $this->wiki->name;
        $description = format_text($this->wiki->intro, $this->wiki->introformat);
        $info = $this->get_coversheet_info();

        $img = 'images/logo.png';
        $imgsrc = $CFG->dirroot.'/local/wikiexport/pix/logo.png';
        $fp = fopen($imgsrc, 'r');
        $exp->add_item_file($fp, mimeinfo('type', $imgsrc), $img);

        $html = '';

        $imgel = html_writer::empty_tag('img', array('src' => $img, 'style' => 'max-width: 90%;'));
        $html .= html_writer::div($imgel, 'fronttitle', array('style' => 'text-align: center; padding: 1em 0;'));
        $html .= html_writer::div(' ', 'fronttitletop', array('style' => 'display: block; width: 100%; height: 0.4em;
                                                                               background-color: #b8cce4; margin-top: 1em;'));
        $html .= html_writer::tag('h1', $title, array('style' => 'display: block; width: 100%; background-color: #4f81bd;
                                                                  min-height: 2em; text-align: center; padding-top: 0.5em;
                                                                  size: 1em; margin: 0;' ));
        $html .= html_writer::div(' ', 'fronttitlebottom', array('style' => 'display: block; width: 100%; height: 0.4em;
                                                                               background-color: #4bacc6; margin-bottom: 1em;'));
        $html .= html_writer::div($description, 'frontdescription', array('style' => 'margin: 0.5em 1em;'));
        $html .= html_writer::div($info, 'frontinfo', array('style' => 'margin: 2em 1em'));

        $html = html_writer::div($html, 'frontpage', array('style' => 'margin: 0.5em; border: solid black 1px; border-radius: 0.8em;
                                                                       width: 90%;'));

        $exp->add_spine_item($html, 'cover.html');
    }

    protected function add_coversheet_pdf(pdf $exp) {
        global $CFG;

        $exp->startPage();
        // Rounded rectangle.
        $exp->RoundedRect(9, 9, 192, 279, 6.5);
        // Logo.
        $exp->Image($CFG->dirroot.'/local/wikiexport/pix/logo.png', 52, 27, 103, 36);
        // Title bar.
        $exp->Rect(9, 87.5, 192, 2.5, 'F', array(), array(184, 204, 228));
        $exp->Rect(9, 90, 192, 30, 'F', array(), array(79, 129, 189));
        $exp->Rect(9, 120, 192, 2.5, 'F', array(), array(75, 172, 198));

        // Title text.
        $title = $this->wiki->name;
        $exp->SetFontSize(36);
        $exp->Text(9, 97, $title, false, false, true, 0, 0, 'C', false, '', 1, false, 'T', 'C');
        $exp->SetFontSize(12); // Set back to default.

        // Description.
        $description = format_text($this->wiki->intro, $this->wiki->introformat);
        $exp->writeHTMLCell(140, 40, 30, 130, $description);

        // Creation / modification / printing time.
        if ($info = $this->get_coversheet_info()) {
            $exp->writeHTMLCell(176, 20, 12, 255, $info);
        }
    }

    protected function get_coversheet_info() {
        $info = array();
        if ($this->wikiinfo->has_timemodified()) {
            $strinfo = (object)array(
                'timemodified' => $this->wikiinfo->format_timemodified(),
                'modifiedby' => $this->wikiinfo->get_modifiedby()
            );
            $info[] = get_string('modified', 'local_wikiexport', $strinfo);
        }
        if ($this->wikiinfo->has_timeprinted()) {
            $info[] = get_string('printed', 'local_wikiexport', $this->wikiinfo->format_timeprinted());
        }

        if ($info) {
            $info = implode("<br/>\n", $info);
        } else {
            $info = null;
        }

        return $info;
    }
}

/**
 * Insert the 'Export as epub' and 'Export as PDF' links into the navigation.
 *
 * @param $unused
 */
function local_wikiexport_extends_navigation($unused) {
    local_wikiexport_extend_navigation($unused);
}

function local_wikiexport_extend_navigation($unused) {
    global $PAGE, $DB, $USER;
    if (!$PAGE->cm || $PAGE->cm->modname != 'wiki') {
        return;
    }
    $groupid = groups_get_activity_group($PAGE->cm);
    $userid = 0;
    $wiki = $DB->get_record('wiki', array('id' => $PAGE->cm->instance), '*', MUST_EXIST);
    if ($wiki->wikimode == 'individual') {
        $userid = $USER->id;
        if ($uid = optional_param('uid', null, PARAM_INT)) {
            $userid = $uid;
        } else if ($pageid = optional_param('pageid', null, PARAM_INT)) {
            $page = $DB->get_record('wiki_pages', array('id' => $pageid), 'id, subwikiid', MUST_EXIST);
            $subwiki = $DB->get_record('wiki_subwikis', array('id' => $page->subwikiid), 'id, userid', MUST_EXIST);
            $userid = $subwiki->userid;
        }
    }
    if (!$links = local_wikiexport::get_links($PAGE->cm, $userid, $groupid)) {
        return;
    }
    $settingsnav = $PAGE->settingsnav;
    $modulesettings = $settingsnav->get('modulesettings');
    if (!$modulesettings) {
        $modulesettings = $settingsnav->prepend(get_string('pluginadministration', 'mod_wiki'), null,
                                                navigation_node::TYPE_SETTING, null, 'modulesettings');
    }

    foreach ($links as $name => $url) {
        $modulesettings->add($name, $url, navigation_node::TYPE_SETTING);
    }

    // Use javascript to insert the pdf/epub links.
    $jslinks = array();
    foreach ($links as $name => $url) {
        $link = html_writer::link($url, $name);
        $link = html_writer::div($link, 'wiki_right');
        $jslinks[] = $link;
    }
    $PAGE->requires->yui_module('moodle-local_wikiexport-printlinks', 'M.local_wikiexport.printlinks.init', array($jslinks));
}

function local_wikiexport_cron() {
    local_wikiexport::cron();
}

/**
 * Class local_wikiexport_info
 */
class local_wikiexport_info {
    protected $timecreated = 0;
    protected $timemodified = 0;
    protected $modifiedbyid = null;
    protected $modifiedby = null;
    protected $timeprinted = 0;

    public function __construct() {
        $this->timeprinted = time();
    }

    public function update_times($timecreated, $timemodified, $modifiedbyid) {
        if (!$this->timecreated || $this->timecreated > $timecreated) {
            $this->timecreated = $timecreated;
        }
        if ($this->timemodified < $timemodified) {
            $this->timemodified = $timemodified;
            if ($modifiedbyid != $this->modifiedbyid) {
                $this->modifiedbyid = $modifiedbyid;
                $this->modifiedby = null;
            }
        }
    }

    public function has_timecreated() {
        return (bool)$this->timecreated;
    }

    public function has_timemodified() {
        return (bool)$this->timemodified;
    }

    public function has_timeprinted() {
        return (bool)$this->timeprinted;
    }

    public function format_timecreated() {
        return userdate($this->timecreated);
    }

    public function format_timemodified() {
        return userdate($this->timemodified);
    }

    public function format_timeprinted() {
        return userdate($this->timeprinted);
    }

    public function get_modifiedby() {
        global $USER, $DB;

        if ($this->modifiedby === null) {
            if ($this->modifiedbyid == $USER->id) {
                $this->modifiedby = $USER;
            } else {
                $this->modifiedby = $DB->get_record('user', array('id' => $this->modifiedbyid), 'id, firstname, lastname');
            }
        }
        if (!$this->modifiedby) {
            return '';
        }
        return fullname($this->modifiedby);
    }
}

/**
 * Convert an image URL into a stored_file object, if it refers to a local file.
 * @param $fileurl
 * @param context $restricttocontext (optional) if set, only files from this wiki will be included
 * @return null|stored_file
 */
function local_wikiexport_get_image_file($fileurl, $restricttocontext = null) {
    global $CFG;
    if (strpos($fileurl, $CFG->wwwroot.'/pluginfile.php') === false) {
        return null;
    }

    $fs = get_file_storage();
    $params = substr($fileurl, strlen($CFG->wwwroot.'/pluginfile.php'));
    if (substr($params, 0, 1) == '?') { // Slasharguments off.
        $pos = strpos($params, 'file=');
        $params = substr($params, $pos + 5);
    } else { // Slasharguments on.
        if (($pos = strpos($params, '?')) !== false) {
            $params = substr($params, 0, $pos - 1);
        }
    }
    $params = urldecode($params);
    $params = explode('/', $params);
    array_shift($params); // Remove empty first param.
    $contextid = (int)array_shift($params);
    $component = clean_param(array_shift($params), PARAM_COMPONENT);
    $filearea  = clean_param(array_shift($params), PARAM_AREA);
    $itemid = array_shift($params);

    if (empty($params)) {
        $filename = $itemid;
        $itemid = 0;
    } else {
        $filename = array_pop($params);
    }

    if (empty($params)) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $params).'/';
    }

    if ($restricttocontext) {
        if ($component != 'mod_wiki' || $contextid != $restricttocontext->id) {
            return null; // Only allowed to include files directly from this wiki.
        }
    }

    if (!$file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename)) {
        if ($itemid) {
            $filepath = '/'.$itemid.$filepath; // See if there was no itemid in the original URL.
            $itemid = 0;
            $file = $fs->get_file($contextid, $component, $filename, $itemid, $filepath, $filename);
        }
    }

    if (!$file) {
        return null;
    }
    return $file;
}

/**
 * Class wikiexport_pdf
 */
class wikiexport_pdf extends pdf {

    protected $directimageload = false;
    protected $restricttocontext = false;

    public function use_direct_image_load($restricttocontext = false) {
        $this->directimageload = true;
        $this->restricttocontext = $restricttocontext;
    }

    /**
     * Override the existing function to:
     * a) Convert any spaces in filenames into '%20' (as TCPDF seems to incorrectly do the opposite).
     * b) Make any broken file errors non-fatal (replace the image with an error message).
     *
     * @param $file
     * @param string $x
     * @param string $y
     * @param int $w
     * @param int $h
     * @param string $type
     * @param string $link
     * @param string $align
     * @param bool $resize
     * @param int $dpi
     * @param string $palign
     * @param bool $ismask
     * @param bool $imgmask
     * @param int $border
     * @param bool $fitbox
     * @param bool $hidden
     * @param bool $fitonpage
     * @param bool $alt
     * @param array $altimgs
     */
    public function Image($file, $x = '', $y = '', $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false,
                          $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false,
                          $fitonpage = false, $alt = false, $altimgs = array()) {
        if ($this->directimageload) {
            // Get the image data directly from the Moodle files API (needed when generating within cron, instead of downloading).
            $file = $this->get_image_data($file);
        } else {
            // Make sure the filename part of the URL is urlencoded (convert spaces => %20, etc.).
            if (strpos('pluginfile.php', $file) !== false) {
                $urlparts = explode('/', $file);
                $filename = array_pop($urlparts); // Get just the part at the end.
                $filename = rawurldecode($filename); // Decode => make sure the URL isn't double-encoded.
                $filename = rawurlencode($filename);
                $urlparts[] = $filename;
                $file = implode('/', $urlparts);
            }
        }
        try {
            parent::Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border,
                          $fitbox, $hidden, $fitonpage, $alt, $altimgs);
        } catch (Exception $e) {
            $this->writeHTML(get_string('failedinsertimage', 'local_wikiexport', $file));
        }
    }

    public function Header() {
        // No header.
    }

    public function Footer() {
        // No footer.
    }

    /**
     * Copy the image data from the Moodle files API and return it directly.
     *
     * @param $fileurl
     * @return string either the original fileurl param or the file content with '@' appended to the start.
     */
    protected function get_image_data($fileurl) {
        if ($file = local_wikiexport_get_image_file($fileurl, $this->restricttocontext)) {
            $fileurl = '@'.$file->get_content();
        }
        return $fileurl;
    }

    /**
     * Override the existing function to create anchor destinations for any '<a name="x">' tags.
     *
     * @param $dom
     * @param $key
     * @param $cell
     * @return mixed
     */
    protected function openHTMLTagHandler($dom, $key, $cell) {
        $tag = $dom[$key];
        if (array_key_exists('name', $tag['attribute'])) {
            $this->setDestination($tag['attribute']['name']); // Store the destination for TOC links.
        }
        return parent::openHTMLTagHandler($dom, $key, $cell);
    }
}

/**
 * Class wikiexport_epub
 */
class wikiexport_epub extends LuciEPUB {
    public function add_html($html, $title, $config) {
        if ($config['tidy'] && class_exists('tidy')) {
            $tidy = new tidy();
            $tidy->parseString($html, array(), 'utf8');
            $tidy->cleanRepair();
            $html = $tidy->html()->value;
        }

        // Handle <img> tags.
        if (preg_match_all('~(<img [^>]*?)src=([\'"])(.+?)[\'"]~', $html, $matches)) {
            foreach ($matches[3] as $imageurl) {
                if ($file = local_wikiexport_get_image_file($imageurl)) {
                    $newpath = implode('/', array('images', $file->get_contextid(), $file->get_component(), $file->get_filearea(),
                                                  $file->get_itemid(), $file->get_filepath(), $file->get_filename()));
                    $newpath = str_replace(array('///', '//'), '/', $newpath);
                    $this->add_item_file($file->get_content_file_handle(), $file->get_mimetype(), $newpath);
                    $html = str_replace($imageurl, $newpath, $html);
                }
            }
        }

        // Set the href value, if specified.
        $href = null;
        if (!empty($config['href'])) {
            $href = $config['href'];
        }
        $this->add_spine_item($html, $href);
        if ($config['toc']) {
            $this->set_item_toc($title, true);
        }

        return $title;
    }

    public function add_spine_item($data, $href = NULL,
                                   $fallback = NULL, $properties = NULL) {
        if (strpos('<html', $data) === false) {
            $data = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
    <head>
    </head>
    <body>
    '.$data.'
    </body>
</html>';
        }

        return parent::add_spine_item($data, $href, $fallback, $properties);
    }
}

class local_wikiexport_sortpages {
    protected $cm;
    protected $wiki;
    protected $userid;
    protected $groupid;

    protected $action = null;
    protected $pages = array();

    public function __construct($cm, $wiki, $userid, $groupid) {
        $this->cm = $cm;
        $this->wiki = $wiki;
        $this->userid = $userid;
        if (is_object($this->userid)) {
            $this->userid = $this->userid->id;
        } else if ($this->userid === null) {
            $this->userid = 0;
        }
        $this->groupid = $groupid;
        if (is_object($this->groupid)) {
            $this->groupid = $this->groupid->id;
        } else if ($this->groupid === null) {
            $this->groupid = 0;
        }
    }

    public function check_access() {
        $context = context_module::instance($this->cm->id);
        require_capability('mod/wiki:managewiki', $context);
    }

    public function process($action) {
        global $PAGE;

        if ($action) {
            $this->action = $action;
        } else {
            $this->action = 'list';
        }

        $this->load_pages();

        if ($this->action == 'moveup' || $this->action == 'movedown' || $this->action == 'moveto') {
            $pageid = required_param('pageid', PARAM_INT);
            require_sesskey();

            if (!isset($this->pages[$pageid])) {
                throw new moodle_exception('invalidpageid', 'local_wikiexport');
            }
            $page = $this->pages[$pageid];

            if ($this->action == 'moveto') {
                $newpos = required_param('position', PARAM_INT);
            } else if ($this->action == 'moveup') {
                $newpos = $page->sortorder - 1;
            } else { // Move down.
                $newpos = $page->sortorder + 1;
            }
            $this->move_page_to($page, $newpos);

            if (AJAX_SCRIPT) {
                $result = (object)array(
                    'error' => 0,
                    'order' => $this->get_new_pageorder(),
                );
                echo json_encode($result);
                die();

            } else {
                redirect($PAGE->url);
            }

        } else if ($this->action != 'list') {
            throw new moodle_exception('invalidaction', 'local_wikiexport');
        }
    }

    public function output() {
        global $OUTPUT, $PAGE;

        $opts = array('cmid' => $this->cm->id, 'groupid' => $this->groupid, 'userid' => $this->userid, 'sesskey' => sesskey());
        $PAGE->requires->yui_module('moodle-local_wikiexport-sortpages', 'M.local_wikiexport.sortpages.init',
                                    array($opts), null, true);

        $upicon = $OUTPUT->pix_icon('t/up', get_string('moveup'));
        $downicon = $OUTPUT->pix_icon('t/down', get_string('movedown'));
        $spacericon = $OUTPUT->pix_icon('spacer', '');
        $moveicon = $OUTPUT->pix_icon('i/move_2d', get_string('move'));

        $intro = html_writer::tag('p', get_string('sortpagesintro', 'local_wikiexport'));

        $list = '';
        $lastpage = end($this->pages);
        foreach ($this->pages as $page) {
            $item = '';

            $nojsicons = '';
            if ($page->sortorder != 0) { // Cannot move the first page.
                $baseurl = new moodle_url($PAGE->url, array('pageid' => $page->id, 'sesskey' => sesskey()));
                if ($page->sortorder > 1) {
                    $url = new moodle_url($baseurl, array('action' => 'moveup'));
                    $nojsicons .= html_writer::link($url, $upicon);
                } else {
                    $nojsicons .= $spacericon;
                }
                if ($page->sortorder < $lastpage->sortorder) {
                    $url = new moodle_url($baseurl, array('action' => 'movedown'));
                    $nojsicons .= html_writer::link($url, $downicon);
                } else {
                    $nojsicons .= $spacericon;
                }

                $jsicons = $moveicon;
            } else {
                $nojsicons = $spacericon.$spacericon;
                $jsicons = $spacericon;
            }

            $item .= html_writer::span($nojsicons, 'nojsicons');
            $item .= html_writer::span($jsicons, 'jsicons');

            $title = shorten_text(format_string($page->title), 100);
            $item .= html_writer::span($title, 'wikititle');

            $attrib = array('id' => 'wikipageid-'.$page->id, 'class' => 'sortorder-'.$page->sortorder);
            if ($page->sortorder == 0) {
                $attrib['class'] .= ' nomove';
            }
            $list .= html_writer::tag('li', $item, $attrib);
        }
        $list = html_writer::tag('ul', $list, array('class' => 'wiki-sortpages', 'id' => 'wiki-sortpages'));
        $spinner = html_writer::div('&nbsp;', 'wiki-sortpages-spinner', array('id' => 'wiki-sortpages-spinner'));

        return $intro.$spinner.$list;
    }

    /**
     * Load the wiki pages, making sure the sortorder is set for each of them.
     * Pages will be sorted in the correct order and indexed by the page id.
     */
    protected function load_pages() {
        global $DB;

        // Load the pages into memory.
        $subwiki = $DB->get_record('wiki_subwikis', array('wikiid' => $this->wiki->id, 'groupid' => $this->groupid,
                                                          'userid' => $this->userid), '*');
        if (!$subwiki) {
            return;
        }
        $sql = "SELECT p.id, p.title, xo.sortorder, xo.id AS orderid
                  FROM {wiki_pages} p
                  LEFT JOIN {local_wikiexport_order} xo ON xo.pageid = p.id
                 WHERE p.subwikiid = :subwikiid
                 ORDER BY xo.sortorder, p.title";
        $params = array('subwikiid' => $subwiki->id);
        $this->pages = $DB->get_records_sql($sql, $params);
        foreach ($this->pages as $id => $page) {
            if ($page->title == $this->wiki->firstpagetitle) {
                unset($this->pages[$id]);
                $this->pages = array($id => $page) + $this->pages; // Move the first page to the start of the list.
                break;
            }
        }

        // Make sure the sortorder is set for each page in the wiki.
        $sortorder = 0;
        foreach ($this->pages as $page) {
            if ($page->sortorder !== $sortorder) {
                $page->sortorder = $sortorder;
                $this->save_sortorder($page);
            }
            $sortorder++;
        }
    }

    protected function move_page_to($movepage, $newpos) {
        if ($newpos < 0) {
            return; // Cannot move below 0.
        }
        if ($movepage->sortorder == $newpos) {
            return; // No change in position.
        }
        $move = -1; // Other pages need to move backward.
        if ($movepage->sortorder > $newpos) {
            $move = 1; // Other pages need to move forward.
        }

        $maxpos = 0;
        foreach ($this->pages as $page) {
            if ($page->id == $movepage->id) {
                continue; // Update the page being moved after moving all other pages.
            }
            if ($move > 0) {
                if ($page->sortorder >= $newpos) {
                    if ($page->sortorder < $movepage->sortorder) {
                        // Move pages newpos...oldpos one space forward.
                        $page->sortorder += 1;
                        $this->save_sortorder($page);
                    }
                }
            } else {
                if ($page->sortorder <= $newpos) {
                    if ($page->sortorder > $movepage->sortorder) {
                        // Move pages oldpos...newpos one space backward.
                        $page->sortorder -= 1;
                        $this->save_sortorder($page);
                    }
                }
            }
            $maxpos = $page->sortorder;
        }

        if ($newpos > $maxpos) {
            $newpos = $maxpos + 1; // Limit to one more than the maximum of the other sortorders.
        }

        if ($movepage->sortorder != $newpos) {
            $movepage->sortorder = $newpos;
            $this->save_sortorder($movepage);
        }
    }

    protected function save_sortorder($page) {
        global $DB;
        if ($page->orderid) {
            $DB->set_field('local_wikiexport_order', 'sortorder', $page->sortorder, array('id' => $page->orderid));
        } else {
            $ins = (object)array(
                'cmid' => $this->cm->id,
                'courseid' => $this->cm->course,
                'pageid' => $page->id,
                'sortorder' => $page->sortorder,
            );
            $page->orderid = $DB->insert_record('local_wikiexport_order', $ins);
        }
    }

    protected function get_new_pageorder() {
        $pageorder = array();
        foreach ($this->pages as $page) {
            $pageorder[$page->id] = $page->sortorder;
        }
        return $pageorder;
    }
}
