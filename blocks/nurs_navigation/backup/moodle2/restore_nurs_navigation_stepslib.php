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

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/blocks/nurs_navigation/locallib.php');

/**
 * Nurs Navigation block restore class for defining steps: there are
 * two classes here since the plugin requires two largely unrelated
 * tables.
 *
 * @package    block_nurs_navigation
 * @copyright  2013 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_nurs_navigation_block_structure_step extends restore_structure_step {

    /**
     * This method sets up the restore.  It's taken largely from the
     * moodle docs page.  I think the path has to match exactly to the
     * path from the backup operation.
     *
     * @return array Path elements indicating what to restore.
     *
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('nurs_navigation', '/block/nurs_navigation');
        return $paths;
    }

    /**
     * This method inserts the backed up records into the database
     * one at a time.  The only needed change is to update the courseid.
     * I've put an explicit check here for the same record in case the
     * user restores twice into the same course.
     *
     * @param object $data The record to insert
     *
     */
    protected function process_nurs_navigation($data) {
        global $DB;

        $data = (object)$data;
        $query = "SELECT COUNT(id) FROM {nurs_navigation}
                            WHERE courseid = ? AND fileid = ? AND sectionname = ?";
        $params = array($this->get_courseid(), $data->fileid, $data->sectionname);
        if ($DB->count_records_sql($query, $params) == 0) {
            $data->courseid = $this->get_courseid();
            $newitemid = $DB->insert_record('nurs_navigation', $data);
        }
        // Remove any old section records -> can happen on merge or delete.
        $selectcount = 'SELECT COUNT(id) ';
        $select = 'SELECT id ';
        $query = 'FROM {nurs_navigation} WHERE courseid = ? AND fileid <> ? AND sectionname = ?';
        if ($DB->count_records_sql($selectcount.$query, $params) > 0) {
            echo 'found extra records';
            $records = $DB->get_records_sql($select.$query, $params);
            foreach ($records as $record) {
                $DB->delete_records('nurs_navigation', array('id' => $record->id));
            }
        }
    }

}

/**
 * Nurs Navigation block restore class for the settings table.
 *
 * @package    block_nurs_navigation
 * @copyright  2013 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_nurs_navigation_settings_block_structure_step extends restore_structure_step {

    /**
     * Setup restore operation.
     *
     * @return array Path elements indicating what to restore.
     *
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('nurs_navigation_settings', '/block/nurs_navigation_setting');
        return $paths;
    }

    /**
     * This method inserts each settings record into the database unless
     * it already exists.
     *
     * @param mixed $data The record to insert
     *
     */
    protected function process_nurs_navigation_settings($data) {
        global $DB;

        $data = (object)$data;
        $query = "SELECT COUNT(id) FROM {nurs_navigation_settings}
                            WHERE courseid = ? AND sectionname = ? AND disableicon = ?";
        $params = array($this->get_courseid(), $data->sectionname, $data->disableicon);
        // Check for record already existing -> skip on the customlabel check, NULLs get handled strangely.
        if ($DB->count_records_sql($query, $params) == 0) {
            $data->courseid = $this->get_courseid();
            $newitemid = $DB->insert_record('nurs_navigation_settings', $data);
        }
    }

}

/**
 * Nurs Navigation block restore class for the activities table.
 *
 * @package    block_nurs_navigation
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_nurs_navigation_activities_block_structure_step extends restore_structure_step {

    /** @var array IDs of records to process */
    protected $ids = array();

    /**
     * Setup restore operation.
     *
     * @return array Path elements indicating what to restore.
     *
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('nurs_navigation_activities', '/block/nurs_navigation_activity');
        return $paths;
    }

    /**
     * Save all IDs for use in the after_restore() method.
     *
     * @param mixed $data The record to insert
     *
     */
    protected function process_nurs_navigation_activities($data) {
        global $DB;

        $data = (object)$data;
        $this->ids[] = $data->id;
    }

    /**
     * Processes the list of IDs and uses the {backup_ids_temp} table to find the
     * activity ID in the new course to map the activity categorization.
     *
     */
    protected function after_restore() {
        global $DB;

        if (empty($this->ids)) {
            return;
        }
        list($insql, $inparams) = $DB->get_in_or_equal($this->ids, SQL_PARAMS_NAMED, 'id');
        $sql = "SELECT a.*, b.newitemid
                FROM {backup_ids_temp} b
                INNER JOIN {nurs_navigation_activities} a ON b.itemid = a.modid
                WHERE a.id $insql";
        $records = $DB->get_records_sql($sql, $inparams);
        foreach ($records as $record) {
            $record->modid = $record->newitemid;
            unset($record->newitemid);
            if ($record->courseid != $this->get_courseid()) {
                $record->courseid = $this->get_courseid();
            }
            $DB->insert_record('nurs_navigation_activities', $record);
        }

    }

}
