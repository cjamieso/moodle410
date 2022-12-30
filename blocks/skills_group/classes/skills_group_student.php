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
require_once($CFG->dirroot.'/mod/feedback/lib.php');

/**
 * skills_group_student class.
 *
 * This class holds the results of a particular student.  The results need to
 * be retrieved from a specified feedback activity.
 *
 * @package    block_skills_group
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class skills_group_student {

    /** ID of the course */
    private $courseid;
    /** skills_group_settings object to hold feedback information */
    private $settings;
    /** Student user ID */
    private $userid;
    /** Item scores */
    private $itemscores = array();

    /**
     * This function stores the needed variables and computes the total skill
     * scores for a student.
     *
     * @param int $courseid The ID of the course.
     * @param int $userid The ID of the user to retrieve the scores for.
     * @param int $itemids The IDs of the feedback items that contain valid scores.
     *
     */
    public function __construct($courseid, $userid, $itemids = null) {

        $this->courseid = $courseid;
        $this->settings = new skills_group_setting($courseid);
        $this->userid = $userid;
        $this->compute_scores();
    }

    /**
     * This function retrieves the scores from the feedback activity for the
     * current student.
     *
     * The feedback results have three different parts:
     * 1) Feedback questions (items)
     * 2) Feedback completed status
     * 3) Feedback question values (responses)
     *
     */
    private function compute_scores() {

        if ($this->settings->get_feedback_id('pre') != 0 && $this->settings->get_feedback_id('post') != 0) {
            $this->itemscores['pre'] = $this->compute_feedback_scores($this->settings->get_feedback_id('pre'));
            $this->itemscores['post'] = $this->compute_feedback_scores($this->settings->get_feedback_id('post'));
        }
    }

    /**
     * Compute feedback scores for a feedback.
     *
     * @param  int    $feedbackid  the ID of the feedback to compute the scores for
     * @return array  the compupted score
     */
    private function compute_feedback_scores($feedbackid) {
        global $DB;
        $itemscores = array();
        $levels = $this->settings->get_feedback_levels();

        $feedbackitems = $DB->get_records('feedback_item', array('feedback' => $feedbackid), 'position');
        $params = array('feedback' => $feedbackid, 'userid' => $this->userid, 'anonymous_response' => FEEDBACK_ANONYMOUS_NO);
        $feedbackcompleted = $DB->get_record('feedback_completed', $params);
        foreach ($feedbackitems as $feedbackitem) {
            if ($feedbackitem->typ == 'multichoice') {
                if ($feedbackcompleted !== false) {
                    $value = feedback_get_item_value($feedbackcompleted->id, $feedbackitem->id);
                    if ($value > 0 && isset($levels[$value - 1])) {
                        // Moodle indexes values from 1 (0 = "not-selected").
                        $label = $levels[$value - 1];
                        $itemscores[$feedbackitem->position] = intval($label);
                    } else {
                        // Zero valued returns are "not-selected" -> map to null.
                        $itemscores[$feedbackitem->position] = null;
                    }
                } else {
                    // Add null valued scored if student has not completed the survey.
                    $itemscores[$feedbackitem->position] = null;
                }
            }
        }
        return $itemscores;
    }

    /**
     * This function returns the score of a student for a particular feedback question
     * based on the item ID that it is given.
     *
     * @param  int  $itemid The ID of the feedback item to retrieve the score for.
     * @param  string  $type the type of feedback ID to get {'pre'|'post'}
     * @return int The score for the given feedback item ID.
     *
     */
    public function get_score($type, $itemid) {
        if (isset($this->itemscores[$type][$itemid])) {
            return $this->itemscores[$type][$itemid];
        } else {
            return null;
        }
    }

    /**
     * This function returns the set of scores for the student.
     *
     * @param  string  $type the type of feedback ID to get {'pre'|'post'}
     * @return array The scores for the student.
     *
     */
    public function get_scores($type) {
        if (isset($this->itemscores[$type])) {
            return $this->itemscores[$type];
        } else {
            return null;
        }
    }

    /**
     * This function returns whether the student has locked in their choice.
     * IMPORTANT: if no record exists in the table, false must be returned.
     *
     * @return bool T/F indicating if choice has been locked
     *
     */
    public function get_lock_choice() {
        global $DB;

        $lockchoice = $DB->get_field('skills_group_student', 'finalizegroup', array('userid' => $this->userid,
            'groupingid' => $this->settings->get_grouping_id()));
        if ($lockchoice !== false) {
            if ($lockchoice == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            // If no setting exists, return false (safe default).
            return false;
        }
    }

    /**
     * This function updates the flag that determines whether the student has locked in
     * their group choice.
     *
     * @param bool $lockchoice Indicates whether student has locked in their choice.
     *
     */
    public function set_lock_choice($lockchoice) {
        global $DB;

        $record = $DB->get_record('skills_group_student', array('userid' => $this->userid,
            'groupingid' => $this->settings->get_grouping_id()));
        if ($lockchoice === true) {
            if ($record !== false) {
                $record->finalizegroup = 1;
                $DB->update_record('skills_group_student', $record);
            } else {
                $record = new \stdClass;
                $record->userid = $this->userid;
                $record->groupingid = $this->settings->get_grouping_id();
                $record->finalizegroup = 1;
                if (!$DB->insert_record('skills_group_student', $record)) {
                    print_error(get_string('dberror', BLOCK_SG_LANG_TABLE));
                }
            }
        } else {
            // Only update if record exists -> no record will default to 0.
            if ($record !== false) {
                $record->finalizegroup = 0;
                $DB->update_record('skills_group_student', $record);
            }
        }
    }
}