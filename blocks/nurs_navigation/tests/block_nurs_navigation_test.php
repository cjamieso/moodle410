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
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/nurs_navigation/block_nurs_navigation.php');
require_once($CFG->dirroot . '/blocks/nurs_navigation/tests/nurs_navigation_unit_test.class.php');

/**
 * This is the unittest class for block_nurs_navigation.php.
 *
 * @package    block_nurs_navigation
 * @group      block_nurs_navigation_tests
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_nursnavigationblock extends nurs_navigation_unit_test{

    /**
     * This method tests the code that the block loads with basic content (activity types, but
     * does not load any section based content.  Both roles are tested to ensure that they can
     * see the content.
     *
     */
    public function test_block() {
        global $COURSE;

        // This appears to be my only way to hard switch the course.
        $COURSE->id = $this->testcourseid;
        // Generate mock object to test with that overrides no methods.
        $mockblock = $this->getMockBuilder('block_nurs_navigation')->setMethods(null)->getMock();
        // Match block up with that is created for the tests.
        $mockblock->_load_instance($this->blockinstance, null);

        // Admin users should see the activity link, but not the section.
        $this->setAdminUser();
        $this->assertTag(array('tag' => 'img', 'attributes' => array('alt' => get_string('quiztitle', 'block_nurs_navigation'))),
                         $mockblock->refresh_content()->footer);
        $this->assertNotTag(array('tag' => 'img', 'attributes' => array('alt' => self::SECTION1_NAME)),
                            $mockblock->refresh_content()->footer);

        // Students should see the activity link, but not the section.
        $this->setUser($this->student);
        $this->assertTag(array('tag' => 'img', 'attributes' => array('alt' => get_string('quiztitle', 'block_nurs_navigation'))),
                         $mockblock->refresh_content()->footer);
        $this->assertNotTag(array('tag' => 'img', 'attributes' => array('alt' => self::SECTION5_NAME)),
                            $mockblock->refresh_content()->footer);
    }
}