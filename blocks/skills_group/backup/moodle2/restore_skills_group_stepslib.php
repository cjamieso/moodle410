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

/**
 * skills_group block restore class for defining steps.  This class is for
 * the mapping table.
 *
 * @package    block_skills_group
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_skills_group_mapping_block_structure_step extends restore_structure_step {

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
        // Note: the second parameter refers to the xml file structure (tag path) where the data is saved.
        $paths[] = new restore_path_element('skills_group_mapping', '/block/skills_group_mapping');
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
    protected function process_skills_group_mapping($data) {
        global $DB;

        $data = (object)$data;
        $query = "SELECT id FROM {skills_group_mapping}
                            WHERE courseid = ? AND position = ?";
        $params = array($this->get_courseid(), $data->position);
        // Check for and remove any old records (merge/delete produces duplicates).
        $records = $DB->get_records_sql($query, $params);
        if (count($records) > 0) {
            foreach ($records as $record) {
                $DB->delete_records('skills_group_mapping', array('id' => $record->id));
            }
        }
        // Now insert the new record.
        $data->courseid = $this->get_courseid();
        $newitemid = $DB->insert_record('skills_group_mapping', $data);
    }

    /**
     * This method is empty, since the settings table has no files.
     * I've left it here as a placeholder in case it does later contain
     * pointers to files.
     *
     */
    protected function after_execute() {
    }
}
