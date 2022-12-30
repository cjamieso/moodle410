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
 * Any functions that are used across several different modules are here.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analytics_helper {

    /**
     * This function set the page header -> JS/CSS includes.
     *
     */
    public static function set_header() {
        global $PAGE;

        // Css.
        $PAGE->requires->css('/report/analytics/css/multiple-select.css');
        // Js files.
        $PAGE->requires->js_call_amd('report_analytics/report_analytics', 'init');
        // Jquery UI.
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui-css');
        // Language strings.
        $PAGE->requires->strings_for_js(array('nofilters', 'baddate', 'events', 'totalevents', 'badrequest', 'average', 'nodata',
            'toomanyevents', 'nogradevalue', 'allcore', 'nocriterion', 'nocriterionvalue', 'removecriterionalt', 'nocopyinbrowser',
            'nousersselected', 'nouserscriteria', 'norecipients', 'reads', 'writes', 'grades', 'engagement'), 'report_analytics');
        $PAGE->requires->strings_for_js(array('to', 'from', 'page'), 'moodle');
    }
}
