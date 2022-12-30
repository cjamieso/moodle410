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
 * activitychart class
 *
 * This class is used for the content engagement graph and filters.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activitychart extends chart{

    /**
     * Collect and return data needed to update the chart via ajax.
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return array activity data for d3js to graph
     */
    public function ajax_get_data($filters) {

        $filters = $this->set_empty_filters_to_default($filters);
        $analyticseport = new \report_analytics\report_activities($this->courseid, $filters);
        return $analyticseport->get_events_by_activity();
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
        $info['value'] = 'ActivityChart';
        $info['sort'] = 1;
        return $info;
    }

    /**
     * Export the chart data into an excel spreadsheet.  Any columns visible in the graph
     * legend are used as the columns.  Row entries begin with the x-axis label.
     *
     * @param  object  $filters  the filters to use to generate the chart
     */
    public function export($filters) {

        $data = $this->ajax_get_data($filters);

        $classname = (new \ReflectionClass($this))->getShortName();
        $chartname = get_string($classname . 'name', 'report_analytics');
        $columns = $this->get_columns($data[0]);
        \core\dataformat::download_data(trim($chartname), 'excel', $columns, $data, array($this, 'format_export_data'));
    }

    /**
     * Get the header columns for the worksheet.  These are stored in the "values"
     * field of each entry in the data array.
     *
     * @param  array  $header  any entry in the data array that contains the header columns
     * @return array  a list of header columns for the worksheet
     */
    protected function get_columns($header) {

        $columns = array('name' => get_string('activity'));
        foreach ($header['values'] as $h) {
            $columns[$h->name] = $h->name;
        }
        return $columns;
    }

    /**
     * This method converts the data (normally given to JS) into a format more
     * suitable for an excel export.
     *
     * @param  array  $d  the data for javascript
     * @return array  data usable for an excel export ('columnname' => 'value')
     */
    public static function format_export_data($d) {

        $return = array('name' => $d['label']);
        foreach ($d['values'] as $v) {
            $return[$v->name] = intval($v->value);
        }
        return $return;
    }

}
