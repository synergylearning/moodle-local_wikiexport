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
 * Main class for handling the export
 *
 * @package   local_wikiexport
 * @copyright 2023 Synergy Learning
 * @author    Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wikiexport;

class export {
    /** @var object */
    protected $cm;
    /** @var object */
    protected $wiki;
    /** @var info */
    protected $wikiinfo;
    /** @var string */
    protected $exporttype;
    /** @var object */
    protected $userid;
    /** @var object */
    protected $groupid;

    public const EXPORT_EPUB = 'epub';
    public const EXPORT_PDF = 'pdf';

    public const MAX_EXPORT_ATTEMPTS = 2;

    protected static $exporttypes = [self::EXPORT_EPUB, self::EXPORT_PDF];

    public function __construct($cm, $wiki, $exporttype, $userid = 0, $groupid = 0) {
        $this->cm = $cm;
        $this->wiki = $wiki;
        if (in_array($exporttype, self::$exporttypes, true)) {
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
        $this->wikiinfo = new info();
    }

    public static function get_links($cm, $userid = null, $groupid = null): array {
        $ret = array();
        $context = \context_module::instance($cm->id);

        // Add links for the different export types.
        foreach (self::$exporttypes as $exporttype) {
            $capability = 'local/wikiexport:export'.$exporttype;
            if (has_capability($capability, $context)) {
                $name = get_string('export'.$exporttype, 'local_wikiexport');
                $url = new \moodle_url('/local/wikiexport/export.php', array('id' => $cm->id, 'type' => $exporttype));
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
            $url = new \moodle_url('/local/wikiexport/sortpages.php', array('id' => $cm->id));
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

    public function check_access(): void {
        global $USER;
        $context = \context_module::instance($this->cm->id);
        $capability = 'local/wikiexport:export'.$this->exporttype;
        require_capability($capability, $context);

        $groupmode = (int)groups_get_activity_groupmode($this->cm);
        if ($this->groupid && $groupmode === SEPARATEGROUPS && !groups_is_member($this->groupid)) {
            if (!has_capability('mod/wiki:managewiki', $context)) {
                require_capability('moodle/site:accessallgroups', $context);
            }
        }
        if ($this->userid && $this->userid !== $USER->id && $this->cm->groupmode != VISIBLEGROUPS) {
            require_capability('mod/wiki:managewiki', $context);
        }
    }

    /**
     * Generate the export file and (optionally) send direct to the user's browser.
     *
     * @param bool $download (optional) true to send the file directly to the user's browser
     * @return string the path to the generated file, if not downloading directly
     */
    public function export(bool $download = true): string {
        // Raise the max execution time to 5 min, not 30 seconds.
        \core_php_time_limit::raise(300);

        $pages = $this->load_pages();
        $exp = $this->start_export($download);
        $this->add_coversheet($exp);
        foreach ($pages as $page) {
            $this->export_page($exp, $page);
        }
        return $this->end_export($exp, $download);
    }

    public static function cron(): void {
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
        foreach (\core_user\fields::get_name_fields() as $fieldname) {
            $touser->$fieldname = '';
        }

        $msg = get_string('wikiupdated_body', 'local_wikiexport');
        while ($subwiki = self::get_next_from_queue()) {
            if ($subwiki->exportattempts == self::MAX_EXPORT_ATTEMPTS) {
                // Already failed to export the maximum allowed times - drop an email to the user to let them know, then move on
                // to the next wiki to export.
                $wikiurl = new \moodle_url('/mod/wiki/view.php', array('id' => $subwiki->cm->id));
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
                $export = new self($subwiki->cm, $subwiki->wiki, self::EXPORT_PDF, $subwiki->userid, $subwiki->groupid);
                $filepath = $export->export(false);
                $filename = basename($filepath);
                email_to_user($touser, $touser, get_string('wikiupdated', 'local_wikiexport', $subwiki->wiki->name), $msg, '',
                              $filepath, $filename, false);
                @unlink($filepath);

                // Export successful - update the queue.
                self::remove_from_queue($subwiki);
            } catch(\Exception $e) {
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
    protected static function update_queue($config): void {
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
                if ((int)$queueitem->exportattempts !== 0) {
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
    protected static function get_next_from_queue(): ?object {
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
                mtrace("Page updated for wiki ID $nextitem->wikiid, which does not exist\n");
                return self::get_next_from_queue();
            }
            if (!$cm = get_coursemodule_from_instance('wiki', $wiki->id)) {
                mtrace("Missing course module for wiki ID $wiki->id\n");
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
    protected static function remove_from_queue(object $subwiki): void {
        global $DB;
        $DB->delete_records('local_wikiexport_queue', array('id' => $subwiki->queueid));
    }

    protected function load_pages(): array {
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
            if ($page->title === $this->wiki->firstpagetitle) {
                unset($pages[$id]);
                $pages = array($id => $page) + $pages; // Move the first page to the start of the list.
                break;
            }
        }

        $context = \context_module::instance($this->cm->id);
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

    protected function fix_internal_links($page, $pageids): void {
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
        $baseurl = new \moodle_url('/mod/wiki/view.php', array('pageid' => 'PAGEID'));
        $baseurl = $baseurl->out(false);
        $baseurl = preg_quote($baseurl, '|');
        $baseurl = str_replace(array('&', 'PAGEID'), array('(&|&amp;)', '(\d+)'), $baseurl);
        if (preg_match_all("|$baseurl|", $page->content, $matches)) {
            $ids = $matches[count($matches) - 1];
            $urls = $matches[0];
            foreach ($ids as $idx => $pageid) {
                if (in_array($pageid, $pageids, false)) {
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
        $baseurl = new \moodle_url('/mod/wiki/create.php');
        $baseurl = $baseurl->out(false);
        $baseurl = preg_quote($baseurl, '|');
        $baseurl = str_replace(array('&'), array('(&|&amp;)'), $baseurl);
        if (preg_match_all('|href="'.$baseurl.'[^"]*"|', $page->content, $matches)) {
            foreach ($matches[0] as $createurl) {
                $page->content = str_replace($createurl, '', $page->content);
            }
        }

        // Remove any 'edit' links.
        $page->content = preg_replace('|<a href="edit\.php.*?\[edit]</a>|', '', $page->content);
    }

    protected function start_export($download) {
        global $CFG;
        $exp = null;
        if ($this->exporttype == self::EXPORT_EPUB) {
            $exp = new export_epub();
            $exp->set_title($this->wiki->name);
            $exp->set_uid();
            $exp->set_date();
            if ($CFG->lang) {
                $exp->add_language($CFG->lang);
            }
            $exp->set_publisher(get_string('publishername', 'local_wikiexport'));
        } else { // PDF.
            $exp = new export_pdf();
            $restricttocontext = false;
            if ($download) {
                $restricttocontext = \context_module::instance($this->cm->id);
            }
            $exp->use_direct_image_load($restricttocontext);
            $exp->SetMargins(20, 10, -1, true); // Set up wider left margin than default.
        }

        return $exp;
    }

    protected function export_page($exp, $page) {
        if ($this->exporttype == self::EXPORT_EPUB) {
            /** @var export_epub $exp */
            $content = '<h1>'.$page->title.'</h1>'.$page->content;
            $href = 'pageid-'.$page->id.'.html';
            $exp->add_html($content, $page->title, array('tidy' => false, 'href' => $href, 'toc' => true));

        } else { // PDF.
            /** @var export_pdf $exp */
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
            /** @var export_epub $exp */
            $exp->generate_nav();
            $out = $exp->generate();
            if ($download) {
                $out->sendZip($filename, 'application/epub+zip');
            } else {
                $out->setZipFile($filename);
            }

        } else { // PDF
            /** @var export_pdf $exp */
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

    protected function add_coversheet_epub(export_epub $exp) {
        global $CFG;

        $title = $this->wiki->name;
        $description = format_text($this->wiki->intro, $this->wiki->introformat);
        $info = $this->get_coversheet_info();

        $img = 'images/logo.png';
        $imgsrc = $CFG->dirroot.'/local/wikiexport/pix/logo.png';
        $fp = fopen($imgsrc, 'r');
        $exp->add_item_file($fp, mimeinfo('type', $imgsrc), $img);

        $html = '';

        $imgel = \html_writer::empty_tag('img', array('src' => $img, 'style' => 'max-width: 90%;'));
        $html .= \html_writer::div($imgel, 'fronttitle', array('style' => 'text-align: center; padding: 1em 0;'));
        $html .= \html_writer::div(' ', 'fronttitletop', array('style' => 'display: block; width: 100%; height: 0.4em;
                                                                               background-color: #b8cce4; margin-top: 1em;'));
        $html .= \html_writer::tag('h1', $title, array('style' => 'display: block; width: 100%; background-color: #4f81bd;
                                                                  min-height: 2em; text-align: center; padding-top: 0.5em;
                                                                  size: 1em; margin: 0;' ));
        $html .= \html_writer::div(' ', 'fronttitlebottom', array('style' => 'display: block; width: 100%; height: 0.4em;
                                                                               background-color: #4bacc6; margin-bottom: 1em;'));
        $html .= \html_writer::div($description, 'frontdescription', array('style' => 'margin: 0.5em 1em;'));
        $html .= \html_writer::div($info, 'frontinfo', array('style' => 'margin: 2em 1em'));

        $html = \html_writer::div($html, 'frontpage', array('style' => 'margin: 0.5em; border: solid black 1px; border-radius: 0.8em;
                                                                       width: 90%;'));

        $exp->add_spine_item($html, 'cover.html');
    }

    protected function add_coversheet_pdf(export_pdf $exp) {
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

    protected function get_coversheet_info(): ?string {
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
