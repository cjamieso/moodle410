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

namespace report_analytics;

defined('MOODLE_INTERNAL') || die();

/**
 * Class that wraps a scheduled results record retrieved from the DB.  This ensures
 * that proper defaults can be assigned.  Some basic methods for saving/loading
 * the record are included as well.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduled_results_record {

    /** Whether record exists in DB */
    protected $exists = false;
    /** DB record ID (if it exists) */
    protected $id;
    /** Course ID */
    protected $courseid;
    /** User IDs to receive emails */
    protected $userids;
    /** Saved filters */
    protected $filters = null;
    /** Results */
    protected $results = null;
    /** Timstamp of last time results were generated */
    protected $resultstime = 0;
    /** Timstamp of last time results were emailed */
    protected $emailtime = 0;

    /**
     * The constructor saves the courseid, userid, and attempt to retrieve any
     * pre-existing record.
     *
     * @param  int|object  $courseidorobject  the ID of the course or an existing record
     */
    public function __construct($courseidorobject) {

        if (is_object($courseidorobject)) {
            if (isset($courseidorobject->id)) {
                $this->exists = true;
                $this->id = $courseidorobject->id;
            } else {
                $this->exists = false;
            }
            $this->set_userids($courseidorobject->userids);
            $this->set_filters($courseidorobject->filters);
            $this->set_results($courseidorobject->results);
            $this->set_results_time($courseidorobject->resultstime);
            $this->set_email_time($courseidorobject->emailtime);
            $this->courseid = $courseidorobject->courseid;
        } else {
            $this->courseid = $courseidorobject;
            $this->retrieve();
        }
    }

    /**
     * Retrieve the most up-to-date copy of the record (if one exists).
     */
    public function retrieve() {
        global $DB;
        $record = $DB->get_record('report_analytics_results', array('courseid' => $this->courseid));
        if ($record !== false) {
            $this->exists = true;
            $this->id = $record->id;
            $this->set_userids($record->userids);
            $this->set_filters($record->filters);
            $this->set_results($record->results);
            $this->set_results_time($record->resultstime);
            $this->set_email_time($record->emailtime);
        }
    }

    /**
     * This method checks to see if the record exists.
     *
     * @return bool  T/F indicating whether the record exists (T) or not (F).
     */
    public function exists() {
        return $this->exists;
    }

    /**
     * This method updates or creates the record.
     */
    public function save() {
        global $DB;

        if (empty($this->courseid)) {
            throw new \Exception(get_string('invalidcourse', 'report_analytics'));
        }
        if (empty($this->userids)) {
            $this->delete();
            return;
        }

        $record = new \stdClass;
        $record->courseid = $this->courseid;
        $record->userids = $this->userids;
        $record->filters = $this->filters;
        $record->results = $this->results;
        $record->resultstime = $this->resultstime;
        $record->emailtime = $this->emailtime;
        if ($this->exists() && isset($this->id)) {
            $record->id = $this->id;
            $DB->update_record('report_analytics_results', $record);
        } else {
            $id = $DB->insert_record('report_analytics_results', $record);
            if ($id === false) {
                throw new \Exception(get_string('dberror', 'report_analytics'));
            } else {
                $this->id = $id;
                $this->exists = true;
            }
        }
    }

    /**
     * This function deletes the record from the database and marks it as no longer existing and
     * resets the various data fields.
     */
    public function delete() {
        global $DB;

        if ($this->exists) {
            if ($DB->count_records('report_analytics_results', array('courseid' => $this->courseid))) {
                $DB->delete_records('report_analytics_results', array('courseid' => $this->courseid));
                $this->exists = false;
                $this->userids = null;
                $this->filters = null;
                $this->results = null;
                $this->resultstime = 0;
                $this->emailtime = 0;
            }
        }
    }

    /**
     * This function retrieves the course ID.
     *
     * @return int  the ID of the course
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Sets the userids field.
     *
     * @param  array|json  $userids  the userids value to set in the DB
     */
    public function set_userids($userids) {
        $this->userids = (isset($userids)) ? $this->to_json($userids) : null;
    }

    /**
     * Converts a variable to JSON, however, this function checks to
     * see if the variable is already in JSON format.
     *
     * @param  array|json  $variable  the variable to convert
     * @return string  the variable converted to JSON
     */
    protected function to_json($variable) {

        if (is_array($variable)) {
            return json_encode($variable);
        } else {
            json_decode($variable);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return json_encode($variable);
            } else {
                return $variable;
            }
        }
    }

    /**
     * Gets the userids field from the cached record.
     *
     * @return array  the userids from the DB
     */
    public function get_userids() {

        if (!empty($this->userids)) {
            return json_decode($this->userids, true);;
        } else {
            return array();
        }
    }

    /**
     * Sets the filters field.
     *
     * @param  array|json  $filters  the filters desired by the user
     */
    public function set_filters($filters) {
        $this->filters = (isset($filters)) ? $this->to_json($filters) : null;
    }

    /**
     * Gets the filters field from the cached record.
     *
     * @return array  the filters from the DB
     */
    public function get_filters() {

        if (!empty($this->filters)) {
            return json_decode($this->filters);;
        } else {
            return array();
        }
    }

    /**
     * Sets the results field.
     *
     * @param  array|json  $results  the results from checking the filters
     */
    public function set_results($results) {
        $this->results = (isset($results)) ? $this->to_json($results) : null;
    }

    /**
     * Gets the results field from the cached record.
     *
     * @return array  the results from checking the filters
     */
    public function get_results() {

        if (!empty($this->results)) {
            return json_decode($this->results, true);;
        } else {
            return array();
        }
    }

    /**
     * Sets the results timestamp field.
     *
     * @param  int  $unixtime  unix timestamp for last time results were generated
     */
    public function set_results_time($unixtime = 0) {
        $this->resultstime = $unixtime;
    }

    /**
     * Gets the results timestamp field.
     *
     * @return int  unix timestamp for last time results were generated
     */
    public function get_results_time() {
        return $this->resultstime;
    }

    /**
     * Sets the email timestamp field.
     *
     * @param  int  $unixtime  unix timestamp for last time results were emailed
     */
    public function set_email_time($unixtime = 0) {
        $this->emailtime = $unixtime;
    }

    /**
     * Gets the email timestamp field.
     *
     * @return int  unix timestamp for last time results were emailed
     */
    public function get_email_time() {
        return $this->emailtime;
    }

}
