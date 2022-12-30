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

use renderer_base;
use stdClass;

/**
 * Holds the data needed to the userposts chart on the page.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userpostschart_renderable extends chart_renderable {

    /** @var array defaults for the word count filters */
    protected $worddefaults;
    /** @var array a set of posts by a user. */
    public $postlist;

    /**
     * Constructor.
     *
     * @param  int    $courseid    the ID of the course to use
     * @param  array  $options     options given to create the chart
     * @param  array  $filters     the filters employed by the user
     * @param  array  $postlist    the list of posts and list of forums for the user
     */
    public function __construct($courseid, $options = array(), $filters = array(), $postlist = array()) {

        $this->worddefaults = array('min' => 0, 'max' => 9999);
        $this->postlist = $postlist;
        parent::__construct($courseid, $options, $filters);
        $this->options['types'] = 'forum';
    }

    /**
     * Export data for mustache template of filters and placeholder.
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the template
     */
    public function export_for_template(renderer_base $output) {

        $data = parent::export_for_template($output);
        $data->activity = $this->get_filter_data('activity', get_string('activities'), true);
        $data->student = $this->get_filter_data('student', get_string('students'), true, 'style="display: none;"');
        $data->grade = $this->get_filter_data('grade');
        $data->minwords = $this->get_filter_data('wordcount', 'min');
        $data->maxwords = $this->get_filter_data('wordcount', 'max');
        return $data;
    }

    /**
     * Gets the data for a wordcount filter.
     *
     * @param  string  $type  the type of word count {'min'|'max'}
     * @return array  data for a word count filter
     */
    protected function get_wordcount_filter_data($type) {
        return array('label' => ucfirst($type) . ' ' . get_string('words', 'report_analytics'), 'class' => 'wordcount',
            'name' => time() .  $type . 'wordcount', 'value' => $this->worddefaults[$type]);
    }

    /**
     * Export data for rendering a toolbar using a template.
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the toolbar
     */
    public function export_for_toolbar_template(renderer_base $output) {

        $chartinfo = $this->get_chart_info();
        $data = new stdClass();
        $data->png = false;
        $data->excel = $this->get_excel_export_data($chartinfo['value']);
        $data->wordcloud = true;
        $data->undo = true;
        return $data;
    }

    /**
     * Export data for rendering a set of posts.
     *
     * @param  object  $output  the output renderer
     * @return object  the data needed to render the toolbar
     */
    public function export_for_userposts_template(renderer_base $output) {

        $data = new stdClass();
        $data->students = array();
        foreach ($this->filters->students as $id) {
            if (isset($this->postlist[$id])) {
                $username = $this->postlist[$id]->username;
                $data->students[] = array('name' => $username, 'postlist' => $this->get_post_list_data($id));
            }
        }
        return $data;
    }

    /**
     * Get the data for displaying a list of all posts by a user one forum at a
     * time.  If a student has no posts, return false.
     *
     * @param  int  $studentid  the ID of the student
     * @return array|false  list of all posts by a user (over all forums) or false if no posts found
     */
    protected function get_post_list_data($studentid) {
        if (count($this->postlist[$studentid]->posts) == 0) {
            return false;
        } else {
            $forums = array();
            foreach ($this->postlist[$studentid]->posts as $forumid => $posts) {
                $forums[] = $this->get_forum_posts_data($studentid, $forumid, $posts);
            }
            return array('forums' => $forums);
        }
    }

    /**
     * Get the data for a list of all posts by a user in a forum.  This uses the
     * forum_print_post() method to trap the html for inclusion in a template.
     *
     * @param  int    $studentid  the ID of the student
     * @param  int    $forumid    the ID of the forum
     * @param  array  $posts      an array of all posts by the user
     * @return array  the data for all posts by a user in a particular forum
     */
    protected function get_forum_posts_data($studentid, $forumid, $posts) {
        global $USER;

        $forum = $this->postlist[$studentid]->forums[$forumid];
        $return = array('name' => $forum->name, 'link' => '../../mod/forum/view.php?id=' . $forum->cmid);
        ob_start();
        foreach ($posts as $post) {
            $discussion = $this->get_discussion($post->discussion);
            $course = $this->get_course();
            $entityfactory = \mod_forum\local\container::get_entity_factory();
            $postentity = $entityfactory->get_post_from_stdclass($post);
            $discussionentity = $entityfactory->get_discussion_from_stdclass($discussion);
            $forumentity = $entityfactory->get_forum_from_stdclass($forum, $forum->cm->context, $forum->cm, $course);
            $rendererfactory = \mod_forum\local\container::get_renderer_factory();
            $postsrenderer = $rendererfactory->get_single_discussion_posts_renderer(null, false);
            echo $postsrenderer->render($USER, [$forumentity], [$discussionentity], [$postentity]);
        }
        $return['postshtml'] = ob_get_contents();
        ob_end_clean();
        $return['postcount'] = count($posts);
        return $return;
    }

    /**
     * Retrieve a discussion object from the database - needed for forum_print_post().
     *
     * @param  int     $discussionid  the ID of the discussion to retrieve
     * @return object  the full discussion record from the database
     */
    protected function get_discussion($discussionid) {
        global $DB;

        return $DB->get_record('forum_discussions', array('id' => $discussionid), '*', MUST_EXIST);
    }

    /**
     * Retrieve a course object from the database - needed for forum_print_posts().
     *
     * @return object  the full course record from the database
     */
    protected function get_course() {
        global $DB;

        return $DB->get_record('course', array('id' => $this->courseid), '*', MUST_EXIST);
    }

}
