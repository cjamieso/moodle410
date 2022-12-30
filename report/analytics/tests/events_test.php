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

use report_analytics\event\analytics_viewed;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for events in report_analytics.
 *
 * It's not possible to use the moodle API to simulate the viewing of log report, so here we
 * simply create the event and trigger it.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_analytics_events_testcase extends report_analytics_testcase {

    /**
     * Test the report viewed event.
     */
    public function test_report_viewed() {
        global $USER;

        $context = context_course::instance($this->courseid);
        $graphname = get_string('activitychartname', 'report_analytics');
        $params = array(
            'context' => context_course::instance($this->courseid),
            'objectid' => 1,
            'other' => array('request' => 'get_chart_data', 'graphtype' => $graphname),
            'courseid' => $this->courseid
        );
        $event = analytics_viewed::create($params);
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Test 1: verify event type.
        $this->assertInstanceOf('\report_analytics\event\analytics_viewed', $event);
        // Test 2: test context.
        $this->assertEquals($context, $event->get_context());
        // Test 3: verify legacy logdate string.
        $exptected = array($this->courseid, get_string('pluginname', 'report_analytics'),
                           get_string('eventanalyticsviewed', 'report_analytics'),
                           '/report/analytics/index.php?id=' . $this->courseid,
                           "User {$USER->id} viewed analytics report ({$graphname}) in course {$this->courseid}.");
        $this->assertEventLegacyLogData($exptected, $event);
        // Test 4: verify context not used.
        $this->assertEventContextNotUsed($event);
        // Test 5: verify get_url().
        $url = new moodle_url('/report/analytics/index.php', array('id' => $event->courseid));
        $this->assertEquals($url, $event->get_url());
    }

}
