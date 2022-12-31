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
 * Unit tests for min decimal question type.
 *
 * @package    qtype
 * @subpackage calculatedmedical
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/calculatedmedical/question.php');
require_once($CFG->dirroot . '/question/type/calculatedmedical/questiontype.php');

/**
 * Unit tests for {@link qtype_calculatedmedical_variable_substituter}.
 *
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_calculatedmedical_variable_substituter_test extends advanced_testcase {

    /**
     * Test rounding to ensure that redundant zeros are not displayed.  Additionally,
     * test to ensure that the maximum number of decimals is enforced.
     */
    public function test_format_float() {

        $vs = new qtype_calculatedmedical_variable_substituter(array('a' => -1, 'b' => 2), '.');
        $this->assertSame('0.7', $vs->format_float(0.7, 1, 3));
        $this->assertSame('0.7', $vs->format_float(0.7, 2, 3));
        $this->assertSame('0.7', $vs->format_float(0.7, 3, 3));
        $this->assertSame('0.667', $vs->format_float(0.666667, 3, 3));
        $this->assertSame('11', $vs->format_float(10.99, 1, 3));
        $this->assertSame('10.7', $vs->format_float(10.7, 3, 3));
        $this->assertSame('100.7', $vs->format_float(100.7, 3, 3));
    }

}
