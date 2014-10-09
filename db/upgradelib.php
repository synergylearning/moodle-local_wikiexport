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
 * Library to help with upgrades
 *
 * @package   local_wikiexport
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function local_wikiexport_add_wiki_db_fields() {
    global $DB;
    $dbman = $DB->get_manager();

    // Add 'sortorder' field to all wiki pages.
    $table = new xmldb_table('wiki_pages');
    $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
}
