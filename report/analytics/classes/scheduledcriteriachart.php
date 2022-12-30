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

namespace report_analytics;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/dataformatlib.php');

/**
 * scheduledcriteriachart class
 *
 * This class is used as a slightly modded completionsearch chart to enable
 * a user to select a few criteria to be checked on a schedule.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduledcriteriachart extends completionsearchchart{

    /**
     * The chart has its sort value set to '0' so that it's not visible in the
     * report selector.
     *
     * @return array  array containing graph information
     */
    public function get_chart_info() {
        if ($this->options['instructor'] === true) {
            $info = parent::get_chart_info();
            $info['value'] = 'ScheduledCriteriaChart';
            $info['sort'] = 0;
            return $info;
        } else {
            return false;
        }
    }

}
