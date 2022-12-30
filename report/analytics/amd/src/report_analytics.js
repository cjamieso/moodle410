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
 * This is the main javascript file that contains the init loop and a few
 * helper functions.
 *
 * @category   report
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'report_analytics/jquery.chartcontainer', 'report_analytics/jquery.chooser'], function($) {

    /**
     * Retrieve the course ID from the page.  Moodle stores it as a class on the
     * body element starting with 'course-' followed by the ID.
     *
     * A course selector is visible if the user is accessing the page from the
     * moodle homepage (courseid = 1).  If that selector is being used, grab its
     * value instead.
     *
     * @return {int}  the ID of the course being used
     */
    window.retrieveCourseID = function() {

        var courseid = 1;
        if ($('.coursefiltercontainer').is(":visible")) {
            courseid = $('.coursefiltercontainer select').val();
        } else {
            var classes = $('body').attr('class').toString();
            var index = classes.indexOf('course-');
            if (index !== -1) {
                courseid = parseInt(classes.substring(index + 7));
            }
        }
        return courseid;
    };

    /**
     * Retrieves data stored with a d3.js node.  The primary purpose of this function
     * is to make data accessible for behat tests.
     *
     * @param  {string} label the graph label to retrieve the data for
     * @return {Array}  the data for specified label (or false if no data found)
     */
    window.getSeriesD3data = function(label) {
        var t = false;
        $('.series').each(function() {
            if ((this.__data__.key) === label) {
                t = this.__data__.values;
            }
        });
        return t;
    };

    /**
     * Retrieves data stored with a d3.js grouped bar.  The primary purpose of this function
     * is to make data accessible for behat tests.
     *
     * @param  {string} label the graph label to retrieve the data for
     * @return {Array}  the data for specified label (or false if no data found)
     */
    window.getGroupedbarD3data = function(label) {
        var t = false;
        $('.groupedbar').each(function() {
            if ((this.__data__.label) === label) {
                t = this.__data__;
            }
        });
        return t;
    };

    /**
     * Retrieves the data stored in a node in the grades vs. actions scatter plot.
     *
     * @param  {string}  name  the name of the student to retrieve the data for
     * @return {Array}  the data for the specified student
     */
    window.getScatterD3data = function(name) {
        var t = false;
        $('.node').each(function() {
            if ((this.__data__.name) === name) {
                t = this.__data__;
            }
        });
        return t;
    };

    return {
        /**
         * Iniitalize the plugin.
         */
        init: function() {
            $(document).ready(function() {

                var courseid = window.retrieveCourseID();
                if (courseid === 1) {
                    $('.coursefiltercontainer').show();
                }
                $('#chartcontainer').ChartContainer({});
                $('.jschooser').Chooser({});
            });
        }
    };

});
