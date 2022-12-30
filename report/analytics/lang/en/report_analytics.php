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
 * Lang file for analytics project.
 *
 * @package    report_analytics
 * @category   report
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['analytics'] = 'Analytics';
$string['betaname'] = 'Analytics (Beta)';
$string['pluginname'] = 'Analytics';

// Used by report_activities.php.
$string['event'] = 'Event';
$string['percentage'] = 'Percentage';
$string['quantity'] = 'quantity';

// Capability names.
$string['analytics:studentview'] = 'View student analytics report';
$string['analytics:view'] = 'View analytics report';

// Various Labels.
$string['activitytypes'] = 'Activity Types';
$string['addcondition'] = 'Add condition';
$string['addcriteria'] = 'Add more';
$string['addreport'] = 'Add a report';
$string['advancedtoggle'] = 'Toggle advanced settings';
$string['allactions'] = 'All Actions';
$string['applyfilterbutton'] = 'Apply filter';
$string['average'] = 'Average';
$string['backtotop'] = 'Back to posts index';
$string['conditions'] = 'Conditions';
$string['daterange'] = 'Dates';
$string['email'] = 'E-mail Address';
$string['engagement'] = 'Engagement';
$string['events'] = 'Events';
$string['filterby'] = 'Filter by:';
$string['filterbygradebutton'] = 'Grades';
$string['filterbystudentbutton'] = 'Students';
$string['grades'] = 'Grades';
$string['granularity'] = 'Granularity';
$string['noposts'] = 'No posts found';
$string['nouserscriteria'] = 'No users matched the selected criteria';
$string['novalidcourses'] = 'No valid courses';
$string['reads'] = 'Views';
$string['recipients'] = 'Email to:';
$string['savecriteria'] = "Save all";
$string['savecriteriasuccess'] = "Criteria successfully saved";
$string['schedulereport'] = 'Schedule report';
$string['selectcourse'] = 'Select a course:';
$string['separateactions'] = 'Separate Actions';
$string['studentquadrant'] = 'If your instructor does not update the course total grade, this tool will not be accurate.';
$string['testfilterbutton'] = 'Test filter';
$string['titleall'] = 'Displaying results for all students';
$string['titleselected'] = 'Displaying results for selected users';
$string['titlestudent'] = 'Displaying results for: ';
$string['totalevents'] = 'Total events';
$string['totalposts'] = 'Total posts: ';
$string['usercriteriatitle'] = 'Users matching criteria';
$string['usersperpage'] = 'Users per page';
$string['wordcount'] = 'Word count';
$string['words'] = 'Words';
$string['writes'] = 'Interactions';

// Grade comparison operator labels.
$string['equal'] = '=';
$string['greaterthan'] = '>';
$string['lessthan'] = '<';

// Average filter options.
$string['allstudents'] = 'All students';
$string['bottom15'] = 'Bottom 15% of class';
$string['top15'] = 'Top 15% of class';

// Help labels.
$string['actionfilter'] = 'Action Filter';
$string['actionfilter_help'] = 'Select the type of action(s)/event(s) you wish to see.';
$string['activityfilter'] = 'Activity Filter';
$string['activityfilter_help'] = 'Select an activity, activity type, or section (week/topic) to apply to your filter.';
$string['averagefilter'] = 'Averages';
$string['averagefilter_help'] = 'Include data for the course average to compare specific students with general engagement.';
$string['gradefilter'] = 'Grade Filter';
$string['gradefilter_help'] = 'Select a grade condition that will be used to filter students in your class.';
$string['recipientfilter'] = 'Recipient Filter';
$string['recipientfilter_help'] = 'Select one or more recipients to receive scheduled reports.';
$string['interactions'] = 'Interactions';
$string['interactions_help'] = 'Examples of interactions include:

* posting to a forum
* subscribing to a forum
* submitting an assignment
* submitting a quiz
* editing an activity';
$string['studentfilter'] = 'Student Filter';
$string['studentfilter_help'] = 'Select one or more student to apply to the filter.';
$string['timeslider'] = 'Granularity';
$string['timeslider_help'] = 'Increase the number of divisions on the x-axis (time) to adjust resolution.';
$string['uniquefilter'] = 'Unique';
$string['uniquefilter_help'] = 'Show unique actions rather than cumulative actions. (omit multiple actions by the same student)';
$string['views'] = 'Views';
$string['views_help'] = 'Views include accessing an activity (discussion, page, assignment, etc)';

// Error messages.
$string['actionnotfound'] = 'ERROR: Action/event was not found.';
$string['baddate'] = 'ERROR: Invalid date specification.';
$string['badrequest'] = 'ERROR: Bad request.';
$string['badsesskey'] = 'ERROR: invalid sesskey (session idle too long).  Please refresh the page.';
$string['classnotfound'] = 'ERROR: Activity class not found.';
$string['dberror'] = 'ERROR: unable to retrieve saved settings from the database';
$string['emptygroup'] = 'ERROR: Empty group selected.  Select group with students.';
$string['invalidchart'] = 'ERROR: word cloud only valid for user posts chart.';
$string['invalidcourse'] = 'ERROR: Invalid course.';
$string['invalidscaletext'] = 'ERROR: Value entered in grade condition not found in scale.';
$string['namenotfound'] = 'ERROR: Name of item was not found.';
$string['nocopyinbrowser'] = 'ERROR: your browser does not support copying directly to the clipboard.';
$string['nocriterion'] = 'ERROR: no criterion selected.  Please select an action or grade.';
$string['nocriterionvalue'] = 'ERROR: no value entered for criterion.';
$string['nodata'] = 'ERROR: No data found for chart.';
$string['nofilters'] = 'ERROR: No filters selected.';
$string['nogradevalue'] = 'ERROR: You must enter a value for the grade filter.';
$string['nologin'] = 'ERROR: You are not logged in or your session has timed out.  Please refresh and login again.';
$string['nopermission'] = 'ERROR: You do not have permission to access that report.';
$string['norecipients'] = 'ERROR: You must select at least one recipient for the scheduled reports.';
$string['nousers'] = 'ERROR: No users found matching criteria.';
$string['nousersselected'] = 'ERROR: no users selected.';
$string['sectionnotfound'] = 'ERROR: Section not found.';
$string['toomanyevents'] = 'ERROR: Too many events selected.  Please choose 3 or less.';

