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

/**
 * Insert the 'Export as epub' and 'Export as PDF' links into the navigation.
 *
 * @param navigation_node $settingsnav
 */
function local_wikiexport_extend_settings_navigation(navigation_node $settingsnav) {
    global $PAGE, $DB, $USER;

    if (!$PAGE->cm || $PAGE->cm->modname !== 'wiki') {
        return;
    }
    $groupid = groups_get_activity_group($PAGE->cm);
    $userid = 0;
    $wiki = $DB->get_record('wiki', array('id' => $PAGE->cm->instance), '*', MUST_EXIST);
    if ($wiki->wikimode === 'individual') {
        $userid = $USER->id;
        if ($uid = optional_param('uid', null, PARAM_INT)) {
            $userid = $uid;
        } else if ($pageid = optional_param('pageid', null, PARAM_INT)) {
            $page = $DB->get_record('wiki_pages', array('id' => $pageid), 'id, subwikiid', MUST_EXIST);
            $subwiki = $DB->get_record('wiki_subwikis', array('id' => $page->subwikiid), 'id, userid', MUST_EXIST);
            $userid = $subwiki->userid;
        }
    }
    if (!$links = \local_wikiexport\export::get_links($PAGE->cm, $userid, $groupid)) {
        return;
    }
    $modulesettings = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
    if (!$modulesettings) {
        $modulesettings = $settingsnav->add(
            get_string('pluginadministration', 'mod_wiki'), null,
            navigation_node::TYPE_SETTING, null, 'modulesettings');
    }

    foreach ($links as $name => $url) {
        $modulesettings->add($name, $url, navigation_node::TYPE_SETTING);
    }
}
