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
require_once($CFG->dirroot.'/blocks/nurs_navigation/locallib.php');

/**
 * This is the edit_form.php for the block which controls Moodle-based block
 * configuration.
 *
 * @package    block_nurs_navigation
 * @category   block
 * @copyright  2012 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_nurs_navigation_edit_form extends block_edit_form{

    protected function specific_definition($mform) {
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Checkbox to disable section text.
        $mform->addElement('advcheckbox', 'config_disabletext', get_string('disablesectiontext', BNN_LANG_TABLE),
            '', null, array(0, 1));

        // Checkbox to enable drawing of sections.
        $mform->addElement('advcheckbox', 'config_sections', get_string('showallsections', BNN_LANG_TABLE),
            '', null, array(0, 1));

        // Multiple selection to disable use of a particular activity.
        $activities = explode(',', preg_replace("/[^A-Za-z,]+/", "", get_config('nurs_navigation', 'Activities')));
        $potential = array('none' => get_string('none', BNN_LANG_TABLE));
        $mform->addElement('static', 'customlabel', get_string('customlabel', BNN_LANG_TABLE));
        foreach ($activities as $activity) {
            $potential[$activity] = get_activity_title($activity);
            $mform->addElement('text', 'config_custom' . $activity, get_activity_title($activity));
            $mform->setType('config_custom' . $activity, PARAM_TEXT);
        }
        $options = array('multiple' => true);
        $mform->addElement('autocomplete', 'config_disableactivities', get_string('disableactivities', BNN_LANG_TABLE),
            $potential, $options);

    }
}