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
require_once($CFG->dirroot.'/blocks/nurs_navigation/locallib.php');

/**
 * Nurs Navigation block class.
 *
 * This is the class definition for the nurs_navigation block.  Most of what follows below is standard
 * Moodle requirements.
 *
 * @package    block_nurs_navigation
 * @copyright  2012 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_nurs_navigation extends block_base {

    /**
     * This function sets the title of the block.
     *
     */
    public function init() {
        $this->title = get_string('blocktitle', 'block_nurs_navigation');
    }

    /**
     * This function tells moodle to process the admin settings.
     *
     */
    public function has_config() {
        return true;
    }

    /**
     * This function restricts the block to only courses and mods, preventing
     * acess to it on the front page.
     *
     */
    public function applicable_formats() {
        return array('course-view' => true,
            'mod' => true,
            'my' => false);
    }

    /**
     * This function draws the block on the screen.  Icons are retrieved from the database and displayed in
     * the block.  The link to edit the page is drawn only if the user has editing rights.
     *
     */
    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new StdClass;
        $courseid = $COURSE->id;
        if (!$this->check_category($courseid)) {
            $this->content->footer = "<p>" . get_string('notenabled', BNN_LANG_TABLE) . "</p>";
            return $this->content;
        }
        $currentsection = $this->get_current_section($courseid);

        $sectionheaders = array();
        $numberofsections = get_section_titles($courseid, $sectionheaders);
        $this->content->footer = '<ul class="unlist">';

        $placement = get_config('nurs_navigation', 'Placement');
        if ($placement == "before") {
            $this->content->footer .= $this->get_activity_links($courseid);
        }

        if (isset($this->config->sections) && ($this->config->sections)) {
            for ($i = 1; $i <= $numberofsections; $i++) {
                $this->content->footer .= $this->get_block_icon_link($courseid, $i, $sectionheaders[$i], $currentsection);
            }
        }

        if ($placement == "after") {
            $this->content->footer .= $this->get_activity_links($courseid);
        }

        $this->content->footer .= '</ul>';

        $context = context_course::instance($courseid);
        $canmanage = has_capability('block/nurs_navigation:caneditnursnavigation', $context);

        if (isset($this->config->sections) && ($this->config->sections)) {
            $url = new moodle_url('/course/view.php', array('id' => $COURSE->id, 'section' => 0));
            $this->content->footer .= "<div>".html_writer::link($url, get_string('showallsections', BNN_LANG_TABLE))."</div>";
            if ($canmanage) {
                $url = new moodle_url('/blocks/nurs_navigation/edit_navigation.php',
                                       array('courseid' => $COURSE->id, 'blockid' => $this->instance->id));
                $this->content->footer .= "<div>".html_writer::link($url, get_string('editsettings', BNN_LANG_TABLE))."</div>";
            }
        }

        if ($canmanage) {
            $url = new moodle_url('/blocks/nurs_navigation/edit_activities.php',
                                   array('courseid' => $COURSE->id, 'blockid' => $this->instance->id));
            $this->content->footer .= "<div>".html_writer::link($url, get_string('editactivities', BNN_LANG_TABLE))."</div>";
        }

        return $this->content;
    }

    /**
     * Check if the course is in one of the permitted categories.
     *
     * @param  int  $courseid  the ID of the course
     * @return bool  T/F indicating whether the course is in a valid category or not
     */
    private function check_category($courseid) {

        $categories = get_config('nurs_navigation', 'Categories');
        $course = get_course($courseid);
        $categoryids = explode(',', $categories);
        return in_array($course->category, $categoryids);
    }

    /**
     * This method returns the current section that is displayed by the user
     *
     * @param int $courseid The ID of the course to get the current section of
     * @return int The current section as an integer
     *
     */
    private function get_current_section($courseid) {
        $currentsection = optional_param('section', 0, PARAM_INT);
        if ($currentsection == 0) {
            $format = course_get_format($courseid);
            $currentsection = $format->get_course()->marker;
        }

        return $currentsection;
    }

    /**
     * This function formats and returns a link to the image for a particular section and
     * course.  If a particular section is active, the icons for other sections are faded
     * out.
     *
     * @param  int     $courseid        the ID of the course
     * @param  integer $sectionnumber   the number of the section
     * @param  string  $sectionheader   the textual name of the section
     * @param  int     $currentsection  the current section the user has selected
     *
     * @return string   The block icon link.
     */
    private function get_block_icon_link($courseid, $sectionnumber, $sectionheader, $currentsection) {

        if (!$this->verify_visibility($sectionnumber, $sectionheader)) {
            return;
        }

        $si = new \block_nurs_navigation\section_icon($courseid, $sectionheader);
        // If icon is set to disable, then skip.
        if ($si->get_icon_disable()) {
            return;
        }

        // Check for custom label text.
        $customlabel = $si->get_custom_label();
        $sectionheader = ($customlabel != null) ? $customlabel : $sectionheader;

        $imagefile = $si->get_image(true);

        // Grab height/width from admin settings.
        $height = get_config('nurs_navigation', 'Image_Height');
        $width = get_config('nurs_navigation', 'Image_Width');

        $outputbuffer = "<li><div>";
        $outputbuffer .= "<a title=\"Section: {$sectionheader} \"href=
            '/course/view.php?id={$courseid}&section={$sectionnumber}'>";
        $outputbuffer .= "<span class=\"media-left\">";
        $outputbuffer .= "<img alt=\"$sectionheader\" src='$imagefile' height='$height' width='$width' class=\"icon";
        if ($currentsection != 0 && $sectionnumber == $currentsection) {
            $outputbuffer .= " faded";
        }
        $outputbuffer .= "\"/></span>";
        if (!isset($this->config->disabletext) || (isset($this->config->disabletext) && !$this->config->disabletext)) {
            $outputbuffer .= "<span class=\"media-body\">$sectionheader</span>";
        }

        $outputbuffer .= "</a></div></li>";

        return $outputbuffer;
    }

    /**
     * Retrieve the links to add to the block for the exams, assignments, and quests headers.
     *
     * @param  int     $courseid  the ID of the course
     * @return string  a buffer containing the activity links
     */
    private function get_activity_links($courseid) {

        $outputbuffer = '';
        $activities = explode(',', preg_replace("/[^A-Za-z,]+/", "", get_config('nurs_navigation', 'Activities')));
        $disable = isset($this->config->disableactivities) ? $this->config->disableactivities : [];
        foreach ($activities as $activity) {
            if ((array_search('none', $disable) !== false) || (array_search($activity, $disable) === false)) {
                $outputbuffer .= $this->get_activity_link($activity, $courseid);
            }
        }
        return $outputbuffer;
    }

    /**
     * Retrieve the link for one specific activity type
     *
     * @param  int     $courseid  the ID of the course
     * @return string  a buffer containing the activity link
     */
    private function get_activity_link($type, $courseid) {
        global $CFG;

        // Grab height/width from admin settings.
        $height = get_config('nurs_navigation', 'Image_Height');
        $width = get_config('nurs_navigation', 'Image_Width');
        $field = 'custom' . $type;
        if (isset($this->config->$field) && !empty(trim($this->config->$field))) {
            $title = $this->config->$field;
        } else {
            $title = get_activity_title($type);
        }

        $outputbuffer = "<li><div>";
        $outputbuffer .= "<a title=\"{$title}\"href=
            '/blocks/nurs_navigation/activity_table.php?courseid={$courseid}&type={$type}'>";
        if (file_exists($CFG->dirroot . '/blocks/nurs_navigation/pix/' . $type . '.png')) {
            $src = '/blocks/nurs_navigation/pix/' . $type . '.png';
        } else {
            $src = '/mod/' . $type . '/pix/icon.png';
        }
        $outputbuffer .= "<span class=\"media-left\">";
        $outputbuffer .= "<img class=\"icon\" alt=\"$title\" src='$src' height='$height' width='$width' /></span>";
        $outputbuffer .= "<span class=\"media-body\">$title</a></span></div></li>";
        return $outputbuffer;
    }

    /**
     * This function determines whether a user has permission to view the section.  Nursing uses
     * this to prevent students from seeing some section links that are more administrative in
     * their content.
     *
     * Caution: this does not prevent access to the session, it only hides the icon.
     *
     * @return bool T/F indicating whether the icon should be visible
     */
    private function verify_visibility($sectionnumber, $sectionheader) {
        global $COURSE;

        $modinfo = get_fast_modinfo($COURSE);
        $sections = $modinfo->get_section_info_all();
        $visible = $sections[$sectionnumber]->uservisible || ($sections[$sectionnumber]->visible &&
            !$sections[$sectionnumber]->available && !empty($sections[$sectionnumber]->availableinfo));
        return $visible;
    }

}
