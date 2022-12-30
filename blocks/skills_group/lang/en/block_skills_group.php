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


/**
 * This is the english language file for the project.
 *
 * @package    block_skills_group
 * @category   block
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Group Sign-up';

// Capability labels.
$string['skills_group:canmanageskillsgroups'] = 'Manage/Configure skills group settings';
$string['skills_group:cancreateorjoinskillsgroups'] = 'Create or join a skills group';
$string['skills_group:addinstance'] = 'Add a new skills group block';

// Block labels.
$string['editgroupsettings'] = 'Edit skills group settings';
$string['togglelockstatus'] = 'View/edit student lock status';
$string['graphresults'] = 'Graph/Export feedback results';
$string['identry'] = 'Enter IDs for export';
$string['createskillsgroup'] = 'Create/Edit a group';
$string['editskillsgroup'] = 'Edit group';
$string['joinskillsgroup'] = 'Join existing group';
$string['notconfigured'] = 'Instructor has not yet configured settings.  Please check back later.';
$string['notconfiguredleft'] = 'Settings not yet configured.';
$string['notconfiguredright'] = 'Please check later.';
$string['dateexpired'] = 'Date for creating groups expired.  Please contact your instructor for assistance';
$string['dateexpiredleft'] = 'Date expired.';
$string['dateexpiredright'] = 'Please contact your instructor for assistance';
$string['groupexpired'] = 'Group creation expired';

// Labels/Strings for Settings Edit page.
$string['editsettingstitle'] = 'Edit Skills Group Settings';
$string['inputsheader'] = 'Inputs';
$string['note'] = 'Note:';
$string['inputextrahelp'] = 'By default, all students are permitted to use the block (no setup needed).  Optionally, select a feedback below to pull data from.';
$string['prefeedback'] = 'Choose a pre-course feedback activity:';
$string['postfeedback'] = 'Choose a post-course feedback activity:';
$string['feedbackerror'] = 'No feedback activities found, please create one first';
$string['threshold'] = 'Score threshold:';
$string['outputsheader'] = 'Outputs';
$string['outputextrahelp'] = 'All student created groups are placed in this grouping.  Attach the grouping to other activities in your course as needed.';
$string['groupingid'] = 'Choose a grouping';
$string['groupingerror'] = 'No groupings found, please create one';
$string['none'] = 'None';
$string['settingsheader'] = 'Additional Settings';
$string['maxsize'] = 'Maximum group size:';
$string['allowchanges'] = 'Allow group changes until:';
$string['instructorgroups'] = 'Group Creation:';
$string['instructorgroupsright'] = 'Instructor must create all groups';
$string['allownaming'] = 'Naming:';
$string['allownamingright'] = 'Students able to name their own groups';
$string['allowadding'] = 'Adding:';
$string['allowaddingright'] = 'Students able to add classmates to their group';
$string['allowgroupview'] = 'Viewing:';
$string['allowgroupviewright'] = 'Students able to able to view their group members';
$string['enabled'] = 'Enabled';

// Labels/Strings for Graph Results page.
$string['graphresultstitle'] = 'Graph of pre and post course results';
$string['questions'] = 'Questions';
$string['itemfilter'] = 'Question filter';
$string['itemfilter_help'] = 'Select one or more feedback questions to aggregate';
$string['applyfilterbutton'] = 'Apply filter';
$string['chartname'] = 'Pre and post course survey results for selected questions';
$string['count'] = 'Count';
$string['excelexportalt'] = 'Export to excel';
$string['pngexportalt'] = 'Export to png';
$string['placeholderalt'] = 'Graph placeholder';
$string['pre'] = 'Pre-course';
$string['post'] = 'Post-course';
$string['exceldatafor'] = 'Excel data for:';
$string['preexport'] = 'Export pre-course data';
$string['postexport'] = 'Export post-course data';

// Labels/Strings for the ID entry page.
$string['identrytitle'] = 'Enter IDs for export';
$string['idextrahelp'] = 'Enter the ID that will be used in the accreditation system.';
$string['missingposition'] = 'ERROR: missing position of feedback question';
$string['missingaccreditationid'] = 'ERROR: no mapping for accreditation ID provided';
$string['uploadheader'] = 'Upload ID mapping';
$string['uploadheader_help'] = 'Upload a csv file containing the names in first column and the IDs in the second column.';
$string['uploadids'] = 'Upload a file';
$string['submitupload'] = 'Submit file';

// Help bubbles for Settings Edit page.
$string['prefeedback_help'] = 'Select the pre-course feedback activity to pull student data from (optional).';
$string['postfeedback_help'] = 'Select the post-course feedback activity to pull student data from (optional).';
$string['groupingid_help'] = 'Students and their groups will be automatically placed in this grouping for you.';
$string['maxsize_help'] = 'The maximum size for groups in your course.';
$string['threshold_help'] = 'If using a feedback activity for data, any number ABOVE this will be considered a high score.';
$string['allowchanges_help'] = 'Select a date here to have the group changes no longer avaiable after this date.';
$string['instructorgroups_help'] = 'Instructor must pre-create groups for students to use.';
$string['allownaming_help'] = 'Permit students to name their own groups.';
$string['allowadding_help'] = 'Permit students to add classmates to their group.';
$string['allowgroupview_help'] = 'Permit students to view their groups.';

// Create group page.
$string['creategrouptitle'] = 'Create/Edit Group';
$string['creategroupheader'] = 'Create/Edit group';
$string['existinggroup'] = 'Existing group:';
$string['editmembers'] = 'Edit group members';
$string['leavegroup'] = 'Leave group';
$string['nogroup'] = 'None';
$string['creategroup'] = 'Create group';
$string['groupsearchable'] = 'Allow classmates to search for group';
$string['groupautoname'] = 'Team';
$string['gojoingroupleft'] = 'No group: ';
$string['gojoingroupright'] = 'Return to main course page and join a group.';
$string['groupnote'] = 'Group note: ';

// Lock choice page.
$string['lockchoiceheader'] = 'Lock Group Choice';
$string['lockchoicetitle'] = 'Lock Group Choice';
$string['lockgroup'] = 'Lock my group choice';
$string['status'] = 'Status:';
$string['choicelocked'] = 'You have already locked in your group selection.';

// Toggle lock choice page.
$string['student'] = 'Student';
$string['lockstatus'] = 'Lock status';
$string['lock'] = 'Lock user\'s choice';
$string['unlock'] = 'Unlock user\'s choice';

// Add users page.
$string['adduserstogroup'] = 'Add Users to Group';
$string['groupmembers'] = 'Group members:';
$string['lockedmembers'] = 'Locked members:';
$string['groupplaceholder'] = 'Type a name';
$string['submitbutton'] = 'Submit';
$string['returnbutton'] = 'Back to Course';

// Javascript -> edit_skills_group.php.
$string['nomembers'] = 'ERROR: No group members added';
$string['nologin'] = 'ERROR: You are not logged in or your session has timed out.  Please refresh and login again.';
$string['badsesskey'] = 'ERROR: invalid sesskey (session idle too long).';
$string['groupupdatesuccess'] = 'Group successfully updated.';
$string['groupupdateerror'] = 'ERROR: Failure updating group.  Please refresh page.';
$string['notingroup'] = 'ERROR: You are not part of this group.  Return to course and attempt again.';
$string['toomanymembers'] = 'ERROR: Too many members in group';
$string['nogrouperror'] = 'ERROR: You are not part of a group';
$string['noviewgrouperror'] = 'ERROR: You are not permitted to view your group';

// Join group page.
$string['join'] = 'Join';
$string['joingroup'] = 'Join a Group';
$string['joingroupbutton'] = 'Join Group';
$string['refreshgroupsbutton'] = 'Refresh Groups';
$string['numberofmembers'] = 'Number of members';
$string['note'] = 'Note';

// View group page.
$string['viewskillsgroup'] = 'View group';
$string['skillheader'] = 'Skill';
$string['skillcount'] = 'Team (Count of Strengths)';
$string['incomplete'] = 'Incomplete';

// Javascript -> join_skills_group.php.
$string['groupsloading'] = "Loading...";
$string['emptygroups'] = "No groups available to join.";
$string['groupsloaderror'] = "Error retrieving groups.";
$string['groupjoinsuccess'] = 'Successfully joined group.';
$string['groupjoinerror'] = 'ERROR: Failure joining group.  Please refresh page.';
$string['alreadyingroup'] = 'ERROR: You are already in a group.';
$string['invalidgroup'] = 'ERROR: Invalid group choice.';

// Error messages.
$string['loginrequired'] = 'You must be logged in to use this page.';
$string['dberror'] = 'Error accessing the database';
$string['noaccess'] = 'You have no access to this page.  Please contact a system administrator if you believe this is an error.';
$string['groupingmissing'] = 'ERROR: No grouping was specified.';
$string['badrequest'] = 'ERROR: Bad request.';
$string['noquestions'] = 'ERROR: You must select at least one question from the list.';

// Logging URLS.
$string['skillsgroupcreated'] = 'create group';
$string['creategroupinfo'] = 'created group, groupid: ';
$string['skillsgroupleft'] = 'leave group';
$string['leavegroupinfo'] = 'leaving group, groupid: ';
$string['skillsgroupjoined'] = 'join group';
$string['joingroupinfo'] = 'joining group, groupid: ';
$string['skillsgroupedited'] = 'edit group';
$string['editgroupinfo'] = 'edit group, groupid: ';

// Privacy API
$string['privacy:metadata:skills_group_student'] = 'Contains results of student group lock status';
$string['privacy:metadata:core_group'] = 'Moodle groups are created by the plugin';
$string['privacy:metadata:skills_group_student:userid'] = 'ID of user';
$string['privacy:metadata:skills_group_student:groupingid'] = 'ID of grouping';
$string['privacy:metadata:skills_group_student:finalizegroup'] = 'Lock status';
