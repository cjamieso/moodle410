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
require_once($CFG->dirroot.'/blocks/skills_group/locallib.php');

/**
 * This lets the instructor specify a collection of IDs for the items/questions
 * on the pre and post feedback surveys.  These IDs are included as part of the
 * student data export to upload data into the Faculty of Engineering accreditation
 * system.
 *
 * @package    block_skills_group
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enter_ids_form extends \moodleform {

    /** The ID of the course */
    private $courseid;
    /** skills_group_settings object to hold feedback information */
    private $settings;

    /**
     * Constructor -> store courseid and retrieve settings.
     *
     * @param int $courseid The ID of the course to create the form for
     *
     */
    public function __construct($courseid) {

        $this->courseid = $courseid;
        $this->settings = new skills_group_setting($courseid);
        parent::__construct();
    }

    /**
     * Form definition: basic form consists of two elements: {feedback, grouping}
     * The user is presented with a list of these from those available in the course.
     *
     */
    public function definition() {

        $mform = &$this->_form;
        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'uploadheader', get_string('uploadheader', BLOCK_SG_LANG_TABLE));
        $mform->addHelpButton('uploadheader', 'uploadheader', 'block_skills_group');
        $mform->addElement('filepicker', 'uploadids', get_string('uploadids', BLOCK_SG_LANG_TABLE), null,
            array('accepted_types' => 'csv', 'maxbytes' => 10 * 1024 * 1024));
        $mform->addElement('submit', 'submitupload', get_string('submitupload', BLOCK_SG_LANG_TABLE));

        // -------------------------------------------------------------------------------
        $items = $this->settings->get_feedback_items();
        $j = 0;
        // Grab all items on the feedback indexed by position.
        foreach ($items as $item) {
            foreach ($item as $name => $questions) {
                $mform->addElement('header', 'labelheader' . $j, $name);
                $mform->addElement('static', 'extra' . $j, get_string('note', BLOCK_SG_LANG_TABLE),
                           get_string('idextrahelp', BLOCK_SG_LANG_TABLE));
                $j++;
                foreach ($questions as $position => $question) {
                    $mform->addElement('text', 'position' . $position, $question);
                    $mform->setType('position' . $position, PARAM_INT);
                }
            }
        }

        // Hidden element courseid: needed for posting.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Function grabs the ID of the feedback item based on it's position and name
     * based on the current course's feedback activity.  The position field is unique
     * so no duplicate records should be returned.
     *
     * @param  int     position  the position of the question/item on the feedback
     * @param  string  name      the text of the question/item on the feedback
     * @return int  the ID of the feedback item in the table
     */
    private function get_feedback_item_id($position, $name) {
        global $DB;

        return $DB->get_field('feedback_item', 'id', array('feedback' => $this->settings->get_feedback_id('pre'),
            'position' => $position, 'name' => $name));
    }


    /**
     * This function processes the submitted form by saving the IDs of the feedback activity
     * and the grouping.
     *
     * @param object $submittedform  Object holding the submitted data by the user.
     */
    public function process_form($submittedform) {
        global $DB;

        $sgm = new skills_group_mapping($this->courseid);
        foreach ($submittedform as $position => $accreditationid) {
            if (($accreditationid != 0) && strstr($position, 'position') !== false) {
                $id = trim(preg_replace('/\D/', '', $position));
                $sgm->update_record($id, $accreditationid);
            }
        }

    }

    /**
     * Proceses and uploaded csv file.
     *
     * @param string  $file  the file uploaded by the user
     */
    public function process_upload($file) {

        $sgm = new skills_group_mapping($this->courseid);
        $rows = explode("\n", $file);
        foreach ($rows as $row) {
            $columns = explode(',', $row);
            if (!empty($columns[0])) {
                $position = $this->find_position(trim($columns[0]));
                if ($position !== false && !empty($columns[1])) {
                    $sgm->update_record($position, $columns[1]);
                } else {
                    mtrace('label not found on feedback: ', $columns[0]);
                }
            }
        }
    }

    /**
     * Search for the specificed name amongst the list of feedback items.  The
     * associated key in the array (denoting its position) is returned.
     *
     * @param string $label  the name of the feedback item to search for
     * @return int|bool the position on the feedback of the item or false if not found
     */
    private function find_position($label) {

        $items = $this->settings->get_feedback_items();
        foreach ($items as $item) {
            foreach ($item as $name => $questions) {
                $key = array_search($label, $questions);
                if ($key !== false) {
                    return $key;
                }
            }
        }
        return false;
    }
}