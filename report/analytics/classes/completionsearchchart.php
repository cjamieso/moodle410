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
 * completionsearchchart class
 *
 * This class is used for the completion search chart and filters.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completionsearchchart extends chart{

    /**
     * Collect and return a list of users that match the specified criteria.
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return array  the list of users matching the criteria
     */
    public function ajax_get_data($filters) {

        $filters = $this->set_empty_filters_to_default($filters);
        $conditionsreport = new report_conditions($this->courseid, $filters);
        return $conditionsreport->get_users_by_condition();
    }

    /**
     * Returns info about the chart, including:
     * -The ID tag to use when drawing
     * -The name to use in the chart selector
     * -It's type
     * -The request it should use when drawing
     *
     * @return array  array containing graph information
     */
    public function get_chart_info() {
        if ($this->options['instructor'] === true) {
            $info = parent::get_chart_info();
            $info['value'] = 'CompletionSearchChart';
            $info['sort'] = 6;
            return $info;
        } else {
            return false;
        }
    }

    /**
     * Graph title is a simple string in all cases.  The $filters parameter
     * is included to match the signature of the base method.
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return string the title for the graph
     */
    public function get_title($filters) {
        return get_string('usercriteriatitle', 'report_analytics');
    }

    /**
     * Export the contact information to a csv that can be used for import into
     * google or outlook.
     *
     * @param  object  $filters  the filters to use to generate the chart
     */
    public function export($filters) {

        $users = $this->ajax_get_data($filters);
        $columns = array('name' => get_string('name'), 'email' => get_string('email', 'report_analytics'));
        \core\dataformat::download_data('email', 'csv', $columns, $users, array($this, 'format_export_data'));
    }

    /**
     * This method converts the firstname and lastname fields into a username field
     * so that it can be exported to a csv file.  Invalid characters are stripped
     * out.
     *
     * @param  array  $user  a user record
     * @return array  data usable for a csv export ('columnname' => 'value')
     */
    public static function format_export_data($user) {

        $return = array($user['firstname'] . ' ' . $user['lastname'], $user['email']);
        $return = preg_replace('/[\x00-\x1F]/', '', $return);
        return $return;
    }

}