// Events.
$string['eventanalyticsviewed'] = 'Analytics report viewed.';

// Tasks.
$string['emailresults'] = 'Email analytics results';
$string['generateresults'] = 'Generate analytics results';
$string['resultssubject'] = 'Scheduled analytics criteria report';

// Graph chooser.
$string['activitychartname'] = 'Content engagement';
$string['activitychart_help'] = 'This report shows a graph of aggregated student engagement data on a selected set of activities.';
$string['activitytimelinechartname'] = 'Engagement over time';
$string['activitytimelinechart_help'] = 'This report shows a graph of student activities over time.
The results can be filtered by activity, by date, and by action.';
$string['choosereporttoadd'] = 'Choose type of analytics report to add:';
$string['completionsearchchartname'] = 'Student list by criteria';
$string['completionsearchchart_help'] = 'This report produces a list of students contact information.
The list can be filtered on grade, level of participation, or a combination of both.
This report can be used to identify students at varying levels of performance and engagement to provide feedback.
This report provides a copyable list of email addresses or can be exported to csv.';
$string['forumchartname'] = 'Forum engagement';
$string['forumchart_help'] = 'This report shows a graph of aggregated post/views for a selected set of forums.';
$string['forumtimelinechartname'] = 'Forum engagement over time';
$string['forumtimelinechart_help'] = 'This report shows a graph of student forum activities over time.
The results can be filtered by forum, by date, and by action.';
$string['gradechartname'] = 'Grades vs. Actions';
$string['gradechart_help'] = 'Display a scatter plot of grades vs. actions';
$string['lefttitle'] = 'Reports:';
$string['scheduledcriteriachartname'] = 'Scheduled criteria checks';
$string['scheduledcriteriachart_help'] = 'This report can be used to build a sets of criteria to be checked on a scheduled basis.
The results of these checks are sent to a list of contacts via email.';
$string['selectreportfordescription'] = 'Select a report to view the description.';
$string['userpostschartname'] = 'Forum posts by user(s)';
$string['userpostschart_help'] = 'This report lists all forum posts by a user.  This list can be filtered by forum, by date, and by
the total word count.';

// Component Labels.
$string['allassignments'] = 'All assignments';
$string['allcore'] = 'System';
$string['allfeedbacks'] = 'All feedbacks';
$string['allfiles'] = 'All files';
$string['allfolders'] = 'All folders';
$string['allforums'] = 'All forums';
$string['alllessons'] = 'All lessons';
$string['allpages'] = 'All pages';
$string['allquizzes'] = 'All quizzes';
$string['allurls'] = 'All URLs';

// Alt text for images.
$string['addcriterionalt'] = 'Add to list';
$string['applyfilteralt'] = 'Apply filters to generate view';
$string['chartgridalt'] = 'Change to grid layout';
$string['chartlistalt'] = 'Change to list layout';
$string['closebuttonalt'] = 'Close this report';
$string['copyusersalt'] = 'Copy selected users to clipboard';
$string['dateselectoralt'] = 'Date selector';
$string['excelexportalt'] = 'Export to excel';
$string['pngexportalt'] = 'Export to png';
$string['redoalt'] = 'Reverse the most recent undo operation';
$string['removecriterionalt'] = 'Remove from list';
$string['undoalt'] = 'Revert to previous set of data';
$string['wordcloudalt'] = 'Create word cloud';

// Text for date selector.
$string['lastfourmonths'] = 'Last 4 months';
$string['lastthirteenweeks'] = 'Last 13 weeks';
$string['lastweek'] = 'Last week';

// Email footer text.
$string['emailnotification'] = 'This is a notification email from eClass at the University of Alberta, please do not reply.';
$string['emailremove'] = 'To edit/remove these notifications, visit: ';

// Privacy API.
$string['privacy:metadata:report_analytics_results'] = 'Contains results from scheduled queries';
$string['privacy:metadata:report_analytics_results:userids'] = 'IDs of users to send reports to';
$string['privacy:metadata:report_analytics_results:courseid'] = 'ID of course';
$string['privacy:metadata:report_analytics_results:filters'] = 'Filters used (json encoded)';
$string['privacy:metadata:report_analytics_results:results'] = 'Results of applying filter (json encoded)';
$string['privacy:metadata:report_analytics_results:resultstime'] = 'Timestamp for most recent results';
$string['privacy:metadata:report_analytics_results:emailtime'] = 'Timestamp for when user emailed results';
$string['privacy:export:times'] = 'Timestamps for when results generated and emailed.';
