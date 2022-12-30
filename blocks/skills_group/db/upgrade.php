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


/**
 * This is the upgrade script for the project.
 *
 * @package    block_skills_group
 * @category   block
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_skills_group_upgrade($oldversion=0) {

    global $DB;
    $dbman = $DB->get_manager();
    $result = true;

    // Sept. 13, 2014 version added the skills_group_settings table.
    if ($oldversion < 2014091302) {
        $table = new xmldb_table('skills_group_settings');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('feedbackid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupingid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2014091302, 'skills_group');
    }
    // On Oct. 5th, 2014 I added the maxsize (for groups) setting.
    if ($oldversion < 2014100500) {
        $table = new xmldb_table('skills_group_settings');
        $field = new xmldb_field('maxsize', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2014100500, 'skills_group');
    }
    // Later on Oct. 5th I added the other two tables: {skills_group, skills_group_student}.
    if ($oldversion < 2014100502) {
        $table = new xmldb_table('skills_group');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('allowjoin', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('skills_group_student');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('finalizegroup', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2014100502, 'skills_group');
    }

    // Nov. 11 2015 version1 added the fields for date.
    if ($oldversion < 2015111100) {
        $table = new xmldb_table('skills_group_settings');
        $field = new xmldb_field('date', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2015111100, 'skills_group');
    }

    // Nov. 11 2015 version2 added the fields for threshold.
    if ($oldversion < 2015111101) {
        $table = new xmldb_table('skills_group_settings');
        $field = new xmldb_field('threshold', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, 1);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2015111101, 'skills_group');
    }

    // Nov. 19 2015 "01" added the fields for whether to allow naming.
    if ($oldversion < 2015111906) {
        $table = new xmldb_table('skills_group_settings');
        $field = new xmldb_field('allownaming', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 1);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2015111906, 'skills_group');
    }

    // May 25 2016 version added the fields for whether to allow instructor created groups.
    if ($oldversion < 2016052500) {
        $table = new xmldb_table('skills_group_settings');
        $field = new xmldb_field('instructorgroups', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2016052500, 'skills_group');
    }

    // On July 16th, 2016 I added the post course feedback option.
    if ($oldversion < 2016071600) {
        $table = new xmldb_table('skills_group_settings');
        $field = new xmldb_field('postfeedbackid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2016071600, 'skills_group');
    }

    // On Nov. 30th, 2016 I added the groupingid parameter for the student lock.
    if ($oldversion < 2016113002) {
        $table = new xmldb_table('skills_group_student');
        $field = new xmldb_field('groupingid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Find all users that locked in at least one course AND:
        // Were in a group that was attached to the grouping used by the plugin.
        // For students using the plugin in multiple courses, 2+ records for their userid will be returned (different groupingids).
        // The row_number() portion is added to keep Moodle happy (first item must be unique).
        $sql = "SELECT row_number() OVER () AS rownumber, gm.userid, gg.groupingid
        FROM {groupings} gs
        JOIN {groupings_groups} gg ON (gs.id = gg.groupingid)
        JOIN {skills_group_settings} sgs ON (gs.id = sgs.groupingid)
        JOIN {groups_members} gm ON (gg.groupid = gm.groupid)
        JOIN {skills_group_student} sgst ON (sgst.userid = gm.userid)";
        $records = $DB->get_records_sql($sql);

        // Iterate through each tuple (userid, groupingid).
        foreach ($records as $record) {
            $oldrecord = $DB->get_record('skills_group_student', array('userid' => $record->userid));
            // For old records, groupingid defaults to 0, check it and update.
            if ($oldrecord->groupingid == 0) {
                // Replace groupingid and update.
                $oldrecord->groupingid = $record->groupingid;
                $DB->update_record('skills_group_student', $oldrecord);
            } else {
                // If the groupingid is already non-zero, then the record has already been updated.
                // That is, the student had the plugin used in multiple courses.
                // Create a second lock entry to represent the second course.
                $newrecord = new \stdClass;
                $newrecord->userid = $record->userid;
                $newrecord->finalizegroup = 1;
                $newrecord->groupingid = $record->groupingid;
                $DB->insert_record('skills_group_student', $newrecord);
            }
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2016113002, 'skills_group');
    }

    // On Dec 1st, 2016 I added the mapping table.
    if ($oldversion < 2016120100) {
        $table = new xmldb_table('skills_group_mapping');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('position', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('accreditationid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2016120100, 'skills_group');
    }

    // On July 24, 2018 I added the settings for group member addition and viewing.
    if ($oldversion < 2018072401) {
        $table = new xmldb_table('skills_group_settings');
        $field = new xmldb_field('allowadding', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 1);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('allowgroupview', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 1);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('feedbackid');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
            upgrade_set_timeout(7200);
            $dbman->rename_field($table, $field, 'prefeedbackid');
        }
        // Update savepoint.
        upgrade_block_savepoint(true, 2018072401, 'skills_group');
    }

    // On Oct. 21, 2020, I added the note field to the group table
    if ($oldversion < 2020102100) {

        $table = new xmldb_table('skills_group');
        $field = new xmldb_field('note', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update savepoint.
        upgrade_block_savepoint(true, 2020102100, 'skills_group');
    }

    return $result;
}
