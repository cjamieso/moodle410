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
 * A small jQuery plugin to let the user select between a few pre-built
 * date options.
 *
 * @module     report_analytics/DateSelect
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    var pluginName = 'DateSelect',
    defaults = {
        datefilter: null
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
        // Total milliseconds in one day.
        this._day = 24 * 60 * 60 * 1000;

        this.init();
        this._events();
    }

    $.extend(Plugin.prototype, {

        /**
         * Function used to initialize (or re-initialize) the container.
         *
         * Move the dateselector so that it is just after the first date line on the page.
         */
        init: function() {

            var image = this.$element.detach();
            image.css('display', 'inline-block');
            var calendar = this.options.datefilter.node.find('a[id$="datefrom_calendar"]').first();
            calendar.before(image);
            this.options.datefilter.node.find('a[id$="datefrom_calendar"]').hide();
            this.options.datefilter.node.find('a[id$="dateto_calendar"]').hide();
        },

        /**
         * All event handlers are bound inside of this function.
         */
        _events: function() {
            this.$element.on('click', 'i', this, this._showDialogue);
            this.$element.on('click', 'span', this, this._changeDate);
            $('body').on('click', null, this, this._hideDialogue);
        },

        /**
         * Set the date picker to be visible on the page.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _showDialogue: function(event) {
            event.stopPropagation();
            var that = event.data;
            that.$element.find('div').show();
        },

        /**
         * Set the date picker to be invisible on the page.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _hideDialogue: function(event) {
            var that = event.data;
            that.$element.find('div').hide();
        },

        /**
         * Determine the date option clicked on by the user and set the date
         * fields accordingly.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _changeDate: function(event) {

            event.stopPropagation();
            var that = event.data;
            var value = $(this).attr('value');
            var now = new Date();
            var past = now - parseInt(value) * that._day;
            var date = {from: that._parseDate(past), to: that._parseDate(now)};
            if (that.options.datefilter !== null) {
                that.options.datefilter.setFilterData(date);
            }
        },

        /**
         * Converts an epoch time into a formatted date as a string.
         *
         * @param   {int}     epochTime  the date specified in linux epoch time
         * @return  {string}  the date (formatted as 'y-m-d h:m')
         */
        _parseDate: function(epochTime) {

            var jsDate = new Date(epochTime);
            var date = jsDate.getFullYear() + '-' + parseInt(jsDate.getMonth() + 1) + '-' + jsDate.getDate();
            var time = jsDate.getHours() + ':' + jsDate.getMinutes();
            return date + ' ' + time;
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
