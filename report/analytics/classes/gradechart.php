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
 * gradechart class
 *
 * This class is used for the grade vs. action scatter chart.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradechart extends chart{

    /**
     * Collect and return data needed to update the chart via ajax.
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return array activity data for d3js to graph
     */
    public function ajax_get_data($filters) {

        $userreport = new report_users($this->courseid, $filters);
        return $userreport->get_user_data();
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
        $info['value'] = 'GradeChart';
        $info['sort'] = 7;
        return $info;
    }

    /**
     * Export the chart data into an excel spreadsheet.  Any columns visible in the graph
     * legend are used as the columns.  Row entries begin with the x-axis label.
     *
     * @param  object  $filters  the filters to use to generate the chart
     */
    public function export($filters) {

        $userreport = new report_users($this->courseid, $filters);
        list($userpoints, $eventrange, $graderange) = $userreport->get_user_data();
        $classname = (new \ReflectionClass($this))->getShortName();
        $chartname = get_string($classname . 'name', 'report_analytics');
        $columns = $this->get_columns($userpoints[0]);
        \core\dataformat::download_data(trim($chartname), 'excel', $columns, $userpoints);
    }

    /**
     * Get the header columns for the worksheet.
     *
     * @param  array  $header  any entry in the data array that contains the header columns
     * @return array  a list of header columns for the worksheet
     */
    protected function get_columns($header) {

        $columns = array();
        foreach (array_keys($header) as $h) {
            $columns[$h] = $h;
        }
        return $columns;
    }


}
