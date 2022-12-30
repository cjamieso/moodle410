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

/**
 * recipientfilter class
 *
 * This class retrieves a list of all users with instructor level access in a course.
 * This is determined by the ability to see the instructor version of analytics reports.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recipientfilter extends studentfilter {

    /**
     * Returns instructor filter data.
     *
     * @return array  instructor filter data containing groups and students.
     */
    public function get_filter_data() {

        $context = \context_course::instance($this->courseid);
        return $this->parse_student_objects(get_users_by_capability($context, 'report/analytics:view'));
    }

}
