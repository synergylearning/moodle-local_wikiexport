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
 * Helper functions
 *
 * @package   local_wikiexport
 * @copyright 2023 Synergy Learning
 * @author    Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wikiexport;

class helper {

    /**
     * Convert an image URL into a stored_file object, if it refers to a local file.
     * @param $fileurl
     * @param \context $restricttocontext (optional) if set, only files from this wiki will be included
     * @return null|\stored_file
     */
    public static function get_image_file($fileurl, $restricttocontext = null) {
        global $CFG;
        if (strpos($fileurl, $CFG->wwwroot.'/pluginfile.php') === false) {
            return null;
        }

        $fs = get_file_storage();
        $params = substr($fileurl, strlen($CFG->wwwroot.'/pluginfile.php'));
        if (substr($params, 0, 1) === '?') { // Slasharguments off.
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
        $filearea = clean_param(array_shift($params), PARAM_AREA);
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
            if ($component !== 'mod_wiki' || $contextid != $restricttocontext->id) {
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
}
