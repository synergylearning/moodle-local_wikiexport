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
 * Database upgrade steps
 *
 * @package   local_wikiexport
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_wikiexport_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2014102000) {
        // Define table local_wikiexport_order to be created.
        $table = new xmldb_table('local_wikiexport_order');

        // Adding fields to table local_wikiexport_order.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_wikiexport_order.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('cmid', XMLDB_KEY_FOREIGN, array('cmid'), 'course_modules', array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->add_key('pageid', XMLDB_KEY_FOREIGN_UNIQUE, array('pageid'), 'wiki_pages', array('id'));

        // Conditionally launch create table for local_wikiexport_order.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Wikiexport savepoint reached.
        upgrade_plugin_savepoint(true, 2014102000, 'local', 'wikiexport');
    }

    if ($oldversion < 2014102001) {
        // Transfer any existing 'sortorder' fields from wiki_pages to local_wikiexport_order.
        $table = new xmldb_table('wiki_pages');
        $field = new xmldb_field('sortorder');
        if ($dbman->field_exists($table, $field)) {
            mtrace('local_wikiexport: Transferring sortorder fields from wiki_pages to local_wikiexport_order, '.
                   'this may take a while to complete ...');
            $wikimoduleid = $DB->get_field('modules', 'id', array('name' => 'wiki'), MUST_EXIST);

            // Find all the wiki pages that have a sortorder value, where there is not (yet) a local_wikiexport_order entry ...
            $sql = "SELECT p.id AS pageid, p.sortorder, w.course AS courseid, cm.id AS cmid
                      FROM {wiki_pages} p
                      LEFT JOIN {local_wikiexport_order} xo ON xo.pageid = p.id
                      JOIN {wiki_subwikis} sw ON sw.id = p.subwikiid
                      JOIN {wiki} w ON w.id = sw.wikiid
                      JOIN {course_modules} cm ON cm.module = :wikimoduleid AND cm.instance = w.id
                     WHERE p.sortorder IS NOT NULL AND xo.id IS NULL";
            $params = array('wikimoduleid' => $wikimoduleid);
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $order) {
                // ... and insert local_wikiexport_order entries for each of them.
                $DB->insert_record('local_wikiexport_order', $order, false, true);
            }
        }
        upgrade_plugin_savepoint(true, 2014102001, 'local', 'wikiexport');
    }

    if ($oldversion < 2014102004) {
        // Remove the 'sortorder' field added to 'wiki_pages' by previous versions of this plugin.
        $table = new xmldb_table('wiki_pages');
        $field = new xmldb_field('sortorder');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2014102004, 'local', 'wikiexport');
    }

    return true;
}
