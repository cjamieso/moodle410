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
 * Calculated (minimal decimal) question definition class.
 *
 * @package    qtype
 * @subpackage calculatedmedical
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/type/calculated/question.php');
require_once($CFG->dirroot . '/question/type/calculatedmulti/question.php');
require_once($CFG->dirroot . '/question/type/calculated/questiontype.php');

/**
 * Here we remap the calls to the question helper to use the min decimal version.
 *
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_calculatedmedical_single_question extends qtype_calculatedmulti_single_question {

    public function start_attempt(question_attempt_step $step, $variant) {
        qtype_calculatedmedical_question_helper::start_attempt($this, $step, $variant);
        parent::start_attempt($step, $variant);
    }

    public function apply_attempt_state(question_attempt_step $step) {
        qtype_calculatedmedical_question_helper::apply_attempt_state($this, $step);
        parent::apply_attempt_state($step);
    }

    public function get_variants_selection_seed() {
        return $this->stamp;
    }
}

/**
 * Here we remap the calls to the question helper to use the min decimal version.
 *
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_calculatedmedical_multi_question extends qtype_calculatedmulti_multi_question {

    public function start_attempt(question_attempt_step $step, $variant) {
        qtype_calculatedmedical_question_helper::start_attempt($this, $step, $variant);
        parent::start_attempt($step, $variant);
    }

    public function apply_attempt_state(question_attempt_step $step) {
        qtype_calculatedmedical_question_helper::apply_attempt_state($this, $step);
        parent::apply_attempt_state($step);
    }

    public function get_variants_selection_seed() {
        return $this->stamp;
    }
}

/**
 * This is a copy of the version in the calculated question, but I have changed
 * the calls to the variable substituter to be the modified version I have created
 * below.
 *
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_calculatedmedical_question_helper extends qtype_calculated_question_helper {

    public static function start_attempt(
            qtype_calculated_question_with_expressions $question,
            question_attempt_step $step, $variant) {

        $question->vs = new qtype_calculatedmedical_variable_substituter(
                $question->datasetloader->get_values($variant),
                get_string('decsep', 'langconfig'));
        $question->calculate_all_expressions();

        foreach ($question->vs->get_values() as $name => $value) {
            $step->set_qt_var('_var_' . $name, $value);
        }
    }

    public static function apply_attempt_state(
            qtype_calculated_question_with_expressions $question, question_attempt_step $step) {
        $values = array();
        foreach ($step->get_qt_data() as $name => $value) {
            if (substr($name, 0, 5) === '_var_') {
                $values[substr($name, 5)] = $value;
            }
        }

        $question->vs = new qtype_calculatedmedical_variable_substituter(
                $values, get_string('decsep', 'langconfig'));
        $question->calculate_all_expressions();
    }
}

/**
 * Update variable substituter class to detect new format and return value
 * correctly.
 *
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_calculatedmedical_variable_substituter extends qtype_calculated_variable_substituter {

    /**
     * Display a float properly formatted with a certain number of decimal places.
     *
     * Use the base class unless the new option (3) is selected.  In that case, look
     * for and remove redundant zeros.
     *
     * @param number $x the number to format
     * @param int $length restrict to this many decimal places or significant
     *      figures. If null, the number is not rounded.
     * @param int format 1 => decimalformat, 2 => significantfigures, 3=> minimum decimals.
     * @return string formtted number.
     */
    public function format_float($x, $length = null, $format = null) {
        if (!is_null($length) && !is_null($format)) {
            if ($format == '3') {
                // Round first.
                $x = sprintf('%.' . $length . 'F', $x);
                // Then strip trailing zeros.
                $x = (float)$x;
                return str_replace('.', $this->decimalpoint, $x);
            } else {
                return parent::format_float($x, $length, $format);
            }
        }
        return str_replace('.', $this->decimalpoint, $x);
    }

}
