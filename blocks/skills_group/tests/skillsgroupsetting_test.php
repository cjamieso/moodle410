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
require_once($CFG->dirroot.'/blocks/skills_group/tests/skillsgroupunittest.php');

/**
 * This is the unittest class for skills_group_setting.class.php.
 *
 * update_record() new | existing
 * exists()
 * get_feedback_id()
 * get_feedback_name()
 * get_grouping_id()
 * get_grouping_name()
 * get_group_size()
 *
 * @package    block_skills_group
 * @group      block_skills_group_tests
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_skillsgroupsetting extends skills_group_unit_test {

    /**
     * This function tests update_record first, since this is how a record
     * is created. If this test fails, all subsequent results are suspect.
     *
     */
    public function test_update_record() {
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);
        $settings = $this->get_skills_group_settings();

        // Test new record creation.
        $sgs->update_record($settings);
        $this->check_settings_record($this->courseid, $settings);

        // Now toggle fields.
        $settings->prefeedbackid = 44;
        $sgs->update_record($settings);
        $this->check_settings_record($this->courseid, $settings);

        $settings = $this->get_skills_group_settings();
        $settings->groupings = 55;
        $sgs->update_record($settings);
        $this->check_settings_record($this->courseid, $settings);

        $settings = $this->get_skills_group_settings();
        $settings->maxsize = 66;
        $sgs->update_record($settings);
        $this->check_settings_record($this->courseid, $settings);

        $settings->threshold = 500;
        $sgs->update_record($settings);
        $this->check_settings_record($this->courseid, $settings);

        $settings->threshold = 1;
        $settings->datecheck = 1;
        $settings->date = time();
        $sgs->update_record($settings);
        $this->check_settings_record($this->courseid, $settings);

        $settings->allownaming = 0;
        $sgs->update_record($settings);
        $this->check_settings_record($this->courseid, $settings);

        $settings->instructorgroups = 1;
        $sgs->update_record($settings);
        $this->check_settings_record($this->courseid, $settings);

        $settings->allowadding = 0;
        $sgs->update_record($settings);
        $this->check_settings_record($this->courseid, $settings);

        $settings->allowgroupview = 0;
        $sgs->update_record($settings);
        $this->check_settings_record($this->courseid, $settings);
    }

    /**
     * This function tests the exists() method.  exists() returns T/F depending
     * on if the settings entry has been created.
     *
     */
    public function tests_exists() {

        $sgs = new \block_skills_group\skills_group_setting($this->courseid);
        $this->assertFalse($sgs->exists());
        $sgs->update_record($this->get_skills_group_settings());
        $this->assertTrue($sgs->exists());
    }

    /**
     * This function tests to see that the feedback ID gets correctly stored and retrieved
     * in the class.
     *
     */
    public function test_get_feedback_id() {

        $sgs = new \block_skills_group\skills_group_setting($this->courseid);

        // Test retrieval of feedback ID.
        $sgs->update_record($this->get_skills_group_settings());
        $this->assertEquals($sgs->get_feedback_id('pre'), $this->feedbackids[0]);
        $this->assertEquals($sgs->get_feedback_id('post'), $this->feedbackids[1]);
    }

    /**
     * This function tests to see that the feedback name gets correctly stored and retrieved
     * in the class.
     *
     */
    public function test_get_feedback_name() {

        $sgs = new \block_skills_group\skills_group_setting($this->courseid);

        // Test retrieval of feedback name.
        $sgs->update_record($this->get_skills_group_settings());
        $this->assertEquals($sgs->get_feedback_name('pre'), $this->feedbacknames[0]);
        $this->assertEquals($sgs->get_feedback_name('post'), $this->feedbacknames[1]);
    }

    /**
     * This function tests to see that choices on the feedback questions are parsed correctly.
     */
    public function test_get_feedback_levels() {
        global $DB;

        $this->configure_settings();
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);
        $this->assertEquals($sgs->get_feedback_levels(), array(0, 1, 2, 3, 4));

        $items = $DB->get_records('feedback_item', array('feedback' => $this->feedbackids[0]));
        foreach ($items as $item) {
            $item->presentation = 'r>>>>>1|2|3|4|5';
            $DB->update_record('feedback_item', $item);
        }
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);
        $this->assertEquals($sgs->get_feedback_levels(), array(1, 2, 3, 4, 5));
    }

    /**
     * This function tests to see that the feedback offset (for choices) is parsed correctly.
     */
    public function test_get_feedback_offset() {
        global $DB;

        $this->configure_settings();
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);
        $this->assertEquals($sgs->get_feedback_offset(), 1);

        $items = $DB->get_records('feedback_item', array('feedback' => $this->feedbackids[0]));
        foreach ($items as $item) {
            $item->presentation = 'r>>>>>0|1|2|3|4';
            $DB->update_record('feedback_item', $item);
        }
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);
        $this->assertEquals($sgs->get_feedback_offset(), 0);
    }

    /**
     * This function tests to see that the feedback items (questions) are parsed correctly to
     * create a filter.
     */
    public function test_get_feedback_items() {

        $this->configure_settings();
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);
        $this->assertEquals($sgs->get_feedback_items(), array(array('Questions' => array('1' => 'skill 1?', '2' => 'skill 2?',
            '3' => 'skill 3?'))));
    }

    /**
     * This function tests to see that the grouping ID gets correctly stored and retrieved
     * in the class.
     *
     */
    public function test_get_grouping_id() {

        $sgs = new \block_skills_group\skills_group_setting($this->courseid);

        // Test retrieval of grouping ID.
        $sgs->update_record($this->get_skills_group_settings());
        $this->assertEquals($sgs->get_grouping_id(), $this->groupingid);
    }

    /**
     * This function tests to see that the grouping name gets correctly stored and retrieved
     * in the class.
     *
     */
    public function test_get_grouping_name() {

        $sgs = new \block_skills_group\skills_group_setting($this->courseid);

        // Test new record creation.
        $sgs->update_record($this->get_skills_group_settings());
        $this->assertEquals($sgs->get_grouping_name(), $this->groupingname);
    }

    /**
     * This function tests to see that the max group size gets correctly stored and retrieved
     * in the class.
     *
     */
    public function test_get_group_size() {

        $sgs = new \block_skills_group\skills_group_setting($this->courseid);

        // Test max group size setting.
        $sgs->update_record($this->get_skills_group_settings());
        $this->assertEquals($sgs->get_group_size(), self::MAXGROUPSIZE);
    }

    /**
     * This function tests to see that the threshold gets correctly stored and retrieved
     * in the class.
     */
    public function test_get_threshold() {
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);

        // Test threshold setting (defaults to 1).
        $sgs->update_record($this->get_skills_group_settings());
        $this->assertEquals($sgs->get_threshold(), 1);
    }

    /**
     * This function tests to see that the instructor groups flag gets correctly stored and
     * retrieved in the class.
     */
    public function test_get_instructorgroups() {
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);

        // Test instructor groups setting (defaults to 0).
        $sgs->update_record($this->get_skills_group_settings());
        $this->assertEquals($sgs->get_instructorgroups(), 0);
    }

    /**
     * This function tests to see that the allow addition of group members flag gets correctly
     * stored and retrieved in the class.
     */
    public function test_get_allowadding() {
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);

        // Test instructor groups setting (defaults to 1).
        $sgs->update_record($this->get_skills_group_settings());
        $this->assertEquals($sgs->get_allowadding(), 1);
    }

    /**
     * This function tests to see that the group view flag gets correctly stored and
     * retrieved in the class.
     */
    public function test_get_allowgroupviews() {
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);

        // Test instructor groups setting (defaults to 1).
        $sgs->update_record($this->get_skills_group_settings());
        $this->assertEquals($sgs->get_allowgroupview(), 1);
    }

    /**
     * This function tests to see that the date gets correctly stored and retrieved
     * in the class.
     */
    public function test_get_date() {
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);
        $date = time();

        // Test retrieval of date.
        $sgs->update_record($this->get_skills_group_settings(array('date' => $date, 'datecheck' => 1)));
        $this->assertEquals($sgs->get_date(), $date);
    }

    /**
     * This function tests the date_restriction() method.  The method returns T/F depending
     * on if a date restriction exists.
     */
    public function test_date_restriction() {
        $sgs = new \block_skills_group\skills_group_setting($this->courseid);

        $sgs->update_record($this->get_skills_group_settings());
        $this->assertFalse((bool)$sgs->date_restriction());
        $sgs->update_record($this->get_skills_group_settings(array('date' => time(), 'datecheck' => 1)));
        $this->assertTrue((bool)$sgs->date_restriction());
    }

    /**********************************************************************
     * Helper functions are below:
     **********************************************************************/
    /**
     * This function is used to check a series of mail map records.
     *
     * @param int $courseid This is the ID of the course for which we should test
     * @param object $settings Record with all skills_group settings
     */
    private function check_settings_record($courseid, $settings) {
        global $DB;

        $record = $DB->get_record('skills_group_settings', array('courseid' => $courseid));
        $this->assertEquals($record->prefeedbackid, $settings->prefeedbackid);
        $this->assertEquals($record->postfeedbackid, $settings->postfeedbackid);
        $this->assertEquals($record->groupingid, $settings->groupingid);
        $this->assertEquals($record->maxsize, $settings->maxsize);
        $this->assertEquals($record->threshold, $settings->threshold);
        $this->assertEquals($record->date, $settings->date);
        $this->assertEquals((bool)$record->allownaming, $settings->allownaming);
        $this->assertEquals((bool)$record->allowadding, $settings->allowadding);
        $this->assertEquals((bool)$record->allowgroupview, $settings->allowgroupview);
        $this->assertEquals((bool)$record->instructorgroups, $settings->instructorgroups);
    }
}