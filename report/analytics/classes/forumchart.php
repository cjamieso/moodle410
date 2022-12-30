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
 * forumchart class
 *
 * This class is used for the forum engagement graph and filters.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumchart extends activitychart{

    /**
     * Change the defaults to be forum-specific.
     *
     * @param  int    $courseid  the ID of the course to work with
     * @param  array  $options   options given to create the chart
     */
    public function __construct($courseid, $options = array()) {
        $this->optionsdefaults['modtypes'] = 'forum';
        parent::__construct($courseid, $options);
    }

    /**
     * Returns info about the chart, including:
     * -The ID tag to use when drawing
     * -The name to use in the chart selector
     * -It's type
     *
     * @return array  array containing graph information
     */
    public function get_chart_info() {
        $info = parent::get_chart_info();
        $info['value'] = 'ForumChart';
        $info['sort'] = 3;
        return $info;
    }

}
