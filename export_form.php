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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot.'/mod/wikifilter/lib.php');

/**
 * Wikiexport form class.
 *
 * @package   local_wikiexport
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2022 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $customdata = $this->_customdata;

        // Adding export form fieldset.
        $mform->addElement('header', 'exportformheader', get_string('exportformheader', 'local_wikiexport'));

        // Adding tags checkboxes.
        $this->add_checkbox_controller(1);
        foreach ($customdata->wikitags as $key => $value) {
            $mform->addElement('advcheckbox', $key, $value, null, array('group' => 1));
            $mform->setDefault($key, 1);
        }

        // Adding action buttons.
        $this->add_action_buttons(false, get_string('export', 'local_wikiexport'));
    }
}
