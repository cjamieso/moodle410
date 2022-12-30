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
require_once($CFG->dirroot.'/blocks/skills_group/tests/skillsgroupunittest.php');

/**
 * This is the unittest class for scores_report.class.php.
 *
 * get_class_scores()
 *
 * @package    block_skills_group
 * @group      block_skills_group_tests
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_scoresreport extends skills_group_unit_test {

    /**
     * This function tests to see if individual student scores can be retrieved.
     *
     */
    public function test_get_class_scores() {

        $this->configure_settings();
        $sr = new \block_skills_group\scores_report($this->courseid);
        $scores = $sr->bin_class_scores();

        // Students 0, 5 answer 1 (converts to 0), Students 1, 6 answer 2 (converts to 1).
        // Students 2, 7 answer 3 (converts to 2), Students 3, 8 answer 4 (converts to 3).
        // Students 4. 9 answer 5 (converts to 4) -> two of each numerical answer.
        for ($i = 1; $i <= self::FEEDBACKITEMS; $i++) {
            foreach ($scores['pre'][$i] as $score) {
                $this->assertEquals(2, $score);
            }
            foreach ($scores['post'][$i] as $score) {
                $this->assertEquals(2, $score);
            }
        }

        // Retrieve single feedback item.
        $items = 1;
        $scores = $sr->get_class_scores($items);
        $i = 0;
        foreach ($scores as $score) {
            $this->assertEquals($i, $score['label']);
            $this->assertEquals(2, $score[get_string('pre', 'block_skills_group')]);
            $this->assertEquals(2, $score[get_string('post', 'block_skills_group')]);
            $i++;
        }

        // Retrieve multiple feedback items (added together).
        $items = array(1, 2, 3);
        $scores = $sr->get_class_scores($items);
        $i = 0;
        foreach ($scores as $score) {
            $this->assertEquals($i, $score['label']);
            $this->assertEquals(6, $score[get_string('pre', 'block_skills_group')]);
            $this->assertEquals(6, $score[get_string('post', 'block_skills_group')]);
            $i++;
        }
    }

}