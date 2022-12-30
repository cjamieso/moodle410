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

namespace block_skills_group\output;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->dirroot.'/blocks/skills_group/locallib.php');

/**
 * Generates html for the chart.
 *
 * @package    block_skills_group
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chart_renderer extends \plugin_renderer_base {

    /** @var chart_renderable data needed to output the html. */
    protected $renderable;
    /** @var array chart data for rendering via js. */
    protected $chartdata;

    /**
     * Render function is mostly empty, saves data for drawing later.
     *
     * @param  chart_renderable  $renderable  data needed to output the html
     */
    protected function render_chart(chart_renderable $renderable) {

        $this->renderable = $renderable;
        $this->chartdata = array();
    }

    /**
     * Displays the base version of the chart.
     */
    public function display_entry_data() {
        global $PAGE;

        echo \html_writer::start_tag('canvas', array('style' => 'display: none;'));
        echo \html_writer::end_tag('canvas');
        echo \html_writer::start_div('row-fluid', array('id' => 'chartcontainer'));
        echo $this->display_export_button($this->renderable->prefeedbackid, get_string('preexport', BLOCK_SG_LANG_TABLE),
            'precourseexport');
        echo $this->display_export_button($this->renderable->postfeedbackid, get_string('postexport', BLOCK_SG_LANG_TABLE),
            'postcourseexport');
        echo \html_writer::div('', 'clear');
        echo $this->display_chart();
        echo \html_writer::end_div();
        echo \html_writer::div('', 'addstatustext');
    }

    /**
     * Displays a form and submit button to export the excel data
     *
     * @param  int     $feedbackid  the ID to send to the feedback exporter (either the pre or post ID)
     * @param  string  $label       the label for the export button
     * @param  string  $class       the class of the div enclosing the form
     * @return string  html for the export button
     */
    private function display_export_button($feedbackid, $label, $class) {
        global $USER;

        $params = array('courseid' => $this->renderable->courseid, 'sesskey' => $USER->sesskey, 'id' => $feedbackid);
        $url = new \moodle_url('analysis_to_excel.php', $params);
        $formcontents = \html_writer::empty_tag('input', array('type' => 'submit', 'value' => $label,
            'class' => 'feedbackexport btn'));
        $formcontents .= \html_writer::input_hidden_params($url);
        $form = \html_writer::nonempty_tag('form', $formcontents, array('method' => 'post',
            'action' => 'analysis_to_excel.php'));
        return \html_writer::div($form, $class);
    }

    /**
     * Display the chart (filters + toolbar + graph placeholder)
     *
     * @return string  html for chart
     */
    private function display_chart() {

        $buffer = \html_writer::start_div('chartheader', array('id' => 'chart'));
        $buffer .= \html_writer::div($this->display_item_filter(), 'filterheader');
        $buffer .= $this->display_apply_button();
        $buffer .= $this->display_toolbar_placeholder();
        $buffer .= $this->display_graph_placeholder('d3chart', 'chart' . time());
        $buffer .= \html_writer::end_div();
        return $buffer;
    }

    /**
     * Display placeholder to be filled in by graph.
     *
     * @param  string  $class  classes to add to the graph placeholder
     * @param  string  $id     id tag for graph
     * @return string  text to be displayed on screen
     */
    public function display_graph_placeholder($class, $id) {
        $this->chartdata = array_merge($this->chartdata, array('id' => $id));
        $content = \html_writer::div('', 'graphplaceholder');
        return \html_writer::div($content, $class, array('id' => $id));
    }

    /**
     * Display html for apply filter button.
     *
     * @return string  html for apply filter button (including clear)
     */
    protected function display_apply_button() {
        $buffer = \html_writer::div('', 'clear');
        $buffer .= \html_writer::nonempty_tag('button', get_string('applyfilterbutton', BLOCK_SG_LANG_TABLE),
            array('type' => 'button', 'class' => 'd3button btn'));
        $buffer .= \html_writer::div('', '', array('class' => 'filterstatustext'));
        return $buffer;
    }

    /**
     * Display the div that will hold the toolbar.
     *
     * @return string  html for the toolbar placeholder.
     */
    protected function display_toolbar_placeholder() {
        return \html_writer::div('', 'd3export');
    }

    /**
     * Generate the html for a graph toolbar.  Currently this includes two buttons:
     * {export to excel, export to png}.
     *
     * @param  array   $filters  the filters used to generate the graph
     * @return string  html for the toolbar
     */
    public function get_toolbar($filters) {
        global $USER;

        $buffer = '';
        $buffer .= $this->pix_icon('pngexport', get_string('pngexportalt', BLOCK_SG_LANG_TABLE), BLOCK_SG_LANG_TABLE,
            array('class' => 'pngexport'));
        $buffer .= $this->pix_icon('excel', get_string('excelexportalt', BLOCK_SG_LANG_TABLE), BLOCK_SG_LANG_TABLE,
            array('class' => 'excelexport'));
        $params = array('courseid' => $this->renderable->courseid, 'sesskey' => $USER->sesskey,
            'request' => 'export', 'filters' => json_encode($filters));
        $url = new \moodle_url('ajax_request.php', $params);
        $formcontents = \html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Excel',
            'class' => 'excelsubmit'));
        $formcontents .= \html_writer::input_hidden_params($url);
        $buffer .= \html_writer::nonempty_tag('form', $formcontents, array('method' => 'post',
            'action' => 'ajax_request.php', 'style' => 'display: none;'));
        return $buffer;
    }

    /**
     * Outputs the html to display the student filter.  Filter is rendered as a
     * multiple select html element.
     *
     * @return string  text to be displayed on screen
     */
    public function display_item_filter() {

        $buffer = \html_writer::label(get_string('questions', BLOCK_SG_LANG_TABLE), null);
        $helptext = get_string('helpprefix2', '', trim(get_string('itemfilter', BLOCK_SG_LANG_TABLE), ". \t"));
        $buffer .= $this->help_icon('itemfilter', BLOCK_SG_LANG_TABLE, $helptext);
        $attributes = array('class' => 'itemfilter', 'multiple' => 'multiple');
        $buffer .= \html_writer::select($this->renderable->items, 'studentfilter', '', '', $attributes);
        $buffer .= \html_writer::div('', 'clear');
        return $buffer;
    }

}
