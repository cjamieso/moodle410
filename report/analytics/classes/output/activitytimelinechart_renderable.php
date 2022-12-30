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

namespace report_analytics\output;

defined('MOODLE_INTERNAL') || die;

use renderer_base;

/**
 * Holds the data needed to draw an activity chart on the screen.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activitytimelinechart_renderable extends chart_renderable {

    /**
     * Export data for mustache template rendering
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the template
     */
    public function export_for_template(renderer_base $output) {

        $this->options['action'] = 'single';
        $data = parent::export_for_template($output);
        $data->activity = $this->get_filter_data('activity', get_string('activities'), true);
        $data->student = $this->get_filter_data('student', get_string('students'), true, 'style="display: none;"');
        $data->grade = $this->get_filter_data('grade');
        $data->action = $this->get_filter_data('action', get_string('actions'));
        $data->unique = $this->get_filter_data('unique');
        $data->granularity = $this->get_filter_data('time_slider');
        return $data;
    }

    /**
     * Gets the data for a time slider.
     *
     * @return array  data for a time slider
     */
    protected function get_time_slider_filter_data() {
        $helptooltip = $this->get_help_tooltip('timeslider');
        return array('helptooltip' => $helptooltip);
    }
}
