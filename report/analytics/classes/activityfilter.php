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
require_once(dirname(__FILE__) . '/../../../course/lib.php');
require_once(dirname(__FILE__) . '/../../../lib/modinfolib.php');

/**
 * activityfilter class
 *
 * This class retrieves data from a particular course and generates the elements
 * necessary for an activity filter.  This is composed of three things:
 *
 * 1) A list of all activity classes (assignment, quiz, etc)
 * 2) A list of all sections
 * 3) A list of all activities
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activityfilter extends filter {

    /** @var array default settings for options. */
    protected $optionsdefaults = array('types' => 'all');
    /** @var array list of components classes - initalized below, since I cannot initialize with get_string() here. */
    protected static $components = array();

    /**
     * Returns acvitity filter data, including: all activity classes, all sections,
     * and all activities in a course.
     *
     * @return array  array containing the types of data, each in its own sub-array
     */
    public function get_filter_data() {
        if ($this->options['types'] === 'forum') {
            return array(get_string('activities') => $this->get_mods_of_class(get_string('allforums', 'report_analytics')));
        }
        $temp = array(get_string('activitytypes', 'report_analytics') => $this->get_all_activity_classes(),
            get_string('sections') => $this->get_all_sections());
        $activities = $this->get_all_activities();
        foreach ($activities as $sectionname => $cmids) {
            $temp[$sectionname] = $cmids;
        }
        return $temp;
    }

    /**
     * Returns a list of all activities classes that can be used for filtering.  This is
     * hard-coded as part of the class.
     *
     * Skipping: chat, choice, database, glossary, scorm, survey, wiki, workshop, book, label.
     *
     * @return array  list of all activities classes in the course ([dbname] => label)
     */
    public static function get_all_activity_classes() {
        if (empty(self::$components)) {
            self::$components = array("mod_quiz" => get_string('allquizzes', 'report_analytics'),
                                      "mod_assignment" => get_string('allassignments', 'report_analytics'),
                                      "mod_page" => get_string('allpages', 'report_analytics'),
                                      "mod_resource" => get_string('allfiles', 'report_analytics'),
                                      "mod_folder" => get_string('allfolders', 'report_analytics'),
                                      "mod_forum" => get_string('allforums', 'report_analytics'),
                                      "mod_url" => get_string('allurls', 'report_analytics'),
                                      "mod_feedback" => get_string('allfeedbacks', 'report_analytics'),
                                      "mod_lesson" => get_string('alllessons', 'report_analytics'),
                                      "core" => get_string('allcore', 'report_analytics'));
        }
        return self::$components;
    }

    /**
     * Return a list of activities that should be used for an empty selection on
     * an activity filter.
     *
     * @return array  a default list of activities to use
     */
    public function get_default_activities() {

        if ($this->options['types'] === 'forum') {
            return array_keys($this->get_mods_of_class(get_string('allforums', 'report_analytics')));
        } else {
            $all = self::get_all_activity_classes();
            unset($all['core']);
            return array_keys($all);
        }
    }

    /**
     * Returns a list of all activities used in a course to be used by the filters.
     * The lists are sorted by the section of the course in which the activity appears.
     *
     * @return array  list of all activities in the course
     */
    protected function get_all_activities() {

        $mods = array();
        $modinfo = get_fast_modinfo($this->courseid);
        foreach ($modinfo->sections as $section => $cmids) {
            $sectionname = get_section_name($this->courseid, $section);
            $mods[$sectionname] = array();
            foreach ($cmids as $cmid) {
                $mods[$sectionname][$cmid] = $this->get_cm_name($cmid);
            }
        }
        return $mods;
    }

    /**
     * Returns a list of all sections in the course, along with their name.
     *
     * @return array  associative array containing ([s + sectionnumber] => name)
     */
    protected function get_all_sections() {
        $sections = array();

        $numberofsections = $this->get_number_of_sections();
        for ($i = 1; $i <= $numberofsections; $i++) {
            $sections["s$i"] = get_section_name($this->courseid, $i);
        }

        return $sections;
    }

    /**
     * This method returns the number of active sections in a course or zero if the course does
     * not exist.
     *
     * @return int  Total number of active sections.
     *
     */
    protected function get_number_of_sections() {
        global $DB;

        $course = $DB->get_record('course', array('id' => $this->courseid), '*', MUST_EXIST);
        return course_get_format($course)->get_last_section_number();
    }

    /**
     * Retrieves the item type and returns it as a string.
     *
     * @param  string  $item  the desired activity info to retrieve
     * @return string  the type of item - {activity|activity_class|section}
     */
    public function get_type($item) {

        if (is_numeric($item)) {
            return 'activity';
        } else {
            $temp = strval($item);
            if (substr($temp, 0, 1) == 's') {
                return 'section';
            } else {
                return 'activity_class';
            }
        }
    }

    /**
     * Retrieves the label (name) of the item.
     *
     * @param  string  $item      the desired activity info to retrieve
     * @return string  the label of the item {activity name|activity class|section name}
     */
    public function get_label($item) {

        if (is_numeric($item)) {
            return self::get_cm_name(intval($item));
        } else {
            $temp = strval($item);
            if (substr($temp, 0, 1) == 's') {
                $sectionnumber = substr($temp, 1);
                return get_section_name($this->courseid, $sectionnumber);
            } else {
                $components = self::get_all_activity_classes();
                if (isset($components[$item])) {
                    return $components[$item];
                } else {
                    throw new \Exception(get_string('namenotfound', 'report_analytics'));
                }
            }
        }
    }

    /**
     * Returns the name of the activity for the given cmid.
     *
     * I've had to re-fetch this information in php since the multiselect will
     * not return the proper text for optgroups (returns header).
     *
     * @param  int  $cmid      the ID of the course module to lookup
     * @return string  the name of the course module
     */
    protected function get_cm_name($cmid) {

        $modinfo = get_fast_modinfo($this->courseid);
        $cm = $modinfo->cms[$cmid];
        return format_string($cm->name);
    }

    /**
     * Builds a list of mods within a section and returns their IDs.
     *
     * @param  int  $section  number (or name) of section to retrieve mods for
     * @return array  list of mods in course by cmid
     */
    public function get_mods_in_section($section) {

        $modinfo = get_fast_modinfo($this->courseid);
        if (!is_numeric($section)) {
            $section = $this->get_section_from_name($section);
        }
        $cmids = isset($modinfo->sections[$section]) ? $modinfo->sections[$section] : array();
        return $cmids;
    }

    /**
     * Retrieves all of the modids for a particular activity class.
     *
     * @param  string  $classtype  the activity class to retrieve IDs for
     * @return array  array of IDs for mods of class type
     */
    public function get_mods_of_class($classtype) {

        $modinfo = get_fast_modinfo($this->courseid);
        if ($classtype !== get_string('allcore', 'report_analytics')) {
            $classes = self::get_all_activity_classes();
            $key = array_search($classtype, $classes);
            if ($key === false) {
                throw new \Exception(get_string('classnotfound', 'report_analytics'));
            } else {
                $key = preg_replace('/(mod_)/', '', $key);
                $instances = array_values($modinfo->get_instances_of($key));
                // Anonymous function not defined inline, so that codechecker is happy.
                $anon = function($o) {
                    return $o->id;
                };
                $mods = array_values(array_map($anon, $instances));
                $fullmods = array();
                foreach ($mods as $mod) {
                    $fullmods[$mod] = self::get_cm_name($mod);
                }
                return $fullmods;
            }
        }
        throw new \Exception(get_string('classnotfound', 'report_analytics'));
    }

    /**
     * Retrieve a section number by searching with its name.  Section names are stored
     * in tables in the database.  However, if a section has no name, a default name
     * (usually "Topic XX") is used.
     *
     * @param  string  $sectionname The name of the section (as displayed in Moodle)
     * @throws Exception  if section is not found in database
     * @return int  the numerical value of the section in the course
     */
    protected function get_section_from_name($sectionname) {
        global $DB;

        $format = $DB->get_field('course', 'format', array('id' => $this->courseid), MUST_EXIST);
        $sectionstring = get_string('sectionname', 'format_' . $format);

        if (preg_match('/(' . $sectionstring . ' )\d+/', $sectionname)) {
            $sectionnumber = (int) preg_replace('/(' . $sectionstring . ' )/', '', $sectionname);
            return $sectionnumber;
        } else {
            $sql = "SELECT section FROM {course_sections} WHERE name = :name and course = :courseid";
            $params = array('name' => $sectionname, 'courseid' => $this->courseid);
            $records = $DB->get_records_sql($sql, $params);
            if (count($records) == 0) {
                throw new \Exception(get_string('sectionnotfound', 'report_analytics'));
            } else {
                foreach ($records as $r) {
                    if (isset($r->section)) {
                        return $r->section;
                    }
                }
            }
        }
        throw new \Exception(get_string('sectionnotfound', 'report_analytics'));
    }

}
