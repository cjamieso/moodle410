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
 * This class holds the data needed to generate the graph chooser renderable.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class selector_renderable implements renderable, templatable {

    /** @var int the ID of the course. */
    public $courseid;
    /** @var array options to control rendering. */
    public $options;
    /** @var array holds info about available graphs. */
    public $graphinfo = array();

    /**
     * Create list of graphs based on the type of user (student or instructor).
     *
     * @param  int    $courseid  the ID of the course to use
     * @param  array  $options  array of additional options, (types => activty class)
     */
    public function __construct($courseid, $options = array()) {

        $this->courseid = $courseid;
        $this->options = $options;
        if (!isset($this->options['instructor'])) {
            $this->options['instructor'] = false;
        }

        $this->get_charts();
    }

    /**
     * Loop through the classes folder and search for charts.  For all charts that
     * are found, add their info to the selector.
     */
    public function get_charts() {

        $this->graphinfo = array();
        $files = new \DirectoryIterator(dirname(__FILE__) . '/..');
        foreach ($files as $file) {
            $result = strstr($file->getFilename(), 'chart', true);
            if ($result !== false && trim($result) != false) {
                $classname = "\\report_analytics\\" . $result . 'chart';
                $chart = new $classname($this->courseid, $this->options);
                $temp = $chart->get_chart_info();
                if ($temp !== false && $temp['sort'] !== 0) {
                    $this->graphinfo[$temp['sort']] = $temp;
                }
            }
        }
        ksort($this->graphinfo);
    }

    /**
     * Retrieve a list of courses that the user is enrolled in.
     *
     * @return array  the user's courses (id => fullname)
     */
    protected function get_user_courses() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        $courses = enrol_get_my_courses(array('id', 'fullname'));
        $return = array();
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            if (has_capability('report/analytics:view', $context) || has_capability('report/analytics:studentview', $context)) {
                $return[] = array('id' => $course->id, 'name' => $course->fullname);
            }
        }
        if (empty($return)) {
            $return[] = array('id' => 1, 'name' => get_string('novalidcourses', 'report_analytics'));
        }
        return $return;
    }

    /**
     * Export data for mustache template rendering.
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the template
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $data = new \stdClass();
        $data->sesskey = $USER->sesskey;
        $data->sections = array('id' => 'section', 'label' => get_string('reports'), 'items' => array_values($this->graphinfo));
        $data->courses = $this->get_user_courses();
        return $data;
    }

}
