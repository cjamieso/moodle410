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
require_once($CFG->dirroot.'/blocks/skills_group/backup/moodle2/backup_skills_group_stepslib.php');

/**
 * skills_group block backup class: this class executes the steps.
 *
 * @package    block_skills_group
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_skills_group_block_task extends backup_block_task {

    /**
     * There are no "settings" for the backup.
     *
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * The two main steps get created here.  The filenames of the
     * xml file are used in the restore process.
     *
     */
    protected function define_my_steps() {
        // Note: skills_group contains two tables to backup.
        $this->add_step(new backup_skills_group_block_structure_step_mapping('skills_group_mapping', 'skills_group_mapping.xml'));
    }

    /**
     * This method is used to indicate what file areas belong to the
     * block.
     *
     * @return array Empty array.
     *
     */
    public function get_fileareas() {
        return array();
    }

    /**
     * This is a method only exists for block backups.  If a block instance
     * contains data, it would get backed up here.  I chose to store settings in
     * a separate table, so nothing goes here.
     *
     */
    public function get_configdata_encoded_attributes() {
        // No special handling for config data.
        return array();
    }

    /**
     * This method would re-encode any links that were hardcoded with
     * information that might change.  All of my links are encoded at
     * run time, so no conversion is necessary.
     *
     */
    static public function encode_content_links($content) {
        return $content;
    }
}