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

use report_analytics\report_posts;
use report_analytics\user_filters;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: report_posts.php
 *
 * The following functions are checked:
 * 1) get_posts_by_user()
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_posts_class_testcase extends report_analytics_testcase {

    /**
     * Setup a standard test and add the events data as well.
     *
     */
    protected function setUp(): void {

        parent::setUp();
        $this->create_forums();
    }

    /**
     * This function tests the get_posts_by_user() function. Iniitally, only a user
     * filter is used, but subsequent tests add the other valid filters.
     */
    public function test_get_posts_by_user() {

        $this->setAdminUser();

        $filters = new user_filters();
        $student = $this->users[0]->id;
        $rp = new report_posts($this->courseid, $filters);

        // Test 1: user filter (required).
        $postlist = $rp->get_posts_by_user($student);
        $this->assertEquals(count($postlist->forums), self::NUMBER_OF_FORUMS);
        foreach ($postlist->posts as $post) {
            $this->assertFalse((bool)empty($post));
            $this->assertEquals(count($post), 2);
        }

        // Test 2: date filter.
        // 2a: dates set to be in the future.
        $start = new DateTime('tomorrow');
        $end = new DateTime('tomorrow + 1 day');
        $filters->date = new stdClass();
        $filters->date->from = $start->format('Y-m-d H:i');
        $filters->date->to = $end->format('Y-m-d H:i');
        $rp = new report_posts($this->courseid, $filters);
        $postlist = $rp->get_posts_by_user($student);
        // Expected: returns both forums as objects, but list of posts is empty (dates are in future).
        $this->assertEquals(count($postlist->forums), self::NUMBER_OF_FORUMS);
        foreach ($postlist->posts as $post) {
            $this->assertTrue((bool)empty($post));
        }
        // 2b: dates that include the past.
        $start = new DateTime('yesterday');
        $filters->date = new stdClass();
        $filters->date->from = $start->format('Y-m-d H:i');
        $filters->date->to = $end->format('Y-m-d H:i');
        $rp = new report_posts($this->courseid, $filters);
        $postlist = $rp->get_posts_by_user($student);
        // Expected: returns both forums as objects and a post for each forum.
        $this->assertEquals(count($postlist->forums), self::NUMBER_OF_FORUMS);
        foreach ($postlist->posts as $post) {
            $this->assertFalse((bool)empty($post));
            $this->assertEquals(count($post), 2);
        }

        // Test 3: activity filter.
        $filters->activities = array($this->forums[0]->cmid);
        $rp = new report_posts($this->courseid, $filters);
        $postlist = $rp->get_posts_by_user($student);
        $this->assertEquals(count($postlist->forums), 1);
        foreach ($postlist->posts as $post) {
            $this->assertFalse((bool)empty($post));
            $this->assertEquals(count($post), 2);
        }

        // Test 4: word count filter.
        $filters->activities = null;
        $filters->words = new stdClass();
        $filters->words->minwords = 0;
        $filters->words->maxwords = 5;
        $rp = new report_posts($this->courseid, $filters);
        $postlist = $rp->get_posts_by_user($student);
        $this->assertEquals(count($postlist->forums), self::NUMBER_OF_FORUMS);
        foreach ($postlist->posts as $post) {
            $this->assertFalse((bool)empty($post));
            $this->assertEquals(count($post), 1);
        }

        // Test 5: all filters.
        $filters->activities = array($this->forums[0]->cmid);
        $rp = new report_posts($this->courseid, $filters);
        $postlist = $rp->get_posts_by_user($student);
        $this->assertEquals(count($postlist->forums), 1);
        foreach ($postlist->posts as $post) {
            $this->assertFalse((bool)empty($post));
            $this->assertEquals(count($post), 1);
        }
    }

}
