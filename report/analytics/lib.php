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
 * Lib file for the project for standard moodle lib requirements.
 *
 * @package    report_analytics
 * @category   report
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the report items.
 *
 * The "extends" variant is now deprecated: see MDL-49643
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_analytics_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/analytics:view', $context)) {
        $url = new moodle_url('/report/analytics/index.php', array('id' => $course->id));
        $navigation->add(get_string('betaname', 'report_analytics'), $url, navigation_node::TYPE_SETTING,
                         null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Add node to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree tree to add node to
 * @param stdClass $user user to add node for
 * @param bool $iscurrentuser unused flag for current user
 * @param stdClass $course current course being used
 */
function report_analytics_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {

    if (empty($course)) {
        $course = get_fast_modinfo(SITEID)->get_course();
    } else {
        $context = context_course::instance($course->id);
        if (!has_capability('report/analytics:studentview', $context)) {
            return;
        }
    }
    $url = new moodle_url('/report/analytics/index.php', array('id' => $course->id));
    $node = new core_user\output\myprofile\node('reports', 'analytics', get_string('betaname', 'report_analytics'), null, $url);
    $tree->add_node($node);
}

/**
 * Callback to verify if the given instance of store is supported by this report or not.
 *
 * @param string $instance store instance.
 *
 * @return bool returns true if the store is supported by the report, false otherwise.
 */
function report_analytics_supports_logstore($instance) {
    if ($instance instanceof \core\log\sql_internal_table_reader || $instance instanceof \logstore_legacy\log\store) {
        return true;
    }
    return false;
}
