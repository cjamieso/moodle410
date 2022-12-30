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
require_once($CFG->dirroot.'/blocks/skills_group/tests/skillsgroupunittest.php');

/**
 * This is the unittest class for skills_group_mapping.class.php.
 *
 * update_record() new | existing
 * get_accreditation_id()
 * get_all_mappings()
 *
 * @package    block_skills_group
 * @group      block_skills_group_tests
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_skillsgroupmapping extends skills_group_unit_test {

    /**
     * This function tests to see that the feedback ID gets correctly stored and retrieved
     * in the class.
     *
     */
    public function test_update_record() {

        $sgm = new \block_skills_group\skills_group_mapping($this->courseid);
        $sgm->update_record(1, 54);
        $this->check_mapping_record($this->courseid, array('position' => 1, 'accreditationid' => 54));

        $sgm->update_record(2, 75);
        $this->check_mapping_record($this->courseid, array('position' => 2, 'accreditationid' => 75));

        $sgm->update_record(1, 100);
        $this->check_mapping_record($this->courseid, array('position' => 1, 'accreditationid' => 100));
    }

    /**
     * This function tests update_record first, since this is how a record
     * is created. If this test fails, all subsequent results are suspect.
     *
     */
    public function test_get_accreditation_id() {

        $sgm = new \block_skills_group\skills_group_mapping($this->courseid);
        $sgm->update_record(1, 54);
        $this->assertEquals($sgm->get_accreditation_id(1), 54);
        $sgm->update_record(15, 554);
        $this->assertEquals($sgm->get_accreditation_id(15), 554);
    }

    /**
     * This function tests the exists() method.  exists() returns T/F depending
     * on if the settings entry has been created.
     *
     */
    public function tests_get_all_mappings() {

        $sgm = new \block_skills_group\skills_group_mapping($this->courseid);
        $positions = array(1, 2, 3, 4, 5);
        $accreditationids = array(54, 63, 72, 81, 90);
        for ($i = 0; $i < count($positions); $i++) {
            $sgm->update_record($positions[$i], $accreditationids[$i]);
        }
        $mappings = $sgm->get_all_mappings();
        $i = 0;
        foreach ($mappings as $mapping) {
            $this->assertEquals($mapping->position, $positions[$i]);
            $this->assertEquals($mapping->accreditationid, $accreditationids[$i]);
            $i++;
        }
    }

    /**********************************************************************
     * Helper functions are below:
     **********************************************************************/
    /**
     * This function is used to check a mapping record from the DB.
     *
     * @param int $courseid This is the ID of the course for which we should test
     * @param object $mapping Record with all skills_group mapping
     */
    private function check_mapping_record($courseid, $mapping) {
        global $DB;

        $record = $DB->get_record('skills_group_mapping', array('courseid' => $courseid, 'position' => $mapping['position']));
        $this->assertEquals($record->accreditationid, $mapping['accreditationid']);
    }
}
