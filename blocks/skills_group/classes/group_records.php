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
require_once($CFG->dirroot.'/blocks/skills_group/locallib.php');

/**
 * This class is responsible for retrieving and formatting group results for the
 * YUI data table.
 *
 * @package    block_skills_group
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_records {

    /** This is the list of records */
    private $records;

    /**
     * The constructor grabs all of the groups to retrieve data for.
     *
     * @param int $courseid This is the course ID
     *
     */
    public function __construct($courseid) {
        global $DB, $USER;

        $this->courseid = $courseid;
        $sgs = new skills_group_setting($courseid);

        $this->records = $DB->get_records('groupings_groups', array('groupingid' => $sgs->get_grouping_id()));
    }

    /**
     * This function loops through each group, calculates, and formats the scores.
     *
     * @return array List of all scores for each of the valid groups a user could join.
     *
     */
    public function get_table_rows() {
        global $DB;

        $sgs = new skills_group_setting($this->courseid);
        $tablerows = array();

        foreach ($this->records as $group) {
            $sgroup = new skills_group($group->groupid);
            if (($sgroup->count_members() < $sgs->get_group_size())) {
                // Group is valid if either students allow joining or it is instructor created.
                if ($sgroup->get_allow_others_to_join() === true || $sgs->get_instructorgroups() === true) {
                    $scores = ($sgs->get_feedback_id() == 0) ? array() : $sgroup->get_join_form_score();
                    $name = $sgroup->get_group_name();
                    $temp = array('id' => $group->groupid, 'name' => $name, 'size' => $sgroup->count_members(),
                        'note' => $sgroup->get_note()) + $scores;
                    $tablerows[] = $temp;
                }
            }
        }
        return $tablerows;
    }

    /**
     * This function retrieves the list of headers used in the table.
     *
     * @return array Full named list of skills in the feedback
     *
     */
    public function get_skills_list($type = 'multichoice') {
        global $DB;

        $sgs = new skills_group_setting($this->courseid);
        $skills = array();
        // If no feedback setup, return empty array.
        if ($sgs->get_feedback_id() == NOFEEDBACK) {
            return $skills;
        }
        $feedbackitems = $DB->get_records('feedback_item', array('feedback' => $sgs->get_feedback_id()), 'position');
        foreach ($feedbackitems as $feedbackitem) {
            if ($feedbackitem->typ == $type) {
                if ($feedbackitem->typ == 'multichoice') {
                    $skills[$feedbackitem->position] = $feedbackitem->name;
                } else if ($feedbackitem->typ == 'label') {
                    $skills[$feedbackitem->position] = $feedbackitem->presentation;
                }
            }
        }
        return $skills;
    }

}