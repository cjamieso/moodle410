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
require_once($CFG->libdir.'/formslib.php');

/**
 * Moodle form with two dates for selecting a date range.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class datetime_form extends \moodleform {
    /** @var $datefieldoptions options to use for moodle form, enable/disable handled via custom JS */
    public static $datefieldoptions = array('step' => 1, 'optional' => false);

    /**
     * Form definition: create a form with two date_time_selectors.
     *
     */
    public function definition() {
        $mform = &$this->_form;
        // Form can be used for multiple dates, make the IDs unique.
        $prefix = time();

        $mform->addElement('date_time_selector', $prefix . 'datefrom', get_string('from', 'moodle'),
            self::$datefieldoptions);
        $mform->addElement('date_time_selector', $prefix . 'dateto', get_string('to', 'moodle'),
            self::$datefieldoptions);
    }

}