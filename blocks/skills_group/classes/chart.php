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

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once($CFG->dirroot.'/blocks/skills_group/locallib.php');

/**
 * This class is used to get data to draw the graph or export.
 *
 * @package    block_skills_group
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chart{

    /** @var int the ID of the course that the filter should apply to. */
    protected $courseid;
    /** @var array default settings for filters. */
    protected $filterdefaults = array('items' => null);


    /**
     * Store the course ID for later use.
     *
     * @param  int    $courseid  the ID of the course to work with
     */
    public function __construct($courseid) {
        $this->courseid = $courseid;
    }

    /**
     * Check the filters array and set to safe defaults if no value exists.
     *
     * @param  array  $filters  filter data for SQL query
     * @return array  filters array with safe defaults added where no value existed
     */
    protected function set_empty_filters_to_default($filters) {

        if (!is_array($filters)) {
            $filters = array();
        }
        $filters = array_merge($this->filterdefaults, array_filter($filters));
        return $filters;
    }

    /**
     * Collect and return data needed to update the chart via ajax.
     *
     * @param  array  $filters  the various filters selected on the chart
     * @return array  T/F indicating if the data retrieval was successful and the data generated
     */
    public function ajax_get_data($filters) {

        $filters = $this->set_empty_filters_to_default($filters);
        $sr = new scores_report($this->courseid);
        $scores = $sr->get_class_scores($filters['items']);
        return array('true', $scores);
    }

    /**
     * Returns the title of the graph based on the filters that were provided.
     *
     * @param  array  $filters  the various filters selected on the chart
     * @return string the correct title for the graph
     */
    public function get_title($filters) {
        return get_string('chartname', BLOCK_SG_LANG_TABLE);
    }

    /**
     * Export the chart data into an excel spreadsheet.  Any columns visible in the graph
     * legend are used as the columns.  Row entries begin with the x-axis label.
     *
     * @param  array  $filters  the various filters from javascript
     */
    public function export($filters) {

        $filters = $this->set_empty_filters_to_default($filters);
        $sr = new scores_report($this->courseid);
        $scores = $sr->get_class_scores($filters['items']);
        $itemnames = $this->get_item_names($filters);

        $workbook = new \MoodleExcelWorkbook('graph ' . time() . '.xlsx');
        $format = $workbook->add_format(array('bold' => 1, 'size' => 12));
        $worksheet = $workbook->add_worksheet(get_string('chartname', BLOCK_SG_LANG_TABLE));
        $title = get_string('exceldatafor', BLOCK_SG_LANG_TABLE) . ' ' . implode(', ', array_values($itemnames));
        $worksheet->write_string(0, 0, $title, $format);

        $entries = $this->print_worksheet_header($worksheet, $format, $scores[0]);
        $row = 2;
        foreach ($scores as $score) {
            $worksheet->write_string($row, 0, $score['label']);
            for ($i = 0; $i < count($entries); $i++) {
                $worksheet->write_number($row, $i + 1, $score[$entries[$i]]);
            }
            $row++;
        }
        $workbook->close();
    }

    /**
     * Print the header columns at the top of the worksheet.  Display any entries
     * shown on the graph legend.  That is, ignore 'label', 'value'.
     *
     * @param  object  $worksheet  the sheet in the excel document to add the headers to
     * @param  object  $format     the format/style to print the entry with
     * @param  array   $header     any entry in the data array that contains the header columns
     */
    private function print_worksheet_header($worksheet, $format, $header) {

        $entries = array();
        foreach ($header as $name => $value) {
            if ($name !== 'label' && $name !== 'value') {
                $entries[] = $name;
            }
        }
        $worksheet->write_string(1, 0, '', $format);
        for ($i = 0; $i < count($entries); $i++) {
            $worksheet->write_string(1, $i + 1, $entries[$i], $format);
        }
        return $entries;
    }

    /**
     * Retrieve the item names (labels) from their numeric IDs.
     *
     * The formatting of the item array is awkward due to how Moodle sets up their
     * multiselects.
     *
     * @param  array  $filters  the various filters from javascript
     * @return array  the items (key -> numeric index, value -> text name)
     */
    private function get_item_names($filters) {

        $sgs = new skills_group_setting($this->courseid);
        $items = $sgs->get_feedback_items();

        $itemnames = array();
        foreach ($filters['items'] as $selected) {
            foreach ($items as $item) {
                reset($item);
                $firstkey = key($item);
                if (array_key_exists($selected, $item[$firstkey])) {
                    $itemnames[$selected] = $item[$firstkey][$selected];
                }
            }
        }
        return $itemnames;
    }

}
