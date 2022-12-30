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

namespace block_skills_group;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');
// Non-anonymous feedbacks have table entry set to "2".
define('FEEDBACK_NONANONYMOUS', 2);

/**
 * Basic skills_group editing page.  Let's an administrator choose the
 * grouping to place the students in and also the feedback to pull data
 * from.
 *
 * @package    block_skills_group
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_skills_group_settings_form extends \moodleform {

    /** The ID of the course */
    private $courseid;

    /**
     * This method constructs the form by storing the course ID for later.
     *
     * @param int $courseid The ID of the course to create the form for.
     *
     */
    public function __construct($courseid) {

        $this->courseid = $courseid;
        parent::__construct();
    }

    /**
     * Form definition: basic form consists of two elements: {feedback, grouping}
     * The user is presented with a list of these from those available in the course.
     *
     */
    public function definition() {
        global $DB;

        $elementmissing = false;
        $mform = &$this->_form;
        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'inputheader', get_string('inputsheader', BLOCK_SG_LANG_TABLE));
        $mform->addElement('static', 'inputextra', get_string('note', BLOCK_SG_LANG_TABLE),
                           get_string('inputextrahelp', BLOCK_SG_LANG_TABLE));

        // Feedbacks.
        $feedback = $this->get_course_feedback();
        $feedbacknames = array();
        $feedbacknames[] = get_string('none', BLOCK_SG_LANG_TABLE);
        foreach ($feedback as $fb) {
            $feedbacknames[$fb->id] = $fb->name;
        }
        $mform->addElement('select', 'prefeedbackid', get_string('prefeedback', BLOCK_SG_LANG_TABLE), $feedbacknames);
        $mform->addHelpButton('prefeedbackid', 'prefeedback', 'block_skills_group');
        $mform->addElement('select', 'postfeedbackid', get_string('postfeedback', BLOCK_SG_LANG_TABLE), $feedbacknames);
        $mform->addHelpButton('postfeedbackid', 'postfeedback', 'block_skills_group');
        $mform->disabledIf('postfeedbackid', 'prefeedbackid', 'eq', 0);

        // High/Low score threshold.
        $mform->addElement('text', 'threshold', get_string('threshold', BLOCK_SG_LANG_TABLE));
        $mform->setType('threshold', PARAM_INT);
        $mform->addHelpButton('threshold', 'threshold', 'block_skills_group');
        $mform->disabledIf('threshold', 'prefeedbackid', 'eq', 0);

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'outputheader', get_string('outputsheader', BLOCK_SG_LANG_TABLE));
        $mform->addElement('static', 'outputextra', get_string('note', BLOCK_SG_LANG_TABLE),
                           get_string('outputextrahelp', BLOCK_SG_LANG_TABLE));

        // Grouping.
        $groupings = $this->get_course_groupings();
        if (count($groupings) == 0) {
            $elementmissing = true;
            $groupingnames = array(get_string('groupingerror', BLOCK_SG_LANG_TABLE));
        } else {
            $groupingnames = array();
            foreach ($groupings as $grouping) {
                $groupingnames[$grouping->id] = $grouping->name;
            }
        }
        $mform->addElement('select', 'groupingid', get_string('groupingid', BLOCK_SG_LANG_TABLE), $groupingnames);
        $mform->addRule('groupingid', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('groupingid', 'groupingid', 'block_skills_group');

        // Force students to use instructor created groups.
        $mform->addElement('advcheckbox', 'instructorgroups', get_string('instructorgroups', BLOCK_SG_LANG_TABLE),
                           get_string('instructorgroupsright', BLOCK_SG_LANG_TABLE), null, array(0, 1));
        $mform->addRule('instructorgroups', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('instructorgroups', 'instructorgroups', 'block_skills_group');

        // Allow students to name their groups.
        $mform->addElement('advcheckbox', 'allownaming', get_string('allownaming', BLOCK_SG_LANG_TABLE),
                           get_string('allownamingright', BLOCK_SG_LANG_TABLE), null, array(0, 1));
        $mform->addHelpButton('allownaming', 'allownaming', 'block_skills_group');
        $mform->disabledIf('allownaming', 'instructorgroups', 'checked');

        // Allow students to add other students.
        $mform->addElement('advcheckbox', 'allowadding', get_string('allowadding', BLOCK_SG_LANG_TABLE),
                           get_string('allowaddingright', BLOCK_SG_LANG_TABLE), null, array(0, 1));
        $mform->addHelpButton('allowadding', 'allowadding', 'block_skills_group');

        // Allow students to view group members.
        $mform->addElement('advcheckbox', 'allowgroupview', get_string('allowgroupview', BLOCK_SG_LANG_TABLE),
                           get_string('allowgroupviewright', BLOCK_SG_LANG_TABLE), null, array(0, 1));
        $mform->addHelpButton('allowgroupview', 'allowgroupview', 'block_skills_group');

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'settingheader', get_string('settingsheader', BLOCK_SG_LANG_TABLE));

        // Max group size.
        $mform->addElement('text', 'maxsize', get_string('maxsize', BLOCK_SG_LANG_TABLE));
        $mform->setType('maxsize', PARAM_INT);
        $mform->addRule('maxsize', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('maxsize', 'maxsize', 'block_skills_group');

        // Date/Time for cutoff.
        $pagegroup = array();
        $pagegroup[] = $mform->createElement('date_time_selector', 'date', '');
        $pagegroup[] = $mform->createElement('advcheckbox', 'datecheck', '', get_string('enabled', BLOCK_SG_LANG_TABLE),
                                             null, array(0, 1));
        $mform->addGroup($pagegroup, 'allowchanges', get_string('allowchanges', BLOCK_SG_LANG_TABLE), null, false);
        $mform->addHelpButton('allowchanges', 'allowchanges', 'block_skills_group');

        // Hidden element courseid: needed for posting.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        // Only give user option to save if no elements were missing.
        if (!$elementmissing) {
            $this->add_action_buttons();
        } else {
            // Creating as array adds more consistency to the look of the buttons.
            $buttonarray = array();
            $buttonarray[] = &$mform->createElement('cancel');
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        }
    }

    /**
     * Function grabs all of the feedback activities currently within the course.
     * Only feedback activities that are non-anonymous are considered valid.
     *
     * @return array Feedback activities for course.
     *
     */
    private function get_course_feedback() {
        global $DB;

        return $DB->get_records('feedback', array('course' => $this->courseid, 'anonymous' => FEEDBACK_NONANONYMOUS));
    }

    /**
     * Function grabs all of the groupings currently within the course.
     *
     * @return array Groupings in the course.
     *
     */
    private function get_course_groupings() {
        global $DB;

        return $DB->get_records('groupings', array('courseid' => $this->courseid));
    }
}