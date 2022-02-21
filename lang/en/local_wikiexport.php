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
 * Language strings
 *
 * @package   local_wikiexport
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['created'] = 'Created on {$a}';
$string['export'] = 'Export';
$string['exportepub'] = 'Export as epub';
$string['exportformheader'] = 'Export pages with the following tags';
$string['exportpdf'] = 'Export as PDF';
$string['exportwithtags'] = 'Export wiki with tags';
$string['failedinsertimage'] = 'Failed to insert image: {$a}';
$string['filename'] = 'Export {$a->wikiname} {$a->timestamp}';
$string['modified'] = 'Last modified by {$a->modifiedby} on {$a->timemodified}';
$string['pluginname'] = 'Wiki export';
$string['printed'] = 'This document was downloaded on {$a}';
$string['privacy:metadata'] = 'The Wikiexport plugin does not store any personal data.';
$string['publishemail'] = 'Auto-publish email';
$string['publishemail_desc'] = 'The email address that PDFs will be sent to automatically whenever a wiki changes';
$string['publishername'] = 'Unknown';
$string['returntowiki'] = 'Return to wiki';
$string['sortpages'] = 'Sort pages for export';
$string['sortpagesintro'] = 'This is the order in which pages will currently be exported - please move pages into the order you would like them to be exported';
$string['wikiexport:exportepub'] = 'Export wiki as epub';
$string['wikiexport:exportpdf'] = 'Export wiki as PDF';
$string['wikiexportfailed'] = 'Export of wiki \'{$a}\' failed';
$string['wikiexportfailed_body'] = 'The wiki \'{$a->name}\' has been updated, but the attempt to export and email it has failed, after {$a->exportattempts} attempts. If the wiki is updated again, then further attempts will be made to export it.

The wiki can be found at: {$a->url}.';
$string['wikiupdated'] = 'Wiki \'{$a}\' updated';
$string['wikiupdated_body'] = 'Updated export attached';
