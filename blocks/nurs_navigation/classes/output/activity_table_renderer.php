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

namespace block_nurs_navigation\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;

/**
 * Generates html for list of quizzes/assignments/quests.
 *
 * @package    block_nurs_navigation
 * @copyright  2018 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_table_renderer extends plugin_renderer_base {

    /**
     * Render the table of activities.
     *
     * @param  activity_table_renderable  $renderable  data needed to output the html
     * @return string  html to draw the table
     */
    public function render_activity_table(activity_table_renderable $renderable) {
        $data = $renderable->export_for_template($this);
        return $this->render_from_template('block_nurs_navigation/activity_table', $data);
    }

}
