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

use report_analytics\activityfilter;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: activityfilter.php
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_analytics_activityfilter_testcase extends report_analytics_testcase {

    /**
     * This function tests the activity filter.
     */
    public function test_get_filter_data() {

        $activityfilter = new activityfilter($this->courseid);

        // Build the expected answers.
        $components = activityfilter::get_all_activity_classes();
        $sections = array();
        for ($i = 1; $i <= self::NUMBER_OF_SECTIONS; $i++) {
            $sections['s' . $i] = get_section_name($this->courseid, $i);
        }
        $activity = array();
        $i = 0;
        foreach ($this->activities as $a) {
            $activity[$a->cmid] = $this->activitynames[$i++];
        }
        // Grab data and check.
        $data = $activityfilter->get_filter_data();
        $this->assertEquals($components, $data[get_string('activitytypes', 'report_analytics')]);
        $this->assertEquals($sections, $data[get_string('sections')]);
        $this->assertEquals($activity, $data[get_string('sectionname', 'format_topics') . ' 1']);
    }

    /**
     * This function tests the default activity retrival function.
     */
    public function test_get_default_activities() {

        $this->create_forums();
        // Test 1: use 'forums' types.
        $activityfilter = new activityfilter($this->courseid, array('types' => 'forum'));
        $actual = $activityfilter->get_default_activities();
        $expected = array();
        foreach ($this->forums as $forum) {
            $expected[] = $forum->cmid;
        }
        sort($actual);
        $this->assertEquals($expected, $actual);

        // Test 2: no type, will return all activity classes.
        $activityfilter = new activityfilter($this->courseid);
        $this->assertEquals(9, count($activityfilter->get_default_activities()));
    }

    /**
     * This function tests the section number retrieval function.  Invalid names return false.
     */
    public function test_get_type() {

        $activityfilter = new activityfilter($this->courseid);

        // Test 1: activity by ID.
        $item = $this->activities[0]->id;
        $this->assertEquals('activity', $activityfilter->get_type($item));

        // Test 2: section by ID.
        $item = 's1';
        $this->assertEquals('section', $activityfilter->get_type($item));

        // Test 3: class of activity.
        $item = 'mod_forum';
        $this->assertEquals('activity_class', $activityfilter->get_type($item));
    }


    /**
     * This function tests the section number retrieval function.  Invalid names throw an exception.
     */
    public function test_get_label() {

        $activityfilter = new activityfilter($this->courseid);

        // Test 1: activity by ID.
        $item = $this->activities[0]->cmid;
        $this->assertEquals($this->activitynames[0], $activityfilter->get_label($item, $this->courseid));

        // Test 2: section by ID.
        $item = 's1';
        $this->assertEquals('Topic 1', $activityfilter->get_label($item, $this->courseid));

        // Test 3: class of activity.
        $item = 'mod_forum';
        $this->assertEquals(get_string('allforums', 'report_analytics'), $activityfilter->get_label($item, $this->courseid));

        // Test 4: invalid request.
        $item = 'invalid';
        $this->expectException('Exception', get_string('namenotfound', 'report_analytics'));
        $activityfilter->get_label($item, $this->courseid);
    }

    /**
     * This function tests the ability to retrieve all mods (by ID) in a section.
     */
    public function test_get_mods_in_section() {
        global $DB;

        $activityfilter = new activityfilter($this->courseid);

        // Test 1: valid request.
        $cmids = $activityfilter->get_mods_in_section(1);
        $expected = array();
        foreach ($this->activities as $activity) {
            $expected[] = $activity->cmid;
        }
        $this->assertEquals($expected, $cmids);

        // Test 2: section with no mods.
        $cmids = $activityfilter->get_mods_in_section(2);
        $this->assertEquals(array(), $cmids);

        // Test 3: default name.
        $cmids = $activityfilter->get_mods_in_section('Topic 1');
        $this->assertEquals($expected, $cmids);

        // Test 4: valid name.
        $name = 'New Section Name';
        $record = $DB->get_record('course_sections', array('course' => $this->courseid, 'section' => 2));
        $record->name = $name;
        $DB->update_record('course_sections', $record);
        $cmids = $activityfilter->get_mods_in_section($name);
        $this->assertEquals(array(), $cmids);

        // Test 5: invalid name.
        $this->expectException('Exception', get_string('sectionnotfound', 'report_analytics'));
        $cmids = $activityfilter->get_mods_in_section('invalid');
    }

    /**
     * This function tests the ability to get all mods of a particular class type
     * (page, quiz, etc).
     */
    public function test_get_mods_of_class() {

        $activityfilter = new activityfilter($this->courseid);

        // Test 1: all pages with names.
        $mods = $activityfilter->get_mods_of_class(get_string('allpages', 'report_analytics'));
        $expected = array();
        foreach ($this->activities as $activity) {
            $expected[$activity->cmid] = $activity->name;
        }
        $this->assertEquals($expected, $mods);

        // Test 2: try all core (no results, cannot further subdivide).
        $this->expectException('Exception', get_string('classnotfound', 'report_analytics'));
        $activityfilter->get_mods_of_class(get_string('allcore', 'report_analytics'));
    }

    /**
     * The funciton tests an invalid request to get_mods_of_class().  An exception
     * will be generated.
     */
    public function test_get_mods_of_class_invalid() {

        $activityfilter = new activityfilter($this->courseid);
        $this->expectException('Exception', get_string('classnotfound', 'report_analytics'));
        $activityfilter->get_mods_of_class('invalid');
    }

}
