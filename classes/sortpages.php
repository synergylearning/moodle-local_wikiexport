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
 * Support for ordering the pages to be exported
 *
 * @package   local_wikiexport
 * @copyright 2023 Synergy Learning
 * @author    Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wikiexport;

class sortpages {
    protected $cm;
    protected $wiki;
    protected $userid;
    protected $groupid;

    protected $action;
    protected $pages = [];

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

    public function check_access(): void {
        $context = \context_module::instance($this->cm->id);
        require_capability('mod/wiki:managewiki', $context);
    }

    public function process($action): void {
        global $PAGE;

        if ($action) {
            $this->action = $action;
        } else {
            $this->action = 'list';
        }

        $this->load_pages();

        if ($this->action === 'moveup' || $this->action === 'movedown' || $this->action === 'moveto') {
            $pageid = required_param('pageid', PARAM_INT);
            require_sesskey();

            if (!isset($this->pages[$pageid])) {
                throw new \moodle_exception('invalidpageid', 'local_wikiexport');
            }
            $page = $this->pages[$pageid];

            if ($this->action === 'moveto') {
                $newpos = required_param('position', PARAM_INT);
            } else if ($this->action === 'moveup') {
                $newpos = $page->sortorder - 1;
            } else { // Move down.
                $newpos = $page->sortorder + 1;
            }
            $this->move_page_to($page, $newpos);

            if (AJAX_SCRIPT) {
                $result = (object)[
                    'error' => 0,
                    'order' => $this->get_new_pageorder(),
                ];
                echo json_encode($result);
                die();

            }
            redirect($PAGE->url);

        } else if ($this->action !== 'list') {
            throw new \moodle_exception('invalidaction', 'local_wikiexport');
        }
    }

    public function output(): string {
        global $OUTPUT, $PAGE;

        $opts = ['cmid' => $this->cm->id, 'groupid' => $this->groupid, 'userid' => $this->userid, 'sesskey' => sesskey()];
        $PAGE->requires->yui_module('moodle-local_wikiexport-sortpages', 'M.local_wikiexport.sortpages.init',
                                    [$opts], null, true);

        $upicon = $OUTPUT->pix_icon('t/up', get_string('moveup'));
        $downicon = $OUTPUT->pix_icon('t/down', get_string('movedown'));
        $spacericon = $OUTPUT->pix_icon('spacer', '');
        $moveicon = $OUTPUT->pix_icon('i/move_2d', get_string('move'));

        $intro = \html_writer::tag('p', get_string('sortpagesintro', 'local_wikiexport'));

        $list = '';
        $lastpage = end($this->pages);
        foreach ($this->pages as $page) {
            $item = '';

            $nojsicons = '';
            if ($page->sortorder != 0) { // Cannot move the first page.
                $baseurl = new \moodle_url($PAGE->url, ['pageid' => $page->id, 'sesskey' => sesskey()]);
                if ($page->sortorder > 1) {
                    $url = new \moodle_url($baseurl, ['action' => 'moveup']);
                    $nojsicons .= \html_writer::link($url, $upicon);
                } else {
                    $nojsicons .= $spacericon;
                }
                if ($page->sortorder < $lastpage->sortorder) {
                    $url = new \moodle_url($baseurl, ['action' => 'movedown']);
                    $nojsicons .= \html_writer::link($url, $downicon);
                } else {
                    $nojsicons .= $spacericon;
                }

                $jsicons = $moveicon;
            } else {
                $nojsicons = $spacericon.$spacericon;
                $jsicons = $spacericon;
            }

            $item .= \html_writer::span($nojsicons, 'nojsicons');
            $item .= \html_writer::span($jsicons, 'jsicons');

            $title = shorten_text(format_string($page->title), 100);
            $item .= \html_writer::span($title, 'wikititle');

            $attrib = ['id' => 'wikipageid-'.$page->id, 'class' => 'sortorder-'.$page->sortorder];
            if ($page->sortorder == 0) {
                $attrib['class'] .= ' nomove';
            }
            $list .= \html_writer::tag('li', $item, $attrib);
        }
        $list = \html_writer::tag('ul', $list, ['class' => 'wiki-sortpages', 'id' => 'wiki-sortpages']);
        $spinner = \html_writer::div('&nbsp;', 'wiki-sortpages-spinner', ['id' => 'wiki-sortpages-spinner']);

        return $intro.$spinner.$list;
    }

    /**
     * Load the wiki pages, making sure the sortorder is set for each of them.
     * Pages will be sorted in the correct order and indexed by the page id.
     */
    protected function load_pages(): void {
        global $DB;

        // Load the pages into memory.
        $subwiki = $DB->get_record('wiki_subwikis', [
            'wikiid' => $this->wiki->id, 'groupid' => $this->groupid,
            'userid' => $this->userid,
        ]);
        if (!$subwiki) {
            return;
        }
        $sql = "SELECT p.id, p.title, xo.sortorder, xo.id AS orderid
                  FROM {wiki_pages} p
                  LEFT JOIN {local_wikiexport_order} xo ON xo.pageid = p.id
                 WHERE p.subwikiid = :subwikiid
                 ORDER BY xo.sortorder, p.title";
        $params = ['subwikiid' => $subwiki->id];
        $this->pages = $DB->get_records_sql($sql, $params);
        foreach ($this->pages as $id => $page) {
            if ($page->title === $this->wiki->firstpagetitle) {
                unset($this->pages[$id]);
                $this->pages = [$id => $page] + $this->pages; // Move the first page to the start of the list.
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

    protected function move_page_to($movepage, $newpos): void {
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
            $DB->set_field('local_wikiexport_order', 'sortorder', $page->sortorder, ['id' => $page->orderid]);
        } else {
            $ins = (object)[
                'cmid' => $this->cm->id,
                'courseid' => $this->cm->course,
                'pageid' => $page->id,
                'sortorder' => $page->sortorder,
            ];
            $page->orderid = $DB->insert_record('local_wikiexport_order', $ins);
        }
    }

    protected function get_new_pageorder() {
        $pageorder = [];
        foreach ($this->pages as $page) {
            $pageorder[$page->id] = $page->sortorder;
        }
        return $pageorder;
    }
}
