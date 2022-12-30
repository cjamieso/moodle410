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

namespace block_skills_group\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Holds the data needed to draw a chart (and its filters) on the screen.
 *
 * @package    block_skills_group
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chart_renderable implements \renderable {

    /** @var int the ID of the course. */
    public $courseid;
    /** @var array holds the list of items on the feedback. */
    public $items;
    /** @var int holds the ID of the pre-course feedback. */
    public $prefeedbackid;
    /** @var array holds the ID of the post-course feedback. */
    public $postfeedbackid;

    /**
     * Constructor.
     *
     * @param  int    $courseid   the ID of the course to use
     * @param  array  $items      the master list of items in the feedback
     */
    public function __construct($courseid, $items = null) {

        $this->courseid = $courseid;
        $this->items = $items;
        $sgs = new \block_skills_group\skills_group_setting($courseid);
        $this->prefeedbackid = $sgs->get_feedback_id('pre');
        $this->postfeedbackid = $sgs->get_feedback_id('post');
    }

}
