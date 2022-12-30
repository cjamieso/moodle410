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
 * averagefilter class
 *
 * This class retrieves a list of possible "averages" that can be used to
 * compare to a data set.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class averagefilter extends filter {

    /** @const text mapping for top 15% of class */
    const TOP15 = 'top15';
    /** @const text mapping for all students in class */
    const ALL = 'all';
    /** @const text mapping for bottom 15% of class */
    const BOTTOM15 = 'bot15';

    /**
     * Returns average filter data.
     *
     * @return array  an array containing a list of possible comparisons for "average"
     */
    public function get_filter_data() {
        return array('none' => get_string('none'), self::TOP15 => get_string('top15', 'report_analytics'),
            self::ALL => get_string('allstudents', 'report_analytics'),
            self::BOTTOM15 => get_string('bottom15', 'report_analytics'));
    }

}
