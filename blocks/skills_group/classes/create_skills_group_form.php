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
require_once($CFG->libdir.'/formslib.php');

/**
 * create_skills_group_form class
 *
 * This is the form definition for the create_skills_group.php page.  Strictly
 * speaking, it allows the user to create a new group, edit their existing group,
 * or remove themselves from their current group.
 *
 * @package    block_skills_group
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_skills_group_form extends \moodleform {

    /** This is the ID of the course. */
    private $courseid;

    /**
     * This method saves the variables needed for later when the form is created.
     *
     * @param int $courseid The ID of the course to create the form for.
     *
     */
    public function __construct($courseid) {

        $this->courseid = $courseid;
        parent::__construct();
    }

    /**
     * Form definition with two possibilities:
     *
     * 1) User part of group -> allow user to edit or drop group.
     * 2) User not part of group -> allow user to create new group.
     *
     */
    public function definition() {
        global $DB, $USER;

        $mform = &$this->_form;
        $mform->addElement('header', 'header', get_string('creategroupheader', BLOCK_SG_LANG_TABLE));

        $sgs = new skills_group_setting($this->courseid);

        if ($sgs->exists()) {
            $sgrouping = new skills_grouping($this->courseid);
            $groupid = $sgrouping->check_for_user_in_grouping($USER->id);
            // Student in group -> show options for editing.
            if ($groupid !== false) {
                $this->student_in_group($groupid);
            } else {
                $this->student_not_in_group();
            }
        } else {
            $mform->addElement('static', 'notconfigured', get_string('notconfiguredleft', BLOCK_SG_LANG_TABLE),
                               get_string('notconfiguredright', BLOCK_SG_LANG_TABLE));
        }

        // Hidden elements: courseid needed for posting.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Add the form elements for when the student is in a group
     *
     * @param  int  $groupid  the ID of the group which the student belongs to
     */
    private function student_in_group($groupid) {

        global $USER;
        $mform = &$this->_form;

        $sgs = new skills_group_setting($this->courseid);
        $sgroup = new skills_group($groupid);
        $mform->addElement('static', 'existinggroup', get_string('existinggroup', BLOCK_SG_LANG_TABLE), $sgroup->get_group_name());
        $mform->addElement('hidden', 'groupid', $groupid);
        $mform->setType('groupid', PARAM_INT);
        if ($this->check_for_expiry($sgs) === false) {
            if ($sgs->get_allowadding()) {
                $this->add_member_selector($groupid);
            }
            // Student can only leave if not already locked.
            $student = new skills_group_student($this->courseid, $USER->id);
            if ($student->get_lock_choice() === false) {
                $mform->addElement('advcheckbox', 'leavegroup', get_string('leavegroup', BLOCK_SG_LANG_TABLE), null, null,
                    array(0, 1));
                $mform->disabledIf('members', 'leavegroup', 'checked');
            }
            $mform->addElement('hidden', 'type', 'edit');
            $mform->setType('type', PARAM_TEXT);
        }
        if ($sgs->get_instructorgroups() === false) {
            $mform->addElement('advcheckbox', 'allowjoincheck', get_string('groupsearchable', BLOCK_SG_LANG_TABLE), null, null,
                array(0, 1));
            $mform->disabledIf('allowjoincheck', 'leavegroup', 'checked');
        }
        $mform->addElement('text', 'note', get_string('groupnote', BLOCK_SG_LANG_TABLE));
        $mform->setType('note', PARAM_TEXT);
    }

    /**
     * Add the group member selector with appropriate members.  Possible
     * members include: students in course plus current group members
     * minus any locked group members.
     *
     * @param  int  $groupid  the ID of the group which the student belongs to
     */
    private function add_member_selector($groupid) {

        $mform = &$this->_form;
        $sgroup = new skills_group($groupid);
        $sgrouping = new skills_grouping($this->courseid);
        $potential = $sgrouping->get_potential_students();
        $groupmembers = $sgroup->get_members_list($lock = false);
        $potential = $potential + $groupmembers;
        $lockedstudents = $sgroup->get_members_list(true);
        if (count($lockedstudents) > 0) {
            $text = '';
            foreach ($lockedstudents as $key => $student) {
                unset($potential[$key]);
                $text .= $student . ' ';
            }
            $mform->addElement('static', 'locked', get_string('lockedmembers', BLOCK_SG_LANG_TABLE), $text);
        }
        $options = array('multiple' => true, 'noselectionstring' => get_string('adduserstogroup', BLOCK_SG_LANG_TABLE),
            'placeholder' => get_string('groupplaceholder', BLOCK_SG_LANG_TABLE));
        $mform->addElement('autocomplete', 'members', get_string('groupmembers', BLOCK_SG_LANG_TABLE), $potential, $options);
        $checkmembers = function($value) {
            global $USER;
            $sgrouping = new skills_grouping($this->courseid);
            $groupid = $sgrouping->check_for_user_in_grouping($USER->id);
            if ($groupid === false) {
                return false;
            }
            $sgroup = new skills_group($groupid);
            $lockedstudents = $sgroup->get_members_list($lock = true);
            $sgsetting = new skills_group_setting($this->courseid);
            $values = (array) $value;
            if ((count($values) + count($lockedstudents)) <= $sgsetting->get_group_size() - 1) {
                return true;
            } else {
                return false;
            }

        };
        $mform->addRule('members', get_string('toomanymembers', BLOCK_SG_LANG_TABLE), 'callback', $checkmembers, 'server', false,
            true);
    }

    /**
     * Add the form elements for when a student is not currently in a group.
     */
    private function student_not_in_group() {

        $mform = &$this->_form;
        $sgs = new skills_group_setting($this->courseid);
        $mform->addElement('static', 'existinggroup', get_string('existinggroup', BLOCK_SG_LANG_TABLE),
                           get_string('nogroup', BLOCK_SG_LANG_TABLE));
        if ($this->check_for_expiry($sgs) === false) {
            if ($sgs->get_instructorgroups() === true) {
                $mform->addElement('static', 'gojoingroup', get_string('gojoingroupleft', BLOCK_SG_LANG_TABLE),
                           get_string('gojoingroupright', BLOCK_SG_LANG_TABLE));
                $mform->addElement('hidden', 'type', 'none');
                $mform->setType('type', PARAM_TEXT);

            } else {
                if ($sgs->get_allownaming() == true) {
                    $pagegroup = array();
                    $pagegroup[] = $mform->createElement('advcheckbox', 'creategroupcheck', null, null, null, array(0, 1));
                    $pagegroup[] = $mform->createElement('text', 'creategroup', null, array());
                    $mform->setType('creategroup', PARAM_TEXT);
                    $mform->disabledIf('creategroup', 'creategroupcheck');
                    $mform->addGroup($pagegroup, 'create', get_string('creategroup', BLOCK_SG_LANG_TABLE), null, false);
                } else {
                    // Create element outside of group so that form spacing is correct.
                    $mform->addElement('advcheckbox', 'creategroupcheck', get_string('creategroup', BLOCK_SG_LANG_TABLE), null,
                        null, array(0, 1));
                }
                $mform->addElement('hidden', 'type', 'create');
                $mform->setType('type', PARAM_TEXT);
                $mform->addElement('advcheckbox', 'allowjoincheck', get_string('groupsearchable', BLOCK_SG_LANG_TABLE), null, null,
                    array(0, 1));
                $mform->addElement('text', 'note', get_string('groupnote', BLOCK_SG_LANG_TABLE));
                $mform->setType('note', PARAM_TEXT);
            }
        }
    }

    /**
     * Check for date/time expiry.  If updates are expired, add form elements for
     * user.  Otherwise, return false to indicate valid date.
     *
     * @return     boolean  T/F indicating whether the signup has expired or not
     */
    private function check_for_expiry($sgs) {

        $mform = &$this->_form;
        if ($sgs->date_restriction() && time() > $sgs->get_date()) {
            $mform->addElement('static', 'dateexpired', get_string('dateexpiredleft', BLOCK_SG_LANG_TABLE),
                               get_string('dateexpiredright', BLOCK_SG_LANG_TABLE));
            $mform->addElement('hidden', 'type', 'expired');
            $mform->setType('type', PARAM_TEXT);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Creates a skills_group.
     *
     * @param  object  $submittedform  the values submitted to the form
     * @return string  the url to redirect the user to or false to prevent redirect.
     */
    public function create_group($submittedform) {
        global $USER;

        if ($submittedform->creategroupcheck) {
            $sgrouping = new skills_grouping($this->courseid);
            // Blank names are OK -> plugin will autoname.
            $groupname = (isset($submittedform->creategroup)) ? $submittedform->creategroup : null;
            $groupid = $sgrouping->create_group($groupname);
            update_allow_join($groupid, $submittedform->allowjoincheck);
            update_note($groupid, $submittedform->note);
            $url = new \moodle_url('/blocks/skills_group/create_skills_group.php', array('courseid' => $this->courseid,
                'sesskey' => $USER->sesskey));
            // Logging create group action.
            $params = array(
                'context' => \context_course::instance($this->courseid),
                'objectid' => $groupid,
                'courseid' => $this->courseid,
                'userid' => $USER->id
                );
            $event = \block_skills_group\event\skillsgroup_left::create($params);
            $event->trigger();
        } else {
            $url = new \moodle_url('/course/view.php', array('id' => $this->courseid));
        }
        return $url;
    }

    /**
     * Edit a skills_group.
     *
     * @param  object  $submittedform  the values submitted to the form
     * @return string  the url to redirect the user to or false to prevent redirect.
     */
    public function edit_group($submittedform) {
        global $USER;

        $url = new \moodle_url('/blocks/skills_group/create_skills_group.php', array('courseid' => $this->courseid,
            'sesskey' => $USER->sesskey));
        if (isset($submittedform->leavegroup)) {
            if ($submittedform->leavegroup) {
                groups_remove_member($submittedform->groupid, $USER->id);
                // Logging leave group action.
                $params = array(
                    'context' => \context_course::instance($this->courseid),
                    'objectid' => $submittedform->groupid,
                    'courseid' => $this->courseid,
                    'userid' => $USER->id
                    );
                $event = \block_skills_group\event\skillsgroup_left::create($params);
                $event->trigger();
                $url = new \moodle_url('/course/view.php', array('id' => $this->courseid));
                return $url;
            }
        }
        $groupid = $submittedform->groupid;
        if (isset($submittedform->allowjoincheck)) {
            update_allow_join($groupid, $submittedform->allowjoincheck);
        }
        update_note($groupid, $submittedform->note);
        // Only process group additions of permitted.
        $sgs = new skills_group_setting($this->courseid);
        if ($sgs->get_allowadding()) {
            // Re-map empty group to empty array.
            if (!isset($submittedform->members)) {
                $submittedform->members = array();
            }
            $this->update_members($groupid, $submittedform->members);
        }
        return $url;
    }

    /**
     * Update the members of a group.
     *
     * @param  int    $groupid  the ID of the group to update
     * @param  array  $members  the group members
     */
    private function update_members($groupid, $members) {
        global $DB, $USER;

        $sgroup = new skills_group($groupid);
        // Add locked students to list.
        $lockedstudents = $sgroup->get_members_list($lock = true);
        foreach ($lockedstudents as $key => $lockedstudent) {
            $members[] = $key;
        }
        $sgsetting = new skills_group_setting($this->courseid);
        if (count($members) <= $sgsetting->get_group_size() - 1) {
            // Wipe out old group.
            $DB->delete_records('groups_members', array('groupid' => $groupid));
            // Add self.
            groups_add_member($groupid, $USER->id);
            // Add other members.
            foreach ($members as $member) {
                groups_add_member($groupid, $member);
            }
            // Logging edit group action.
            $params = array(
                'context' => \context_course::instance($this->courseid),
                'objectid' => $groupid,
                'courseid' => $this->courseid,
                'userid' => $USER->id
                );
            $event = \block_skills_group\event\skillsgroup_joined::create($params);
            $event->trigger();
        }
    }

}
