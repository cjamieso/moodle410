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
 * This is the main javascript file for the scheduled report page.  I've set
 * it to depend on the report_analytics.js file so that one gets processed
 * first.
 *
 * @category   report
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'report_analytics/filter', 'report_analytics/report_analytics'], function($, filters) {

    $('.scheduled-save').on('click', _saveCriteria);

    /**
     * Save the criteria/dates selected by the user as well as the list of recipients.
     * Note that the list of recipients applies to report as a whole, while the
     * criteria/dates will be different for each portion of the report.
     */
    function _saveCriteria() {

        var criteriaFilters = _getCriteriaFilters();
        var userFilter = $('.recipientfiltercontainer').data('userFilter');
        if (userFilter !== undefined) {
            var userids = userFilter.getFilterData();
            if (userids === null) {
                $('.addstatustext').text('').removeClass('alert alert-danger');
                $('.addstatustext').addClass('alert alert-danger').text(M.util.get_string('norecipients', 'report_analytics'));
            } else {
                $(this).attr('disabled', 'disabled');
                var time = Date.now();
                M.util.js_pending('save' + time);
                _saveCriteriaAjax(criteriaFilters, userids, time);
            }
        }
    }

    /**
     * Gets the filters for each criteria which the user wishes to generate
     * a report for.
     *
     * @return {Array}  the criteria/dates for each set of criteria the user wishes to see
     */
    function _getCriteriaFilters() {

        var criteriaFilters = [];
        $('#chartcontainer').children().each(function() {
            var $this = $(this);
            if ($this.hasClass('chartheader')) {
                var chart = $this.data('chart');
                if (chart !== undefined) {
                    try {
                        var chartFilters = chart.getFilters();
                        criteriaFilters.push({date: chartFilters.date, criteria: chartFilters.criteria});
                    } catch (e) {
                        var outerdiv = chart.getOuterdiv();
                        outerdiv.find('.filterstatustext').addClass('alert alert-danger').text(e.message);
                    }
                }
            }
        });
        return criteriaFilters;
    }

    /**
     * This function sends a request to save the criteria/dates and list of recipients
     * that have been selected by the user to form a scheduled report.
     *
     * @param  {object}  savedFilters  the criteria/dates on the filters to save
     * @param  {object}  userids       the list of users to send the reports to
     * @param  {string}  time          time of button press: used to mark request complete
     */
    function _saveCriteriaAjax(savedFilters, userids, time) {

        // Wipe any older errors.
        $('.addstatustext').text('').removeClass('alert alert-danger');
        // Add Moodle spinner while AJAX request is running.
        var container = $('.save-spinner');
        var spinner = M.util.add_spinner(Y, Y.one(container[0])).show();

        $.post(M.cfg.wwwroot + "/report/analytics/ajax_request.php", {
            courseid: window.retrieveCourseID(),
            sesskey: M.cfg.sesskey,
            request: 'save_criteria',
            userids: JSON.stringify(userids),
            filters: JSON.stringify(savedFilters)
        }, null, 'json')
        .done(function(data) {
            if (data.result === true) {
                $('.addstatustext').addClass('alert .alert-success').text(data.message);
            } else {
                $('.addstatustext').addClass('alert alert-danger').text(data.message);
            }
        })
        .fail(function() {
            $('.addstatustext').addClass('alert alert-danger').text(M.util.get_string('badrequest', 'report_analytics'));
        })
        .always(function() {
            $('.scheduled-save').removeAttr('disabled');
            spinner.hide();
            M.util.js_complete('save' + time);
        });
    }

    return {
        scheduledReportCriteriaInit: function() {

            $('.jschooser').Chooser({});
            // Display any saved criteria (passed in arguments) on the page.
            for (var j = 0; j < arguments.length; j++) {
                var time = Date.now();
                M.util.js_pending('add' + time);
                $('.jschooser').Chooser('addGraphAjax', 'ScheduledCriteriaChart', time, arguments[j]);
            }
        },

        scheduledReportUserIDsInit: function() {

            var userFilter = new filters.SelectFilter($('.recipientfilter'), {filter: true});
            var userids = [];
            // Add userids to array, "arguments" is actually an object.
            for (var j = 0; j < arguments.length; j++) {
                userids.push(arguments[j]);
            }
            if (arguments.length > 0) {
                userFilter.setFilterData(userids);
            }
            $('.recipientfiltercontainer').data('userFilter', userFilter);
        }
    };

});
