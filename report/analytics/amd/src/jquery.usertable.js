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
 * jQuery plugin which displays a list of users on the page.  Some basic
 * funcitonality is included, such as pagination and select/unselect for
 * copying the list to the clipboard.
 *
 * @module     report_analytics/UserTable
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    var pluginName = 'UserTable',
    defaults = {
        users: null,
        title: null,
        perpage: 20
    };

    /**
     * Constructor for the plugin.
     *
     * @param  {object}  element  the document element that the plugin is attached to
     * @param  {object}  options  options includes user list, title, and number of users per page
     */
    function Plugin(element, options) {

        this.element = element;
        this.$element = $(element);
        this.options = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.currentpage = 1;

        this.init();
        this._events();
    }

    $.extend(Plugin.prototype, {

        /**
         * Function used to initialize (or re-initialize) the container.
         */
        init: function() {
            this._drawUsers(1, true);

        },

        /**
         * All event handlers are bound inside of this function.
         */
        _events: function() {
            this.$element.on('click', '.page', this, this._changePage);
            this.$element.on('click', '.selectall', this, this._checkAll);
        },

        /**
         * Draw the users onto the page, up to the specified max per page.
         *
         * @param  {int}      start         the user to start with
         * @param  {boolean}  drawcontrols  T/F indicating if the page controls need to be drawn
         */
        _drawUsers: function(start, drawcontrols) {

            if (start === undefined) {
                start = 1;
            }
            var table = this.$element.find('table');
            if (table.length > 0) {
                table.empty();
            } else {
                this.$element.append($("<table>"));
            }
            this._drawTableHeader();
            for (var i = start - 1; i < start - 1 + this.options.perpage; i++) {
                if (this.options.users[i] !== undefined) {
                    this._addUser(this.options.users[i]);
                }
            }
            if (drawcontrols === true) {
                this._drawPageControls();
            }

        },

        /**
         * Add the table header: select/unselect all checkbox + title.
         */
        _drawTableHeader: function() {
            if (this.options.users.length === 0) {
                this.$element.find('table').append($("<tr class='userheader'>")
                    .append($("<th>").addClass('d3title').text(M.util.get_string('nouserscriteria', 'report_analytics'))));
            } else {
                this.$element.find('table').append($("<tr class='userheader'>")
                    .append($("<th>").append($("<input>").attr('type', 'checkbox').addClass('selectall')))
                    .append($("<th>").attr('colspan', 3).addClass('d3title').text(this.options.title)));
            }
        },

        /**
         * Add a user to the container.
         *
         * @param  {object}  user  the user to add to the container
         */
        _addUser: function(user) {
            this.$element.find('table').append($("<tr class='user'>")
                .append($("<td>").append($("<input>").attr('type', 'checkbox').addClass('selectuser')))
                .append($("<td class='userpicture'>").append($("<img>").attr('src', user.profileimageurlsmall)))
                .append($("<td class='name'>").text(user.firstname + ' ' + user.lastname))
                .append($("<td class='email'>").text(user.email)));
        },

        /**
         * Draw the paging controls at the bottom of the container.  Add a div for
         * each page that the user can select.
         */
        _drawPageControls: function() {

            if (this.options.users.length > 0) {
                this.$element.append($("<div class='paging'>").text(M.util.get_string('page', 'moodle') + ': '));
                var paging = this.$element.find('.paging');
                var pages = Math.ceil(this.options.users.length / this.options.perpage);
                for (var i = 0; i < pages; i++) {
                    var pagediv = $("<div class='page'>").attr('pageindex', i + 1).text(i + 1);
                    if (i + 1 === this.currentpage) {
                        pagediv.addClass('current');
                    }
                    paging.append(pagediv);
                }
            }
        },

        /**
         * Event handler for user clicking a page number.
         *
         * @param  {object}  event  the event that triggered the call
         */
        _changePage: function(event) {

            var that = event.data;
            var page = $(this).attr('pageindex');
            var start = (page - 1) * that.options.perpage + 1;
            that._drawUsers(start, false);
            this.currentpage = page;
            $(this).parent().find('.page').removeClass('current');
            $(this).addClass('current');
        },

        /**
         * Event handler for user clicking the select (or de-select) all rows in the table.
         */
        _checkAll: function() {

            if ($(this).prop('checked') === true) {
                $(this).closest('table').find('input.selectuser').prop('checked', true);
            } else {
                $(this).closest('table').find('input.selectuser').prop('checked', false);
            }
        },

        /**
         * Copy the list of users to the clipboard.
         */
        copyUsers: function() {

            var userstext = [];
            var statusText = this.$element.closest('.chartheader').find('.filterstatustext');
            statusText.text('').removeClass('alert alert-danger');

            this.$element.find('tr').each(function() {
                if ($(this).find('input.selectuser').prop('checked') === true) {
                    userstext.push($(this).find('td.email').text());
                }
            });
            if (userstext.length === 0) {
                statusText.addClass('alert alert-danger').text(M.util.get_string('nousersselected', 'report_analytics'));
            } else {
                this._copyToClipboard(userstext.join(', '));
            }
        },

        /**
         * Copy text to clipboard for the user.
         *
         * Works in Chrome 43+, Firefox 42+, IE 10+, Edge, Safari 10+
         * For unsupported browsers, user can download table as csv and
         * copy/paste from here.
         *
         * @param  {string}  text  text to copy to the clipboard
         */
        _copyToClipboard: function(text) {

            // IE hacks.
            if (window.clipboardData && window.clipboardData.setData) {
                window.clipboardData.setData("Text", text);
                return;
            } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
                var textarea = document.createElement("textarea");
                textarea.textContent = text;
                textarea.style.position = "fixed";
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand("copy");
                } catch (e) {
                    var statusText = this.$element.closest('.chartheader').find('.filterstatustext');
                    statusText.addClass('alert alert-danger').text(M.util.get_string('nocopyinbrowser', 'report_analytics'));
                } finally {
                    document.body.removeChild(textarea);
                }
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
