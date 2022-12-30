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
 * The Chooser jquery plugin contains the logic to control events for the
 * graph chooser.
 *
 * @module     report_analytics/Chooser
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_d3js/d3', 'report_analytics/graph', 'jquery'], function(d3, analytics, $) {

    var pluginName = 'Chooser',
        defaults = {};

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
            this.$element.parent().on('click', '.d3plus', this, this._show);
            this.$element.parent().on('click', '.scheduledreport', this, this._designScheduledReport);
            this.$element.parent().on('click', '.scheduled-add', this, this._addScheduledReport);
            this.$element.on('click', '.submitbutton', this, this._submit);
            this.$element.on('click', '.addcancel', this, this._cancel);
            this.$element.on('click', '.closebutton', this, this._close);
            this.$element.on('click', 'div.option', this, this._selectOption);
        },

        /**
         * Handle clicks on the "Add" button.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _submit: function(event) {

            event.preventDefault();
            var time = Date.now();
            M.util.js_pending('add' + time);

            var that = event.data;
            var graphType = $("#graphchooserform").find("input[type='radio']:checked").val();
            that.addGraphAjax(graphType, time);
            that._hideChooser();
        },

        /**
         * Handle clicks on the "Cancel" button.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _cancel: function(event) {
            var that = event.data;
            event.preventDefault();
            that._hideChooser();
        },

        /**
         * Handle clicks on the "X" symbol.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _close: function(event) {
            var that = event.data;
            event.preventDefault();
            that._hideChooser();
        },

        /**
         * Handle clicks on the options in the select (the various graphs).
         */
        _selectOption: function() {
            // Find and uncheck all radio buttons and hide help text.
            var outerdiv = $(this).parent();
            outerdiv.find('input[type=radio]').prop('checked', false);
            outerdiv.find('.typesummary').hide();
            // Then check desired button and show help text.
            var input = $(this).find('input[type=radio]');
            input.prop('checked', true);
            $(this).find('.typesummary').css('display', 'block');
        },

        /**
         * Hide the graph chooser.  The modal background spoofing must be turned off.
         */
        _hideChooser: function() {
            $('.modal-background').css('display', 'none');
            this.$element.hide();
        },

        /**
         * Show the graph chooser.  The left and top positions are selected to roughly centre
         * the dialogue on the page.
         *
         * For most devices, use a width of 540px, on low resolution devices (i.e., iphones)
         * use a width of 280px.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _show: function(event) {
            var that = event.data;
            that.$element.show();
            that.$element.children('.chooserdialoguebody').show();
            var dialogue = $('.jschooser .chooserdialogue');
            var container = $('#page');
            var top = container.height() / 2 - dialogue.height() / 2 + scrollY;
            var heightMax = container.height() - dialogue.height();
            if (top > heightMax) {
                top = heightMax;
            }
            var dialgoueWidth = (window.innerWidth > 700) ? 540 : 280;
            var left = container.width() / 2 - dialgoueWidth / 2 + scrollX;
            var styles = {'left': left + 'px', 'top': top + 'px', 'width': dialgoueWidth + 'px', 'z-index': '4033'};
            dialogue.css(styles);
            $('.modal-background').css('display', 'block');
        },

        /**
         * Add graph placeholder and make a call to render the default data.
         *
         * @param  {string}  graphType  the type of graph to draw
         * @param  {string}  time       time of button press: used to mark request complete
         * @param  {object}  filters    pre-defined filters for chart (optional)
         */
        addGraphAjax: function(graphType, time, filters) {

            // This is a moodle 2.4+ fix to unbind the unload handler.
            window.onbeforeunload = null;

            $('#chartcontainer').append('<div class=spinner></div>');
            var spinner = new analytics.Spinner()
                .width(300)
                .height(300);
            d3.select('.spinner').call(spinner);
            // Wipe any older errors.
            $('.addstatustext').text('').removeClass('alert alert-danger');

            $.post(M.cfg.wwwroot + "/report/analytics/ajax_request.php", {
                courseid: window.retrieveCourseID(),
                sesskey: M.cfg.sesskey,
                request: 'add_graph',
                graphtype: graphType
            }, null, 'json')
            .done(function(data) {
                if (data.result === true) {
                    $('#chartcontainer').ChartContainer('addChart', data.message, graphType, filters);
                } else {
                    $('.addstatustext').addClass('alert alert-danger').text(data.message);
                }
            })
            .fail(function() {
                    $('.addstatustext').addClass('alert alert-danger').text(M.util.get_string('badrequest', 'report_analytics'));
            })
            .always(function() {
                $('#chartcontainer .spinner').remove();
                M.util.js_complete('add' + time);
            });
        },

        /**
         * Redirect user to scheduled report page.
         */
        _designScheduledReport: function() {
            window.location.href = M.cfg.wwwroot + "/report/analytics/scheduled.php?id=" + window.retrieveCourseID();
        },

        /**
         * Adds a scheduled report criteria selector to the page.  This option is
         * not directly accessible through the chooser.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _addScheduledReport: function(event) {
            var that = event.data;
            var time = Date.now();
            M.util.js_pending('add' + time);
            that.addGraphAjax('ScheduledCriteriaChart', time);
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
