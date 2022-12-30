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

/* eslint-disable */
define(['jquery', 'format_collblct/jquery.nestedAccordion'], function($) {

    var background_color, foreground_color;

    /**
     * Print the accordion tag so that the menu gets styled.
     *
     * @param  {string}  sectionnumber  the section number
     */
    function print_accordion_tag(sectionnumber) {
        $('#section-' + sectionnumber + ' .section').wrap('<div id="acc' + sectionnumber + '" class="accordion"></div>');

    }

    /**
     * This function removes all mod-indent values from the modules that already exist
     * on the screen.  Moodle currently has definitions for up to 15 levels of indent,
     * so I have choosen to remove them all.
     *
     * @param  {string}  sectionnumber  The sectionnumber
     */
    function remove_all_mod_indents(sectionnumber) {
        var total_indents_to_remove = 15;

        for(var i = 1; i < total_indents_to_remove; i++) {
            var classname = 'mod-indent-' + i.toString();
            var selector = '#section-' + sectionnumber + ' .accordion div';
            $(selector).removeClass(classname);
        }
    }

     /**
      * This function applies the updated indent levels to each of the modules that exist
      * on the screen.  The list of all modules (modid) and the proper depths (moddepth)
      * are passed in the moddepths object.
      *
      * @param  {object}  moddepths  the depths to indent each of the mods (activities)
      */
    function add_correct_mod_indents(moddepths) {
        for(var i = 0; i < moddepths.modid.length; i++) {
            var idtag = '#module-' + moddepths.modid[i];
            var indent = moddepths.moddepth[i];
            if(indent >= 1) {
                $(idtag).find(".mod-indent").addClass("mod-indent-" + indent.toString());
            }
        }
    }

    /**
     * This function ensures that the color preferences have been added to the nested labels.
     */
    function update_styling() {
        $(".accordion .trigger").css("background-color", background_color);
        $(".accordion .trigger").css("color", foreground_color);
    }

    /**
     * Document ready handler -> setup the jquery plugins.
     */
    $(document).ready(function() {

        $("html").addClass("js");
        $.fn.accordion.defaults.container = false;
        $(function (){
            var totalsections = 30;
            for (var i = 0; i < totalsections; i++) {
                var stringnum = i.toString();
                $("#acc" + stringnum).accordion({
                    obj: "div",
                    wrapper: "div",
                    el: ".h",
                    head: "h7, h8, h9, h10, h11, h12",
                    next: "div",
                    showMethod: "show",
                    hideMethod: "hide",
                    standardExpansible: true,
                    initShow: "#current"
                });
            }
            $("html").removeClass("js");
            update_styling();
        });
    });

    return {
        /**
         * This function is responsible for adding the html tags so that the collapsible
         * labels can be created.  This function is called directly by Moodle and the values
         * that it receives are JSON encoded from PHP.  The tags for each of the labels are
         * added in turn and then indent values are adjusted.
         *
         * The commented alert function is just a way to double check to ensure that a
         * non-cached version of the javascript is not being loaded.
         *
         * @param  {string}  jsonlabelinfo  json encoding of the label info
         * @param  {string}  jsonmoddepths  json encoding of the depths for each module
         * @param  {string}  header_start   the header tag number to use for the labels
         * @param  {string}  sectionnumber  the section number to apply the collapsed label to
         */
        setup_nested_section: function(jsonlabelinfo, jsonmoddepths, header_start, sectionnumber) {

            print_accordion_tag(sectionnumber);
            var labelinfo = $.parseJSON(jsonlabelinfo);
            var moddepths = $.parseJSON(jsonmoddepths);

            for(var i = 0; i < labelinfo.labelid.length; i++) {
                var opentag = '#module-' + labelinfo.labelid[i];
                var closetag = 'module-' + labelinfo.closeid[i].substr(1);
                var labelname = $(opentag).find('p').text();
                var headertag = header_start + labelinfo.depthindex[i] - 1;
                $(opentag).before('<h' + headertag.toString() + '>' + labelname + '</h' + headertag.toString() + '>');
                var closedom = document.getElementById(closetag);
                if(labelinfo.closeid[i].charAt(0) == 'N') {
                    $(opentag).nextUntil(closedom).addBack().wrapAll('<div class="inner" />');
                } else {
                    $(opentag).nextUntil(closedom).addBack().add(closedom).wrapAll('<div class="inner" />');
                }
                $(opentag).remove();
            }

            remove_all_mod_indents(sectionnumber);
            add_correct_mod_indents(moddepths);
        },

        /**
         * Save user color preferences for later styling.
         *
         * @param  {string}  moodlebackgroundcolor  the desired background color (hex code)
         * @param  {string}  moodleforegroundcolor  the desired foreground color (hex code)
         */
        color_init: function(moodlebackgroundcolor, moodleforegroundcolor) {
            background_color = moodlebackgroundcolor;
            foreground_color = moodleforegroundcolor;
        }
    };

});
/* eslint-enable */
