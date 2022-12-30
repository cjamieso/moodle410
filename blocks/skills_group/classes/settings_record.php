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

/**
 * settings_record class.
 *
 * This class encapsulates a record that the skills_group_settings are stored
 * within.  The constructor assigns safe defaults to any non-existing properties.
 *
 * @package    block_skills_group
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_record {

    /** Default group size if none given */
    const DEFAULTMAXSIZE = 5;
    /** Default threshold if none given */
    const DEFAULTTHRESHOLD = 1;
    /** Feedback ID */
    public $prefeedbackid = 0;
    /** Post Feedback ID */
    public $postfeedbackid = 0;
    /** Grouping ID */
    public $groupingid = 0;
    /** Maximum Group size */
    public $maxsize = self::DEFAULTMAXSIZE;
    /** Score threshold for feedback activities */
    public $threshold = self::DEFAULTTHRESHOLD;
    /** Whether a date exists (1) or not (0) */
    public $datecheck = 0;
    /** Date to lock group changes */
    public $date = null;
    /** Allow students to name their own groups */
    public $allownaming = true;
    /** Allow students to add members to their group */
    public $allowadding = true;
    /** Allow students to view their group */
    public $allowgroupview = true;
    /** Use instructor created groups */
    public $instructorgroups = false;

    /**
     * Create a settings record.
     *
     * @param object|array $record  DB record on which to base the settings record. (empty is OK -> uses defaults)
     */
    public function __construct($record = null) {

        if (is_object($record)) {
            $this->groupingid = (isset($record->groupingid)) ? $record->groupingid : 0;
            $this->prefeedbackid = (isset($record->prefeedbackid)) ? $record->prefeedbackid : 0;
            $this->postfeedbackid = (isset($record->postfeedbackid)) ? $record->postfeedbackid : 0;
            $this->maxsize = (isset($record->maxsize)) ? $record->maxsize : self::DEFAULTMAXSIZE;
            $this->threshold = (isset($record->threshold)) ? $record->threshold : self::DEFAULTTHRESHOLD;
            $this->datecheck = (isset($record->datecheck)) ? $record->datecheck : 0;
            $this->date = (isset($record->date) && ($record->datecheck == 1)) ? $record->date : null;
            $this->allownaming = (isset($record->allownaming)) ? $record->allownaming : true;
            $this->allowadding = (isset($record->allowadding)) ? $record->allowadding : true;
            $this->allowgroupview = (isset($record->allowgroupview)) ? $record->allowgroupview : true;
            $this->instructorgroups = (isset($record->instructorgroups)) ? $record->instructorgroups : false;
        } else if (is_array($record)) {
            foreach ($record as $key => $value) {
                if (property_exists("\\block_skills_group\\settings_record", $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Copy the valid settings in the record to a stdClass object and return it.
     *
     * @return stdClass  stdClass representation of settings record.
     */
    public function get_as_stdclass() {

        $settings = new \stdClass;
        $settings->prefeedbackid = $this->prefeedbackid;
        $settings->postfeedbackid = $this->postfeedbackid;
        $settings->groupingid = $this->groupingid;
        $settings->maxsize = $this->maxsize;
        $settings->threshold = $this->threshold;
        $settings->date = $this->date;
        $settings->allownaming = $this->allownaming;
        $settings->instructorgroups = $this->instructorgroups;
        $settings->allowadding = $this->allowadding;
        $settings->allowgroupview = $this->allowgroupview;
        return $settings;
    }

    /**
     * Copy the settings in this record into an existing object.
     *
     * @param  object  $record  (as reference) the existing record to copy the settings into.
     */
    public function copy_settings_to(&$record) {

        $record->prefeedbackid = $this->prefeedbackid;
        $record->postfeedbackid = $this->postfeedbackid;
        $record->groupingid = $this->groupingid;
        $record->maxsize = $this->maxsize;
        $record->threshold = $this->threshold;
        $record->date = $this->date;
        $record->allownaming = $this->allownaming;
        $record->allowadding = $this->allowadding;
        $record->allowgroupview = $this->allowgroupview;
        $record->instructorgroups = $this->instructorgroups;
    }

}