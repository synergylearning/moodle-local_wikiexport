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
 * Information about the export
 *
 * @package   local_wikiexport
 * @copyright 2023 Synergy Learning
 * @author    Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wikiexport;

class info {
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
                $sql = \core_user\fields::for_name()->get_sql();
                $fields = 'id'.$sql->selects;
                $this->modifiedby = $DB->get_record('user', ['id' => $this->modifiedbyid], $fields);
            }
        }
        if (!$this->modifiedby) {
            return '';
        }
        return fullname($this->modifiedby);
    }
}
