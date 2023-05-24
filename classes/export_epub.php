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
 * Export to ePub file
 *
 * @package   local_wikiexport
 * @copyright 2023 Synergy Learning
 * @author    Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wikiexport;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/local/wikiexport/luciepub/LuciEPUB.php');

class export_epub extends \LuciEPUB {
    public function add_html($html, $title, $config) {
        if ($config['tidy'] && class_exists('tidy')) {
            $tidy = new \tidy();
            $tidy->parseString($html, [], 'utf8');
            $tidy->cleanRepair();
            $html = $tidy->html()->value;
        }

        // Handle <img> tags.
        if (preg_match_all('~(<img [^>]*?)src=([\'"])(.+?)[\'"]~', $html, $matches)) {
            foreach ($matches[3] as $imageurl) {
                if ($file = helper::get_image_file($imageurl)) {
                    $newpath = implode('/', [
                        'images', $file->get_contextid(), $file->get_component(), $file->get_filearea(),
                        $file->get_itemid(), $file->get_filepath(), $file->get_filename(),
                    ]);
                    $newpath = str_replace(['///', '//'], '/', $newpath);
                    $this->add_item_file($file->get_content_file_handle(), $file->get_mimetype(), $newpath);
                    $html = str_replace($imageurl, $newpath, $html);
                }
            }
        }

        // Set the href value, if specified.
        $href = null;
        if (!empty($config['href'])) {
            $href = $config['href'];
        }
        $this->add_spine_item($html, $href);
        if ($config['toc']) {
            $this->set_item_toc($title, true);
        }

        return $title;
    }

    public function add_spine_item($data, $href = null,
                                   $fallback = null, $properties = null) {
        if (strpos('<html', $data) === false) {
            $data = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
    <head>
    </head>
    <body>
    '.$data.'
    </body>
</html>';
        }

        return parent::add_spine_item($data, $href, $fallback, $properties);
    }
}
