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
 * The ChartContainer jquery plugin contains the logic to add and control
 * the display of charts to the main container.
 *
 * The eclass theme allow for a max width of 1680 px.  A left block only
 * allows for a width of roughly 1396 px (97.4359% of 1680 px - 240 px).
 * Allowing for a 2.54641% margin between graphs means that 640px is a
 * good choice.
 *
 * @module     report_analytics/ChartContainer
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_d3js/d3', 'report_analytics/chart', 'report_analytics/graph', 'jquery', 'report_analytics/jquery.usertable'],
function(d3, charts, analytics, $) {

    // Constants.
    var RESIZETIMEOUT = 500;
    var GRAPHHEIGHT = 500;

    var pluginName = 'ChartContainer',
    defaults = {
        gridSize: 1,
        lastDate: null,
        lastBins: null
    };

    /**
     * Constructor for the plugin.
     *
     * @param  {object}  element  the document element that the plugin is attached to
     * @param  {object}  options  let the user set any location variables (otherwise defaults are used)
     */
    function Plugin(element, options) {

        this.element = element;
        this.$element = $(element);
        this.options = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;

        this._events();
    }

    $.extend(Plugin.prototype, {

        /**
         * All event handlers are bound inside of this function.
         */
        _events: function() {
            this.$element.parent().find('.layoutmenu div').on('click', this, this._selectLayout);
            this.$element.on('click', '.d3button', this, this._applyFilter);
            this.$element.on('click', '.pngexport', this, this._saveAsPng);
            this.$element.on('click', '.excelexport', this, this._exportToExcel);
            this.$element.on('click', '.wordcloud', this, this._addWordCloud);
            this.$element.on('click', '.undo', this, this._undoData);
            this.$element.on('click', '.redo', this, this._redoData);
            this.$element.on('click', '.copyusers', this, this._copyUsers);
            this.$element.on('click', '.advancedtoggle', this, this._toggleAdvanced);
            this.$element.on('click', '.closebutton', this, this._deleteChart);
            var that = this;
            var resizeid;
            $(window).on('resize', function() {
                clearTimeout(resizeid);
                resizeid = setTimeout(function() { that._drawCharts(that.$element); }, RESIZETIMEOUT);
            });
        },

        /**
         * Handle clicks on "Apply filter" button.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _applyFilter: function(event) {
            var time = Date.now();
            M.util.js_pending('get' + time);
            var that = event.data;

            $(this).attr('disabled', 'disabled');
            var outerdiv = $(this).parent();
            outerdiv.find('.filterstatustext').text('').removeClass('alert alert-danger');
            var chart = outerdiv.data('chart');
            try {
                that._getDataAjax(chart, time);
            } catch (e) {
                outerdiv.find('.filterstatustext').addClass('alert alert-danger').text(e.message);
                $(this).removeAttr('disabled');
                M.util.js_complete('get' + time);
            }
        },

        /**
         * Handle clicks on close button (X).
         *
         * @param  {object}  event  the event that triggered the call
         */
        _deleteChart: function(event) {
            var that = event.data;

            $(this).parent().parent().remove();
            that._updateLayout();
        },

        /**
         * Add a chart to the chart container.
         *
         * @param  {string}  html       the html for chart (including filters + graph)
         * @param  {string}  graphType  the type of graph to draw
         * @param  {object}  filters    pre-defined filters for chart (optional)
         */
        addChart: function(html, graphType, filters) {

            this.$element.append(html);
            var graphid = $(html).find('.d3chart').attr('id');
            // Draw default graph.
            var graphNode = $('#' + graphid);
            var outerdiv = graphNode.parent();
            var chart = new charts[graphType](outerdiv, {bins: this.options.lastBins, date: this.options.lastDate});
            outerdiv.data('chart', chart);
            if (filters !== undefined && filters !== null) {
                chart.setFilters(filters);
            }

            // Update responsive styling.
            this._setColumns();
        },

        /**
         * This function retrieves data from the database (via php) using an ajax call.
         * The data is then plotted by calling the passed function.  Prior to loading
         * the data, a placeholder spinner is drawn.
         *
         * @param  {object}   chart    the chart to retrieve data for
         * @param  {string}   time     time of button press: used to mark request complete
         */
        _getDataAjax: function(chart, time) {

            var filters = chart.getFilters();

            // This is a moodle 2.4+ fix to unbind the unload handler.
            window.onbeforeunload = null;

            // Wipe any older errors and placeholder styles.
            var id = chart.getGraphID();
            var graphNode = $('#' + id);
            graphNode.removeAttr('style');
            var graphType = chart.getGraphType();
            var outerdiv = chart.getOuterdiv();
            var statusText = outerdiv.find('.filterstatustext');
            statusText.text('').removeClass('alert alert-danger');
            // Save date and use as starting point for all future graphs.
            this.options.lastDate = filters.date;
            if (filters.bins !== undefined && filters.bins !== null && filters.bins !== 0) {
                this.options.lastBins = filters.bins;
            }

            var spinner = new analytics.Spinner()
                .width(parseInt(outerdiv.css('min-width')))
                .height(GRAPHHEIGHT);
            d3.select('#' + id).call(spinner);
            $.post(M.cfg.wwwroot + "/report/analytics/ajax_request.php", {
                courseid: window.retrieveCourseID(),
                sesskey: M.cfg.sesskey,
                request: 'get_chart_data',
                graphtype: graphType,
                filters: JSON.stringify(filters)
            }, null, 'json')
            .done(function(data) {
                if (data.result === true) {
                    chart.displayToolbar(data.message.toolbar);
                    chart.update(data.message.data, data.message.title);
                } else {
                    statusText.addClass('alert alert-danger').text(data.message);
                    graphNode.empty();
                }
            })
            .fail(function() {
                statusText.addClass('alert alert-danger').text(M.util.get_string('badrequest', 'report_analytics'));
                graphNode.empty();
            })
            .always(function() {
                outerdiv.find('.d3button').removeAttr('disabled');
                M.util.js_complete('get' + time);
            });
        },

        /**
         * Set the desired layout to be used when drawing charts in the container.
         * The layout is determined by which button the user pressed.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _selectLayout: function(event) {
            var that = event.data;
            that.options.gridSize = ($(this).hasClass('chart-grid')) ? 2 : 1;
            that._updateLayout();
        },

        /**
         * Updates the layout of the chart container by re-drawing the graphs according to
         * the current gridsize.
         */
        _updateLayout: function() {
            this._setColumns();
            this._drawCharts(this.$element);
        },

        /**
         * Draws (or re-draw) all charts on the page.
         *
         * Note: this function is called by setTimeout(), which re-binds 'this'
         * to be 'window'.  I've passed the 'container' paramater to avoid needing
         * to use 'this' directly.
         *
         * @param  {object}  container  the container holding the charts
         */
        _drawCharts: function(container) {

            container.children().each(function() {
                var chart = $(this).data('chart');
                if (chart !== undefined && chart.data !== null) {
                    chart.update();
                }
            });
        },

        /**
         * Sets the graphs to the appropriate number of columns by adding a bootstrap class.
         */
        _setColumns: function() {

            var graphs = $('#chartcontainer').children();
            $(graphs).removeClass('col-md-6');
            $(graphs).removeClass('col-md-12');
            if (this.options.gridSize === 1) {
                $(graphs).addClass('col-md-12');
            } else if (this.options.gridSize === 2) {
                $(graphs).addClass('col-md-6');
            }
        },

        /**
         * Save a chart as a png file.  This function draws the image on the hidden
         * canvas, then creats a fake link to download the contents of the canvas.
         *
         * There's an onload handler here to allow for enough time to draw the image
         * to the hidden canvas.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _saveAsPng: function(event) {

            var that = event.data;

            var node = d3.select(this.parentNode.parentNode).select('svg')
                .attr("version", 1.1)
                .attr("xmlns", "http://www.w3.org/2000/svg")
                .style("background-color", 'white')
                .node();
            var name = $(node).attr('name');
            var bb = node.getBoundingClientRect();
            $('canvas').attr('width', bb.width).attr('height', bb.height);
            $(node).prepend(that._getStyles(node));
            var html = that._removeImageTags(node.outerHTML);

            var canvas = document.querySelector("canvas"),
            context = canvas.getContext("2d");

            var image = document.createElement("img");
            image.src = 'data:image/svg+xml;base64,' + btoa(html);
            image.onload = function() {

                context.drawImage(image, 0, 0);
                var a = document.createElement("a");
                a.className = 'pngexport';
                a.download = name + ".png";
                a.href = canvas.toDataURL("image/png");
                document.querySelector("body").appendChild(a);
                if (!M.cfg.behatsiterunning) {
                    a.click();
                    document.querySelector("body").removeChild(a);
                }
            };
        },

        /**
         * Gets all of the stylesheet rules that are in use in the svg element.  These
         * styles can be applied to the image that is downloaded before converting it
         * to a png file.
         *
         * The loops in the function are taken from:
         * https://spin.atomicobject.com/2014/01/21/convert-svg-to-png/
         *
         * @param  {object}  svg  the svg element
         * @return {object}  the html styles used in the svg
         */
        _getStyles: function(svg) {

            var used = "";
            var sheets = document.styleSheets;
            for (var i = 0; i < sheets.length; i++) {
                var rules = sheets[i].cssRules;
                for (var j = 0; j < rules.length; j++) {
                    var rule = rules[j];
                    if (typeof (rule.style) !== "undefined") {
                        var elems = svg.querySelectorAll(rule.selectorText);
                        if (elems.length > 0) {
                            used += rule.selectorText + " { " + rule.style.cssText + " }\n";
                        }
                    }
                }
            }
            var style = $('<style type="text/css"></style>');
            style.html("<![CDATA[\n" + used + "\n]]>");
            return style;
        },

        /**
         * Remove any <image>,<img> tags from the svg that is passed.  These <image>,<img>
         * tags cannot be converted into the png version, so they are stripped out here.
         *
         * @param  {string}  svg  the svg to strip the <image> tags from
         * @return {string}  the updated svg with all <image> tags removed
         */
        _removeImageTags: function(svg) {

            var wrapped = $("<div>" + svg + "</div>");
            $(wrapped).find('image').remove();
            $(wrapped).find('img').remove();
            return wrapped.html();
        },

        /**
         * Intercept click to excel export image and trigger the form submit to
         * download the excel file.
         */
        _exportToExcel: function() {
            var form = $(this).parent().find('form');
            form.submit();
        },

        /**
         * Go back to the most recent set of data before the current set.
         */
        _undoData: function() {

            var outerdiv = $(this).closest('.chartheader');
            var chart = outerdiv.data('chart');
            chart.undo();
        },

        /**
         * Return to the next set of data (right before clicking undo).
         */
        _redoData: function() {

            var outerdiv = $(this).closest('.chartheader');
            var chart = outerdiv.data('chart');
            chart.redo();
        },

        /**
         * Create a word cloud for the user.
         */
        _addWordCloud: function() {

            var outerdiv = $(this).parent().parent();
            var chart = outerdiv.data('chart');
            chart.addWordCloud();
        },

        /**
         * Copy users in usertable to the clipboard for pasting into an email.
         */
        _copyUsers: function() {
            var chart = $(this).parent().parent().find('.d3chart');
            chart.UserTable('copyUsers');
        },

        /**
         * Show the advanced filter options (if hidden) or hide them (if not hidden).
         */
        _toggleAdvanced: function() {

            var time = Date.now();
            M.util.js_pending('toggle' + time);
            var advancedfilters = $(this).parent().find('.advancedfilters');
            if (advancedfilters.is(":hidden")) {
                advancedfilters.slideDown('slow', 'swing', function() {
                    M.util.js_complete('toggle' + time);
                });
                $(this).find('.showadvanced').hide();
                $(this).find('.hideadvanced').css('display', 'inline');
            } else {
                advancedfilters.hide();
                $(this).find('.showadvanced').css('display', 'inline');
                $(this).find('.hideadvanced').hide();
            }
        }

    });

    /* eslint-disable consistent-return */
    /**
     * Plugin wrapper around the constructor, preventing against multiple instantiations and
     * allowing any public function to be called via the jQuery plugin.
     *
     * @param  {object}  options  desired options for the chart container.
     * @return {object} all selectors that are part of the jQuery collection
     */
    $.fn[pluginName] = function(options) {
        var args = arguments;
        if (options === undefined || typeof options === 'object') {
            return this.each(function() {
                if (!$.data(this, 'plugin_' + pluginName)) {
                    $.data(this, 'plugin_' + pluginName, new Plugin(this, options));
                }
            });
        } else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
            return this.each(function() {
                var instance = $.data(this, 'plugin_' + pluginName);

                if (instance instanceof Plugin && typeof instance[options] === 'function') {
                    instance[options].apply(instance, Array.prototype.slice.call(args, 1));
                }

                // Allow instances to be destroyed via the 'destroy' method.
                if (options === 'destroy') {
                    $.data(this, 'plugin_' + pluginName, null);
                }
            });
        }
    };
    /* eslint-enable */

});
