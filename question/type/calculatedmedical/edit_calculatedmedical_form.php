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
 * Defines the editing form for the calculated question type.
 *
 * @package    qtype
 * @subpackage calculatedmedical
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/calculatedmulti/edit_calculatedmulti_form.php');

/**
 * Calculated question type editing form definition.
 * This function is the same as the base class with the addition of the third option
 * in the dropdown to handle the new format.
 *
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_calculatedmedical_edit_form extends qtype_calculatedmulti_edit_form {

    /**
     * In the form definition, we need to inject the new option for minimum decimals
     * in the dropdown.  This is done after the base class has created the form.
     */
    public function get_per_answer_fields($mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
        $repeated = parent::get_per_answer_fields($mform, $label, $gradeoptions, $repeatedoptions, $answersoption);
        // Inject the addtional answer format type.
        foreach ($repeated as $form) {
            if (isset($form->_name)) {
                if ($form->_name == "answerdisplay") {
                    if (isset($form->_elements)) {
                        foreach ($form->_elements as $element) {
                            if ($element->_attributes['name'] == "correctanswerformat") {
                                $element->_options[] = array('text' => get_string('mindecimalsformat', 'qtype_calculatedmedical'),
                                    'attr' => array('value' => 3));
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $repeated;
    }

    public function qtype() {
        return 'calculatedmedical';
    }

}
