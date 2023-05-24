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
 * Test scheduled export of wikis
 *
 * @package   local_wikiexport
 * @copyright 2023 Synergy Learning
 * @author    Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wikiexport;

class export_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest(true);
    }

    public function test_changed_wiki_exported(): void {
        set_config('publishemail', 'wiki@example.com', 'local_wikiexport');
        set_config('lastcron', '1', 'local_wikiexport'); // Otherwise nothing will be exported.

        $gen = self::getDataGenerator();
        /** @var \mod_wiki_generator $wgen */
        $wgen = $gen->get_plugin_generator('mod_wiki');

        self::setAdminUser();
        $c1 = $gen->create_course();
        $w1 = $wgen->create_instance(['course' => $c1->id, 'name' => 'Example wiki']);
        $wgen->create_first_page($w1, [
            'content' => '<p>The content of the first page - with info about [[Cats]], [[Dogs]] and [[Cows]]</p>',
        ]);
        $wgen->create_page($w1, ['title' => 'Cats', 'content' => 'The cat page']);
        $wgen->create_page($w1, ['title' => 'Dogs', 'content' => 'This is the dog page']);
        self::setUser();

        $sink = $this->redirectEmails();
        $task = new \local_wikiexport\task\email_wikis();
        $task->execute();

        $emails = $sink->get_messages();
        $email = array_shift($emails);

        $this->assertEquals('wiki@example.com', $email->to);
        $this->assertEquals('Wiki \'Example wiki\' updated', $email->subject);
        $this->assertStringContainsString('Updated export attached', $email->body);
        $this->assertMatchesRegularExpression('/filename=Export_Example_wiki_.*\.pdf/', $email->body);

        $this->assertEmpty($emails);
    }
}
