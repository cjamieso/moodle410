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

namespace report_analytics\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Analytics report viewed - for logging.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - array courseid: the ID of the course.
 *      - array other: contains information about the graph type being viewed.
 * }
 *
 * @package    report_analytics
 * @since      Moodle 2.8
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analytics_viewed extends \core\event\base {

    /**
     * Event init -> set event parameters.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'logstore_standard_log';
    }

    /**
     * Set name of event - use lang file.
     *
     * @return  string localized name of event
     */
    public static function get_name() {
        return get_string('eventanalyticsviewed', 'report_analytics');
    }

    /**
     * Retrieves description of event
     *
     * @return  string description of event
     */
    public function get_description() {
        $graphtype = (isset($this->other['graphtype'])) ? $this->other['graphtype'] : '';
        return "User {$this->userid} viewed analytics report ({$graphtype}) in course {$this->courseid}.";
    }

    /**
     * Retrieves URL for event so a user can view data.
     *
     * @return  object moodle_url with URL info to generate request
     */
    public function get_url() {
        return new \moodle_url('/report/analytics/index.php',
                               array('id' => $this->courseid));
    }

    /**
     * For legacy logs, this event information will be recorded instead.
     * {courseid, module, action, url, info}
     *
     * @return  array legacy logdate for event
     */
    public function get_legacy_logdata() {
        $graphtype = (isset($this->other['graphtype'])) ? $this->other['graphtype'] : '';
        return array($this->courseid, get_string('pluginname', 'report_analytics'),
                     get_string('eventanalyticsviewed', 'report_analytics'),
                     '/report/analytics/index.php?id=' . $this->courseid,
                     "User {$this->userid} viewed analytics report ({$graphtype}) in course {$this->courseid}.");
    }
}
