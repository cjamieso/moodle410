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
 * This file contains a number of basic types of filters.  Filters have
 * two basic properties, they can:
 * 1) initialize themselves
 * 2) return their selected data
 * 3) set their data
 *
 * @module     report_analytics/Filter
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'jqueryui', 'report_analytics/jquery.multiple.select', 'report_analytics/jquery.dateselect'], function($) {

    /**
     * This is the basic filter class containing the interface.
     * customized method to grab the filter data and render the graph.
     *
     * @param  {object}  node     the node to attach the filter to
     * @param  {object}  options  options used when creating filter
     */
    var Filter = function(node, options) {
        this.node = node;
        this._initFilter(options);
    };

    Filter.prototype = {

        /**
         * Any javascript initialization for the filter is handled here.
         */
        _initFilter: function() {
            return;
        },

        /**
         * Retrieve and return filter data.  Base class returns null, which is
         * expected by PHP classes where no selection was made.
         *
         * @return {object}  null (existential question: is null really an object?)
         */
        getFilterData: function() {
            return null;
        },

    };

    /**
     * The SelectFilter is a dropbox box that allows the user to pick one or more
     * options.  This is used for both the single select and multiple select
     * versions.
     *
     * @param  {object}  node     the node to attach the filter to
     * @param  {object}  options  options used when creating filter
     * @param  {Number}  max      maximum number of options that can be selected
     */
    var SelectFilter = function(node, options, max) {
        Filter.call(this, node, options);
        this.max = max;
    };

    SelectFilter.prototype = Object.create(Filter.prototype);
    SelectFilter.prototype.constructor = SelectFilter;

    /**
     * Setup a select filter on the page.  Multiple select filters require JS initialization.
     * The multiple select plugin actually unhides the parent of the select (unfortunately),
     * so I am saving it's previous display state and restoring it after the multiple select
     * is setup.
     *
     * @param  {object}  options  options to use with the multiple select
     */
    SelectFilter.prototype._initFilter = function(options) {

        if (this.node.attr('multiple') === 'multiple') {
            var display = this.node.parent().css('display');
            this.node.multipleSelect(options);
            this.node.parent().css('display', display);
        }
    };

    /**
     * Retrieve and return options selected by the user.  The css class "multiple"
     * is used to determine whether the filter allows for single or multiple selections.
     *
     * @return {int|Array}  the selected choices
     */
    SelectFilter.prototype.getFilterData = function() {

        var selects;
        if (this.node.attr('multiple') === 'multiple') {
            selects = this.node.multipleSelect('getSelects');
            if (selects.length === 0 || selects === undefined) {
                return null;
            }
        } else {
            selects = this.node.val();
        }
        if (this.max !== undefined) {
            if (this.max.number !== undefined && selects.length > this.max.number) {
                throw new Error(this.max.message);
            }
        }
        return selects;
    };

    /**
     * Sets the filter data.  The method to set the data differs slightly depending
     * on whether multiple selections are permitted.
     *
     * @param  {int|Array}  filterData  the values to set the filter to
     */
    SelectFilter.prototype.setFilterData = function(filterData) {

        if (this.node.attr('multiple') === 'multiple') {
            this.node.multipleSelect('setSelects', filterData);
        } else {
            this.node.val(filterData);
        }
    };

    /**
     * The CheckBoxFilter is used for filters based on a simple checkbox.
     *
     * @param  {object}  node     the node to attach the filter to
     * @param  {object}  options  options used when creating filter
     */
    var CheckBoxFilter = function(node, options) {
        Filter.call(this, node, options);
    };

    CheckBoxFilter.prototype = Object.create(Filter.prototype);
    CheckBoxFilter.prototype.constructor = CheckBoxFilter;

    /**
     * Retrieve and return whether the checkbox is checked.
     *
     * @return {bool}  T/F indicating if the checkbox is checked
     */
    CheckBoxFilter.prototype.getFilterData = function() {
        return this.node.prop('checked');
    };

    /**
     * Sets the checkbox to to the desired value.
     *
     * @param  {bool}  filterData  T/F indicating if the filter should be checked or not
     */
    CheckBoxFilter.prototype.setFilterData = function(filterData) {
        this.node.prop('checked', filterData);
    };

    /**
     * The StudentFilter is based on the SelectFilter.
     *
     * Student filters can be slightly more complex in that some graphs allow the
     * user to filter via a list or grades.  In those cases, we must first ensure
     * that the user elected to filter students by a list.
     *
     * @param  {object}  node     the node to attach the filter to
     * @param  {object}  options  options used when creating filter
     * @param  {Number}  max      maximum number of options that can be selected
     */
    var StudentFilter = function(node, options, max) {
        SelectFilter.call(this, node, options, max);
    };

    StudentFilter.prototype = Object.create(SelectFilter.prototype);
    StudentFilter.prototype.constructor = StudentFilter;

    /**
     * Retrieve and return students selected by the user.
     *
     * When the user may select students via a list or grades, ensure that the
     * student button was pressed (ie: marked as disabled).
     *
     * @return {int|Array}  the selected students
     */
    StudentFilter.prototype.getFilterData = function() {

        var filterheader = this.node.closest('.filterheader');
        var button = filterheader.find('.filterbutton.student');
        if (button.length === 0 || button.is(':disabled')) {
            return SelectFilter.prototype.getFilterData.call(this);
        } else {
            return null;
        }
    };

    /**
     * The DateFilter is a filter consisting of two sets of dates.  A "from" date
     * and a "to" date.
     *
     * @param  {object}  node     the node to attach the filter to
     * @param  {object}  options  options used when creating filter
     */
    var DateFilter = function(node, options) {
        Filter.call(this, node, options);
    };

    DateFilter.prototype = Object.create(Filter.prototype);
    DateFilter.prototype.constructor = DateFilter;

    /**
     * Setup a date filter on the page.  The display is tweaked slightly and a
     * date selector is initialized to allow the user to select from some
     * pre-filled dates.
     *
     * @param  {object}  options  options for the filter {date: desired set of dates}
     */
    DateFilter.prototype._initFilter = function(options) {

        Filter.prototype._initFilter.call(this, options);
        if (options.date === undefined || options.date === null) {
            options.date = this._getStartingDate();
        }
        this.setFilterData(options.date);
        this.node.parent().find('.dateselector').DateSelect({datefilter: this});
        // Add clear between two dates so that it spans two lines.
        this.node.find('.fdate_time_selector:first').after("<div class='clear'></div>");
    };

    /**
     * Retrieves and return the dates that the user has selected.
     *
     * @return {boolean|object}  null -> empty dates, false -> invalid date, otherwise returns date object
     */
    DateFilter.prototype.getFilterData = function() {

        var date = {from: this._getDate('datefrom'), to: this._getDate('dateto')};
        if (!this._validateDate(date)) {
            throw new Error(M.util.get_string('baddate', 'report_analytics'));
        }
        return date;
    };

    /**
     * Retreives a specific date from a date/time picker.
     *
     * @param  {string}  namePrefix  the form element prefix for the date/time picker {dateto|datefrom}
     * @return {string}  date/time in {Y-M-D H:i} format
     */
    DateFilter.prototype._getDate = function(namePrefix) {

        var day = this.node.find('select[name *= "' + namePrefix + '[day]"]').val();
        var month = this.node.find('select[name *= "' + namePrefix + '[month]"]').val();
        var year = this.node.find('select[name *= "' + namePrefix + '[year]"]').val();
        var hour = this.node.find('select[name *= "' + namePrefix + '[hour]"]').val();
        var minute = this.node.find('select[name *= "' + namePrefix + '[minute]"]').val();
        var time = this._padZero(hour) + ':' + this._padZero(minute);
        return year + '-' + this._padZero(month) + '-' + this._padZero(day) + ' ' + time;
    };

    /**
     * Pads a zero in front of values that are a single digit (month, day, minute, second).
     *
     * @param  {number|string}  value   the numeric value taken from the <select>
     * @return {string}  value padded with a 0 if its numeric value is less than 10
     */
    DateFilter.prototype._padZero = function(value) {

        if (value < 10) {
            value = '0' + value;
        }
        return value;
    };

    /**
     * Validates a date/time from the calendar picker.
     *
     * @param  {object}   date    object with two dates/times "from" and "to"
     * @return {boolean}  T if valid date, F if invalid date specification
     */
    DateFilter.prototype._validateDate = function(date) {

        var pattern = /^\d{4}[-](0?[1-9]|1[012])[-](0?[1-9]|[12][0-9]|3[01])[\s+](0?[0-9]|[1][0-9]|2[0123])[:]\d{1,2}$/;
        if (!pattern.test(date.from)) {
            return false;
        } else {
            return pattern.test(date.to);
        }
    };

    /**
     * Sets the two dates on a date/time picker.  A check is made for both an empty date
     * and an invalid date.
     *
     * @param  {object}   date    object with two dates/times "from" and "to"
     */
    DateFilter.prototype.setFilterData = function(date) {

        if (date === null || !this._validateDate(date)) {
            return;
        }
        this._setDate(date.from, 'datefrom');
        this._setDate(date.to, 'dateto');
    };

    /**
     * Sets a specified date from a date/time picker.
     *
     * @param  {string}  date        the date (formatted as 'y-m-d h:m')
     * @param  {string}  namePrefix  the form element prefix for the date/time picker {dateto|datefrom}
     */
    DateFilter.prototype._setDate = function(date, namePrefix) {

        var yearMonth = date.split('-');
        var dayTime = yearMonth[2].split(' ');
        var time = dayTime[1].split(':');
        this.node.find('select[name *= "' + namePrefix + '[day]"]').val(parseInt(dayTime[0]));
        this.node.find('select[name *= "' + namePrefix + '[month]"]').val(parseInt(yearMonth[1]));
        this.node.find('select[name *= "' + namePrefix + '[year]"]').val(yearMonth[0]);
        this.node.find('select[name *= "' + namePrefix + '[hour]"]').val(parseInt(time[0]));
        this.node.find('select[name *= "' + namePrefix + '[minute]"]').val(parseInt(time[1]));
    };

    /**
     * Gets the starting (default) date set to use.  Based on the course name
     * in the header link, look for any appropriate term codes.  If no term
     * codes exist, default to the current term.
     *
     * @return {object}  the from/to dates for the term
     */
    DateFilter.prototype._getStartingDate = function() {

        var courseName = $('.course-header-link a, .page-header-headings h1');
        if (courseName !== undefined || courseName.length > 0) {
            courseName = courseName.get(0).innerHTML;
        } else {
            courseName = '';
        }
        var pattern = /(Sp\d{2}|Su\d{2}|Fa\d{2}|Wi\d{2})/g;
        var matches = courseName.match(pattern);
        var currentYear = String(new Date().getFullYear());
        var dates = [];

        if (matches === null) {
            return this._getTermDates(currentYear);
        } else {
            for (var i = 0; i < matches.length; i++) {
                var year = currentYear.substr(0, 2) + matches[i].substr(2);
                var month = 1;
                switch (matches[i].substr(0, 2).toLowerCase()) {
                    case 'fa':
                        month = 9;
                        break;
                    case 'wi':
                        month = 1;
                        break;
                    case 'sp':
                        month = 5;
                        break;
                    case 'su':
                        month = 7;
                        break;
                }
                dates.push(this._getTermDates(year, month));
            }
            return this._getDateRange(dates);
        }
    };

    /**
     * Gets the from/to dates based on the current month given.  A full date
     * string for each from/to value is created, including the hour:minute.
     *
     * The hour/minute are set to the beginning of the first date and the end
     * of the last date.
     *
     * @param  {string}  year   the year to use
     * @param  {number}  month  the month (determines winter/spring/summer/fall term)
     * @return {object}  the from/to dates for the term
     */
    DateFilter.prototype._getTermDates = function(year, month) {

        // If no month specified, use current month.
        if (month === undefined) {
            month = parseInt(new Date().getMonth()) + 1;
        } else {
            month = parseInt(month);
        }
        var start, stop;
        if (month < 5) {
            start = '01-01';
            stop = '04-30';
        } else if (month < 7) {
            start = '05-01';
            stop = '06-30';
        } else if (month < 9) {
            start = '07-01';
            stop = '08-31';
        } else {
            start = '09-01';
            stop = '12-31';
        }
        return {from: year + '-' + start + ' 00:00', to: year + '-' + stop + ' 23:59'};
    };

    /**
     * Given an array of date ranges, find the widest range that satisfies all of them.
     * For example, if we have a Fa16 Wi17 course, the dates would span from Sept. 1st
     * to April 31st.
     *
     * @param  {Array}  dates  The dates
     * @return {object}  the date ranged which spam all of the individual dates
     */
    DateFilter.prototype._getDateRange = function(dates) {

        var start = dates[0].from;
        var stop = dates[0].to;
        for (var i = 1; i < dates.length; i++) {
            if (dates[i].from < start) {
                start = dates[i].from;
            }
            if (dates[i].to > stop) {
                stop = dates[i].to;
            }
        }
        return {from: start, to: stop};
    };

    // This is written in the negative so that any characters matching this expression are removed.
    var _characterstoremove = /[^\w+\- ']/g;

    /**
     * The GradeFilter is based on the SelectFilter.
     *
     * Grade filters can be slightly more complex in that some graphs allow the
     * user to filter via a list or grades.  In those cases, we must first ensure
     * that the user elected to filter students by grades.
     *
     * @param  {object}  node     the node to attach the filter to
     * @param  {object}  options  options used when creating filter
     */
    var GradeFilter = function(node, options) {
        SelectFilter.call(this, node, options);
    };

    GradeFilter.prototype = Object.create(SelectFilter.prototype);
    GradeFilter.prototype.constructor = GradeFilter;

    /**
     * Setup a grade filter on the page.  Grade filters are accompanied by a selector
     * that lets the user choose whether they'd like to filter students by a list or
     * by a grade criteria.
     *
     * @param  {object}  options  options for the filter
     */
    GradeFilter.prototype._initFilter = function(options) {
        SelectFilter.prototype._initFilter.call(this, options);
        this.node.parent().on('click', '.filterbutton', this, this._setFilter);
        this.node.on('keyup', '.valuefilter', function() {
            $(this).val(this.value.replace(_characterstoremove, ""));
        });
    };

    /**
     * Sets the primary filter to use either the student listing or grades.
     */
    GradeFilter.prototype._setFilter = function() {

        var $this = $(this);
        var selected = $this.val();
        $this.parent().find('.filterbutton').removeAttr('disabled');
        $this.attr('disabled', 'disabled');
        $this.parent().find('button').each(function() {
            $this.parent().find("." + $(this).val() + "filtercontainer").hide();
        });
        $this.parent().find('.' + selected + 'filtercontainer').show();
    };

    /**
     * Retrieve and return information about desired grade filtering.
     *
     * Ensure that the grades button was pressed (ie: marked as disabled)
     * in order to send back information about the filter.  If grades button
     * was not pressed, return null.
     *
     * @return {object}  the grade criteria {operand, operator, value}
     */
    GradeFilter.prototype.getFilterData = function() {

        var filterHeader = this.node.parent().parent();
        if (filterHeader.find(".filterbutton[value='grade']").is(':disabled')) {
            var operand = this.node.find('.gradefilter').val();
            var operator = this.node.find('.gradeoperatorfilter').val();
            var value = this.node.find('.valuefilter').val();
            value = value.replace(_characterstoremove, "");
            if (value === undefined || value === null || value === '') {
                throw new Error(M.util.get_string('nogradevalue', 'report_analytics'));
            }
            return {operand: operand, operator: operator, value: value};
        } else {
            return null;
        }
    };

    /**
     * Set the filter data for a grade filter.  If provided with empty data, set
     * the two selects to be the first element and wipe out the text field.
     *
     * @param  {object}  filterData  the grade criteria {item, operator, text}
     */
    GradeFilter.prototype.setFilterData = function(filterData) {

        if (filterData === undefined || filterData === null) {
            var node = this.node.find('.gradefilter');
            node.val(node.find('option:first').val());
            node = this.node.find('.gradeoperatorfilter');
            node.val(node.find('option:first').val());
            this.node.find('.valuefilter').val('');
        } else {
            this.node.find('.gradefilter').val(filterData.item);
            this.node.find('.gradeoperatorfilter').val(filterData.operator);
            this.node.find('.valuefilter').val(filterData.text);
        }
    };

    // Default to 16 bins for slider.
    var DEFAULTBINS = 16;

    /**
     * The SliderFilter is used to let the user select a value from a slider.
     *
     * @param  {object}  node     the node to attach the filter to
     * @param  {object}  options  options used when creating filter
     */
    var SliderFilter = function(node, options) {
        Filter.call(this, node, options);
    };

    SliderFilter.prototype = Object.create(Filter.prototype);
    SliderFilter.prototype.constructor = SliderFilter;

    /**
     * Setup a slider on the page.  The slider consists of the actual slider
     * and also an uneditable input that displays the value.
     *
     * Setup the handler to update the value and the jQuery slider.
     *
     * @param  {object}  options  options for the slider {bins: desired starting value}
     */
    SliderFilter.prototype._initFilter = function(options) {

        Filter.prototype._initFilter.call(this, options);
        var sliderUpdate = function(event, ui) {
            var outerdiv = $(this).parent();
            outerdiv.find('.binslidervalue').val(ui.value);
        };
        var bins = (options !== undefined && options.bins !== null) ? options.bins : DEFAULTBINS;
        var sliderOptions = {value: bins, min: 6, max: 128, step: 1, slide: sliderUpdate};
        this.node.find(".binslider").slider(sliderOptions);
        this.node.find(".binslidervalue").val(bins);
    };

    /**
     * Retrieve and return the selected value.
     *
     * @return {int}  the value selected on the slider
     */
    SliderFilter.prototype.getFilterData = function() {
        return this.node.find('.binslidervalue').val();
    };

    /**
     * Set the slider to the desired value.
     *
     * @param  {int}  filterData  the desired value for the slider
     */
    SliderFilter.prototype.setFilterData = function(filterData) {
        this.node.find('.binslidervalue').val(filterData);
    };

    /**
     * The NumberFilter is used to let the user select a numeric value.
     *
     * @param  {object}  node     the node to attach the filter to
     * @param  {object}  options  options used when creating filter
     */
    var NumberFilter = function(node, options) {
        Filter.call(this, node, options);
    };

    NumberFilter.prototype = Object.create(Filter.prototype);
    NumberFilter.prototype.constructor = NumberFilter;

    /**
     * Setup the number filter.  Restrict input to digits only.
     *
     * @param  {object}  options  options for the filter {value: set default value for filter}
     */
    NumberFilter.prototype._initFilter = function(options) {

        Filter.prototype._initFilter.call(this, options);
        if (options !== undefined && options.value !== undefined) {
            this.node.val(options.value);
        }
        this.node.on('keyup', function() {
            $(this).val(this.value.replace(/[^0-9]/g, ""));
        });
    };

    /**
     * Retrieve and return the current value of the filter
     *
     * @return {int}  the value of the filter
     */
    NumberFilter.prototype.getFilterData = function() {

        return Number(this.node.val());
    };

    /**
     * Set the number filter to the desired value.
     *
     * @param  {int}  filterData  the desired value for the number filter
     */
    NumberFilter.prototype.setFilterData = function(filterData) {
        this.node.val(Number(filterData));
    };

    /**
     * The WordFilter is used to let the user select two values: a minimum and a
     * maximum number of words.
     *
     * @param  {object}  node     the node to attach the filter to
     * @param  {object}  options  options used when creating filter
     */
    var WordFilter = function(node, options) {
        Filter.call(this, node, options);
        this._minFilter = null;
        this._maxFilter = null;
    };

    WordFilter.prototype = Object.create(Filter.prototype);
    WordFilter.prototype.constructor = WordFilter;

    /**
     * Setup the word count filter.  Min/Max values are fixed at 0, 9999 for now.
     *
     * @param  {object}  options  options for the filter
     */
    WordFilter.prototype._initFilter = function(options) {

        Filter.prototype._initFilter.call(this, options);
        this._minfilter = new NumberFilter(this.node.find('input[name *= "minwordcount"]'));
        this._maxfilter = new NumberFilter(this.node.find('input[name *= "maxwordcount"]'));
    };

    /**
     * Retrieve and return the word limits desired by the user.
     *
     * @return {object}  [minwords] -> minimum, [maxwords] -> maximum
     */
    WordFilter.prototype.getFilterData = function() {
        return {minwords: this._minfilter.getFilterData(), maxwords: this._maxfilter.getFilterData()};
    };

    /**
     * Sets the filter data for both the min and max filters.
     *
     * @param  {object}  filterData  [minwords] -> minimum, [maxwords] -> maximum
     */
    WordFilter.prototype.setFilterData = function(filterData) {
        this._minfilter.setFilterData(filterData.minwords);
        this._maxFilter.setFilterData(filterData.maxwords);
    };

    /**
     * The CriteriaFilter is based on the GradeFilter.
     *
     * Users are able to select multiple criteria and remove any existing criteria
     * that they have previously added.
     *
     * @param  {object}  node     the node to attach the filter to
     * @param  {object}  options  options used when creating filter
     */
    var CriteriaFilter = function(node, options) {
        GradeFilter.call(this, node, options);
    };

    CriteriaFilter.prototype = Object.create(GradeFilter.prototype);
    CriteriaFilter.prototype.constructor = CriteriaFilter;

    /**
     * Setup a criteria filter on the page.  A criteria filter consists of a grade
     * filter and also an action/activity (combined) filter.
     *
     * @param  {object}  options  options for the filter
     */
    CriteriaFilter.prototype._initFilter = function(options) {
        GradeFilter.prototype._initFilter.call(this, options);
        this.node.closest('.filterheader').on('click', '.addcriterion', this, this._addCriterion);
        this.node.closest('.filterheader').on('click', '.removecriterion', this._removeCriterion);
    };

    /**
     * Sets the primary filter to use either the action/event or grades.
     */
    CriteriaFilter.prototype._setFilter = function() {

        GradeFilter.prototype._setFilter.call(this);
        $(this).parent().find('.addcriterion i').show();
    };

    /**
     * Adds a criterion to the list.
     *
     * @param  {object}  event  the event that triggered the call
     */
    CriteriaFilter.prototype._addCriterion = function(event) {

        var that = event.data;
        var $this = $(this);
        $this.closest('.chartheader').find('.filterstatustext').text('').removeClass('alert alert-danger');
        var $filterheader = $this.closest('.filterheader');
        var selected = $filterheader.find(".filterbutton[disabled='disabled']").val();
        if (selected === undefined) {
            $this.closest('.chartheader').find('.filterstatustext').addClass('alert alert-danger')
                .text(M.util.get_string('nocriterion', 'report_analytics'));
            return;
        }
        var containernode = $filterheader.find('.' + selected + 'filtercontainer');
        var operand = that._getOperand(selected, containernode);
        var select = containernode.find('select[class$="operatorfilter"] :selected');
        var operator = {id: select.val(), text: select.text()};
        var value = containernode.find('.valuefilter').val();
        if (value === undefined || value.length === 0 || !value.trim()) {
            $this.closest('.chartheader').find('.filterstatustext').addClass('alert alert-danger')
                .text(M.util.get_string('nocriterionvalue', 'report_analytics'));
            return;
        }

        var container = $filterheader.find('.criteria ul');
        that._addCriterionLI(container, selected, operand, operator, value);
    };

    /**
     * Adds a criterion li to the container.
     *
     * @param  {object}  container  the node with the criterion UL
     * @param  {string}  type       the type of criterion being added
     * @param  {object}  operand    the operand for the criterion
     * @param  {object}  operator   the operator for the criteron
     * @param  {string}  value      the value associated with the criterion
     */
    CriteriaFilter.prototype._addCriterionLI = function(container, type, operand, operator, value) {

        container.append(
            $('<li class="custom-select">').text(operand.text + ' ' + operator.text + ' ' + value)
            .append($('<img>').attr('src', M.util.image_url('t/delete'))
                .addClass('removecriterion')
                .attr('alt', M.util.get_string('removecriterionalt', 'report_analytics'))
                .attr('title', M.util.get_string('removecriterionalt', 'report_analytics'))
            )
            .data('criterion', {type: type, operand: operand.id, operator: operator.id, value: value}));
    };

    /**
     * Retrieves the operand from the selected filter.
     * For grade criterion -> the grade item
     * For action criterion -> the activity and action
     *
     * @param  {string}  selected       the selected criterion type
     * @param  {Object}  containernode  the node holding the operand
     * @return {Object}  the operand id and text
     */
    CriteriaFilter.prototype._getOperand = function(selected, containernode) {

        if (selected === 'grade') {
            var select = containernode.find('select:first :selected');
            return {id: select.val(), text: select.text()};
        } else if (selected === 'action') {
            var activity = containernode.find('select.activityfilter :selected');
            var action = containernode.find('select.actionfilter :selected');
            var id = {cmid: activity.val(), actionid: action.val()};
            return {id: id, text: activity.text() + ', ' + action.text()};
        }
        throw new Error(M.util.get_string('nocriterion', 'report_analytics'));
    };

    /**
     * Removes a criterion from the list.
     */
    CriteriaFilter.prototype._removeCriterion = function() {
        $(this).parent().remove();
    };

    /**
     * Retrieve and return criteria selected by the user.
     *
     * @return {Array}  the criteria {type, operand, operator, value}
     */
    CriteriaFilter.prototype.getFilterData = function() {

        var criteria = [];
        this.node.closest('.filterheader').find('.criteria li').each(function() {
            criteria.push($(this).data('criterion'));
        });
        if (criteria.length === 0) {
            throw new Error(M.util.get_string('nocriterion', 'report_analytics'));
        }
        return criteria;
    };

    /**
     * Set the criteria to the list given to the method.  The raw data does not
     * store the text associated with the selection, so it must be retrieved
     * manually for each item.
     *
     * @param  {Array}  filterData  a list of critera, each with {type, operand, operator, value}
     */
    CriteriaFilter.prototype.setFilterData = function(filterData) {

        var header = this.node.closest('.filterheader');
        var container = header.find('.criteria ul');
        container.empty();
        var operand, operator, operatorText;
        for (var i = 0; i < filterData.length; i++) {
            if (filterData[i].type === 'grade') {
                var gradeText = header.find("select.gradefilter option[value='" + filterData[i].operand + "']").text();
                operand = {id: filterData[i].operand, text: gradeText};
                operatorText = header.find("select.gradeoperatorfilter option[value='" + filterData[i].operator + "']").text();
                operator = {id: filterData[i].operator, text: operatorText};
            } else if (filterData[i].type === 'action') {
                var activityText = header.find("select.activityfilter option[value='" + filterData[i].operand.cmid + "']").text();
                var actionText = header.find("select.actionfilter option[value='" + filterData[i].operand.actionid + "']").text();
                operand = {id: filterData[i].operand, text: activityText + ', ' + actionText};
                operatorText = header.find("select.actionoperatorfilter option[value='" + filterData[i].operator + "']").text();
                operator = {id: filterData[i].operator, text: operatorText};
            } else {
                operand = filterData[i].operand;
                operator = filterData[i].operator;
            }
            this._addCriterionLI(container, filterData[i].type, operand, operator, filterData[i].value);
        }
    };

    return {SelectFilter: SelectFilter, CheckBoxFilter: CheckBoxFilter, StudentFilter: StudentFilter, DateFilter: DateFilter,
        GradeFilter: GradeFilter, SliderFilter: SliderFilter, NumberFilter: NumberFilter, WordFilter: WordFilter,
        CriteriaFilter: CriteriaFilter};
});
