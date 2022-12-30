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
 * List of scheduled tasks to run:
 * 1) check for any results that are to be emailed out and email them to users
 * 2) generate new results and flag them to be emailed out
 *
 * @package    report_analytics
 * @category   report
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'report_analytics\task\email_results',
        'blocking' => 0,
        'minute' => '*/20',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'report_analytics\task\generate_results',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '1',
        'day' => '*',
        'dayofweek' => '0',
        'month' => '*'
    )
);
