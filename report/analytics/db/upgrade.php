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
 * Upgrade script -> force cache purge for class autoloading
 *
 * xmldb field ref:
 * |name|type|precision|signed|null|sequence|default|previous|
 *
 * @package    report_analytics
 * @category   report
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_report_analytics_upgrade($oldversion=0) {

    global $DB;
    $dbman = $DB->get_manager();
    $result = true;

    if ($oldversion < 2017050600) {

        // Force cache purge.
        purge_all_caches();

        // Update savepoint.
        upgrade_plugin_savepoint(true, 2017050600, 'report', 'analytics');
    }

    if ($oldversion < 2017080900) {

        $table = new xmldb_table('report_analytics_results');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('userids', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'courseid');
        $table->add_field('filters', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'userids');
        $table->add_field('results', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'filters');
        $table->add_field('resultstime', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'results');
        $table->add_field('emailtime', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'resultstime');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update savepoint.
        upgrade_plugin_savepoint(true, 2017080900, 'report', 'analytics');
    }

    return $result;
}
