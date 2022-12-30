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

use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * Holds the data needed to draw a graph (and its filters) on the screen.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class chart_renderable implements renderable, templatable {

    /** @var int the ID of the course. */
    public $courseid;
    /** @var array default values for options. */
    public $optionsdefaults = array('action' => 'multiple', 'activity' => 'multiple', 'student' => 'multiple', 'groups' => true,
        'instructor' => true, 'types' => null, 'helptooltip' => true);
    /** @var array options to control rendering. */
    public $options;
    /** @var array filters used to generate the chart (if known). */
    public $filters;

    /**
     * Constructor.
     *
     * @param  int    $courseid  the ID of the course to use
     * @param  array  $options   array of additional options
     * @param  array  $filters   the filters employed by the user
     */
    public function __construct($courseid, $options = array(), $filters = array()) {

        $this->options = array_merge($this->optionsdefaults, $options);
        $this->courseid = $courseid;
        $this->filters = $filters;
    }

    /**
     * Retrieve the chart info from the chart class.
     *
     * @return array  the info for the current chart
     */
    protected function get_chart_info() {

        $graphtype = strstr((new \ReflectionClass($this))->getShortName(), '_renderable', true);
        $classname = "\\report_analytics\\" . $graphtype;
        $chart = new $classname($this->courseid, $this->options);
        return $chart->get_chart_info();
    }

    /**
     * Export data for mustache template rendering.
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the template
     */
    public function export_for_template(renderer_base $output) {

        $chartinfo = $this->get_chart_info();
        $data = new stdClass();
        // No chart info -> student trying to access student restricted graph, generate nothing.
        if ($chartinfo === false) {
            return $data;
        }
        $data->chartname = $chartinfo['value'];
        $data->chartnamelower = strtolower($data->chartname);
        $data->time = time();
        $data->datefilter = str_replace("&nbsp;", '', $this->get_filter_data('date'));
        $data->presetdates = $this->get_preset_dates();
        $data->instructor = $this->options['instructor'];
        $data->advanced = true;
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
        $data->png = true;
        $data->excel = $this->get_excel_export_data($chartinfo['value']);
        $data->wordcloud = false;
        $data->undo = true;
        return $data;
    }

    /**
     * Returns the text to make a datefilter.  This uses the old moodleforms rendering,
     * which is not yet converted over to the templates library.
     *
     * @return string  the HTML text for a date filter
     */
    protected function get_date_filter_data() {
        $dt = new \report_analytics\datetime_form();
        return $dt->render();
    }

    /**
     * Returns an array of preset dates to use to populate the template.
     *
     * @return array  the list of preset dates to use
     */
    protected function get_preset_dates() {
        $fourmonths = date_diff(new \DateTime('now'), new \DateTime('now - 4 months'));
        return array(array('date' => '7', 'text' => get_string('lastweek', 'report_analytics')),
            array('date' => '91', 'text' => get_string('lastthirteenweeks', 'report_analytics')),
            array('date' => $fourmonths->days, 'text' => get_string('lastfourmonths', 'report_analytics')));
    }

    /**
     * Returns the data for a particular filter.  There are two main kinds of filters
     * (select based) with some using optgroups and some using options.
     *
     * Some filters, however, are different altogether and have specialized methods to
     * retrieve their data.  Check for those specialized methods and use if it exists.
     *
     * @param  string  $type        the type of filter to add
     * @param  string  $label       the label to use for the filter
     * @param  bool    $optgroups   T/F indicating whether the filter uses optgroups
     * @param  string  $attributes  any additional attributes to include
     * @return array   the data for the filter
     */
    protected function get_filter_data($type, $label = '', $optgroups = false, $attributes = '') {

        $methodname = 'get_' . $type . '_filter_data';
        // Some filters have specialized methods, if so, use that method.
        if (method_exists($this, $methodname)) {
            return $this->$methodname($label);
        }
        $classname = "\\report_analytics\\" . $type . 'filter';
        $filter = new $classname($this->courseid, $this->options);
        $helptooltip = $this->get_help_tooltip($type . 'filter');
        $data = array('label' => $label, 'type' => $type, 'helptooltip' => $helptooltip, 'attributes' => $attributes);
        $filterdata = $filter->get_filter_data();
        if ($optgroups === true) {
            $data['optgroup'] = $this->optgroups_data_to_select($filterdata);
        } else {
            $data['options'] = array_map(array($this, 'create_options'), array_keys($filterdata), array_values($filterdata));
        }
        if (isset($this->options[$type]) && $this->options[$type] === 'multiple') {
            $data['multiple'] = true;
        }
        return $data;
    }

    /**
     * Gets the data for a grade filter.
     *
     * @return array  the data for a grade filter
     */
    protected function get_grade_filter_data() {

        $gradefilter = new \report_analytics\gradefilter($this->courseid);
        $itemsdata = $gradefilter->get_filter_data();
        $items = array('label' => false, 'type' => 'grade',
            'options' => array_map(array($this, 'create_options'), array_keys($itemsdata), array_values($itemsdata)));
        $operatorsdata = $gradefilter->get_operator_data();
        $operators = array('label' => false, 'type' => 'gradeoperator',
            'options' => array_map(array($this, 'create_options'), array_keys($operatorsdata), array_values($operatorsdata)));
        $helptooltip = $this->get_help_tooltip('gradefilter');
        return array('items' => $items, 'operators' => $operators, 'gradehelptooltip' => $helptooltip);
    }

    /**
     * Gets the data for a unique filter.
     *
     * @return array  data for a unique filter
     */
    protected function get_unique_filter_data() {
        return array('label' => get_string('uniquefilter', 'report_analytics'), 'class' => 'uniquecheck',
            'time' => time(), 'helptooltip' => $this->get_help_tooltip('uniquefilter'));
    }

    /**
     * Gets the data for a help tooltip.
     *
     * Automatic tooltip generation may be turned off by setting 'helptooltip' option to false.
     *
     * @param  string  $identifier  the lang file name for the element
     * @param  string  $display     the display style to use
     * @return object  the data for a help tooltip
     */
    protected function get_help_tooltip($identifier, $display = 'show') {

        if ($this->options['helptooltip'] === false) {
            return false;
        }
        $data = get_formatted_help_string($identifier, 'report_analytics', false);

        $data->alt = get_string('helpprefix2', '', trim(get_string($identifier, 'report_analytics'), ". \t"));
        $data->icon = (new \pix_icon('help', $data->alt, 'core', array('class' => 'iconhelp')));
        $data->title = get_string('helpprefix2', '', trim(get_string($identifier, 'report_analytics'), ". \t"));
        $data->url = (new \moodle_url('/help.php', array('component' => 'report_analytics', 'identifier' => $identifier,
            'lang' => current_language())));
        return $data;
    }

    /**
     * Transform the filter data to a format that can be used in a mustache template.
     * The mustache template handles hashed arrays differently than php.  We need:
     * An 'optgroup' with fields 'label' (name) and 'options'
     * Each 'options' field contains the options with two fields: 'optionvalue' and 'text'
     *
     * @param  array  $data  the data from the filter functions
     * @return array  re-mapped array suitable for use in a mustache template
     */
    protected function optgroups_data_to_select($data) {

        $select = array();
        foreach ($data as $label => $options) {
            $temp = array_map(array($this, 'create_options'), array_keys($options), array_values($options));
            $select[] = array('label' => $label, 'options' => $temp);
        }
        return $select;
    }

    /**
     * Function used to remap an array of keys and values into a format suitable for
     * mustache templates.  Used with the array_map() function in this class.
     *
     * @param  string  $key    the keys from the array
     * @param  string  $value  the values from the array
     * @return array  remapped array entry
     */
    public static function create_options($key, $value) {
        return array('optionvalue' => $key, 'text' => $value);
    }

    /**
     * Gets the hidden inputs used for an excel export.
     *
     * @param  string  $graphtype  the type of graph being rendered
     * @return array  the data for the hidden inputs on an excel export
     */
    protected function get_excel_export_data($graphtype) {
        global $USER;
        return array('params' => array(array('name' => 'courseid', 'value' => $this->courseid),
            array('name' => 'sesskey', 'value' => $USER->sesskey),
            array('name' => 'request', 'value' => 'export'),
            array('name' => 'graphtype', 'value' => $graphtype),
            array('name' => 'filters', 'value' => json_encode($this->filters))));
    }
}
