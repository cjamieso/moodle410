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
global $CFG;
require_once($CFG->dirroot.'/blocks/nurs_navigation/locallib.php');

/**
 * activity class
 *
 * This class abstracts the lower level (DB) functionality from the higher
 * layers.  Each quiz/assignment can be flagged as a quiz, assignment,
 * quest, or none.  This class retrieves/updates that field.
 *
 * @package    block_nurs_navigation
 * @copyright  2018 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity {

    /** This is the record from the DB (if it exists). */
    private $record;
    /** This is the ID of the course that we are getting the icon for. */
    private $courseid;
    /** This is the base activity type on moodle. */
    private $basetype;
    /** This is the ID of the module. */
    private $modid;

    /**
     * Attempt to retrieve the record from the database.
     *
     * @param  int     $courseid  the ID of the course
     * @param  string  $basetype  the base type of activity (used within moodle)]
     * @param  int     $modid     the ID of the module
     */
    public function __construct($courseid, $basetype, $modid) {
        global $DB;

        $this->courseid = $courseid;
        $this->basetype = $basetype;
        $this->modid = $modid;

        $params = array($courseid, $basetype, $modid);
        $query = "SELECT * FROM {nurs_navigation_activities} WHERE courseid = ? AND basetype = ? AND modid = ?";
        $record = $DB->get_record_sql($query, $params, IGNORE_MULTIPLE);
        $this->record = $record;
    }

    /**
     * This method returns the type field of the record.
     *
     * @returns string the type field of the record.
     */
    public function get_type() {
        if ($this->exists() != false) {
            return $this->record->flaggedtype;
        } else {
            return $this->get_moodle_type();
        }
    }

    /**
     * Returns the moodle classification of the activity (quiz or assignment)
     * based on the activity field.
     *
     * @throws \Exception  unknown or invalid moodle type
     * @return string  the moodle classification of the activity
     */
    public function get_moodle_type() {
        return $this->basetype;
    }

    /**
     * Returns the module ID.
     *
     * @return int  the module ID
     */
    public function get_module_id() {
        return $this->modid;
    }

    /**
     * This method creates/updates a record based on the new type.  If an activity
     * type is set to be the same as what it would be in moodle, then no record is
     * stored (or an existing record deleted).  This lightens the load on the DB.
     * Otherwise, the value is written to the DB.
     *
     * @param string $type the new type of the activity
     */
    public function update_type($type) {
        global $DB;

        // Check if type already matches - save the DB write.
        if ($type == $this->get_type()) {
            return;
        }

        if ($type == $this->get_moodle_type()) {
            if ($this->exists()) {
                $DB->delete_records('nurs_navigation_activities', array('id' => $this->record->id));
                $this->record = false;
            }
            return;
        }

        if ($this->exists()) {
            $this->record->flaggedtype = $type;
            $DB->update_record('nurs_navigation_activities', $this->record);
        } else {
            $record = new \stdClass;
            $record->courseid = $this->courseid;
            $record->basetype = $this->basetype;
            $record->modid = $this->modid;
            $record->flaggedtype = $type;
            $id = $DB->insert_record('nurs_navigation_activities', $record);
            if ($id === false) {
                print_error(get_string('dberror', BNN_LANG_TABLE));
            } else {
                // On success, grab the new record and store it.
                $this->record = $DB->get_record('nurs_navigation_activities', array('id' => $id));
                $this->exists = true;
            }
        }
    }

    /**
     * This method checks to see if the record exists.
     *
     * @returns bool T/F indicating whether the record exists (T) or not (F).
     */
    public function exists() {
        if ($this->record != false) {
            return true;
        } else {
            return false;
        }
    }

}