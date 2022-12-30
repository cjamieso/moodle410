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
 * skill_group block backup class for defining steps.  Backup the settings
 * in the mapping table.
 *
 * @package    block_skills_group
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_skills_group_block_structure_step_mapping extends backup_block_structure_step {

    /**
     * This method creates the backup element and ensures that all
     * course related records existing in the database are stored to
     * be used later in the restore process.  There are a number of
     * other optional steps, but none were required.
     *
     */
    protected function define_structure() {
        $skillsgroupmapping = new backup_nested_element('skills_group_mapping', array('id'), array('position', 'courseid',
            'accreditationid'));
        $skillsgroupmapping->set_source_table('skills_group_mapping', array('courseid' => backup::VAR_COURSEID));
        return $this->prepare_block_structure($skillsgroupmapping);
    }
}
