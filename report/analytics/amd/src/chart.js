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
 * This file contains the various "charts" that are used in the project.  They are
 * added to a namespace "Charts".
 *
 * @module     report_analytics/Chart
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['local_d3js/d3', 'jquery', 'local_d3js/d3.layout.cloud', 'report_analytics/filter', 'report_analytics/graph',
'report_analytics/jquery.usertable'], function(d3, $, cloud, filters, analytics) {

    // Constants.
    var MAXACTIONS = 3;
    var GRAPHHEIGHT = 500;

    /**
     * Updates the filters for the activity selector and clicks the apply button to trigger
     * an update to the graph.
     *
     * @param  {Array}  modids    list of modids to update the filter to
     * @param  {object} outerdiv  the div element containing the graph
     */
    function updateFiltersGraph(modids, outerdiv) {

        var activityFilter = $(outerdiv).find('select.activityfilter');
        $(activityFilter).multipleSelect('uncheckAll');
        $(activityFilter).multipleSelect('setSelects', modids);
        $(outerdiv).find('.d3button').click();
    }

    /**
     * Retrieve a set of mods for a class or section via ajax and then draw the updated
     * graph.
     *
     * @param  {string}  type      type of request {section|activity class}
     * @param  {object}  data      the section or activity class to retrieve
     * @param  {object}  outerdiv  the div element containing the graph
     * @param  {string}  time      time of button press: used to mark request complete
     */
    function getModsAjax(type, data, outerdiv, time) {

        $(outerdiv).find('.d3button').attr('disabled', 'disabled');
        var statusText = $(outerdiv).find('.filterstatustext');
        $(statusText).text('');

        $.post(M.cfg.wwwroot + "/report/analytics/ajax_request.php", {
            courseid: window.retrieveCourseID(),
            sesskey: M.cfg.sesskey,
            request: 'get_mods',
            type: type,
            data: data
        }, null, 'json')
        .done(function(data) {
            if (data.result === true) {
                $(outerdiv).find('.d3button').removeAttr('disabled');
                updateFiltersGraph(data.message, outerdiv);
            } else {
                $(statusText).addClass('alert alert-danger').text(data.message);
            }
        })
        .fail(function() {
            $(statusText).addClass('alert alert-danger').text(M.util.get_string('badrequest', 'report_analytics'));
        })
        .always(function() {
            $(outerdiv).find('.d3button').removeAttr('disabled');
            M.util.js_complete('mods' + time);
        });
    }

    /**
     * The base chart object with some common methods.
     *
     * @param  {object} outerdiv       the div element containing the graph
     * @param  {object} filteroptions  options used when creating filters
     */
    var Chart = function(outerdiv, filteroptions) {

        this.outerdiv = $(outerdiv);
        this.displayNode = this.outerdiv.find('div[class*=d3chart]');
        this.graph = null;
        this.data = null;
        this.title = null;
        this.filteroptions = filteroptions;
        this._setupFilters();
        // Chart data for undo/redo.
        this.currentData = null;
        this.undoData = [];
        this.redoData = [];
    };

    Chart.prototype = {

        /**
         * Returns the outermost div of the chart.
         *
         * @return {object}  the outermost div for the chart
         */
        getOuterdiv: function() {
            return this.outerdiv;
        },

        /**
         * Returns the ID of the node holding the graph.
         *
         * @return {string}  the ID of the graph node
         */
        getGraphID: function() {
            return this.displayNode.attr('id');
        },

        /**
         * Returns the type of graph being used.
         *
         * @return {string}  the graph type
         */
        getGraphType: function() {
            return this.outerdiv.find('input[name=graphtype]').val();
        },

        /**
         * Setup the filters used in the chart.  All charts have three basic filter types:
         * -activity filter
         * -student/group filter
         * -date/time filters
         */
        _setupFilters: function() {

            this.filters = {};
            var selectoptions = {filter: true};
            this.filters.students = new filters.StudentFilter(this.outerdiv.find('select.studentfilter'), selectoptions);
            this.filters.activities = new filters.SelectFilter(this.outerdiv.find('select.activityfilter'), selectoptions);
            this.filters.date = new filters.DateFilter(this.outerdiv.find('div.fdate_time_selector').closest('form'),
                {date: this.filteroptions.date});
        },

        /**
         * Retrieve and validate the filter values selected by the user.
         *
         * @return {object}  [activities] -> cmids, [stduents] -> user ID(s), [date] -> to/from
         */
        getFilters: function() {
            var filterdata = {};

            for (var key in this.filters) {
                filterdata[key] = this.filters[key].getFilterData();
            }
            return filterdata;
        },

        /**
         * Sets the filters on the chart to the desired values.
         *
         * @param  {object}  filterData  the data to set each filter to (keyed based on type)
         */
        setFilters: function(filterData) {
            for (var key in this.filters) {
                if (filterData[key] !== undefined) {
                    this.filters[key].setFilterData(filterData[key]);
                }
            }
        },

        /**
         * Update method.  This does three things:
         * 1) saves the old (current) data
         * 2) updates the chart
         * 3) updates the current data to reflect the new data
         *
         * @param  {string}  data   the dataset for the chart
         * @param  {string}  title  an optional title for the chart
         */
        update: function(data, title) {
            this._saveOldData();
            this._updateChart(data, title);
            this._updateCurrentData(data, title);
        },

        /**
         * Save the old data (if it exists) to the undo stack.
         */
        _saveOldData: function() {
            if (this.currentData !== undefined && this.currentData !== null) {
                this.undoData.push(this.currentData);
            }
        },

        /**
         * Updates the current data set to reflect that a new chart has been
         * successfully drawn.  Since a new data set was drawn, reset the
         * redo stack.
         *
         * @param  {string}  data   the dataset for the chart
         * @param  {string}  title  an optional title for the chart
         */
        _updateCurrentData: function(data, title) {
            this.currentData = {filterData: this.getFilters(), data: data, title: title};
            this.redoData = [];
        },

        /**
         * Update the current chart.  Base method ensures that the chart has the
         * correct data and the title is set (although a title is optional).
         *
         * @param  {string}  data   the dataset for the chart
         * @param  {string}  title  an optional title for the chart
         */
        _updateChart: function(data, title) {

            // If no new data was passed and no old is present, return.
            if (data === null || data === undefined) {
                if (this.data === null || this.data === undefined) {
                    throw new Error(M.util.get_string('nodata', 'report_analytics'));
                }
            } else {
                this.data = this._formatData(data);
            }
            // New title given, use it instead.
            if (title !== undefined) {
                this.title = title;
            }
        },

        /**
         * Placeholder method for any data formatting that is required.  Base
         * version simply returns the existing data set.
         *
         * @param  {Array}  oldData  the data set from PHP
         * @return {Array}  the formatted data set suitable for javascript
         */
        _formatData: function(oldData) {
            return oldData;
        },

        /**
         * Returns the current min-width of the graph.
         *
         * @return {int}  the min-width of the graph
         */
        _getGraphWidth: function() {
            return parseInt(this.outerdiv.css('min-width'));
        },

        /**
         * Click handler for graph interactivity.  When a particular element is clicked
         * plot a second graph to show that activity class or section in more detail.
         * Individual activities are ignored, since no further breakdown is possible.
         *
         * No further results are displayed for all core or all blocks.
         *
         * @param  {object} d the d3.js data attached to the group of bars that was clicked
         */
        onClick: function(d) {

            if (d.type === 'activity_class' || d.type === 'section') {
                if (d.label === M.util.get_string('allcore', 'report_analytics')) {
                    return;
                }
                var time = Date.now();
                M.util.js_pending('mods' + time);
                var outerdiv = $(this).parents('.chartheader');
                getModsAjax(d.type, d.label, outerdiv, time);
            }
        },

        /**
         * Add the html for the toolbar to the placeholder.
         *
         * @param  {string}  html  the html for the toolbar
         */
        displayToolbar: function(html) {
            var div = this.outerdiv.find('.d3export');
            div.empty();
            div.html(html);
        },

        /**
         * Undo an "apply" operation and return to the previous set of data.
         */
        undo: function() {
            this._swapData(this.undoData, this.redoData);
        },

        /**
         * Redo an undo operation and return to a previous set of data (the one
         * before the undo).
         */
        redo: function() {
            this._swapData(this.redoData, this.undoData);
        },

        /**
         * Swap a set of data by pushing the current set onto the "to" array and
         * popping a set of data from the "from" array.
         *
         * @param  {Array}  fromData  the array to pop from
         * @param  {Array}  toData    the array to push to
         */
        _swapData: function(fromData, toData) {
            if (fromData.length > 0) {
                toData.push(this.currentData);
                this.currentData = fromData.pop();
                this._updateChart(this.currentData.data, this.currentData.title);
                this.setFilters(this.currentData.filterData);
            }
        }

    };

    /**
     * The ActivityChart dervies from the basic chart object and requires a
     * customized method to grab the filter data and render the graph.
     *
     * @param  {object} outerdiv       the div element containing the graph
     * @param  {object} filteroptions  options used when creating filters
     */
    var ActivityChart = function(outerdiv, filteroptions) {
        Chart.call(this, outerdiv, filteroptions);
    };

    ActivityChart.prototype = Object.create(Chart.prototype);
    ActivityChart.prototype.constructor = ActivityChart;

    /**
     * Setup filters used in the chart.  The ActivityChart also contains:
     * {action filter, average checkbox, unique checkbox}
     */
    ActivityChart.prototype._setupFilters = function() {

        Chart.prototype._setupFilters.call(this);
        this.filters.grade = new filters.GradeFilter(this.outerdiv.find('.gradefiltercontainer'));
        this.filters.action = new filters.SelectFilter(this.outerdiv.find('select.actionfilter'), {selectAll: false},
            {number: MAXACTIONS, message: M.util.get_string('toomanyevents', 'report_analytics')});
        this.filters.average = new filters.SelectFilter(this.outerdiv.find('select.averagefilter'));
        this.filters.unique = new filters.CheckBoxFilter(this.outerdiv.find("input[name*='uniquecheck']"));
    };

    /**
     * Creates the event data vs. activity type graph on the page.
     *
     * @param  {object} data   php array containing number of events for each activity class
     * @param  {string} title  the title of the graph
     */
    ActivityChart.prototype._updateChart = function(data, title) {

        Chart.prototype._updateChart.call(this, data, title);
        var id = this.displayNode.attr('id');
        if (this.graph !== undefined && this.graph !== null) {
            this.graph.removeTooltip();
        }

        this.graph = analytics.GroupedBarGraph()
            .options({width: this._getGraphWidth(), height: GRAPHHEIGHT, title: this.title})
            .helpTooltips([M.util.get_string('reads', 'report_analytics'), M.util.get_string('writes', 'report_analytics')])
            .on('barclick', this.onClick);
        d3.select('#' + id).datum(this.data).call(this.graph);
    };

    /**
     * Format the data so that it can be graphed using grouped bars.
     *
     * @param  {Array}  oldData the data received directly from PHP
     * @return {Array}  the data to be used to create the graph
     */
    ActivityChart.prototype._formatData = function(oldData) {

        // eslint-disable-next-line max-statements-per-line
        oldData.forEach(function(d) { d.values = $.map(d.values, function(value) { return [value]; }); });
        return oldData;
    };

    /**
     * The ForumChart uses the same methods as the activity graph.  The filters
     * only render forums, so no further work is necessary in javascript.
     *
     * @param  {object} outerdiv       the div element containing the graph
     * @param  {object} filteroptions  options used when creating filters
     */
    var ForumChart = function(outerdiv, filteroptions) {
        ActivityChart.call(this, outerdiv, filteroptions);
    };

    ForumChart.prototype = Object.create(ActivityChart.prototype);
    ForumChart.prototype.constructor = ForumChart;

    /**
     * The ActivityTimelineChart dervies from the basic chart object and requires a
     * customized method to grab the filter data and render the graph.
     *
     * @param  {object} outerdiv       the div element containing the graph
     * @param  {object} filteroptions  options used when creating filters
     */
    var ActivityTimelineChart = function(outerdiv, filteroptions) {
        Chart.call(this, outerdiv, filteroptions);
        this.types = null;
    };

    ActivityTimelineChart.prototype = Object.create(Chart.prototype);
    ActivityTimelineChart.prototype.constructor = ActivityTimelineChart;

    /**
     * Setup filters used in the chart.  The ActivityTimelineChart also contains:
     * {action filter, unique checkbox, slider for time bins}
     */
    ActivityTimelineChart.prototype._setupFilters = function() {

        Chart.prototype._setupFilters.call(this);
        this.filters.grade = new filters.GradeFilter(this.outerdiv.find('.gradefiltercontainer'));
        this.filters.action = new filters.SelectFilter(this.outerdiv.find('select.actionfilter'), {selectAll: false});
        this.filters.unique = new filters.CheckBoxFilter(this.outerdiv.find("input[name*='uniquecheck']"));
        this.filters.bins = new filters.SliderFilter(this.outerdiv, {bins: this.filteroptions.bins});
    };

    /**
     * Simple javascript function that creates a line graph (as a series) and plots some data.
     *
     * @param  {array}  data   php array containing timeline data
     * @param  {string} title  the title of the graph
     */
    ActivityTimelineChart.prototype._updateChart = function(data, title) {

        Chart.prototype._updateChart.call(this, data, title);

        var id = this.displayNode.attr('id');
        if (this.graph !== undefined && this.graph !== null) {
            this.graph.removeTooltip();
        }

        this.graph = analytics.SeriesGraph()
            .options({width: this._getGraphWidth(), height: GRAPHHEIGHT, title: this.title})
            .on('legendclick', this.onClick);
        d3.select('#' + id).datum(this.data).call(this.graph);
    };

    /**
     * Format the data so that it can be graphed using a series of lines:
     * The dates need to be formatted into a d3js compatible format and also needs
     * to be grouped by label.
     *
     * @param  {Array}  oldData the flat data received directly from PHP
     * @return {Array}  the data to be used to create the graph
     */
    ActivityTimelineChart.prototype._formatData = function(oldData) {

        var parseDate = d3.time.format("%Y-%m-%d %H:%M").parse;
        var data = oldData.map(function(d) {
            return {
                label: d.label,
                type: d.type,
                date: parseDate(d.date),
                count: Number(d.count)
            };
        });
        data = d3.nest().key(function(d) { return d.label; }).entries(data);
        return data;
    };

    /**
     * The ForumTimeGraph_Chart uses the same methods as the user graph.  The filters
     * only render forums, so no further work is necessary in javascript.
     *
     * @param  {object} outerdiv       the div element containing the graph
     * @param  {object} filteroptions  options used when creating filters
     */
    var ForumTimelineChart = function(outerdiv, filteroptions) {
        ActivityTimelineChart.call(this, outerdiv, filteroptions);
    };

    ForumTimelineChart.prototype = Object.create(ActivityTimelineChart.prototype);
    ForumTimelineChart.prototype.constructor = ForumTimelineChart;

    // Constants - for word cloud (in pixels).
    var CLOUDWIDTH = 640;
    var CLOUDHEIGHT = 400;
    var MAXFONT = 40;

    /**
     * The UserPostsChart dervies from the basic chart object and requires a
     * customized method to grab the filter data and render the posts data.
     *
     * @param  {object} outerdiv       the div element containing the graph
     * @param  {object} filteroptions  options used when creating filters
     */
    var UserPostsChart = function(outerdiv, filteroptions) {
        Chart.call(this, outerdiv, filteroptions);
    };

    UserPostsChart.prototype = Object.create(Chart.prototype);
    UserPostsChart.prototype.constructor = UserPostsChart;

    /**
     * Setup filters used in the chart.  The UserPostsChart also contains:
     * {word filter}
     */
    UserPostsChart.prototype._setupFilters = function() {

        Chart.prototype._setupFilters.call(this);
        this.filters.grade = new filters.GradeFilter(this.outerdiv.find('.gradefiltercontainer'));
        this.filters.words = new filters.WordFilter(this.outerdiv);
    };

    /**
     * Display the users posts on the screen.  Unlike the graphs, the html is created
     * in php and then sent back to JS.  Wipe out any old data and then append the new
     * html.
     *
     * @param  {string}  data  the html to display on the page
     * @param  {string}  title the title of the graph
     */
    UserPostsChart.prototype._updateChart = function(data, title) {

        Chart.prototype._updateChart.call(this, data, title);

        this.displayNode.empty();
        this.displayNode.append($("<h3>").text(this.title));
        this.displayNode.append(this.data);
        this.displayNode.width(this._getGraphWidth());
    };

    /**
     * Create a word cloud for the user.
     */
    UserPostsChart.prototype.addWordCloud = function() {

        var wordclouddiv = this.outerdiv.find('.wordcloudcontainer');
        if (wordclouddiv.has('svg').length === 0) {
            var time = Date.now();
            M.util.js_pending('get' + time);

            this.outerdiv.find('.filterstatustext').text('').removeClass('alert alert-danger');
            try {
                this._retrieveWords(time);
            } catch (e) {
                this.outerdiv.find('.filterstatustext').addClass('alert alert-danger').text(e.message);
                M.util.js_complete('get' + time);
            }
        } else {
            this._showWordCloud(wordclouddiv, {width: CLOUDWIDTH, height: CLOUDHEIGHT});
        }
    };

    /**
     * This function retrieves the word data via an ajax call and then, if
     * successful, sends a request to draw the word cloud.
     *
     * @param  {string}   time  time of button press: used to mark request complete
     */
    UserPostsChart.prototype._retrieveWords = function(time) {

        var filters = this.getFilters();
        var graphType = this.getGraphType();
        var that = this;

        // This is a moodle 2.4+ fix to unbind the unload handler.
        window.onbeforeunload = null;

        // Wipe any older errors and placeholder styles.
        var statusText = this.outerdiv.find('.filterstatustext');
        statusText.text('').removeClass('alert alert-danger');
        // Add Moodle spinner while AJAX request is running.
        var imgcontainer = this.outerdiv.find('.d3export');
        var spinner = M.util.add_spinner(Y, Y.one(imgcontainer[0])).show();

        $.post(M.cfg.wwwroot + "/report/analytics/ajax_request.php", {
            courseid: window.retrieveCourseID(),
            sesskey: M.cfg.sesskey,
            request: 'word_cloud',
            graphtype: graphType,
            filters: JSON.stringify(filters)
        }, null, 'json')
        .done(function(data) {
            if (data.result === true) {
                that._createWordCloud(data.message);
            } else {
                statusText.addClass('alert alert-danger').text(data.message);
            }
        })
        .fail(function() {
            statusText.addClass('alert alert-danger').text(M.util.get_string('badrequest', 'report_analytics'));
        })
        .always(function() {
            spinner.hide();
            M.util.js_complete('get' + time);
        });
    };

    /**
     * Create the word cloud layout, set to draw, and make visible.
     *
     * @param  {object}  wordsajax  the list of words from php {keys - text labels, values - count}
     */
    UserPostsChart.prototype._createWordCloud = function(wordsajax) {

        var that = this;
        var wordlist = this._formatWords(wordsajax);
        var maxcount = d3.max(wordlist, function(d) { return d.value; });
        var wordclouddiv = this.outerdiv.find('.wordcloudcontainer');
        var layout = cloud()
            .size([CLOUDWIDTH, CLOUDHEIGHT])
            .rotate(0)
            .words(wordlist)
            .padding(5)
            .fontSize(function(d) { return Math.sqrt(d.value / maxcount) * MAXFONT; })
            .on("end", function(words) { that._drawWordCloud(wordclouddiv[0], {width: CLOUDWIDTH, height: CLOUDHEIGHT}, words); });

        layout.start();
        wordclouddiv.on('click', '.wordcloudclosebutton', this._hideWordCloud);
        this._showWordCloud(wordclouddiv, {width: CLOUDWIDTH, height: CLOUDHEIGHT});
    };

    /**
     * Change the data passed via ajax into an array of objects, formatted as
     * the d3cloud expects.
     *
     * @param  {object}  words  the list of words from php {keys - text labels, values - count}
     * @return {Array}  an array of objects, each with a "text" (label) and "value" (count) entry
     */
    UserPostsChart.prototype._formatWords = function(words) {

        var temp = [];
        for (var key in words) {
            temp.push({text: key, value: words[key]});
        }
        return temp;
    };

    /**
     * Draw function for the word cloud.  This code is mostly taken from the jasondavies project
     * page: https://github.com/jasondavies/d3-cloud
     *
     * @param  {object}  node   the node that holds the worldcloud
     * @param  {object}  size   the size {width, height} of the word cloud
     * @param  {object}  words  the words for the word cloud
     */
    UserPostsChart.prototype._drawWordCloud = function(node, size, words) {

        var fill = d3.scale.category20();
        d3.select(node).append("svg")
                .attr("width", size.width)
                .attr("height", size.height)
                .attr("name", 'cloud')
            .append("g")
                .attr("transform", "translate(" + size.width / 2 + "," + size.height / 2 + ")")
            .selectAll("text")
                .data(words)
            .enter().append("text")
                .style("font-size", function(d) { return d.size + "px"; })
                .style("font-family", "Impact")
                .style("fill", function(d, i) { return fill(i); })
                .attr("text-anchor", "middle")
                .attr("transform", function(d) {
                    return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
                })
                .text(function(d) { return d.text; });
    };

    /**
     * Shows the word cloud.  The rest of the page is faded slightly to give
     * the appearance of a modal dialogue box.
     *
     * @param  {object}  node  the node that holds the worldcloud
     * @param  {int}     size  the size of the word cloud {width, height}
     */
    UserPostsChart.prototype._showWordCloud = function(node, size) {

        node.show();
        var width = size.width.toString() + 'px';
        var marginleft = (-size.width / 2).toString() + 'px';
        var margintop = (-size.height / 2).toString() + 'px';
        var styles = {'left': '50%', 'top': '50%', 'margin-left': marginleft, 'margin-top': margintop, 'width': width,
            'z-index': '4033', 'position': 'fixed'};
        node.css(styles);
        $('.modal-background').css('display', 'block');
    };

    /**
     * Hides the word cloud.
     */
    UserPostsChart.prototype._hideWordCloud = function() {
        $('.modal-background').css('display', 'none');
        $(this).parent().hide();
    };

    /**
     * The CompletionSearchChart dervies from the basic chart object but contains
     * a criteria filter and displays its results as a list of users.
     *
     * @param  {object} outerdiv       the div element containing the graph
     * @param  {object} filteroptions  options used when creating filters
     */
    var CompletionSearchChart = function(outerdiv, filteroptions) {
        Chart.call(this, outerdiv, filteroptions);
    };

    CompletionSearchChart.prototype = Object.create(Chart.prototype);
    CompletionSearchChart.prototype.constructor = CompletionSearchChart;

    /**
     * Setup filters used in the chart.  The CompletionSearchChart also contains:
     * {criteria filter}
     */
    CompletionSearchChart.prototype._setupFilters = function() {

        Chart.prototype._setupFilters.call(this);
        this.filters.criteria = new filters.CriteriaFilter(this.outerdiv.find('.gradefiltercontainer'));
        this.filters.perpage = new filters.NumberFilter(this.outerdiv.find('.usersperpage'));
    };

    /**
     * Display the list of users matching the criteria that were specified by
     * the user.
     *
     * @param  {object}  data  user list to display in the table
     * @param  {string}  title the title of the graph
     */
    CompletionSearchChart.prototype._updateChart = function(data, title) {

        Chart.prototype._updateChart.call(this, data, title);
        this.displayNode.UserTable('destroy');
        this.displayNode.empty();
        this.displayNode.UserTable({users: this.data, title: title, perpage: this.filters.perpage.getFilterData()});
        this.displayNode.width(this.graphwidth);
    };

    /**
     * The ScheduledCriteriaChart is a copy of the completion search chart.
     * Some changes are made on the php side, but the JS side is identical.
     *
     * @param  {object} outerdiv       the div element containing the graph
     * @param  {object} filteroptions  options used when creating filters
     *
     * @module     report_analytics/ScheduledCriteriaChart
     * @copyright  2017 Craig Jamieson
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    var ScheduledCriteriaChart = function(outerdiv, filteroptions) {
        CompletionSearchChart.call(this, outerdiv, filteroptions);
    };

    ScheduledCriteriaChart.prototype = Object.create(CompletionSearchChart.prototype);
    ScheduledCriteriaChart.prototype.constructor = ScheduledCriteriaChart;

    /**
     * The GradeChart is a scatter plot with the area divided into four
     * quadrants.  We plot each student based on where they fall in the
     * grades vs. actions distribution.
     *
     * @param  {object} outerdiv       the div element containing the graph
     * @param  {object} filteroptions  options used when creating filters
     *
     * @module     report_analytics/GradeChart
     * @copyright  2017 Craig Jamieson
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    var GradeChart = function(outerdiv, filteroptions) {
        Chart.call(this, outerdiv, filteroptions);
    };

    GradeChart.prototype = Object.create(Chart.prototype);
    GradeChart.prototype.constructor = GradeChart;

    /**
     * Setup filters used in the chart.  The ActivityChart also contains:
     * {action filter, average checkbox, unique checkbox}
     */
    GradeChart.prototype._setupFilters = function() {

        this.filters = {};
        this.filters.students = new filters.StudentFilter(this.outerdiv.find('select.studentfilter'), {filter: true});
    };

    /**
     * Display the users on the scatter plot.
     *
     * @param  {object}  data  user list to display in the table
     * @param  {string}  title the title of the graph
     */
    GradeChart.prototype._updateChart = function(data, title) {

        Chart.prototype._updateChart.call(this, data[0], title);
        var id = this.displayNode.attr('id');
        if (this.graph !== undefined && this.graph !== null) {
            this.graph.removeTooltip();
        }

        var length = Math.min(GRAPHHEIGHT, this._getGraphWidth());
        this.graph = analytics.QuadrantGraph()
            .options({width: length, height: length, title: this.title, xTitle: M.util.get_string('engagement', 'report_analytics'),
                yTitle: M.util.get_string('grades', 'report_analytics')})
            .xDomain(this._toRangeArray(data[1]))
            .yDomain(this._toRangeArray(data[2]));
        d3.select('#' + id).datum(this.data).call(this.graph);
    };

    /**
     * Converts an object representation of a range {min: XX, max: XX} into an array
     * representation [min, max].
     *
     * @param  {object}  rangeObject  the range specified as an object
     * @return {Array}  the range specified as an array
     */
    GradeChart.prototype._toRangeArray = function(rangeObject) {

        var rangeArray = [];
        rangeArray.push(rangeObject.min);
        rangeArray.push(rangeObject.max);
        return rangeArray;
    };

    /**
     * Format the data so that it can be graphed using the more generic names.
     * The "actions" key will be the data on the x-axis, while the "grade" key
     * will be the data on the y-axis.  The other keys (name, size) are unchanged.
     *
     * @param  {Array}  oldData the data received directly from PHP
     * @return {Array}  the data to be used to create the graph
     */
    GradeChart.prototype._formatData = function(oldData) {

        var data = oldData.map(function(d) {
            return {
                name: d.name,
                x: d.actions,
                y: d.grade
            };
        });
        return data;
    };

    return {ActivityChart: ActivityChart, ActivityTimelineChart: ActivityTimelineChart, ForumChart: ForumChart,
        ForumTimelineChart: ForumTimelineChart, UserPostsChart: UserPostsChart, CompletionSearchChart: CompletionSearchChart,
        ScheduledCriteriaChart: ScheduledCriteriaChart, GradeChart: GradeChart};
});
