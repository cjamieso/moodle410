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

namespace block_nurs_navigation\output;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/course/lib.php');

use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * Holds the data needed to draw the activity table on the screen.
 *
 * @package    block_nurs_navigation
 * @copyright  2018 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_table_renderable implements renderable, templatable {

    /** @var int the ID of the course. */
    public $courseid;
    /** @var object the full course object. */
    public $course;
    /** @var string the type of activity to render. (quiz|assign|quest) */
    public $type;

    /**
     * Constructor.
     *
     * @param  int    $courseid  the ID of the course to use
     * @param  array  $type      the type of activity
     */
    public function __construct($courseid, $type) {

        $this->courseid = $courseid;
        $this->course = get_course($this->courseid);
        $this->type = $type;
    }

    /**
     * Export data for mustache template rendering.
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the template
     */
    public function export_for_template(renderer_base $output) {

        $data = new \stdClass();
        $data->mods = $this->format_results($this->generate_list());
        return $data;
    }

    /**
     * Generate a list of mods (by cmid) to use for the list.
     *
     * @return array   a list of cmids
     */
    protected function generate_list() {
        global $DB;

        $cmids = [];
        if (array_search($this->type, \core_plugin_manager::instance()->standard_plugins_list('mod')) !== false) {
            $mods = get_all_instances_in_course($this->type, $this->course, null, true);
            foreach ($mods as $mod) {
                $activity = new \block_nurs_navigation\activity($this->courseid, $this->type, $mod->coursemodule);
                if ($activity->get_type() == $this->type) {
                    $cmids[] = $mod->coursemodule;
                }
            }
        }
        $params = array($this->course->id, $this->type, $this->type);
        $query = "SELECT * FROM {nurs_navigation_activities} WHERE courseid = ? AND basetype <> ? AND flaggedtype = ?";
        $records = $DB->get_records_sql($query, $params);
        foreach ($records as $record) {
            $activity = new \block_nurs_navigation\activity($this->course->id, $record->basetype, $record->modid);
            $cmids[] = $activity->get_module_id();
        }
        return $cmids;
    }

    /**
     * Format the information about the mod by retrieving the appropriate information.
     *
     * @param  array  $cmids  the cmIDs to include in the table
     * @return array  the final list of mods to display
     */
    protected function format_results($cmids) {

        $results = [];
        $modinfo = get_fast_modinfo($this->course);
        $sectionheaders = array();
        get_section_titles($this->courseid, $sectionheaders);
        foreach ($modinfo->sections as $number => $mods) {
            foreach ($mods as $mod) {
                if (array_search($mod, $cmids) !== false) {
                    $section = ($number > 0) ? $sectionheaders[$number] : get_string('general');
                    // Skip if flagged as hidden on course page.
                    if (!$modinfo->cms[$mod]->visible) {
                        continue;
                    }
                    $temp = array('section' => $section, 'visible' => $modinfo->cms[$mod]->uservisible, 'cmid' => $mod,
                        'type' => $modinfo->cms[$mod]->modname);
                    $method = 'get_' . $modinfo->cms[$mod]->modname . '_info';
                    if (method_exists($this, $method)) {
                        $info = $this->$method($modinfo->cms[$mod]);
                    } else {
                        $info = array('name' => $modinfo->cms[$mod]->name,
                            'close' => get_string('closedateerror', 'block_nurs_navigation'));
                    }
                    $results[] = array_merge($temp, $info);
                }
            }
        }
        return $results;
    }

    /**
     * Gets the quiz information.
     *
     * @param  object  $cm  the module info
     * @return array  quiz information (name + time closing)
     */
    protected function get_quiz_info($cm) {
        global $DB;

        $record = $DB->get_record('quiz', array('id' => $cm->instance));
        $close = ($record->timeclose == 0) ? get_string('none', 'quiz') : userdate($record->timeclose);
        return array('name' => $record->name, 'close' => $close);
    }

    /**
     * Gets the assignment information.
     *
     * @param  object  $cm  the module info
     * @return array  the assignment information (name + time closing)
     */
    protected function get_assign_info($cm) {
        global $DB;

        $record = $DB->get_record('assign', array('id' => $cm->instance));
        $close = ($record->duedate == 0) ? get_string('none', 'assign') : userdate($record->duedate);
        return array('name' => $record->name, 'close' => $close);
    }

    /**
     * Gets the forum information.
     *
     * @param  object  $cm  the module info
     * @return array  the assignment information (name + time closing)
     */
    protected function get_forum_info($cm) {
        global $DB;

        $availability = json_decode($cm->availability);
        $close = get_string('closedateerror', 'block_nurs_navigation');
        $closeconditions = array();
        if (is_object($availability)) {
            foreach ($availability->c as $condition) {
                if ($condition->d == "<") {
                    $closeconditions[] = $condition->t;
                }
            }
        }
        if (!empty($closeconditions)) {
            $close = userdate(min($closeconditions));
        }
        return array('name' => $cm->name, 'close' => $close);
    }

}
