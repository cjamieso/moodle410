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
 * Holds the data needed to draw an activity chart on the screen.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completionsearchchart_renderable extends chart_renderable {

    /** @const default number of users per page. */
    const DEFAULT_USERS_PER_PAGE = 20;

    /**
     * Export data for mustache template rendering
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the template
     */
    public function export_for_template(renderer_base $output) {

        $data = parent::export_for_template($output);
        $data->grade = $this->get_filter_data('grade');
        $this->options['helptooltip'] = false;
        $this->options['action'] = 'single';
        $this->options['activity'] = 'single';
        $data->activity = $this->get_filter_data('activity', get_string('activities'), true);
        $data->action = $this->get_filter_data('action', get_string('actions'));
        $data->operators = $this->get_action_operator_data();
        $data->usersperpage = $this->get_filter_data('users_per_page');
        return $data;
    }

    /**
     * Export data for rendering a toolbar using a template.
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the toolbar
     */
    public function export_for_toolbar_template(renderer_base $output) {

        $chartinfo = $this->get_chart_info();
        $data = new stdClass();
        $data->png = false;
        $data->excel = $this->get_excel_export_data($chartinfo['value']);
        $data->copy = true;
        $data->wordcloud = false;
        $data->undo = true;
        return $data;
    }

    /**
     * Gets the data for a grade filter.
     *
     * @return array  the data for a grade filter
     */
    protected function get_action_operator_data() {

        $actionfilter = new \report_analytics\actionfilter($this->courseid);
        $operatorsdata = $actionfilter->get_operator_data();
        $helptooltip = $this->get_help_tooltip('actionfilter');
        return array('label' => false, 'type' => 'actionoperator', 'helptooltip' => $helptooltip,
            'options' => array_map(array($this, 'create_options'), array_keys($operatorsdata), array_values($operatorsdata)));
    }

    /**
     * Gets the data for a users per page filter.
     *
     * @return array  data for a users per page filter
     */
    protected function get_users_per_page_filter_data() {
        return array('label' => get_string('usersperpage', 'report_analytics'), 'class' => 'usersperpage',
            'name' => time() . 'usersperpage', 'value' => self::DEFAULT_USERS_PER_PAGE);
    }

}
