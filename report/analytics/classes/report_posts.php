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

namespace report_analytics;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * report_posts class
 *
 * This class retrieves all post by a user from the database.
 *
 * @package    report_posts
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_posts {

    /** @const maxiumum posts by a user that will be returned (across all forums - BEFORE filtering) */
    const MAX_POSTS = 5000;
    /** @var int the course id. */
    protected $courseid;
    /** @var array filters to be used to generate the report */
    protected $filters;

    /**
     * Analytics post report constructor.
     *
     * @param  int    $courseid  the ID of the course
     * @param  array  $filters   the various filters from javascript (student, activity, etc).
     */
    public function __construct($courseid, $filters) {

        $this->courseid = $courseid;
        $this->filters = $filters;
    }

    /**
     * Retrieve form posts from a user matching the given filters.
     *
     * The functions in lib.php (forum) are not all that useful.  We only have a
     * sledgehammer to work with (get all posts in all forums in a course).  I then
     * have to manually apply the filters using php array functions.
     *
     * @param  int  $userid  the ID of the user to retrieve the list of posts for
     * @return object  an object that matches the return from forum_get_posts_by_user() with the posts/forums filtered
     */
    public function get_posts_by_user($userid) {
        global $DB;

        $temp = $DB->get_record('course', array('id' => $this->courseid), '*', MUST_EXIST);
        $course = array($this->courseid => $temp);
        $user = $DB->get_record("user", array("id" => $userid), '*', MUST_EXIST);
        $results = forum_get_posts_by_user($user, $course, false, false, 0, self::MAX_POSTS);

        $results->forums = $this->filter_forums($this->filters->activities, $results->forums);
        $results->posts = $this->filter_posts($results->forums, $results->posts, $this->filters);
        $results->username = $user->firstname . ' ' . $user->lastname;

        return $results;
    }

    /**
     * Filter the master forums list, leaving only those on the list of forums
     * requested by the user.
     *
     * @param  array  $cmids     desired list of cmids given by the user (or empty to keep all)
     * @param  array  $dbforums  full list of forums in course retrieved by forum lib.php
     * @return array  filtered list of forums, indexed by forum ID
     */
    private function filter_forums($cmids, $dbforums) {

        $forum = array();
        foreach ($dbforums as $f) {
            if (!isset($cmids) || array_search($f->cmid, $cmids) !== false) {
                $forum[$f->id] = $f;
            }
        }
        return $forum;
    }

    /**
     * Filter a full list of users posts, organizing by forum and removing those
     * outside the date range and word count (if given).
     *
     * @param  array  $forums  list of forums to include posts for
     * @param  array  $posts   full list of posts from forum lib.php
     * @return array  posts sorted by forum (indexed by forum ID)
     */
    private function filter_posts($forums, $posts) {

        $dates = $this->get_dates($this->filters->date);
        list($minwords, $maxwords) = $this->get_words($this->filters->words);
        $p = array();
        foreach ($forums as $f) {
            $forumid = $f->id;
            $p[$f->id] = array_filter($posts,
                                function ($e) use($forumid, $dates, $minwords, $maxwords) {
                                    if (!empty($dates)) {
                                        $datevalid = ($e->modified >= $dates['from']) && ($e->modified <= $dates['to']);
                                    } else {
                                        $datevalid = true;
                                    }
                                    $wordcount = count_words($e->message);
                                    if (isset($maxwords)) {
                                        $wordvalid = ($wordcount >= $minwords) && ($wordcount <= $maxwords);
                                    } else {
                                        $wordvalid = ($wordcount >= $minwords);
                                    }
                                    return $datevalid && $wordvalid && ($e->forum == $forumid);
                                });
        }
        return $p;
    }

    /**
     * Convert the dates from the form into unix timestamps.
     *
     * @param   array  $date  dates (to|from) from the form
     * @return  array  dates (to|from) in unix timestamp format
     */
    private function get_dates($date) {

        if (is_object($date)) {
            $from = \DateTime::createFromFormat('Y-m-d H:i', $date->from)->getTimestamp();
            $to = \DateTime::createFromFormat('Y-m-d H:i', $date->to)->getTimestamp();
            return array('from' => $from, 'to' => $to);
        } else {
            return array();
        }
    }

    /**
     * Retrieve the minimum and maximum word counts from the filter.
     *
     * @param  array  $words  array containing minimum and maximum number of words to filter posts with
     * @return array  array containing minimum and maximum words (with validation performed)
     */
    private function get_words($words) {

        if (isset($words)) {
            $minwords = $words->minwords;
            $maxwords = $words->maxwords;
        } else {
            $minwords = 0;
            $maxwords = null;
        }
        return array($minwords, $maxwords);
    }

}
