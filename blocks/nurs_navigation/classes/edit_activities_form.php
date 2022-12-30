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

namespace block_nurs_navigation;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/blocks/nurs_navigation/lib.php');

/**
 * edit_activities_form class
 *
 * This class creates form which allows users to categorize activities in the course
 * so that they can be aggregated and displayed to users.
 *
 * @package    block_nurs_navigation
 * @copyright  2018 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_activities_form extends \moodleform {

    /**
     * This method constructs the form and stores the course ID for later use.
     *
     * @param int $courseid The ID of the course to create the form for.
     *
     */
    public function __construct($courseid) {

        $this->courseid = $courseid;
        parent::__construct();
    }

    /**
     * Form definition: the form contains a section for each activity.
     * The user may indicate what the classificiation is of each activity from a list.
     */
    public function definition() {
        global $DB;

        $mform = &$this->_form;
        $course = $DB->get_record('course', array('id' => $this->courseid));

        $types = explode(',', preg_replace("/[^A-Za-z,]+/", "", get_config('nurs_navigation', 'Activities')));
        foreach ($types as $type) {
            if (array_search($type, \core_plugin_manager::instance()->standard_plugins_list('mod')) !== false) {
                $this->add_assessments($type, get_string('modulenameplural', $type),
                    get_all_instances_in_course($type, $course));
            }
        }

        // Hidden elements (courseid + blockid: needed for posting).
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Add a list of activities to the form.
     *
     * @param  string  $headerid     the ID for the form header
     * @param  string  $headertitle  the title for the section
     * @param  object  $mods         the list of assessments to add
     */
    private function add_assessments($headerid, $headertitle, $mods) {

        $mform = &$this->_form;
        $types = explode(',', preg_replace("/[^A-Za-z,]+/", "", get_config('nurs_navigation', 'Activities')));
        $options = array();
        foreach ($types as $type) {
            if (array_search($type, \core_plugin_manager::instance()->standard_plugins_list('mod')) === false) {
                $options[$type] = get_string('quest', BNN_LANG_TABLE);
            } else {
                $options[$type] = get_string('modulename', $type);
            }
        }
        $options['none'] = get_string('none');
        $mform->addElement('header', $headerid, $headertitle);
        foreach ($mods as $mod) {
            $field = $headerid . $mod->coursemodule;
            $select = $mform->addElement('select', $field, $mod->name, $options);
            $activity = new activity($this->courseid, $headerid, $mod->coursemodule);
            $select->setSelected($activity->get_type());
        }
    }

    /**
     * Process a submitted form.  Go through each activity and update its
     * associated type field that the user has indicated.
     *
     * @param  object  $form  the submitted data
     */
    public function process_form($form) {
        global $DB;

        $course = $DB->get_record('course', array('id' => $this->courseid));
        $types = explode(',', preg_replace("/[^A-Za-z,]+/", "", get_config('nurs_navigation', 'Activities')));
        foreach ($types as $type) {
            if (array_search($type, \core_plugin_manager::instance()->standard_plugins_list('mod')) !== false) {
                $mods = get_all_instances_in_course($type, $course);
                foreach ($mods as $mod) {
                    $activity = new activity($this->courseid, $type, $mod->coursemodule);
                    $field = $type . $mod->coursemodule;
                    $activity->update_type($form->$field);
                }
            }
        }
    }

}
