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

use report_analytics\ajax_controller;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: ajax_controller.php
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_analytics_ajax_controller_testcase extends report_analytics_testcase {

    /**
     * Test perform_request() when no sesskey given.
     */
    public function test_perform_request_badsesskey_exception() {

        $this->login_user($this->users[0]);
        $_POST = array('courseid' => $this->courseid, 'filters' => '', 'sesskey' => 'badsesskey');
        $controller = new ajax_controller();
        $request = 'get_chart_data';
        $this->expectException('Exception', get_string('badsesskey', 'report_analytics'));
        $controller->perform_request($request);
    }

    /**
     * Test valid requests for perform_request().
     */
    public function test_perform_request() {

        $this->login_admin_user();

        // Test 1: get_chart_data.
        $_POST = array('courseid' => $this->courseid, 'filters' => '', 'sesskey' => sesskey(), 'graphtype' => 'activitychart');
        $controller = new ajax_controller();
        $request = 'get_chart_data';
        ob_start();
        $controller->perform_request($request);
        $this->assertEquals(json_decode(ob_get_contents())->result, true);
        ob_clean();

        // Test 2: add_graph.
        $_POST = array('courseid' => $this->courseid, 'filters' => '', 'sesskey' => sesskey(), 'graphtype' => 'activitychart');
        $request = 'add_graph';
        $controller->perform_request($request);
        $this->assertEquals(json_decode(ob_get_contents())->result, true);
        ob_clean();

        // Test 3: get_mods().
        $_POST = array('courseid' => $this->courseid, 'sesskey' => sesskey(), 'type' => 'section', 'data' => 'Topic 1');
        $request = 'get_mods';
        $controller->perform_request($request);
        $cmids = array();
        foreach ($this->activities as $a) {
            $cmids[] = $a->cmid;
        }
        $this->assertEquals(json_decode(ob_get_contents())->message, $cmids);
        ob_end_clean();
    }

    /**
     * Test the save_criteria() request - returns true on success.
     */
    public function test_perform_request_save_criteria() {

        $this->login_admin_user();
        $_POST = array('courseid' => $this->courseid, 'filters' => 'dummy', 'userids' => 2, 'sesskey' => sesskey());
        $controller = new ajax_controller();
        $request = 'save_criteria';
        ob_start();
        $controller->perform_request($request);
        $this->assertEquals(json_decode(ob_get_contents())->result, true);
        ob_end_clean();
    }

}
