<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Unit tests for local_wikiexport lib.
 *
 * @package   local_wikiexport
 * @category  test
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2022 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/wikiexport/lib.php');

/**
 * Class to access local_wikiexport internal methods.
 *
 * @package   local_wikiexport
 * @category  test
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2022 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_local_wikiexport extends local_wikiexport {

    /**
     * Public accessor.
     *
     * @return  array containing wiki pages
     */
    public function load_pages() {
        return parent::load_pages();
    }
}

/**
 * Unit tests for local_wikiexport lib
 *
 * @package   local_wikiexport
 * @category  test
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2022 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wikiexport_lib_test extends advanced_testcase {

    /**
     * Set up for every test
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test test_load_pages.
     *
     * @return void
     */
    public function test_load_pages() {
        global $DB;

        // Create course.
        $course = $this->getDataGenerator()->create_course();

        // Create wiki.
        $wiki = $this->getDataGenerator()->create_module('wiki', array('course' => $course->id, 'wikimode' => 'collaborative'));

        // Create wiki pages.
        $firstpage = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_first_page($wiki);
        $page1 = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_page($wiki, array('title' => 'page 1'));
        $page2 = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_page($wiki, array('title' => 'page 2'));
        $page3 = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_page($wiki, array('title' => 'page 3'));

        // Add tags to wiki pages.
        $context = context_module::instance($wiki->cmid);
        $wikipagestags = array($firstpage->id => array('simulation'),
            $page1->id => array('observation', 'master'),
            $page2->id => array('simulation', 'exam'),
            $page3->id => array('observation', 'zoom')
        );

        $tagsids = array();
        foreach ($wikipagestags as $pageid => $tags) {
            foreach ($tags as $tag) {
                $taginstanceid = core_tag_tag::add_item_tag('mod_wiki', 'wiki_pages', $pageid, $context, $tag);
                $taginstance = $DB->get_record('tag_instance', array('id' => $taginstanceid), '*', MUST_EXIST);
                $tagsids[] = $taginstance->tagid;
            }
        }

        $cm = get_coursemodule_from_instance('wiki', $wiki->id);
        $exporttype = 'pdf';

        // Get wiki pages tagged with "simulation" tag.
        $selectedtags = array($tagsids[0]);
        $export = new testable_local_wikiexport($cm, $wiki, $exporttype, null, null, $selectedtags);
        $pages = $export->load_pages();
        $expectedpages = array($firstpage->id, $page2->id);
        $this->assertEquals($expectedpages, array_keys($pages));

        // Get wiki pages tagged with "observation" tag.
        $selectedtags = array($tagsids[1]);
        $export = new testable_local_wikiexport($cm, $wiki, $exporttype, null, null, $selectedtags);
        $pages = $export->load_pages();
        $expectedpages = array($page1->id, $page3->id);
        $this->assertEquals($expectedpages, array_keys($pages));
    }
}
