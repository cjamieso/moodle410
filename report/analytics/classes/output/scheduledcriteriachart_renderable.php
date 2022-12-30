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
use stdClass;

/**
 * This class holds the data needed to generate the scheduled report renderable.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduledcriteriachart_renderable extends completionsearchchart_renderable {

    /**
     * Constructor - set recipient filter to allow multiple selections.
     *
     * @param  int    $courseid  the ID of the course to use
     * @param  array  $options   array of additional options
     * @param  array  $filters   the filters employed by the user
     */
    public function __construct($courseid, $options = null, $filters = null) {

        parent::__construct($courseid, $options, $filters);
        $this->options['recipient'] = 'multiple';
    }

    /**
     * Export data for mustache template rendering.
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the template
     */
    public function export_for_designer_template(renderer_base $output) {

        $data = new \stdClass();
        $data->users = $this->get_filter_data('recipient', get_string('recipients', 'report_analytics'));
        return $data;
    }

    /**
     * Gets the data for an instructor filter.
     *
     * @return array  data for an instructor filter
     */
    protected function get_instructor_filter_data() {

        $filter = new \report_analytics\instructorfilter($this->courseid, $this->options);
        $data = $filter->get_filter_data();
        $helptooltip = $this->get_help_tooltip('instructorfilter');
        $instructor = array('label' => get_string('instructors', 'report_analytics'), 'type' => 'instructor',
            'helptooltip' => $helptooltip, 'multiple' => true,
            'options' => array_map(array($this, 'create_options'), array_keys($data), array_values($data)));
        return $instructor;
    }

}
