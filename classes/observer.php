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
 * Handle any relevant events.
 *
 * @package   local_wikiexport
 * @copyright 2016 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wikiexport;

defined('MOODLE_INTERNAL') || die();

class observer {
    /**
     * Clean up any local_wikiexport_order records associated with the deleted course.
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;
        $DB->delete_records('local_wikiexport_order', ['courseid' => $event->courseid]);
    }

    /**
     * Clean up any local_wikiexport_order records associated with the deleted wiki.
     * @param \core\event\course_module_deleted $event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        global $DB;
        if ($event->other['modulename'] != 'wiki') {
            return; // Nothing to do if it is not a wiki which was deleted.
        }
        $DB->delete_records('local_wikiexport_order', ['cmid' => $event->contextinstanceid]);
    }
}
