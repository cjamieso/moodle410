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

namespace block_skills_group;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot.'/blocks/skills_group/locallib.php');

/**
 * Table used to allow participants to join a group.
 *
 * @package    block_skills_group
 * @copyright  2018 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class join_groups_table extends \table_sql {

    /** This is the ID of the course. */
    private $courseid;

    /**
     * Setup the table.
     *
     * @param  int  $courseid  the ID of the course
     */
    public function __construct($courseid) {
        global $CFG;

        parent::__construct('join-groups-' . $courseid);
        $this->courseid = $courseid;

        $headers = [];
        $columns = [];
        $gr = new group_records($this->courseid);
        $skillslist = $gr->get_skills_list();
        $columns[] = 'join';
        $columns[] = 'name';
        $columns[] = 'size';
        $columns[] = 'note';
        $columns = array_merge($columns, array_keys($skillslist));
        $headers[] = get_string('join', BLOCK_SG_LANG_TABLE);
        $headers[] = get_string('name');
        $headers[] = get_string('numberofmembers', BLOCK_SG_LANG_TABLE);
        $headers[] = get_string('note', BLOCK_SG_LANG_TABLE);
        $headers = array_merge($headers, array_values($skillslist));

        $this->define_columns($columns);
        foreach ($columns as $column) {
            if ($column !== 'name') {
                $this->no_sorting($column);
            }
        }
        $this->sortable(true, 'name', SORT_ASC);
        $this->define_headers($headers);
    }

    /**
     * Render the table.  Parameters 2 and 3 are included to match the parent function.
     *
     * @param  int     $pagesize            size of the table
     * @param  bool    $useinitialsbar      include initials bar?
     * @param  string  $downloadhelpbutton  help button to set for download (not used)
     */
    public function out($pagesize, $useinitialsbar = false, $downloadhelpbutton = '') {
        global $PAGE;

        $sgs = new skills_group_setting($this->courseid);
        $PAGE->requires->js_call_amd('block_skills_group/join', 'init', array('courseid' => $this->courseid));
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Generate the group name column.
     *
     * @param  \stdClass $data  the data in that row
     * @return string  the formatted data to use
     */
    public function col_join($data) {
        global $OUTPUT, $USER;

        $sg = new \block_skills_group\skills_group($data->id);
        $label = ($sg->user_in_group($USER->id)) ? 'i/competencies' : 'i/unchecked';
        $image = $OUTPUT->pix_icon($label, get_string('joingroupbutton', BLOCK_SG_LANG_TABLE));
        return \html_writer::div($image, 'joingroup', array('name' => $data->name, 'value' => $data->id));
    }

    /**
     * Generate the group name column.
     *
     * @param  \stdClass $data  the data in that row
     * @return string  the formatted data to use
     */
    public function col_name($data) {
        return \html_writer::nonempty_tag('p', $data->name);
    }

    /**
     * Generate the group size column.
     *
     * @param  \stdClass $data  the data in that row
     * @return string  the formatted data to use
     */
    public function col_size($data) {
        return \html_writer::nonempty_tag('p', $data->size);
    }

    /**
     * Extra user fields - each feedback item entry will be processed here.
     *
     * Skip the id field so that it is not visible in the table.
     *
     * @param  string    $colname  the name of the column
     * @param  \stdClass $data     the data in that row
     * @return string  the formatted data to use
     */
    public function other_cols($colname, $data) {

        if ($colname === "id") {
            return '';
        }
        // Some columns have purely numeric IDs - cast to array so they can be read.
        $array = (array)$data;
        return isset($array[$colname]) ? $array[$colname] : '';
    }

    /**
     * Query the database for results to display in the table.  This function is part of
     * the table library, but my results are spread across two tables.  I used the
     * 'group_records' class to manually retrieve them and then trim the results to
     * the desired number thereafter.
     *
     * @param  int   $pagesize        size of the table
     * @param  bool  $useinitialsbar  include initials bar?
     */
    public function query_db($pagesize, $useinitialsbar = false) {

        $gr = new group_records($this->courseid);
        $data = $gr->get_table_rows();
        $this->pagesize($pagesize, count($data));

        $sort = $this->get_sql_sort();
        $sorttokens = explode(" ", $sort);
        foreach ($data as $key => $row) {
            $tosort[$key] = $row[$sorttokens[0]];
        }
        array_multisort($tosort, constant("SORT_" . $sorttokens[1]), $data);

        $page = optional_param('page', 0, PARAM_INT);
        $start = $page * $pagesize;
        if (($start + $pagesize) > count($data)) {
            $length = count($data) % $pagesize;
        } else {
            $length = $pagesize;
        }

        $this->rawdata = array_slice($data, $start, $length);
        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars(true);
        }
    }
}
