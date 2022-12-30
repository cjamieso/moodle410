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

use report_analytics\scheduled_results_record;
use report_analytics\filter;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: scheduled_results_record.php
 *
 * Test the functionality of the database record wrapper class.  This includes
 * set/get paramaters and saving records.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduled_results_record_class_testcase extends report_analytics_testcase {

    /**
     * Test the save() method: ensure new records are created.
     */
    public function test_save() {
        global $DB;

        $record = new scheduled_results_record($this->courseid);
        $record->set_userids(array('dummy string'));
        $record->save();
        $this->assertEquals($DB->count_records('report_analytics_results'), 1);
    }

    /**
     * Test the exists() method: indicates whether a record currently exists in
     * the database.
     */
    public function test_exists() {

        $record = new scheduled_results_record($this->courseid);
        $this->assertFalse((bool)$record->exists());
        $record->set_userids(array('dummy string'));
        $record->save();
        $this->assertTrue((bool)$record->exists());
    }

    /**
     * Test the set/get methods for the userids parameter.
     */
    public function test_setget_userids() {

        $record = new scheduled_results_record($this->courseid);
        $this->assertEquals($record->get_userids(), array());
        $record->set_userids(array('dummy string'));
        $record->save();
        $this->assertEquals($record->get_userids(), array('dummy string'));
        $record->set_userids('[1, 2, 3]');
        $record->save();
        $this->assertEquals($record->get_userids(), array(1, 2, 3));
    }

    /**
     * Test the set/get methods for the filters parameter.
     */
    public function test_setget_filters() {

        $record = new scheduled_results_record($this->courseid);
        $this->assertEquals($record->get_filters(), array());
        $record->set_filters(array('dummy string'));
        $record->save();
        $this->assertEquals($record->get_filters(), array('dummy string'));
        $record->set_filters('[1, 2, 3]');
        $record->save();
        $this->assertEquals($record->get_filters(), array(1, 2, 3));
    }

    /**
     * Test the set/get methods for the results paramater.
     */
    public function test_setget_results() {

        $record = new scheduled_results_record($this->courseid);
        $this->assertEquals($record->get_results(), array());
        $record->set_results(json_encode(array('dummy string')));
        $record->save();
        $this->assertEquals($record->get_results(), array('dummy string'));
        $record->set_results('["result1", "result2", "result3"]');
        $record->save();
        $this->assertEquals($record->get_results(), array('result1', 'result2', 'result3'));
    }

    /**
     * Test the set/get methods for the results timestamp.
     */
    public function test_setget_results_timestamp() {

        $this->login_admin_user();
        $record = new scheduled_results_record($this->courseid);
        $this->assertEquals(0, $record->get_results_time());
        $now = time();
        $record->set_results_time($now);
        $record->save();
        $this->assertEquals($now, $record->get_results_time());
    }

    /**
     * Test the set/get methods for the email timestamp.
     */
    public function test_setget_email_timestamp() {

        $this->login_admin_user();
        $record = new scheduled_results_record($this->courseid);
        $this->assertEquals(0, $record->get_email_time());
        $now = time();
        $record->set_email_time($now);
        $record->save();
        $this->assertEquals($now, $record->get_email_time());
    }

}
