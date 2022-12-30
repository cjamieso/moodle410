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
require_once($CFG->libdir . '/dataformatlib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * userpostschart class
 *
 * This class is used for the display a list of a user's posts.
 *
 * Note: the list of commond words was originally taken from:
 * https://github.com/arc12/Text-Mining-Weak-Signals/wiki/Standard-set-of-english-stopwords
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userpostschart extends chart{

    /** @const maximum number of unique words to include in the cloud */
    const MAX_UNIQUE_WORDS = 80;
    /** @var array list of common words to exclude from cloud */
    protected $commonwords = array("a", "about", "above", "across", "after", "again", "against", "all", "almost", "alone", "along",
        "already", "also", "although", "always", "am", "among", "an", "and", "another", "any", "anybody", "anyone", "anything",
        "anywhere", "are", "area", "areas", "aren't", "around", "as", "ask", "asked", "asking", "asks", "at", "away", "b", "back",
        "backed", "backing", "backs", "be", "became", "because", "become", "becomes", "been", "before", "began", "behind", "being",
        "beings", "below", "best", "better", "between", "big", "both", "but", "by", "c", "came", "can", "cannot", "can't", "case",
        "cases", "certain", "certainly", "clear", "clearly", "come", "could", "couldn't", "d", "did", "didn't", "differ",
        "different", "differently", "do", "does", "doesn't", "doing", "done", "don't", "down", "downed", "downing", "downs",
        "during", "e", "each", "early", "either", "end", "ended", "ending", "ends", "enough", "even", "evenly", "ever", "every",
        "everybody", "everyone", "everything", "everywhere", "f", "face", "faces", "fact", "facts", "far", "felt", "few", "find",
        "finds", "first", "for", "four", "from", "full", "fully", "further", "furthered", "furthering", "furthers", "g", "gave",
        "general", "generally", "get", "gets", "give", "given", "gives", "go", "going", "good", "goods", "got", "great", "greater",
        "greatest", "group", "grouped", "grouping", "groups", "h", "had", "hadn't", "has", "hasn't", "have", "haven't", "having",
        "he", "he'd", "he'll", "her", "here", "here's", "hers", "herself", "he's", "high", "higher", "highest", "him", "himself",
        "his", "how", "however", "how's", "i", "i'd", "if", "i'll", "i'm", "important", "in", "interest", "interested",
        "interesting", "interests", "into", "is", "isn't", "it", "its", "it's", "itself", "i've", "j", "just", "k", "keep", "keeps",
        "kind", "knew", "know", "known", "knows", "l", "large", "largely", "last", "later", "latest", "least", "less", "let",
        "lets", "let's", "like", "likely", "long", "longer", "longest", "m", "made", "make", "making", "man", "many", "may", "me",
        "member", "members", "men", "might", "more", "most", "mostly", "mr", "mrs", "much", "must", "mustn't", "my", "myself", "n",
        "necessary", "need", "needed", "needing", "needs", "never", "new", "newer", "newest", "next", "no", "nobody", "non",
        "noone", "nor", "not", "nothing", "now", "nowhere", "number", "numbers", "o", "of", "off", "often", "old", "older",
        "oldest", "on", "once", "one", "only", "open", "opened", "opening", "opens", "or", "order", "ordered", "ordering",
        "orders", "other", "others", "ought", "our", "ours", "ourselves", "out", "over", "own", "p", "part", "parted", "parting",
        "parts", "per", "perhaps", "place", "places", "point", "pointed", "pointing", "points", "possible", "present", "presented",
        "presenting", "presents", "problem", "problems", "put", "puts", "q", "quite", "r", "rather", "really", "right", "room",
        "rooms", "s", "said", "same", "saw", "say", "says", "second", "seconds", "see", "seem", "seemed", "seeming", "seems",
        "sees", "several", "shall", "shan't", "she", "she'd", "she'll", "she's", "should", "shouldn't", "show", "showed", "showing",
        "shows", "side", "sides", "since", "small", "smaller", "smallest", "so", "some", "somebody", "someone", "something",
        "somewhere", "state", "states", "still", "such", "sure", "t", "take", "taken", "than", "that", "that's", "the", "their",
        "theirs", "them", "themselves", "then", "there", "therefore", "there's", "these", "they", "they'd", "they'll", "they're",
        "they've", "thing", "things", "think", "thinks", "this", "those", "though", "thought", "thoughts", "three", "through",
        "thus", "to", "today", "together", "too", "took", "toward", "turn", "turned", "turning", "turns", "two", "u", "under",
        "until", "up", "upon", "us", "use", "used", "uses", "v", "very", "w", "want", "wanted", "wanting", "wants", "was", "wasn't",
        "way", "ways", "we", "we'd", "well", "we'll", "wells", "went", "were", "we're", "weren't", "we've", "what", "what's",
        "when", "when's", "where", "where's", "whether", "which", "while", "who", "whole", "whom", "who's", "whose", "why",
        "why's", "will", "with", "within", "without", "won't", "work", "worked", "working", "works", "would", "wouldn't", "x", "y",
        "year", "years", "yes", "yet", "you", "you'd", "you'll", "young", "younger", "youngest", "your", "you're", "yours",
        "yourself", "yourselves", "you've", "z", "nbsp", "-");

    /**
     * Change the defaults to be forum-specific.
     *
     * @param  int    $courseid  the ID of the course to work with
     * @param  array  $options   options given to create the chart
     */
    public function __construct($courseid, $options = array()) {

        $this->optionsdefaults['modtypes'] = 'forum';
        parent::__construct($courseid, $options);
    }

    /**
     * Collect and return data needed to update the chart via ajax.
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return array  the html which contains all of a student's posts
     */
    public function ajax_get_data($filters) {
        global $PAGE;

        $filters = $this->set_empty_filters_to_default($filters);
        $postlist = $this->get_postlist($filters);
        $renderable = new \report_analytics\output\userpostschart_renderable($this->courseid, $this->options, $filters,
            $postlist);
        $renderer = $PAGE->get_renderer('report_analytics', 'userpostschart');
        return $renderer->render($renderable);
    }

    /**
     * Gets the list of posts for each selected user.
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return array   The postlist.
     */
    protected function get_postlist($filters) {

        $postsreport = new report_posts($this->courseid, $filters);
        $postlist = array();
        foreach ($filters->students as $studentid) {
            $postlist[$studentid] = $postsreport->get_posts_by_user($studentid);
        }
        return $postlist;
    }

    /**
     * Returns info about the chart, including:
     * -The ID tag to use when drawing
     * -The name to use in the chart selector
     * -It's type
     *
     * @return array  array containing graph information
     */
    public function get_chart_info() {
        $info = parent::get_chart_info();
        $info['value'] = 'UserPostsChart';
        $info['sort'] = 5;
        return $info;
    }

    /**
     * Adjust students filter to:
     * 1) replace an empty filter with a list of all students
     * 2) ensure it is an array
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return object  the updated filters object
     */
    protected function set_empty_filters_to_default($filters) {

        $filters = parent::set_empty_filters_to_default($filters);
        $studentfilter = new studentfilter($this->courseid);
        if (empty($filters->students) && $this->options['instructor'] === true) {
            $filters->students = array_keys($studentfilter->get_all_students());
        } else {
            if (!is_array($filters->students)) {
                $filters->students = array($filters->students);
            }
            $filters->students = $studentfilter->parse_groups($filters->students);
        }
        return $filters;
    }

    /**
     * Export the chart data into an excel spreadsheet.  The following columns are used:
     * | username | forum | subject | message | time/date | word count |
     *
     * @param  object  $filters  the filters to use to generate the chart
     */
    public function export($filters) {

        $filters = $this->set_empty_filters_to_default($filters);
        $postlist = $this->get_postlist($filters);

        $classname = (new \ReflectionClass($this))->getShortName();
        $chartname = get_string($classname . 'name', 'report_analytics');
        $columns = $this->get_columns();
        $posts = $this->flatten_posts($postlist);
        \core\dataformat::download_data(trim($chartname), 'excel', $columns, $posts);
    }

    /**
     * Get the header columns for the worksheet.
     * | username | forum | subject | message | time/date | word count |
     *
     * @return array  a list of header columns for the worksheet
     */
    protected function get_columns() {
        return array('username' => get_string('username'), 'forum' => get_string('forum', 'mod_forum'),
            'subject' => get_string('subject', 'mod_forum'), 'message' => get_string('message', 'mod_forum'),
            'date' => get_string('date'), 'wordcount' => get_string('wordcount', 'report_analytics'));
    }

    /**
     * Convert the postlist array into a format suitable for writing to an excel
     * document (one post per array entry).
     *
     * @param  array  $postlist  the list of posts across all users
     * @return array   a flattened array with one post per entry
     */
    protected function flatten_posts($postlist) {

        $posts = array();
        foreach ($postlist as $p) {
            foreach ($p->posts as $forumid => $forumposts) {
                $forum = $p->forums[$forumid];
                foreach ($forumposts as $post) {
                    $time = \DateTime::createFromFormat("U", $post->modified);
                    // Unix timestamp ignores timezone -> set manually.
                    $time->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                    $posts[] = array('username' => $post->firstname . ' ' . $post->lastname, 'forum' => $forum->name,
                        'subject' => $post->subject, 'message' => strip_tags($post->message), 'date' => $time->format('Y-m-d H:i'),
                        'wordcount' => count_words($post->message));
                }
            }
        }
        return $posts;
    }

    /**
     * Create a list of words used across all of a user's posts.
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return array  a list of all words used in the posts (keys) and the number of times each word was used (values)
     */
    public function word_cloud($filters) {

        $filters = $this->set_empty_filters_to_default($filters);
        $postlist = $this->get_postlist($filters);

        $frequency = array();
        foreach ($postlist as $p) {
            foreach ($p->posts as $forumposts) {
                foreach ($forumposts as $post) {
                    $words = preg_split("/\s+/", preg_replace("/[^\w\ _\-']+/", '', strip_tags($post->message)), -1,
                        PREG_SPLIT_NO_EMPTY);
                    foreach ($words as $word) {
                        $key = strtolower($word);
                        if (array_key_exists($key, $frequency)) {
                            $frequency[$key]++;
                        } else {
                            $frequency[$key] = 1;
                        }
                    }
                }
            }
        }
        $this->reset_common_words_frequency($frequency);
        $this->sort_trim_word_frequency($frequency);
        return $frequency;
    }

    /**
     * Remove common words from list of word frequencies.
     *
     * @param  array  $frequency  array containing frequency of word usage (passed by reference)
     */
    protected function reset_common_words_frequency(&$frequency) {

        foreach ($this->commonwords as $commonword) {
            if (array_key_exists($commonword, $frequency)) {
                $frequency[$commonword] = 0;
            }
        }
        $frequency = array_filter($frequency);
    }

    /**
     * Sort the list of word frequencies, trimming if too many exist.
     *
     * @param  array  $frequency  array containing frequency of word usage (passed by reference)
     */
    protected function sort_trim_word_frequency(&$frequency) {

        arsort($frequency);
        if (count($frequency) > self::MAX_UNIQUE_WORDS) {
            $frequency = array_slice($frequency, 0, self::MAX_UNIQUE_WORDS, true);
        }
    }

}
