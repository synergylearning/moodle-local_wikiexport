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
 * Main entry point for export
 *
 * @package   local_wikiexport
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $DB, $USER, $PAGE;
require_once($CFG->dirroot.'/local/wikiexport/lib.php');
require_once($CFG->dirroot.'/local/wikiexport/export_form.php');

$cmid = required_param('id', PARAM_INT);
$exporttype = required_param('type', PARAM_ALPHA);
$groupid = optional_param('groupid', 0, PARAM_INT);

$user = null;
$cm = get_coursemodule_from_id('wiki', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$wiki = $DB->get_record('wiki', array('id' => $cm->instance), '*', MUST_EXIST);
if ($wiki->wikimode == 'individual') {
    $userid = required_param('userid', PARAM_INT);
    if ($userid == $USER->id) {
        $user = $USER;
    } else {
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
    }
}
$group = null;
if ($groupid && $cm->groupmode != NOGROUPS) {
    $group = $DB->get_record('groups', array('id' => $groupid, 'courseid' => $course->id), '*', MUST_EXIST);
}

$url = new moodle_url('/local/wikiexport/export.php', array('id' => $cm->id, 'type' => $exporttype));
if ($user) {
    $url->param('userid', $user->id);
}
if ($group) {
    $url->param('groupid', $group->id);
}

$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);
$PAGE->set_url($url);
$PAGE->set_title(format_string(get_string('exportwithtags', 'local_wikiexport')));
$PAGE->set_heading(format_string($course->fullname));

require_login($course, false, $cm);

// Get wiki pages tags.
$wikipagestags = get_wiki_pages_tags($wiki->id, $course->id);

if (!empty($wikipagestags)) {
    // Instantiate export_form.
    $wikiurl = new moodle_url('/mod/wiki/view.php', array('id' => $cm->id));
    $customdata = new stdClass();
    $customdata->wikitags = $wikipagestags;
    $customdata->wikiurl = $wikiurl;
    $mform = new export_form($url, $customdata);

    // Form processing.
    if ($formdata = $mform->get_data()) {

        $selectedtags = array();
        foreach (array_keys($wikipagestags) as $tag) {
            if ($formdata->$tag) {
                array_push($selectedtags, $tag);
            }
        }

        $export = new local_wikiexport($cm, $wiki, $exporttype, $user, $group, $selectedtags);
        $export->check_access();
        $export->export();
    }

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
} else {
    $export = new local_wikiexport($cm, $wiki, $exporttype, $user, $group);
    $export->check_access();
    $export->export();
}
