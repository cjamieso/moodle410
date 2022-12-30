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
global $CFG;
require_once($CFG->dirroot . '/blocks/nurs_navigation/tests/nurs_navigation_unit_test.class.php');

/**
 * This is the unittest class for section_icon.php.
 *
 * @package    block_nurs_navigation
 * @group      block_nurs_navigation_tests
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_activity extends nurs_navigation_unit_test{

    /** @var array Holds the quizzes that are created */
    protected $quizzes = array();
    /** @var array Holds the assigments that are created */
    protected $assignments = array();
    /** Total number of quizzes in course */
    const NUMBER_OF_QUIZZES = 3;
    /** Total number of assignments in course */
    const NUMBER_OF_ASSIGNMENTS = 3;

    /**
     * The setup function is used to place the DB in its initial state.
     *
     */
    protected function setUp(): void {

        $this->resetAfterTest(true);

        parent::setUp();
        $this->create_activity_entries();
    }

    /**
     * This function creates the test data for the various tests below.  The file
     * setup is done on its own in the base class.
     *
     */
    private function create_activity_entries() {
        $courseid = $this->testcourseid;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        for ($i = 0; $i < self::NUMBER_OF_QUIZZES; $i++) {
            $this->quizzes[] = $generator->create_instance(array('course' => $courseid, 'section' => 1));
        }

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        for ($i = 0; $i < self::NUMBER_OF_ASSIGNMENTS; $i++) {
            $this->assignments[] = $generator->create_instance(array('course' => $courseid, 'section' => 1));
        }

    }

    /**
     * This method tests the constructor + exists() method.
     *
     * Records are only created if type is overridden (to save on DB load), so
     * no records will exist initially.
     *
     */
    public function test_constructor() {

        $courseid = $this->testcourseid;

        $activity = new \block_nurs_navigation\activity($courseid, 'quiz', $this->quizzes[0]->cmid);
        $this->assertFalse((bool)$activity->exists());
        $activity = new \block_nurs_navigation\activity($courseid, 'assign', $this->assignments[0]->cmid);
        $this->assertFalse((bool)$activity->exists());
    }

    /**
     * This method tests the type based methods (they all work together):
     * get_type()
     * get_moodle_type()
     * update_type()
     *
     */
    public function test_type() {

        $courseid = $this->testcourseid;

        // Test 1: default record with no type set.
        $activity = new \block_nurs_navigation\activity($courseid, 'quiz', $this->quizzes[0]->cmid);
        $this->assertEquals($activity->get_type(), 'quiz');
        $this->assertEquals($activity->get_moodle_type(), 'quiz');

        // Test 2: overridding will create a record entry (now exists).
        $activity = new \block_nurs_navigation\activity($courseid, 'quiz', $this->quizzes[0]->cmid);
        $activity->update_type('quest');
        $this->assertTrue((bool)$activity->exists());
        $this->assertEquals($activity->get_type(), 'quest');
        $this->assertEquals($activity->get_moodle_type(), 'quiz');

        // Test 3: now reset the type and ensure that it gets deleted.
        $activity = new \block_nurs_navigation\activity($courseid, 'quiz', $this->quizzes[0]->cmid);
        $activity->update_type('quiz');
        $this->assertFalse((bool)$activity->exists());
        $this->assertEquals($activity->get_type(), 'quiz');
        $this->assertEquals($activity->get_moodle_type(), 'quiz');
    }

    /**
     * Test the get_module_id() function to see if the correct ID is returned.
     *
     */
    public function test_get_module_id() {

        $courseid = $this->testcourseid;

        // Test 1: default record with no type set.
        $activity = new \block_nurs_navigation\activity($courseid, 'quiz', $this->quizzes[0]->cmid);
        $this->assertEquals($activity->get_module_id(), $this->quizzes[0]->cmid);

        // Test 2: overridding will create a record entry.
        $activity = new \block_nurs_navigation\activity($courseid, 'quiz', $this->quizzes[0]->cmid);
        $activity->update_type('quest');
        $this->assertEquals($activity->get_module_id(), $this->quizzes[0]->cmid);
    }

}
