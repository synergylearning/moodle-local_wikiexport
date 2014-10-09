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
 * Global settings
 *
 * @package   local_wikiexport
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    if (!$page = $ADMIN->locate('modsettingwiki')) {
        // No settings page exists for the wiki - add it.
        $wikiname = get_string('pluginname', 'wiki');
        $page = new admin_settingpage('modsettingwiki', $wikiname);

        // Insert the new wiki settings page in the correct alphabetical order.
        $beforesibling = null;
        $modules = $ADMIN->locate('modsettings');
        foreach ($modules->children as $module) {
            if (strcmp($module->visiblename, $wikiname) > 0) {
                $beforesibling = $module->name;
                break;
            }
        }
        $ADMIN->add('modsettings', $page, $beforesibling);
    }

    $page->add(new admin_setting_configtext('local_wikiexport/publishemail', get_string('publishemail', 'local_wikiexport'),
                                            get_string('publishemail_desc', 'local_wikiexport'), '', PARAM_EMAIL));
}