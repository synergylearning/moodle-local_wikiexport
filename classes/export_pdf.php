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
 * Export Wiki as a PDF
 *
 * @package   local_wikiexport
 * @copyright 2023 Synergy Learning
 * @author    Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wikiexport;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/pdflib.php');

class export_pdf extends \pdf {
    protected $directimageload = false;
    protected $restricttocontext = false;

    public function use_direct_image_load($restricttocontext = false) {
        $this->directimageload = true;
        $this->restricttocontext = $restricttocontext;
    }

    /**
     * Override the existing function to:
     * a) Convert any spaces in filenames into '%20' (as TCPDF seems to incorrectly do the opposite).
     * b) Make any broken file errors non-fatal (replace the image with an error message).
     *
     * @param $file
     * @param string $x
     * @param string $y
     * @param int $w
     * @param int $h
     * @param string $type
     * @param string $link
     * @param string $align
     * @param bool $resize
     * @param int $dpi
     * @param string $palign
     * @param bool $ismask
     * @param bool $imgmask
     * @param int $border
     * @param bool $fitbox
     * @param bool $hidden
     * @param bool $fitonpage
     * @param bool $alt
     * @param array $altimgs
     */
    public function Image($file, $x = '', $y = '', $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false,
                          $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false,
                          $fitonpage = false, $alt = false, $altimgs = []) {
        if ($this->directimageload) {
            // Get the image data directly from the Moodle files API (needed when generating within cron, instead of downloading).
            $file = $this->get_image_data($file);
        } else {
            // Make sure the filename part of the URL is urlencoded (convert spaces => %20, etc.).
            if (strpos('pluginfile.php', $file) !== false) {
                $urlparts = explode('/', $file);
                $filename = array_pop($urlparts); // Get just the part at the end.
                $filename = rawurldecode($filename); // Decode => make sure the URL isn't double-encoded.
                $filename = rawurlencode($filename);
                $urlparts[] = $filename;
                $file = implode('/', $urlparts);
            }
        }
        try {
            parent::Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border,
                          $fitbox, $hidden, $fitonpage, $alt, $altimgs);
        } catch (\Exception $e) {
            $this->writeHTML(get_string('failedinsertimage', 'local_wikiexport', $file));
        }
    }

    public function Header() {
        // No header.
    }

    public function Footer() {
        // No footer.
    }

    /**
     * Copy the image data from the Moodle files API and return it directly.
     *
     * @param $fileurl
     * @return string either the original fileurl param or the file content with '@' appended to the start.
     */
    protected function get_image_data($fileurl) {
        if ($file = helper::get_image_file($fileurl, $this->restricttocontext)) {
            $fileurl = '@'.$file->get_content();
        }
        return $fileurl;
    }

    /**
     * Override the existing function to create anchor destinations for any '<a name="x">' tags.
     *
     * @param $dom
     * @param $key
     * @param $cell
     * @return mixed
     */
    protected function openHTMLTagHandler($dom, $key, $cell) {
        $tag = $dom[$key];
        if (array_key_exists('name', $tag['attribute'])) {
            $this->setDestination($tag['attribute']['name']); // Store the destination for TOC links.
        }
        return parent::openHTMLTagHandler($dom, $key, $cell);
    }
}
