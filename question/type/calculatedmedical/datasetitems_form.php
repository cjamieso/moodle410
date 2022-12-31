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
 * Defines the editing form for the calculated question data set items.
 * Largely the same as the base class, but with the addition of the new
 * format type.
 *
 * @package    qtype
 * @subpackage calculatedmedical
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/edit_question_form.php');
require_once($CFG->dirroot . '/question/type/calculated/datasetitems_form.php');


/**
 * Calculated (minimum decimals) question data set items editing form definition.
 *
 * This function is the same as the base class with the addition of the third option
 * in the dropdown to handle the new format.
 *
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_dataset_min_decimals_dependent_items_form extends question_dataset_dependent_items_form {

    /**
     * In the form definition, we need to inject the new option for minimum decimals
     * in the dropdown.  This is done after the base class has created the form.
     */
    protected function definition() {

        parent::definition();
        // Inject the addtional answer format type.
        $mform = $this->_form;
        foreach ($mform->_elements as $element) {
            if (isset($element->_attributes)) {
                if (strpos($element->_attributes['name'], 'correctanswerformat') !== false) {
                    $element->_options[] = array('text' => get_string('mindecimalsformat', 'qtype_calculatedmedical'),
                        'attr' => array('value' => 3));
                }
            }
        }
    }


}
