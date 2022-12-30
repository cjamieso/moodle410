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

define(['local_d3js/d3', 'local_d3js/d3tip', 'jquery'], function(d3, d3tip) {

    d3.tip = d3tip;
    // Constants.
    var MAXLENGTH = 16;
    var TOOLTIPOFFSET = 10;
    var MAXSCALEDIFFERENCE = 5;
    var LEGENDITEMSPERPAGE = 10;

    /**
     * The axis object is used for drawing an x or y axis onto a graph.
     *
     * @return {object}  current object to permit function chaining
     *
     * @module     report_analytics/Axis
     * @copyright  2016 Craig Jamieson
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    var Axis = function module() {

        /** @property {object}  axis options {format, orient, trim, cssclass} */
        var options = {format: null, orient: 'bottom', trim: false, cssclass: 'x'};
        /** @property {function}  the d3js scale function to create the axis from */
        var scale = null;
        /** @property {object}  custom attributes for drawing the axis */
        var attributes = {};
        /** @property {object}  custom styles for drawing the axis */
        var styles = {};
        /** @property {object}  the translate properties (x/y shift) for the axis */
        var translate = {x: 0, y: 0};
        /** @property {object}  the title (includes both the text and any attributes) */
        var title = {name: '', translate: '(-3, -20)', textanchor: 'end'};

        /**
         * Draws the ds.js axis on the page - a series of "g" elements.
         *
         * @param  {object}  selection  a d3js selection upon which to add the axis
         */
        function obj(selection) {

            selection.each(function() {
                var axis = obj._createAxis();
                obj._drawAxis(this, axis);
            });
        }

        /**
         * Create a ds.js compatible axis for a graph from the scale object.
         *
         * @return {object} the d3 axis object
         */
        obj._createAxis = function() {

            var axis = d3.svg.axis()
                .scale(scale)
                .orient(options.orient);
            if (options.format !== undefined) {
                axis.tickFormat(options.format);
            }
            return axis;
        };

        /**
         * Adds an axis to the page.
         *
         * @param  {object}  selector   the selector (or a node object) to add the axis to
         * @param  {object}  axis       the d3 axis object for the x-axis
         */
        obj._drawAxis = function(selector, axis) {

            d3.select(selector).append("g")
                .attr("class", options.cssclass + " axis")
                .attr("transform", "translate(" + translate.x + "," + translate.y + ")")
                .call(axis)
                .selectAll("text")
                    .style(styles)
                    .attr(attributes)
                    .attr("class", "eclass-text-fill");
            d3.select(selector).selectAll("line").attr("class", "eclass-text-stroke");
            d3.select(selector).selectAll("path").attr("class", "eclass-text-stroke");
            if (title.name !== '') {
                d3.select(selector).select("." + options.cssclass).append("text")
                    .attr("class", "eclass-text-fill")
                    .attr("transform", "translate" + title.translate)
                    .style("text-anchor", title.textanchor)
                    .text(title.name);
            }
            if (options.trim === true) {
                d3.select(selector).selectAll("." + options.cssclass + " text")
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
        };

        /**
         * Getter/setter for the options property.
         *
         * @param  {object}  _x  desired set of options (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.options = function(_x) {
            if (!arguments.length) {
                return options;
            }
            Object.assign(options, _x);
            return this;
        };

        /**
         * Getter/setter for the scale property.
         *
         * @param  {object}  _x  desired scale (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.scale = function(_x) {
            if (!arguments.length) {
                return scale;
            }
            scale = _x;
            return this;
        };

        /**
         * Getter/setter for the attributes property.
         *
         * @param  {object}  _x  desired set of attributes (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.attributes = function(_x) {
            if (!arguments.length) {
                return attributes;
            }
            Object.assign(attributes, _x);
            return this;
        };

        /**
         * Getter/setter for the styles property.
         *
         * @param  {object}  _x  desired set of styles (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.styles = function(_x) {
            if (!arguments.length) {
                return styles;
            }
            Object.assign(styles, _x);
            return this;
        };

        /**
         * Getter/setter for the translate property.
         *
         * @param  {object}  _x  desired translate parameters (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.translate = function(_x) {
            if (!arguments.length) {
                return translate;
            }
            Object.assign(translate, _x);
            return this;
        };

        /**
         * Getter/setter for the title property.
         *
         * @param  {object}  _x  desired title parameters (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.title = function(_x) {
            if (!arguments.length) {
                return title;
            }
            Object.assign(title, _x);
            return this;
        };

        return obj;
    };

    /**
     * The legend object is used for adding a legend onto a graph.
     *
     * @return {object}  current object to permit function chaining
     *
     * @module     report_analytics/Legend
     * @copyright  2016 Craig Jamieson
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    var Legend = function module() {

        /** @property {number}  the height of the graph */
        var height = 0;
        /** @property  {object}  the d3.scale object containing color information */
        var color = null;
        /** @property {number}  the horizontal indent ot use for the graph */
        var indent = 0;
        /** @property {number}  the current page of the legend */
        var _currentPage = 1;
        /** @property {number}  the highest page in the legend */
        var _maxPage = 1;

        /**
         * Add legend to d3.js graph.
         *
         * @param  {object}  selection  a d3js selection upon which to add the legend
         */
        function obj(selection) {

            var legendRectSize = 18,
                xSpacer = 4,
                ySpacer = 6;

            selection.each(function(data) {

                var visibleItems = (data.length < LEGENDITEMSPERPAGE) ? data.length : LEGENDITEMSPERPAGE;
                var extra = height - legendRectSize * visibleItems;
                _maxPage = Math.ceil(data.length / LEGENDITEMSPERPAGE);

                var svg = d3.select(this).select('svg');
                var legend = svg.selectAll('.legend')
                    .data(data)
                    .enter()
                    .append('g')
                        .attr('class', 'legend eclass-text-fill')
                        .attr('transform', function(d, i) {
                            var index = i % LEGENDITEMSPERPAGE;
                            var horz = indent + legendRectSize;
                            var vert = index * legendRectSize + extra / 2;
                            return 'translate(' + horz + ',' + vert + ')';
                        })
                        .attr('visibility', obj._setVisibility);

                legend.append('rect')
                    .attr('width', legendRectSize)
                    .attr('height', legendRectSize)
                    .style('fill', function(d, i) { var x = (d.label !== undefined) ? d.label : i; return color(x); });

                legend.append('text')
                    .attr('x', legendRectSize + xSpacer)
                    .attr('y', legendRectSize - ySpacer)
                    .text(function(d) {
                        var text = (d.label !== undefined) ? d.label : d;
                        if (text.length > MAXLENGTH) {
                            return text.substring(0, MAXLENGTH) + '...';
                        } else {
                            return text;
                        }
                    })
                    .append("title")
                        .text(function(d) { return (d.label !== undefined) ? d.label : d; });

                if (data.length > LEGENDITEMSPERPAGE) {
                    obj._addPagination(svg, indent + legendRectSize, LEGENDITEMSPERPAGE * legendRectSize + extra / 2);
                }
            });
        }

        /**
         * Sets the visibility of the items in the legend.  The items on the current
         * page are set to be visible, but all other items should be hidden.
         *
         * @param  {number}   d   the data attached to the node (not used, but needed for d3)
         * @param  {number}   i   the index of the legend item
         * @return {string}  whether the item is visible or hidden
         */
        obj._setVisibility = function(d, i) {
            var page = Math.floor(i / LEGENDITEMSPERPAGE) + 1;
            return (page === _currentPage) ? 'visible' : 'hidden';
        };

        /**
         * Adds pagination to the legend
         *
         * @param  {object}  svg  the svg that holds the legend
         * @param  {number}  x    the x offset for the legend
         * @param  {string}  y    the y offset for the pagination controls
         */
        obj._addPagination = function(svg, x, y) {

            var prevOffset = x + 28,
                nextOffset = x + 48;
            svg.append('svg:image')
                .attr('class', 'legendcontrol legendprev')
                .attr("xlink:href", M.util.image_url('t/left'))
                .attr('transform', 'translate(' + prevOffset + ',' + y + ')');
            svg.append('svg:image')
                .attr('class', 'legendcontrol legendnext')
                .attr("xlink:href", M.util.image_url('t/right'))
                .attr('transform', 'translate(' + nextOffset + ',' + y + ')');
            svg.selectAll('.legendcontrol')
                .attr('width', 12)
                .attr('height', 12)
                .on('click', obj._paginator);
        };

        /**
         * Event handler for the pagination.  Update the current page, then
         * adjust the visibility on the legend items.
         */
        obj._paginator = function() {
            if (d3.select(this).classed('legendprev')) {
                if (_currentPage > 1) {
                    _currentPage--;
                }
            } else if (d3.select(this).classed('legendnext')) {
                if (_currentPage < _maxPage) {
                    _currentPage++;
                }
            }
            d3.select(this.parentNode).selectAll('.legend')
            .attr('visibility', obj._setVisibility);
        };

        /**
         * Getter/setter for the height property.
         *
         * @param  {object}  _x  desired the height of the graph (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.height = function(_x) {
            if (!arguments.length) {
                return height;
            }
            height = _x;
            return this;
        };

        /**
         * Getter/setter for the color property.
         *
         * @param  {object}  _x  desired d3js color object (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.color = function(_x) {
            if (!arguments.length) {
                return color;
            }
            color = _x;
            return this;
        };

        /**
         * Getter/setter for the indent property.
         *
         * @param  {number}  _x  desired indent (empty to retrieve)
         * @return {number}  return "this" object to allow for chaining
         */
        obj.indent = function(_x) {
            if (!arguments.length) {
                return indent;
            }
            indent = _x;
            return this;
        };

        return obj;
    };

    /**
     * The title object for adding a title to a graph.
     *
     * @return {object}  current object to permit function chaining
     *
     * @module     report_analytics/Title
     * @copyright  2016 Craig Jamieson
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    var Title = function module() {

        /** @property {object}  the attributes used drawing the title */
        var attributes = {y: -18, cssclass: 'd3title'};

        /**
         * Add title to d3.js plot.
         *
         * @param  {object}  selection  a d3js selection upon which to add the title
         */
        function obj(selection) {
            selection.each(function(data) {
                d3.select(this).append("text")
                    .text(data)
                    .attr(attributes)
                    .attr("class", "eclass-text-fill");
            });
        }

        /**
         * Getter/setter for the attributes property.
         *
         * @param  {object}  _x  desired set of attributes (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.attributes = function(_x) {
            if (!arguments.length) {
                return attributes;
            }
            Object.assign(attributes, _x);
            return this;
        };

        return obj;
    };

    /**
     * The GroupedBarGraph is a bar graph that groups two or more bars for each point
     * on the x-axis.
     *
     * Note: xaxis 'x' is the activity/class ticks
     *       xaxis 'x1' is used for views/interactions
     *       yaxis 'y' is the y-axis
     *       yaxis 'yalt' is used if a second y axis scale is needed (data differs significantly).
     *
     * @return {object}  current object to permit function chaining
     *
     * @module     report_analytics/GroupedBarGraph
     * @copyright  2016 Craig Jamieson
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    var GroupedBarGraph = function module() {

        /** @property {object}  axis options {width, height, margin, title} */
        var options = {width: 0, height: 0, margin: {top: 40, right: 160, bottom: 100, left: 60}, title: ''};
        /** @property {array}   a list of legend entries that should have help tooltips added to them (no longer added) */
        var helpTooltips = [];
        /** @property {object}  the d3.scale object containing color information */
        var _color = null;
        /** @property {object}  the various scales used by the graph (see above) */
        var _scale = null;
        /** @property {function}  allow assignment of "barclick" event that fires when a bar is clicked */
        var _dispatch = d3.dispatch("barclick");
        /** @property {function}  the d3.tip function used to control the tooltip */
        var _tooltip = d3.tip()
            .attr('class', 'd3-tip')
            .offset([-TOOLTIPOFFSET, 0])
            .html(function(d) { return "<span style='color:red'>" + d.value + "</span>"; });

        /**
         * Draws the graph on the page.
         *
         * @param  {object}  _selection  a d3js selection upon which to add the graph
         */
        function obj(_selection) {

            _selection.each(function(data) {
                obj._createContainer(this)._drawAxis(this, data)._drawGraph(this, data)._drawTitle(this)._drawLegend(this, data);
            });
        }

        /**
         * Creates the ds.js container to hold the graph (the "svg" element).
         * selector -> svg -> g (indented by margin)
         *
         * Also sets up the tooltip.
         *
         * @param  {object}  selector  d3js selector indentifying the node to add the svg element
         * @return {object}  current object to permit function chaining
         */
        obj._createContainer = function(selector) {

            d3.select(selector).selectAll("*").remove();
            var node = d3.select(selector).append("svg")
                .attr("width", options.width)
                .attr("height", options.height)
                .attr("name", 'graph')
                .append("g")
                    .attr("transform", "translate(" + options.margin.left + "," + options.margin.top + ")");

            node.call(_tooltip);
            return obj;
        };

        /**
         * Wipes out any existing d3.tip that exists.  This lets the graph be re-drawn
         * without the existing tip persisting.
         */
        obj.removeTooltip = function() {
            _tooltip.destroy();
        };

        /**
         * Draws the various axes used: the x-axis, y-axis, and optionally a yalt-axis.
         *
         * When the scale between the normal values and average values is too large, a
         * second scale (yalt) is used for averages.  Check for this and use draw the
         * yalt axis as well.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @param  {array}   data      data to be plotted on the bar graph
         * @return {object}  current object to permit function chaining
         */
        obj._drawAxis = function(selector, data) {

            _scale = obj._createScales(data);
            var innerHeight = options.height - options.margin.top - options.margin.bottom;
            var xAxis = new Axis()
                .options({trim: true})
                .scale(_scale.x)
                .attributes({transform: 'rotate(-35)', dx: '-0.8em', dy: '0.15em'})
                .styles({'text-anchor': 'end'})
                .translate({x: 0, y: innerHeight});
            d3.select(selector).select('svg g').call(xAxis);

            var yAxis = new Axis()
                .options({format: d3.format(".2"), orient: 'left', cssclass: 'y'})
                .scale(_scale.y)
                .translate({x: 0, y: 0})
                .title({name: _scale.yTitle});
            d3.select(selector).select('svg g').call(yAxis);

            var yAlt = obj._createAlt(data, _scale.y);
            Object.assign(_scale, yAlt);
            if (_scale.yAltTitle !== '') {
                var innerWidth = options.width - options.margin.left - options.margin.right;
                var yAxisAlt = new Axis()
                    .options({format: d3.format(".2"), orient: 'right', cssclass: 'yalt'})
                    .scale(_scale.yAlt)
                    .translate({x: innerWidth, y: 0})
                    .title({name: _scale.yAltTitle, translate: '(30, -20)'});
                d3.select(selector).select('svg g').call(yAxisAlt);
            }

            return obj;
        };

        /**
         * Setup the d3.scale functions used for the x, x1, and y axis.
         *
         * @param  {array}  data  data to be plotted on the bar graph
         * @return {object}  the x, x1, and y axis scales
         */
        obj._createScales = function(data) {

            var x = d3.scale.ordinal()
                .domain(data.map(function(d) { return d.label; }))
                .rangeRoundBands([0, options.width - options.margin.left - options.margin.right], 0.1);
            var names = data[0].values.map(function(value) { return value.name; });
            var x1 = d3.scale.ordinal()
                .domain(names)
                .rangeRoundBands([0, x.rangeBand()]);
            // eslint-disable-next-line max-statements-per-line
            var ymax = d3.max(data, function(d) { return d3.max(d.values, function(d) { return Number(d.value); }); });
            var y = d3.scale.linear()
                .domain([0, ymax])
                .range([options.height - options.margin.top - options.margin.bottom, 0]);
            return {x: x, x1: x1, y: y, yTitle: M.util.get_string('events', 'report_analytics')};
        };

        /**
         * Creates an alternative scale (if needed).  When the difference between
         * the regular scale and average scale exceeds MAXSCALEDIFFERENCE, then an
         * alternative scale is created to graph the average values.
         *
         * @param  {array}     data  data to be plotted on the bar graph
         * @param  {function}  y     the y scale
         * @return {object}  the alt scale and title
         */
        obj._createAlt = function(data, y) {

            var domain = y.domain();
            var yMax = domain[domain.length - 1];
            var yAltMax = d3.max(data, function(d) {
                return d3.max(d.values, function(d) {
                    return (d.name.indexOf(M.util.get_string('average', 'report_analytics')) !== -1) ? d.value : 0;
                });
            });
            var yAlt = y.copy();
            var title = '';
            if (yAltMax > 0 && ((yMax / yAltMax) > MAXSCALEDIFFERENCE)) {
                yAlt.domain([0, yAltMax]);
                title = M.util.get_string('average', 'report_analytics');
            }
            return {yAlt: yAlt, yAltTitle: title};
        };

        /**
         * Draws the grouped set of bars for the bar graph on the page.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @param  {array}   data      data to be plotted on the bar graph
         * @return {object}  current object to permit function chaining
         */
        obj._drawGraph = function(selector, data) {

            if (_color === undefined || _color === null) {
                obj._buildColors(data);
            }

            var innerHeight = options.height - options.margin.top - options.margin.bottom;
            var bar = d3.select(selector).select('svg g').selectAll(".bar")
                .data(data)
                .enter().append("g")
                    .attr("class", "groupedbar")
                    .attr("id", function(d, i) { return "bar" + i; })
                    .attr("transform", function(d) { return "translate(" + _scale.x(d.label) + ",0)"; })
                    .on('click', _dispatch.barclick);

            bar.selectAll("rect")
                .data(function(d) { return d.values; })
                .enter().append("rect")
                    .attr("width", _scale.x1.rangeBand())
                    .attr("x", function(d) { return _scale.x1(d.name); })
                    .attr("y", function(d) {
                        var yScale = obj._getYScale(d.name);
                        return yScale(d.value);
                    })
                    .attr("height", function(d) {
                        var yScale = obj._getYScale(d.name);
                        return innerHeight - yScale(d.value);
                    })
                    .style("fill", function(d) { return _color(d.name); })
                    .on('mouseover', _tooltip.show)
                    .on('mouseout', _tooltip.hide);
            return obj;
        };

        /**
         * Builds an array of colors to use in the chart.  Standard entries are
         * a shade of bluish purple, while entries for averages are a shade of
         * green.
         *
         * @param  {array}  data  data to be plotted on the bar graph
         */
        obj._buildColors = function(data) {

            var baseColors = ["#a7a6bc", "#8B89A6", "#6e6c90"];
            var averageColors = ["#8ac5a7", "#70B894", "#55ab80"];
            var names = data[0].values.map(function(value) { return value.name; });

            var firstIndex = 0;
            for (var i = 1; i < names.length; i++) {
                if (names[i].indexOf(M.util.get_string('average', 'report_analytics')) !== -1) {
                    firstIndex = i;
                    break;
                }
            }
            var colors;
            if (firstIndex > 0) {
                colors = baseColors.slice(0, firstIndex).concat(averageColors.slice(0, firstIndex));
            } else {
                colors = baseColors;
            }
            _color = d3.scale.ordinal()
                .domain(names)
                .range(colors.slice(0, names.length));
        };

        /**
         * Gets the y scale that should be used.  In cases where two of the items have
         * large differences in y axis values, the alternate scale should be used.
         *
         * @param  {string}  name   the name of the data being graphed
         * @return {object}  the appropriate d3.scale object to graph the data
         */
        obj._getYScale = function(name) {
            var yScale = _scale.y;
            if (_scale.yAlt !== undefined && (name.indexOf(M.util.get_string('average', 'report_analytics')) !== -1)) {
                yScale = _scale.yAlt;
            }
            return yScale;
        };

        /**
         * Draws a title onto the graph.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @return {object}  current object to permit function chaining
         */
        obj._drawTitle = function(selector) {

            var title = new Title().attributes({x: (options.width - options.margin.left - options.margin.right) / 2});
            d3.select(selector).select('svg g').datum(options.title).call(title);
            return obj;
        };

        /**
         * Draws the legend onto the graph.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @param  {array}   data      data to be plotted on the bar graph
         * @return {object}  current object to permit function chaining
         */
        obj._drawLegend = function(selector, data) {

            if (_color === undefined || _color === null) {
                obj._buildColors(data);
            }

            var legendSpacer = 20;
            var names = data[0].values.map(function(value) { return value.name; });
            var legend = new Legend()
                .height(options.height)
                .color(_color)
                .indent(options.width - options.margin.right + legendSpacer);
            d3.select(selector).datum(names).call(legend);
            return obj;
        };

        /**
         * Getter/setter for the options property.
         *
         * @param  {object}  _x  desired set of options (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.options = function(_x) {
            if (!arguments.length) {
                return options;
            }
            Object.assign(options, _x);
            return this;
        };

        /**
         * Getter/setter for the helpTooltips property.
         *
         * @param  {object}  _x  desired set of helpTooltips (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.helpTooltips = function(_x) {
            if (!arguments.length) {
                return helpTooltips;
            }
            helpTooltips = _x;
            return this;
        };

        d3.rebind(obj, _dispatch, "on");
        return obj;
    };

    /**
     * The SeriesGraph is used to draw a set of line graphs on the page tracking how
     * data changes over time.
     *
     * @return {object}  current object to permit function chaining
     *
     * @module     report_analytics/SeriesGraph
     * @copyright  2016 Craig Jamieson
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    var SeriesGraph = function module() {

        /** @property {object}  axis options {width, height, margin, title} */
        var options = {width: 0, height: 0, margin: {top: 40, right: 160, bottom: 100, left: 60}, title: ''};
        /** @property  {object}  the d3.scale object containing color information */
        var _color = null;
        /** @property  {object}  the various scales used by the graph (see above) */
        var _scale = null;
        /** @property  {function}  allow assignment of "barclick" event that fires when a bar is clicked */
        var _dispatch = d3.dispatch("legendclick");
        /** @property  {function}  the d3.tip function used to control the tooltip */
        var _tooltip = d3.tip()
            .attr('class', 'd3-tip')
            .offset([-TOOLTIPOFFSET, 0])
            .html(function(d) { return "<span style='color:red'>" + d.value + "</span>"; });

        /**
         * Draws the graph on the page.
         *
         * @param  {object}  _selection  a d3js selection upon which to add the graph
         */
        function obj(_selection) {

            _selection.each(function(data) {
                obj._createContainer(this)._drawAxis(this, data)._drawGraph(this, data)._drawTitle(this)._drawLegend(this, data);
            });
        }

        /**
         * Creates the ds.js container to hold the graph (the "svg" element).
         * selector -> svg -> g (indented by margin)
         *
         * Also sets up the tooltip.
         *
         * @param  {object}  selector  d3js selector indentifying the node to add the svg element
         * @return {object}  current object to permit function chaining
         */
        obj._createContainer = function(selector) {

            d3.select(selector).selectAll("*").remove();
            var node = d3.select(selector).append("svg")
                .attr("width", options.width)
                .attr("height", options.height)
                .attr("name", 'graph')
                .append("g")
                    .attr("transform", "translate(" + options.margin.left + "," + options.margin.top + ")");

            node.call(_tooltip);
            return obj;
        };

        /**
         * Wipes out any existing d3.tip that exists.  This lets the graph be re-drawn
         * without the existing tip persisting.
         */
        obj.removeTooltip = function() {
            _tooltip.destroy();
        };

        /**
         * Draws the x and y axis on the page.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @param  {array}   data      data to be plotted on the series graph
         * @return {object}  current object to permit function chaining
         */
        obj._drawAxis = function(selector, data) {

            _scale = obj._createScales(data);
            var innerHeight = options.height - options.margin.top - options.margin.bottom;
            var xAxis = new Axis()
                .scale(_scale.x)
                .attributes({transform: 'rotate(-35)', dx: '-0.8em', dy: '0.15em'})
                .styles({'text-anchor': 'end'})
                .translate({x: 0, y: innerHeight});
            d3.select(selector).select('svg g').call(xAxis);

            var yAxis = new Axis()
                .options({orient: 'left', cssclass: 'y'})
                .scale(_scale.y)
                .translate({x: 0, y: 0})
                .title({name: _scale.yTitle});
            d3.select(selector).select('svg g').call(yAxis);

            return obj;
        };

        /**
         * Setup the d3.scale functions used for the x and y axis.
         *
         * @param  {array}  data  data to be plotted on the bar graph
         * @return {object}  the x and y axis scales
         */
        obj._createScales = function(data) {

            var x = d3.time.scale()
                .range([0, options.width - options.margin.left - options.margin.right])
                .domain([d3.min(data, function(d) { return d3.min(d.values, function(d) { return d.date; }); }),
                            d3.max(data, function(d) { return d3.max(d.values, function(d) { return d.date; }); })]);

            var y = d3.scale.linear()
                .range([options.height - options.margin.top - options.margin.bottom, 0])
                .domain([0, d3.max(data, function(d) { return d3.max(d.values, function(d) { return d.count; }); })]);

            return {x: x, y: y, yTitle: M.util.get_string('events', 'report_analytics')};
        };

        /**
         * Draws the lines for the series graph on the page.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @param  {object}  data      data to be plotted on the bar graph
         * @return {object}  d3.scale object containing color information
         */
        obj._drawGraph = function(selector, data) {

            if (_color === undefined || _color === null) {
                obj._buildColors();
            }

            var focus = d3.select(selector).select('svg g').append("g")
                .style("display", "none");

            var line = d3.svg.line()
                .interpolate("monotone")
                .x(function(d) { return _scale.x(d.date); })
                .y(function(d) { return _scale.y(d.count); });

            var series = d3.select(selector).select('svg g').selectAll(".series")
                .data(data, function(d) { return d.key; })
                .enter().append("g")
                    .attr("class", "series");

            series.append("path")
                .attr("class", "line")
                .attr("id", function(d, i) { return "lineid" + i; })
                .attr("d", function(d) { return line(d.values); })
                .style("stroke", function(d) { return _color(d.key); });

            focus.append("circle")
                .attr("class", "tooltip")
                .style("fill", "none")
                .attr("r", 4);

            var mouseMove = function() {

                _tooltip.hide();
                var coords = d3.mouse(this);
                var indices = obj._getIntersectValues(coords, data, {x: _scale.x, y: _scale.y});
                var d = data[indices.labelIndex].values[indices.xIndex];
                focus.select("circle.tooltip")
                    .style("stroke", _color(data[indices.labelIndex].key))
                    .attr("transform", "translate(" + _scale.x(d.date) + ", " + _scale.y(d.count) + ")");
                obj._showTooltip(this.getBoundingClientRect(), {x: _scale.x, y: _scale.y}, d);
            };

            var mouseOut = function() {

                var coords = d3.mouse(this);
                var width = parseInt(this.getAttribute('width'));
                var height = parseInt(this.getAttribute('height'));
                // Mousing over the tooltip fires a mouseout event.  Only hide tooltip if we've moved off the rect.
                if (coords[0] < 0 || coords[0] > width || coords[1] < 0 || coords[1] > height) {
                    _tooltip.hide();
                    focus.style("display", "none");
                }
            };

            d3.select(selector).select('svg g').append("rect")
                .attr("width", options.width - options.margin.left - options.margin.right)
                .attr("height", options.height - options.margin.top - options.margin.bottom)
                .attr("class", "tooltipgrid")
                .style("fill", "none")
                .style("pointer-events", "all")
                .on("mouseover", function() { focus.style("display", null); })
                .on("mouseout", mouseOut)
                .on("mousemove", mouseMove);

            return obj;
        };

        /**
         * Setup the colors scale.  This graph is quite straightforward, simply use
         * the built in 20 color scale.
         */
        obj._buildColors = function() {
            _color = d3.scale.category20();
        };

        /**
         * Show the tooltip for the value moused over on the graph.  This requires
         * manually setting the html, left, and top values.  The tooltip is designed
         * primarily for bar graphs: the adjustments are needed for line graphs.
         *
         * @param  {object} bb    the bounding box of the graph
         * @param  {object} axis  d3.scale object containing axis information
         * @param  {object} d     the dataset that is moused over {date, count}
         */
        obj._showTooltip = function(bb, axis, d) {

            _tooltip.html("<span style='color:red'>" + d.count + "</span>");
            _tooltip.show();
            var top = bb.top + axis.y(d.count) - parseInt(_tooltip.style('height'), 10) - TOOLTIPOFFSET + window.scrollY;
            var left = bb.left + axis.x(d.date) - parseInt(_tooltip.style('width'), 10) / 2 + window.scrollX;
            _tooltip.style('top', top + 'px');
            _tooltip.style('left', left + 'px');
        };

        /**
         * Gets the intersect values.
         *
         * @param  {object} coords  the d3.mouse coordinates {0 -> x, 1 -> y}
         * @param  {object} data    data plotted on the graph
         * @param  {object} axis    d3.scale object containing axis information
         * @return {object}  the x index and label index in the dataset
         */
        obj._getIntersectValues = function(coords, data, axis) {

            var x0 = axis.x.invert(coords[0]);
            var xIndex = this._findXIndex(data, x0);
            var y0 = axis.y.invert(coords[1]);
            var labelIndex = this._findLabelIndex(data, y0, xIndex);

            return {xIndex: xIndex, labelIndex: labelIndex};
        };

        /**
         * Find the x index that the mouse cursor is closest to.
         *
         * @param  {object}  data  data plotted on the graph
         * @param  {number}  x0    the x value (on a time scale) representing the current cursor position
         * @return {number}  the x index closest to the mouse cursor position
         */
        obj._findXIndex = function(data, x0) {

            var bisectDate = d3.bisector(function(d) { return d.date; }).left;

            var index = bisectDate(data[0].values, x0, 1);
            var xLeft = data[0].values[index - 1];
            var rightIndex = (index >= data[0].values.length) ? data[0].values.length - 1 : index;
            var xRight = data[0].values[rightIndex];
            return (x0 - xLeft.date > xRight.date - x0) ? rightIndex : index - 1;
        };

        /**
         * Find out which of the lines the mouse is closest to by looking at its
         * y coordinate.  The exact x index is passed as a parameter.
         *
         * @param  {object}  data    data plotted on the graph
         * @param  {number}  y0      the y value (# of events) representing the current cursor position
         * @param  {number}  xIndex  the x index of the bin closest to the cursor position
         * @return {number}  { description_of_the_return_value }
         */
        obj._findLabelIndex = function(data, y0, xIndex) {

            var minDistance = null;
            var minIndex = null;

            data.forEach(function(d, i) {
                var distance = Math.abs(y0 - d.values[xIndex].count);
                if (minDistance === null) {
                    minDistance = distance;
                    minIndex = i;
                } else {
                    if (distance < minDistance) {
                        minDistance = distance;
                        minIndex = i;
                    }
                }
            });
            return minIndex;
        };

        /**
         * Draws a title onto the graph.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @return {object}  current object to permit function chaining
         */
        obj._drawTitle = function(selector) {

            var title = new Title().attributes({x: (options.width - options.margin.left - options.margin.right) / 2});
            d3.select(selector).select('svg g').datum(options.title).call(title);
            return obj;
        };

        /**
         * Draws the legend onto the graph.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @param  {array}   data      data to be plotted on the bar graph
         * @return {object}  current object to permit function chaining
         */
        obj._drawLegend = function(selector, data) {

            if (_color === undefined || _color === null) {
                obj._buildColors();
            }

            var names = data.map(function(d) { return {label: d.key, type: d.values[0].type}; });
            var legendSpacer = 20;
            var legend = new Legend()
                .height(options.height)
                .color(_color)
                .indent(options.width - options.margin.right + legendSpacer);
            d3.select(selector).datum(names).call(legend);
            var svg = d3.select(selector).select('svg');
            svg.selectAll('.legend')
                .on("mouseover", function(d, i) {
                    svg.selectAll('.series .line').style("opacity", 0.1);
                    svg.select("#lineid" + i).style("opacity", 1);
                })
                .on("mouseout", function() { svg.selectAll('.series .line').style("opacity", 1); })
                .on('click', _dispatch.legendclick);
            return obj;
        };

        /**
         * Getter/setter for the options property.
         *
         * @param  {object}  _x  desired set of options (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.options = function(_x) {
            if (!arguments.length) {
                return options;
            }
            Object.assign(options, _x);
            return this;
        };

        d3.rebind(obj, _dispatch, "on");
        return obj;
    };

    /**
     * The QuandrantGraph is used to draw a set of quandrant with 4 sections and plot a
     * student's location within the quadrant.  These types of graphs are used to
     * indicate performance or risk.
     *
     * @return {object}  current object to permit function chaining
     *
     * @module     report_analytics/QuadrantGraph
     * @copyright  2017 Craig Jamieson
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    var QuadrantGraph = function module() {

        /** @property {object}  axis options {width, height, margin, title} */
        var options = {width: 0, height: 0, margin: {top: 30, right: 60, bottom: 40, left: 60}, title: '', xTitle: '', yTitle: ''};
        /** @property  {array}  the domain for the x-axis (optional) */
        var _xDomain = null;
        /** @property  {array}  the domain for the y-axis (optional) */
        var _yDomain = null;
        /** @property  {object}  the various scales used by the graph (see above) */
        var _scale = null;
        /** @property {number}  radius of circles on plot */
        var _size = 5;
        /** @property  {function}  the d3.tip function used to control the tooltip */
        var _tooltip = d3.tip()
            .attr('class', 'd3-tip')
            .offset([-TOOLTIPOFFSET, 0])
            .html(function(d) { return "<span style='color:red'>" + d.name + "</span>"; });
        /** @property {number}  minimum squared distanced to display tooltip (17^2) */
        var _minTooltipDistance = 289;

        /**
         * Draws the graph on the page.
         *
         * @param  {object}  _selection  a d3js selection upon which to add the graph
         */
        function obj(_selection) {

            _selection.each(function(data) {
                obj._createContainer(this)._drawAxis(this, data)._drawGraph(this, data)._drawTitle(this);
            });
        }

        /**
         * Creates the ds.js container to hold the graph (the "svg" element).
         * selector -> svg -> g (indented by margin)
         *
         * Also creates the two rect elements (defining the quandrant).
         *
         * @param  {object}  selector  d3js selector indentifying the node to add the svg element
         * @return {object}  current object to permit function chaining
         */
        obj._createContainer = function(selector) {

            d3.select(selector).selectAll("*").remove();
            var node = d3.select(selector).append("svg")
                .attr("width", options.width)
                .attr("height", options.height)
                .attr("name", 'graph')
                .append("g")
                    .attr("transform", "translate(" + options.margin.left + "," + options.margin.top + ")");

            var graphWidth = options.width - options.margin.left - options.margin.right;
            var graphHeight = options.height - options.margin.top - options.margin.bottom;
            var rects = [{x: 0, y: 0, opacity: 0.1, fill: 'green'},
                {x: graphWidth / 2, y: 0, opacity: 0.2, fill: 'green'},
                {x: graphWidth / 2, y: graphHeight / 2, opacity: 0.1, fill: 'red'},
                {x: 0, y: graphHeight / 2, opacity: 0.2, fill: 'red'}];

            node.selectAll("rect")
                .data(rects)
                .enter().append("rect")
                    .style("opacity", function(d) { return d.opacity; })
                    .attr("fill", function(d) { return d.fill; })
                    .attr("width", graphWidth / 2)
                    .attr("height", graphHeight / 2)
                    .attr("transform", function(d) { return "translate(" + d.x + ", " + d.y + ")"; });

            node.call(_tooltip);
            return obj;
        };

        /**
         * Wipes out any existing d3.tip that exists.  This lets the graph be re-drawn
         * without the existing tip persisting.
         */
        obj.removeTooltip = function() {
            _tooltip.destroy();
        };

        /**
         * Draws the x and y axis on the page.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @param  {array}   data      data to be plotted on the series graph
         * @return {object}  current object to permit function chaining
         */
        obj._drawAxis = function(selector, data) {

            var midWidth = (options.width - options.margin.left - options.margin.right) / 2;
            var translate = '(' + parseInt(midWidth) + ', ' + parseInt(options.margin.bottom - 10) + ')';
            _scale = obj._createScales(data);
            var xAxis = new Axis()
                .scale(_scale.x)
                .translate({x: 0, y: _scale.y.range()[0]})
                .title({name: _scale.xTitle, translate: translate, textanchor: 'middle'});
            d3.select(selector).select('svg g').call(xAxis);

            var yAxis = new Axis()
                .options({orient: 'left', cssclass: 'y'})
                .scale(_scale.y)
                .title({name: _scale.yTitle});
            d3.select(selector).select('svg g').call(yAxis);

            obj._drawQuadrants(selector);

            return obj;
        };

        /**
         * Setup the d3.scale functions used for the x and y axis.
         *
         * @param  {array}  data  data to be plotted on the bar graph
         * @return {object}  the x and y axis scales
         */
        obj._createScales = function(data) {

            var xExtent, yExtent;
            if (_xDomain !== undefined && _xDomain !== null) {
                xExtent = _xDomain;
            } else {
                xExtent = d3.extent(data, function(d) { return d.x; });
            }
            var x = d3.scale.linear()
                .domain(padExtent(xExtent))
                .range([0, options.width - options.margin.left - options.margin.right]);

            if (_yDomain !== undefined && _yDomain !== null) {
                yExtent = _yDomain;
            } else {
                yExtent = d3.extent(data, function(d) { return d.y; });
            }
            var y = d3.scale.linear()
                .domain(padExtent(yExtent))
                .range([options.height - options.margin.top - options.margin.bottom, 0]);

            return {x: x, y: y, xTitle: options.xTitle, yTitle: options.yTitle};
        };

        /**
         * Create a set of secondary axes to draw the inner part of the box
         * on the screen.
         *
         * @param  {object}  selector  d3js selector identifying the outermost node
         */
        obj._drawQuadrants = function(selector) {

            var xAxis = d3.svg.axis().scale(_scale.x).orient("bottom").tickPadding(2);
            var yAxis = d3.svg.axis().scale(_scale.y).orient("left").tickPadding(2);
            var xdomain = _scale.x.domain();
            var ydomain = _scale.y.domain();

            var svg = d3.select(selector).select('svg g');
            svg.append("g")
                .attr("class", "qxgrid")
                .call(xAxis.tickFormat("")
                .tickSize(options.width - options.margin.top - options.margin.bottom)
                .tickValues([(xdomain[0] + xdomain[1]) / 2, xdomain[1]]));

            svg.append("g")
                .attr("class", "qygrid")
                .call(yAxis.tickFormat("")
                .tickSize(-(options.width - options.margin.left - options.margin.right))
                .tickValues([(ydomain[0] + ydomain[1]) / 2, ydomain[1]]));
        };

        /**
         * Draws the points (circles) for the graph on the correct quadrant.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @param  {object}  data      data to be plotted on the quadrant graph
         * @return {object}  current object to permit function chaining
         */
        obj._drawGraph = function(selector, data) {

            var node = d3.select(selector).select('svg g').selectAll("g.node")
                .data(data, function(d) { return d.name; })
                .enter().append("g")
                    .attr("class", "node")
                    .attr('transform', function(d) {
                        return "translate(" + _scale.x(d.x) + "," + _scale.y(d.y) + ")";
                    });

            node.append("circle")
                .attr("r", _size)
                .attr("class", "dot")
                .style("fill", "blue");

            // Transparent rectangle for measuring distances.
            d3.select(selector).select('svg g').append("rect")
                .attr("width", options.width - options.margin.left - options.margin.right)
                .attr("height", options.height - options.margin.top - options.margin.bottom)
                .attr("class", "grid")
                .style("fill", "none");

            var mouseOver = function() {

                var gridNode = d3.select(selector).select('.grid').node();
                var mouse = d3.mouse(gridNode);
                var minDistance = _minTooltipDistance;
                var distance, minIndex, minNode;
                node.each(function(d, i) {
                    var point = {x: _scale.x(d.x), y: _scale.y(d.y)};
                    distance = obj._distance2(mouse, point);
                    if (distance < minDistance) {
                        minIndex = i;
                        minNode = this;
                        minDistance = distance;
                    }
                });
                if (minIndex !== undefined) {
                    _tooltip.show(minNode.__data__, minIndex, minNode);
                } else {
                    _tooltip.hide();
                }
            };
            d3.select(selector).select('svg').on("mousemove", mouseOver);

            return obj;
        };

        /**
         * Measures the squared distance from the mouse to a point.
         *
         * @param  {array}   mouse  the mouse position (x = 0, y = 1)
         * @param  {object}  point  the point to measure distance to {x, y}
         * @return {number}  the squared cartesian distanced between the two points
         */
        obj._distance2 = function(mouse, point) {
            var dx = point.x - mouse[0];
            var dy = point.y - mouse[1];
            return dx * dx + dy * dy;
        };

        /**
         * Draws a title onto the graph.
         *
         * @param  {object}  selector  d3js selector indentifying the outermost node
         * @return {object}  current object to permit function chaining
         */
        obj._drawTitle = function(selector) {

            var title = new Title().attributes({x: (options.width - options.margin.left - options.margin.right) / 2});
            d3.select(selector).select('svg g').datum(options.title).call(title);
            return obj;
        };

        /**
         * Getter/setter for the options property.
         *
         * @param  {object}  _x  desired set of options (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.options = function(_x) {
            if (!arguments.length) {
                return options;
            }
            Object.assign(options, _x);
            return this;
        };

        /**
         * Getter/setter for the xDomain property.  If unset, the graph will be
         * automatically calculated using the extent function.
         *
         * @param  {object}  _x  desired set of options (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.xDomain = function(_x) {
            if (!arguments.length) {
                return _xDomain;
            }
            _xDomain = _x;
            return this;
        };

        /**
         * Getter/setter for the yDomain property.  If unset, the grpah will be
         * automatically calculated using the extent function.
         *
         * @param  {object}  _x  desired set of options (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.yDomain = function(_x) {
            if (!arguments.length) {
                return _yDomain;
            }
            _yDomain = _x;
            return this;
        };

        return obj;
    };

    /**
     * The spinner is used as a placeholder while ajax data is being retrieved.
     *
     * @return {object}  current object to permit function chaining
     *
     * @module     report_analytics/Spinner
     * @copyright  2016 Craig Jamieson
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    var Spinner = function module() {

        /** @property {number}  the width of the container */
        var width = 0;
        /** @property {number}  the width of the spinner inside the container */
        var _innerWidth = 240;
        /** @property {number}  the height of the container */
        var height = 0;
        /** @property {number}  the height of the spinner inside the container */
        var _innerHeight = 120;

        /**
         * Render a spinner (for loading) as a placeholder while the chart data is received.
         *
         * @param  {object}  _selection  a d3js selection upon which to add the graph
         */
        function obj(_selection) {

            _selection.each(function() {
                var radius = Math.min(_innerWidth, _innerHeight) / 2;
                var tau = 2 * Math.PI;

                d3.select(this).selectAll("*").remove();

                var arc = d3.svg.arc()
                    .innerRadius(radius * 0.5)
                    .outerRadius(radius * 0.9)
                    .startAngle(0);

                var svg = d3.select(this).append("svg")
                    .attr("width", width)
                    .attr("height", height)
                    .append("g")
                    .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

                svg.append("path")
                    .datum({endAngle: 0.33 * tau})
                    .attr('class', 'spinner eclass-text-stroke')
                    .attr("d", arc)
                    .call(obj._spin, 750);

            });
        }

        /**
         * Rotates the spinner to create the vision that it is spinning.
         *
         * @param  {object}  selection  the spinner node
         * @param  {number}  duration   how frequently to rotate the spinner
         */
        obj._spin = function(selection, duration) {
            selection.transition()
                .ease("linear")
                .duration(duration)
                .attrTween("transform", function() {
                    return d3.interpolateString("rotate(0)", "rotate(360)");
                });

            setTimeout(function() { obj._spin(selection, duration); }, duration);
        };

        /**
         * Getter/setter for the width property.
         *
         * @param  {object}  _x  desired width (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.width = function(_x) {
            if (!arguments.length) {
                return width;
            }
            width = _x;
            return this;
        };

        /**
         * Getter/setter for the height property.
         *
         * @param  {object}  _x  desired height (empty to retrieve)
         * @return {object}  return "this" object to allow for chaining
         */
        obj.height = function(_x) {
            if (!arguments.length) {
                return height;
            }
            height = _x;
            return this;
        };

        return obj;
    };

    /**
     * Polyfill for Object.assign (part of ES6).
     * Taken from mozilla developer docs:
     * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/assign#Polyfill
     */
    if (typeof Object.assign !== 'function') {
        /* jshint unused: false */
        /* eslint-disable no-unused-vars */
        Object.assign = function(target, varArgs) {
            'use strict';
            // TypeError if undefined or null.
            if (target === null) {
                throw new TypeError('Cannot convert undefined or null to object');
            }

            var to = Object(target);

            for (var index = 1; index < arguments.length; index++) {
                var nextSource = arguments[index];

                if (nextSource !== null) { // Skip over if undefined or null.
                    for (var nextKey in nextSource) {
                        // Avoid bugs when hasOwnProperty is shadowed.
                        if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                            to[nextKey] = nextSource[nextKey];
                        }
                    }
                }
            }
            return to;
        };
    }
    /* eslint-enable no-unused-vars */

    /**
     * Pads extent the domain for a QuandrantGraph.  This extends the domain slightly
     * so the graph does not appear right at the edges.
     *
     * @param  {Array}   e  array to pad
     * @param  {number}  p  padding to add to array
     * @return {Array}  the padded array
     */
    function padExtent(e, p) {
        if (p === undefined) {
            p = 1;
        }
        return ([e[0] - p, e[1] + p]);
    }

    return {GroupedBarGraph: GroupedBarGraph, SeriesGraph: SeriesGraph, QuadrantGraph: QuadrantGraph, Spinner: Spinner};
});
