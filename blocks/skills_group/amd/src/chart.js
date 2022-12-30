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
 * Graphing related functions are contained here.
 *
 * Much of this code is a simpler version of the analytics report plugin.
 *
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['local_d3js/d3', 'local_d3js/d3tip', 'jquery', 'block_skills_group/jquery.multiple.select'], function(d3, d3tip, $) {

d3.tip = d3tip;
 // Constants.
var GRAPHHEIGHT = 500;
var GRAPHWIDTH = 900;
var MAXLENGTH = 20;
var TOOLTIPOFFSET = 10;

// Global variables.
var chart;

/**
 * Initialize the chart.
 */
function init() {

    var outerdiv = $('#chart');
    chart = new Chart(outerdiv);
    initFilters(outerdiv);

    var chartcontainer = $('#chartcontainer');
    chartcontainer.on('click', '.d3button', this, buttonClick);
    chartcontainer.on('click', '.d3export .pngexport', this, saveAsPng);
    chartcontainer.on('click', '.d3export .excelexport', this, exportToExcel);
}

/**
 * Retrieve the course ID from the page.  Moodle stores it as a class on the
 * body element starting with 'course-' followed by the ID.
 *
 * @return {int}  the ID of the course being used
 */
function retrieveCourseID() {

    var courseid = 1;
    var classes = $('body').attr('class').toString().split(' ');
    $.each(classes, function(key, value) {
        var index = value.indexOf('course-');
        if (index !== -1) {
            var parts = value.split('-');
            if (parts[1] !== undefined && parts[1] !== '') {
                courseid = parseInt(parts[1]);
            }
        }
    });
    return courseid;
}

/**
 * Handle clicks on "Apply filter" button.
 */
function buttonClick() {
    var time = Date.now();
    M.util.js_pending('get' + time);

    $(this).attr('disabled', 'disabled');
    var outerdiv = $(this).parent();
    outerdiv.find('.filterstatustext').text('');
    var id = outerdiv.find('div[class*=d3chart]').attr('id');
    var filters = chart.get_filters();
    if (filters.result === true) {
        getDataAjax(id, filters, time);
    } else {
        outerdiv.find('.filterstatustext').addClass('error').text(filters.text);
        $(this).removeAttr('disabled');
        M.util.js_complete('get' + time);
    }
}

/**
 * Initializes any filters that exist as part of the chart
 *
 * @param  {object}  outerdiv  the div containing the chart
 */
function initFilters(outerdiv) {

    // Setup multi-selects only in the added region.
    var msOptions = {filter: true};
    outerdiv.find("select.itemfilter[multiple = 'multiple']").multipleSelect(msOptions);
}

/**
 * This function retrieves data from the database (via php) using an ajax call.
 * The data is then plotted by calling the passed function.  Prior to loading
 * the data, a placeholder spinner is drawn.
 *
 * @param  {string}   id         the id of the chart being updated
 * @param  {object}   filters    filters, one array for each type
 * @param  {string}   time       time of button press: used to mark request complete
 */
function getDataAjax(id, filters, time) {

    filters = (typeof(filters) === 'undefined') ? null : filters;

    // This is a moodle 2.4+ fix to unbind the unload handler.
    window.onbeforeunload = null;

    // Wipe any older errors and placeholder styles.
    var graphNode = $('#' + id);
    graphNode.removeAttr('style');
    var outerdiv = graphNode.parent();
    var statusText = outerdiv.find('.filterstatustext');
    statusText.text('');

    var spinner = new GroupedBarGraph('#' + id, {width: GRAPHWIDTH, height: GRAPHHEIGHT});
    spinner.draw_spinner(240, 120);
    $.post(M.cfg.wwwroot + "/blocks/skills_group/ajax_request.php", {
        courseid: retrieveCourseID(),
        sesskey: M.cfg.sesskey,
        request: 'get_chart_data',
        filters: JSON.stringify(filters)
    }, null, 'json')
    .done(function(data) {
        if (data.result === "true") {
            chart.display_toolbar(data.toolbar);
            chart.update(data.data, filters, data.title);
        } else {
            statusText.addClass('error').text(data.text);
            graphNode.empty();
        }
    })
    .fail(function() {
        statusText.addClass('error').text(M.util.get_string('badrequest', 'block_skills_group'));
        graphNode.empty();
    })
    .always(function() {
        outerdiv.find('.d3button').removeAttr('disabled');
        M.util.js_complete('get' + time);
    });
}

/**
 * Save a chart as a png file.  This function draws the image on the hidden
 * canvas, then creats a fake link to download the contents of the canvas.
 *
 * There's an onload handler here to allow for enough time to draw the image
 * to the hidden canvas.
 */
function saveAsPng() {

    var node = d3.select(this.parentNode.parentNode).select('svg')
        .attr("version", 1.1)
        .attr("xmlns", "http://www.w3.org/2000/svg")
        .style("background-color", 'white')
        .node();
    var bb = node.getBoundingClientRect();
    $('canvas').attr('width', bb.width).attr('height', bb.height);
    $(node).prepend(getStyles(node));
    var html = removeImageTags(node.parentNode.innerHTML);

    var canvas = document.querySelector("canvas"),
    context = canvas.getContext("2d");

    var image = document.createElement( "img" );
    image.src = 'data:image/svg+xml;base64,' + btoa(html);
    image.onload = function() {

        context.drawImage(image, 0, 0);
        var a = document.createElement("a");
        a.download = "graph.png";
        a.href = canvas.toDataURL("image/png");
        document.querySelector("body").appendChild(a);
        a.click();
        document.querySelector("body").removeChild(a);
    };
}

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
function getStyles(svg) {

    var used = "";
    var sheets = document.styleSheets;
    for (var i = 0; i < sheets.length; i++) {
        var rules = sheets[i].cssRules;
        for (var j = 0; j < rules.length; j++) {
            var rule = rules[j];
            if (typeof(rule.style) !== "undefined") {
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
}

/**
 * Remove any <image> tags from the svg that is passed.  These <image> tags
 * cannot be converted into the png version, so they are stripped out here.
 *
 * @param  {string}  svg  the svg to strip the <image> tags from
 * @return {string}  the updated svg with all <image> tags removed
 */
function removeImageTags(svg) {

    var wrapped = $("<div>" + svg + "</div>");
    $(wrapped).find('image').remove();
    return wrapped.html();
}

/**
 * Intercept click to excel export image and trigger the form submit to
 * download the excel file.
 */
function exportToExcel() {
    var form = $(this).parent().find('form');
    form.submit();
}

/**
 * The base chart object with some common methods.
 *
 * @param  {object}  outerdiv  the div containing the chart
 */
var Chart = function(outerdiv) {

    this.outerdiv = outerdiv;
    this.displaynode = outerdiv.find('div[class*=d3chart]');
    this.graph = null;
    this.data = null;
    this.title = null;
};

Chart.prototype = {

    /**
     * Retrieve and validate filters.  Chart contains:
     * -feedback selector
     * -item (question) selector
     *
     * @return     {object}  {[result] -> T/F indicating success, [text] -> error message, [filters] -> object with filters}
     */
    get_filters: function() {

        var items = this.get_filter_selects('item');
        var filters = {items: items};
        if (filters.items === null) {
            filters.result = false;
            filters.text = M.util.get_string('noquestions', 'block_skills_group');
            return filters;
        }

        filters.result = true;
        return filters;
    },

    /**
     * Retreives a list (or single) select from a <select> on the page based on
     * the type given.
     *
     * @param  {string}  type  the type of filter to retrieve selects for
     * @return {object|string}  the selected value(s) of the filter
     */
    get_filter_selects: function(type) {

        var filter = this.outerdiv.find('select.' + type + 'filter');
        var selects;
        if (filter.attr('multiple') === 'multiple') {
            selects = filter.multipleSelect('getSelects');
            if (selects.length === 0 || selects === undefined) {
                selects = null;
            }
        } else {
            selects = filter.val();
        }
        return selects;
    },

    /**
     * Add the html for the toolbar to the placeholder.
     *
     * @param  {string}  html  the html for the toolbar
     */
    display_toolbar: function(html) {
        var div = this.outerdiv.find('.d3export');
        div.empty();
        div.html(html);
    },

    /**
     * Creates the event data vs. activity type graph on the page.
     *
     * @param  {object} data    php array containing number of events for each activity class
     * @param  {object} filters the filters used to generate the graph
     * @param  {string} title   the title to use for the graph
     */
    update: function(data, filters, title) {

        var id = this.displaynode.attr('id');
        if (this.graph !== undefined && this.graph !== null) {
            this.graph.remove_tooltip();
        }
        // If no new data was passed and no old is present, return.
        if (data === null || data === undefined) {
            if (this.data === null || this.data === undefined) {
                return;
            } else {
                data = this.data;
            }
        } else {
            this.data = data;
        }
        // New title given, use it instead.
        if (title !== undefined) {
            this.title = title;
        }

        var margin = {top: 40, right: 160, bottom: 100, left: 60},
            width = GRAPHWIDTH - margin.left - margin.right,
            height = GRAPHHEIGHT - margin.top - margin.bottom;

        var x0 = d3.scale.ordinal()
            .rangeRoundBands([0, width], 0.1);

        var x1 = d3.scale.ordinal();

        var y = d3.scale.linear()
            .range([height, 0]);

        var bargroupnames = d3.keys(this.data[0]).filter(function(key) {
            return (key !== "label") && (key !== "value");
        });

        var colors = ["#a7a6bc", "#6e6c90"];
        var color = d3.scale.ordinal()
            .domain(bargroupnames)
            .range(colors.slice(0, bargroupnames.length));

        this.data.forEach(function(d) {
            d.vals = bargroupnames.map(function(name) { return {name: name, value: Number(d[name])}; });
        });

        x0.domain(this.data.map(function(d) { return d.label; }));
        x1.domain(bargroupnames).rangeRoundBands([0, x0.rangeBand()]);
        y.domain([0, d3.max(this.data, function(d) { return d3.max(d.vals, function(d) { return d.value; }); })]);

        this.graph = new GroupedBarGraph('#' + id, {width: GRAPHWIDTH, height: GRAPHHEIGHT, margin: margin,
            axistitle: M.util.get_string('count', 'block_skills_group'), title: this.title });
        this.graph.create_container();
        this.graph.draw_axis({x: x0, y: y}, true);
        this.graph.draw_graph(this.data, color, {x0: x0, x1: x1, y: y}, this.onclick);
        this.graph.draw_title();
        this.graph.draw_legend(color, width + margin.left, bargroupnames.slice());
    }

};

/**
 * The GroupedBarGraph dervies from the basic graph object and requires customized methods
 * for drawing the graph and setting up the axes.
 *
 * Note: axis 'x0' is the activity/class ticks
 *       axis 'x1' is used for views/interactions
 *       axis 'y' is the y-axis
 *
 * @param {object} selector the selector to apply the graph to
 * @param {object} options  the various options {width, height, margin, title, axistitle}
 */
var GroupedBarGraph = function(selector, options) {

    this.width = options.width;
    this.height = options.height;
    this.margin = options.margin;
    this.title = options.title;
    this.axistitle = options.axistitle;
    this.selector = selector;

    this.tooltip = d3.tip()
        .attr('class', 'd3-tip')
        .offset([-TOOLTIPOFFSET, 0])
        .html(function(d) { return "<span style='color:red'>" + d.value + "</span>"; });
};

GroupedBarGraph.prototype = {

    /**
     * Creates the ds.js container to hold the graph (the "svg" element).
     */
    create_container: function() {

        d3.select(this.selector).selectAll("*").remove();
        this.node = d3.select(this.selector).append("svg")
            .attr("width", this.width)
            .attr("height", this.height)
            .append("g")
                .attr("transform", "translate(" + this.margin.left + "," + this.margin.top + ")");

        this.node.call(this.tooltip);
    },

    /**
     * Wipes out any existing d3.tip that exists.  This lets the graph be re-drawn
     * without the existing tip persisting.
     */
    remove_tooltip: function() {
        this.tooltip.destroy();
    },

    /**
     * Creates an axis variable for a grouped bar graph.  The tickFormat()
     * command is added to accommodate the groups.
     *
     * @param  {string} type    {"xaxis"|"yaxis"}
     * @param  {object} axisvar the ds.js scale object (x|y)
     * @return {object} The ds.js axis object
     */
    create_axis: function(type, axisvar) {

        var axis;
        if(type === "xaxis") {
            axis = d3.svg.axis()
                .scale(axisvar)
                .orient("bottom");
        } else {
            axis = d3.svg.axis()
                .scale(axisvar)
                .orient("left")
                .tickFormat(d3.format(".2s"));
        }
        return axis;
    },

    /**
     * Draws the ds.js axis on the page - a series of "g" elements.
     *
     * @param  {object}  axis d3scale for both x-axis and y-axis
     * @param  {boolean} trim T/F indicating if x-axis should be trimmed if too long
     */
    draw_axis: function(axis, trim){

        if (axis.x === undefined || axis.y === undefined) {
            return false;
        }
        if (trim === undefined) {
            trim = false;
        }
        var xaxis = this.create_axis("xaxis", axis.x);
        var yaxis = this.create_axis("yaxis", axis.y);

        var innerheight = this.height - this.margin.top - this.margin.bottom;
        this.node.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + innerheight + ")")
            .call(xaxis)
            .selectAll("text")
                .style("text-anchor", "end")
                .attr("dx", "-.8em")
                .attr("dy", ".15em")
                .attr("transform", "rotate(-35)");
        if (trim === true) {
            this.node.selectAll("text")
                .text(function(d) {
                    if (d.length > MAXLENGTH) {
                        return d.substring(0, MAXLENGTH) + '...';
                    } else {
                        return d;
                    }
                })
                .append("title")
                    .text(function(d) { return d; });
        }
        this.node.append("g")
            .attr("class", "y axis")
            .call(yaxis)
            .append("text")
                .attr("transform", "rotate(-90)")
                .attr("y", -40)
                .attr("dy", "0.71em")
                .style("text-anchor", "end")
                .text(this.axistitle);

        return this.node;
    },

    /**
     * Draws the grouped set of bars for the bar graph on the page.
     *
     * @param  {object} data  data to be plotted on the bar graph
     * @param  {object} color d3.scale object containing color information
     * @param  {object}   axis    d3.scale object containing axis information
     * @param  {function} onclick function to handle click events
     */
    draw_graph: function(data, color, axis, onclick) {

        if (axis.x0 === undefined || axis.x1 === undefined || axis.y === undefined) {
            return false;
        }
        var innerheight = this.height - this.margin.top - this.margin.bottom;

        var bar = this.node.selectAll(".bar")
            .data(data)
            .enter().append("g")
                .attr("class", "groupedbar")
                .attr("id", function(d, i) { return "bar" + i; })
                .attr("transform", function(d) { return "translate(" + axis.x0(d.label) + ",0)"; })
                .on('click', onclick);

        bar.selectAll("rect")
            .data(function(d) { return d.vals; })
            .enter().append("rect")
                .attr("width", axis.x1.rangeBand())
                .attr("x", function(d) { return axis.x1(d.name); })
                .attr("y", function(d) { return axis.y(d.value); })
                .attr("height", function(d) { return innerheight - axis.y(d.value); })
                .style("fill", function(d) { return color(d.name); })
                .on('mouseover', this.tooltip.show)
                .on('mouseout', this.tooltip.hide);
    },

    /**
     * Add legend to d3.js plot.
     *
     * @param {object} color  d3.js color object
     * @param {int}    left   left indent inside container (allow room for chart)
     * @param {object} data   the dataset for the graph so that the names for the legend can be computed
     */
    draw_legend: function(color, left, data) {

        // The color domain is sufficient for basic graphs, the data must be used for more complex graphs.
        var legendnames = (typeof data === 'undefined') ? color.domain() : data;

        var legendRectSize = 18;
        var legendSpacing = 4;
        var itemheight = legendRectSize + legendSpacing;
        // Compute extra pixels to centre legend.
        var extra = this.height - itemheight * color.domain().length;

        var svg = d3.select(this.selector + ' svg');
        var legend = svg.selectAll('.legend')
            .data(legendnames)
            .enter()
            .append('g')
                .attr('class', 'legend')
                .attr('transform', function(d, i) {
                    var horz = left + legendRectSize;
                    var vert = i * itemheight + extra / 2;
                    return 'translate(' + horz + ',' + vert + ')';
                });

        legend.append('rect')
            .attr('width', legendRectSize)
            .attr('height', legendRectSize)
            .style('fill', function(d, i) { var x = (d.label !== undefined) ? d.label : i; return color(x); })
            .style('stroke', function(d, i) { var x = (d.label !== undefined) ? d.label : i; return color(x); });

        legend.append('text')
            .attr('x', legendRectSize + legendSpacing)
            .attr('y', legendRectSize - legendSpacing)
            .text(function(d) { return (d.label !== undefined) ? d.label : d; });
    },

    /**
     * Add title to d3.js plot.
     */
    draw_title: function() {

        var graphwidth = this.width - this.margin.left - this.margin.right;
        this.node.append("text")
            .text(this.title)
            .attr("x", graphwidth / 2)
            .attr("y", -20)
            .attr("class","d3title");
    },

    /**
     * Render a spinner (for loading) as a placeholder while the chart data
     * is received.
     *
     * @param      {int}  spinnerwidth   width of spinner
     * @param      {int}  spinnerheight  height of spinner
     */
    draw_spinner: function(spinnerwidth, spinnerheight) {
        var radius = Math.min(spinnerwidth, spinnerheight) / 2;
        var tau = 2 * Math.PI;

        d3.select(this.selector).selectAll("*").remove();

        var arc = d3.svg.arc()
            .innerRadius(radius * 0.5)
            .outerRadius(radius * 0.9)
            .startAngle(0);

        var svg = d3.select(this.selector).append("svg")
            .attr("width", this.width)
            .attr("height", this.height)
            .append("g")
            .attr("transform", "translate(" + this.width / 2 + "," + this.height / 2 + ")");

        svg.append("path")
            .datum({endAngle: 0.33 * tau})
            .attr('class', 'spinner')
            .attr("d", arc)
            .call(GroupedBarGraph.prototype.spin, 750);
    },

    /**
     * Rotates the spinner to create the vision that it is spinning.
     *
     * @param      {object}  selection  the spinner node
     * @param      {int}     duration   how frequently to rotate the spinner
     */
    spin: function(selection, duration) {
        selection.transition()
            .ease("linear")
            .duration(duration)
            .attrTween("transform", function() {
                return d3.interpolateString("rotate(0)", "rotate(360)");
            });

        setTimeout(function() { GroupedBarGraph.prototype.spin(selection, duration); }, duration);
    }

};

/**
 * Retrieves data stored with a d3.js grouped bar.  The primary purpose of this function
 * is to make data accessible for behat tests.
 *
 * @param  {string} label the graph label to retrieve the data for
 * @return {Array}  the data for specified label (or false if no data found)
 */
window.get_groupedbar_d3data = function(label) {
    var t = false;
    $('.groupedbar').each(function() {
        if((this.__data__.label) === label) {
            t = this.__data__;
        }
    });
    return t;
};

return {
    // Return init function to load the AMD module (moodle prefers js_call_amd() to load).
    init: init
};

});