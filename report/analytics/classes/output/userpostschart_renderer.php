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

namespace report_analytics\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Generates html for the forum posts display and filters.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userpostschart_renderer extends chart_renderer {


    /**
     * Render a list of a all requested posts to the display.
     *
     * @param  userpostschart_renderable  $renderable  data needed to output the list of posts
     * @return string  the html for the requested posts
     */
    protected function render_userpostschart(userpostschart_renderable $renderable) {

        $data = $renderable->export_for_userposts_template($this);
        return $this->render_from_template('report_analytics/postsdata', $data);
    }

}
