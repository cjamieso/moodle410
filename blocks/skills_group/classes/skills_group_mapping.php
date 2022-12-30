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

/**
 * skills_group_mapping class
 *
 * This class abstracts the lower level (DB) functionality for the skills_group_mapping
 * table.  Fairly straightforward -> can create/delete a record, retrieve accreditation IDs,
 * and there is a simple method to tell if the record exists.
 *
 * @package    block_skills_group
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class skills_group_mapping {

    /** This is the cached database record. */
    private $records;
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
    }

    /**
     * Retrieve the accreditation ID corresponding to the position of the question.
     *
     * @param int $position  the position of the question on the feedback
     * @return array  the desired record (if it exists) or false if no record exists
     */
    public function get_accreditation_id($position) {
        global $DB;
        return $DB->get_field('skills_group_mapping', 'accreditationid', array('courseid' => $this->courseid,
            'position' => $position), IGNORE_MISSING);
    }

    /**
     * Retrieve all mapping records for a given course and return them to the user.
     *
     * @return array  the full set of mapping records
     */
    public function get_all_mappings() {
        global $DB;
        return $DB->get_records('skills_group_mapping', array('courseid' => $this->courseid));
    }

    /**
     * This method updates or creates the a single record in the skills_group_mapping
     * table.
     *
     * @param int $position  the ID of the question in the feedback_item table
     * @param int $accreditationid  the desired ID to map the question onto in the excel export
     *
     */
    public function update_record($position, $accreditationid) {
        global $DB;

        if (!isset($position)) {
            print_error(get_string('missingposition', BLOCK_SG_LANG_TABLE));
        }
        if (!isset($accreditationid)) {
            print_error(get_string('missingaccreditationid', BLOCK_SG_LANG_TABLE));
        }

        $record = $DB->get_record('skills_group_mapping', array('courseid' => $this->courseid, 'position' => $position));

        if ($record != false) {
            $record->position = $position;
            $record->courseid = $this->courseid;
            $record->accreditationid = $accreditationid;
            $DB->update_record('skills_group_mapping', $record);
        } else {
            $record = new \stdClass;
            $record->courseid = $this->courseid;
            $record->position = $position;
            $record->accreditationid = $accreditationid;

            $id = $DB->insert_record('skills_group_mapping', $record);
            if ($id === false) {
                print_error(get_string('dberror', BLOCK_SG_LANG_TABLE));
            }
        }
    }

}
