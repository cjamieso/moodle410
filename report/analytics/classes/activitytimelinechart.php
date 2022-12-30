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
 * activitytimelinechart class
 *
 * This class is used for the time based engagement graph and filters.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activitytimelinechart extends chart{

    /**
     * Collect and return data needed to update the chart via ajax.
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return array timeline data for d3js to graph
     */
    public function ajax_get_data($filters) {

        $filters = $this->set_empty_filters_to_default($filters);
        $analyticseport = new \report_analytics\report_activities($this->courseid, $filters);
        return $analyticseport->get_monthly_user_activity_data();
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
        $info['value'] = 'ActivityTimelineChart';
        $info['sort'] = 2;
        return $info;
    }

    /**
     * Export the chart data into an excel spreadsheet.  The legend entries are displayed
     * as the rows, while the timestamps are displayed as the column headers.
     *
     * @param  object  $filters  the filters to use to generate the chart
     */
    public function export($filters) {

        $data = $this->ajax_get_data($filters);

        $classname = (new \ReflectionClass($this))->getShortName();
        $chartname = get_string($classname . 'name', 'report_analytics');
        $flattened = $this->flatten_data($data);
        $columns = $this->get_columns($flattened[0]);
        \core\dataformat::download_data(trim($chartname), 'excel', $columns, $flattened);
    }

    /**
     * Get the header columns for the worksheet.
     *
     * @param  array  $header  any entry in the flattened data array that contains the header columns
     * @return array  a list of header columns for the worksheet
     */
    protected function get_columns($header) {

        $columns = array_combine(array_keys($header), array_keys($header));
        $columns['name'] = get_string('activity');
        return $columns;
    }

    /**
     * Flattens a timeline based data array into one more suitable for an excel
     * export.  The "flattened" array groups elements by their "label" (i.e., activity)
     * and then arranges them in chronological order.
     *
     * @param  array  $data  the non-flattened data used by JS
     * @return array  data usable for an excel export (1 row per activity)
     */
    protected function flatten_data($data) {

        $flattened = array();
        $labels = array();
        foreach ($data as $d) {
            if (array_search($d['label'], $labels) === false) {
                $labels[] = $d['label'];
                $flattened[$d['label']] = array('name' => $d['label']);
            }
            $flattened[$d['label']][$d['date']] = intval($d['count']);
        }
        // Sort by date just to be extra certain.
        foreach ($flattened as $f) {
            ksort($f);
        }
        return array_values($flattened);
    }

}
