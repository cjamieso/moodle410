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
 * This is the main javascript file that handles a user joining a group.
 *
 * @category   block
 * @copyright  2018 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/modal_factory', 'core/str'], function($, ModalFactory, str) {

    var BLOCKSGLANGTABLE = 'block_skills_group';
    var courseid = 1;

    /**
     * Submit a request for a student to join a group.
     *
     * @param  {object}  event  the event that triggered the call
     */
    function join(event) {

        var joinDiv = $(event.target.parentNode);
        var groupid = joinDiv.attr('value');

        var spinner = M.util.add_spinner(Y, Y.one(joinDiv[0])).show();
        var time = Date.now();
        M.util.js_pending('join' + time);
        $.post(M.cfg.wwwroot + "/blocks/skills_group/ajax_request.php", {
            courseid: courseid,
            sesskey: M.cfg.sesskey,
            request: 'join_group',
            groupid: groupid
        }, null, 'json')
        .done(function(data) {
            var stringPromise, text;
            if (data.result === 'true') {
                stringPromise = str.get_string('success', 'core');
                text = "<p class='alert alert-success'>" + data.text + "</p>";
                var image = $(event.target);
                image.removeClass('fa-square-o');
                image.addClass('fa-check-square-o');
            } else {
                stringPromise = str.get_string('error', 'core');
                text = "<p class='alert alert-danger'>" + data.text + "</p>";
            }
            $.when(stringPromise).done(function(s) {
                ModalFactory.create({
                    title: s,
                    body: text
                })
                .done(function(modal) {
                    modal.show();
                    spinner.hide();
                    M.util.js_complete('join' + time);
                });
            });
        })
        .fail(function() {
            str.get_strings([
                {'key': 'error', component: 'core'},
                {'key': 'groupjoinerror', component: BLOCKSGLANGTABLE}
            ]).done(function(s) {
                ModalFactory.create({
                    title: s[0],
                    body: "<p class='alert alert-danger'>" + s[1] + "</p>"
                })
                .done(function(modal) {
                    modal.show();
                    spinner.hide();
                    M.util.js_complete('join' + time);
                });
            });
        });
    }

    return {
        init: function(mcourseid) {
            courseid = mcourseid;
            $(document).ready(function() {
                $('.flexible').on('click', '.joingroup i', this, join);
                $('#return').click(function() {
                    window.location = M.cfg.wwwroot + '/course/view.php?id=' + courseid;
                });
            });
        }
    };

});
