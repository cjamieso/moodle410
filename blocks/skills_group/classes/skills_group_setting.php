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
require_once($CFG->dirroot.'/blocks/skills_group/locallib.php');
require_once($CFG->dirroot.'/mod/feedback/lib.php');
require_once($CFG->dirroot.'/mod/feedback/item/multichoice/lib.php');

/**
 * skills_group_setting class
 *
 * This class abstracts the lower level (DB) functionality for the skills_group_settings
 * table.  Fairly straightforward -> can create/delete a record, retrieve IDs or names,
 * and there is a simple method to tell if the record exists.
 *
 * @package    block_skills_group
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class skills_group_setting {

    /** This is the cached database record. */
    private $record;
    /** This is the ID of the course. */
    private $courseid;

    /**
     * The constructor saves the courseid and also retrieves the record from the database.  It
     * is possible that false could be returned, use method exists() to check.
     *
     * @param int $courseid This is the course ID.
     *
     */
    public function __construct($courseid) {
        $this->courseid = $courseid;
        $this->retrieve_record();
    }

    /**
     * Retrieve the most up-to-date copy of the settings record.
     */
    public function retrieve_record() {
        global $DB;
        $this->record = $DB->get_record('skills_group_settings', array('courseid' => $this->courseid));
    }

    /**
     * This method returns the ID of the feedback activity associated with the skills_group.
     *
     * @param  string  $type the type of feedback ID to get {'pre'|'post'}
     * @return int ID of feedback activity.
     *
     */
    public function get_feedback_id($type = 'pre') {
        if ($type == 'pre') {
            return $this->record->prefeedbackid;
        } else {
            return $this->record->postfeedbackid;
        }
    }

    /**
     * This method returns the name of the feedback activity associated with the skills_group.
     *
     * @param  string  $type the type of feedback ID to get {'pre'|'post'}
     * @return string  name of feedback activity.
     *
     */
    public function get_feedback_name($type = 'pre') {
        global $DB;

        $id = ($type == 'pre') ? $this->record->prefeedbackid : $this->record->postfeedbackid;
        $record = $DB->get_record('feedback', array('id' => $id));
        return $record->name;
    }

    /**
     * Get the levels (choices) in use for the multiple choice questions in a feedback.
     *
     * Note: the plugin only accepts feedbacks where all questions have the same levels.
     *
     * @return array  the choices that a user can select when completing the feedback
     */
    public function get_feedback_levels() {
        global $DB;

        $presentation = $DB->get_field('feedback_item', 'presentation', array('feedback' => $this->record->prefeedbackid,
            'typ' => 'multichoice'), IGNORE_MULTIPLE);
        $tokens = explode(FEEDBACK_MULTICHOICE_TYPE_SEP, $presentation);
        // Look for the "<<<<<" (FEEDBACK_MULTICHOICE_ADJUST_SEP).
        $values = strstr($tokens[1], FEEDBACK_MULTICHOICE_ADJUST_SEP, true);
        if ($values === false) {
            $values = $tokens[1];
        }
        return array_values(explode(FEEDBACK_MULTICHOICE_LINE_SEP, $values));
    }

    /**
     * Gets the offset for feedback values.  This accommodates for whether multiple choice
     * questions start at 0 or 1 (or some higher value).
     *
     * @return int  the offset used in the feedback values
     */
    public function get_feedback_offset() {
        global $DB;

        $presentation = $DB->get_field('feedback_item', 'presentation', array('feedback' => $this->record->prefeedbackid,
            'typ' => 'multichoice'), IGNORE_MULTIPLE);
        $tokens = explode(FEEDBACK_MULTICHOICE_ADJUST_SEP, $presentation);
        return (isset($tokens[1])) ? intval($tokens[1]) : 0;
    }

    /**
     * Gets a list of items/questions on the pre-course feedback activity.
     *
     * @return array  the list of feedback questions and headings
     */
    public function get_feedback_items() {
        global $DB;

        $select = array();
        $temp = array();
        $name = 'Questions';
        $items = $DB->get_records('feedback_item', array('feedback' => $this->record->prefeedbackid), 'position ASC',
            'position, name, presentation, typ');
        foreach ($items as $item) {
            if ($item->typ == 'label') {
                if (!empty($temp)) {
                    $select[] = array($name => $temp);
                    $temp = array();
                }
                $name = strip_tags($item->presentation);
            } else if ($item->typ == 'multichoice') {
                $temp[$item->position] = $item->name;
            }
        }
        if (!empty($temp)) {
            $select[] = array($name => $temp);
        }
        return $select;
    }

    /**
     * This method returns the ID of the grouping associated with the skills_group.
     *
     * @returns int ID of grouping.
     *
     */
    public function get_grouping_id() {
        return $this->record->groupingid;
    }

    /**
     * This method returns the name of the grouping associated with the skills_group.
     *
     * @returns string Name of grouping.
     *
     */
    public function get_grouping_name() {
        global $DB;

        $record = $DB->get_record('groupings', array('id' => $this->record->groupingid));
        return $record->name;
    }

    /**
     * This method gets the maximum group size parameter from the settings record.
     *
     * @return int Maximum group size.
     *
     */
    public function get_group_size() {
        return $this->record->maxsize;
    }

    /**
     * This method returns the score threshold.
     *
     * @return int Score threshold to determine difference between low/high
     */
    public function get_threshold() {
        return $this->record->threshold;
    }

    /**
     * This method returns the date restriction field.
     *
     * @return array Date restrction (if it exists).
     */
    public function get_date() {
        // Ensure record is up to date (no cacheing).
        $this->retrieve_record();
        return $this->record->date;
    }

    /**
     * This method checks for a valid date restriction and returns T/F to indicate its
     * existence.
     *
     * @return boolean T if date restriction exists, F if not
     */
    public function date_restriction() {
        // Ensure record is up to date (no cacheing).
        $this->retrieve_record();
        if ($this->record->date !== null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * This method returns the allownaming field.
     *
     * @return int Whether students should be permitted to name their own groups
     */
    public function get_allownaming() {
        return $this->record->allownaming;
    }

    /**
     * This method returns the allowadding field.
     *
     * @return int Whether students should be permitted to add other members to their group
     */
    public function get_allowadding() {
        return $this->record->allowadding;
    }

    /**
     * This method returns the allowgroupview field.
     *
     * @return int Whether students should be permitted to view their group
     */
    public function get_allowgroupview() {
        return $this->record->allowgroupview;
    }

    /**
     * This method returns the instructorgroups field.
     *
     * @return bool Whether students are forced to use instructor created groups (T) or not (F)
     */
    public function get_instructorgroups() {
        if ($this->record->instructorgroups == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * This method updates or creates the settings record.  In both cases, the
     * cached values of the $record member are updated.
     *
     * @param object $settings The settings for the new (or updated) settings record.
     *
     */
    public function update_record($settings) {
        global $DB;

        if (!isset($settings->groupingid)) {
            print_error(get_string('groupingmissing', BLOCK_SG_LANG_TABLE));
        }
        $sr = new settings_record($settings);

        if ($this->exists()) {
            $sr->copy_settings_to($this->record);
            $DB->update_record('skills_group_settings', $this->record);
        } else {
            $record = $sr->get_as_stdclass();
            $record->courseid = $this->courseid;

            $id = $DB->insert_record('skills_group_settings', $record);
            if ($id === false) {
                print_error(get_string('dberror', BLOCK_SG_LANG_TABLE));
            } else {
                // On success, grab the new record and store it.
                $this->record = $DB->get_record('skills_group_settings', array('id' => $id));
            }
        }
    }

    /**
     * This method checks to see if the record exists.
     *
     * @return bool T/F indicating whether the record exists (T) or not (F).
     *
     */
    public function exists() {
        if ($this->record != false) {
            return true;
        } else {
            return false;
        }
    }
}