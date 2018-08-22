/*
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2016 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * eLCA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * eLCA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function(window, $, d3, undefined) {

    var elca = (window.elca = window.elca || {});
    elca.charts = (elca.charts = elca.charts || {

        /**
         * Appends a BarChart to the given container
         */
        BarChart: function(container, conf, data) {
            if (!(this instanceof arguments.callee))
                throw Error("Constructor called as a function");
            /**
             * Default config object
             */
            this._defaults = {
                width: 500,
                height: 200,
                margin: {top: 10, right: 20, bottom: 30, left: 40},

                yAxis: {
                    caption: '',
                    refUnit: ''
                }
            };

            /**
             * Properties
             */
            this.conf = {};
            this.width  = null;
            this.height = null;
            this.x = null;
            this.y = null;
            this.xAxis = null;
            this.yAxis = null;
            this.svg = null;

            /**
             * Inits or updates the bar chart with data
             */
            this.updateData = function(data) {

                var self = this;

                this.x.domain(data.map(function(d) { return d.name; }));
                this.y.domain([0, d3.max(data, function(d) { return +d.value; })]);

                this.svg.append("g")
                    .attr("class", "x axis")
                    .attr("transform", "translate(0," + this.height + ")")
                    .call(this.xAxis)
                    .selectAll("text")
                    .style("text-anchor", "end")
                    .attr("dy", "1em")
                    .attr("transform", function(d) {
                        return "rotate(-45)"
                    });

                this.svg.append("g")
                    .attr("class", "y axis")
                    .call(this.yAxis)
                    .append("text")
                    .attr("transform", "rotate(-90)")
                    .attr("y", 6)
                    .attr("dy", ".71em")
                    .style("text-anchor", "end")
                    .text(this.conf.yAxis.caption + ' ' + this.conf.yAxis.wordingIn + ' ' + this.conf.yAxis.refUnit);

                this.svg.selectAll(".bar")
                    .data(data)
                    .enter().append("rect")
                    .attr("class", "bar")
                    .attr("x", function(d) { return self.x(d.name); })
                    .attr("width", this.x.rangeBand())
                    .attr("y", function(d) { return self.y(d.value); })
                    .attr("height", function(d) { return self.height - self.y(d.value); })
                    .on("mouseover", function(d) {
                        var coords = d3.mouse(container);
                        self.tooltip.transition()
                            .duration(200)
                            .style("opacity", .9);

                        self.tooltip.html(d.name.replace(/\n/g, '<br />') + '<br/>' + d.value + " "+ self.conf.yAxis.refUnit)
                            .style("left", coords[0] + "px")
                            .style("top", (coords[1] - 12) + "px");
                    })
                    .on("mouseout", function(d) {
                        self.tooltip.transition()
                            .duration(500)
                            .style("opacity", 0);
                    });

            };

            /**
             * Returns the svg d3 object
             */
            this.getSvg = function() {
                return this.svg;
            };

            /**
             * Inits the chart
             */
            this._init = function(container, conf) {
                // merge defaults and conf
                this.conf = $.extend(true, this.conf, this._defaults, conf);

                // dimensions
                this.width  = this.conf.width  - this.conf.margin.left - this.conf.margin.right,
                this.height = this.conf.height - this.conf.margin.top  - this.conf.margin.bottom;

                // init scales and axis
                this.x = d3.scale.ordinal()
                    .rangeRoundBands([0, this.width], .5);

                this.y = d3.scale.linear()
                    .range([this.height, 0]);

                this.xAxis = d3.svg.axis()
                    .scale(this.x)
                    .orient("bottom");

                this.yAxis = d3.svg.axis()
                    .scale(this.y)
                    .orient("left");

                this.svg = d3.select(container).append("svg")
                    .attr("width", this.width + this.conf.margin.left + this.conf.margin.right)
                    .attr("height", this.height + this.conf.margin.top + this.conf.margin.bottom)
                    .append("g")
                    .attr("transform", "translate(" + this.conf.margin.left + "," + this.conf.margin.top + ")");

                this.tooltip = d3.select(container).append("div")
                    .attr("class", "tooltip")
                    .style("opacity", 0);
            };


            // initialize the chart
            this._init(container, conf);

            if(data) {
                this.updateData(data);
            }
        },

        /**
         * Appends a StackedBarChart to the given container
         */
        StackedBarChart: function(container, conf, data) {
            if (!(this instanceof arguments.callee))
                throw Error("Constructor called as a function");
            /**
             * Default config object
             */
            this._defaults = {
                width: 500,
                height: 200,
                margin: {top: 10, right: 20, bottom: 30, left: 40},

                yAxis: {
                    caption: '',
                    refUnit: '',
                    minValue: 0
                }
            };

            /**
             * Properties
             */
            this.conf = {};
            this.width  = null;
            this.height = null;
            this.x = null;
            this.y = null;
            this.xAxis = null;
            this.yAxis = null;
            this.svg = null;
            this.color = d3.scale.ordinal().range(["#3b486b", "#ff8c00", "#93cd00"]);

            /**
             * Inits or updates the bar chart with data
             */
            this.updateData = function(data) {

                var self = this;

                data.forEach(function(d) {
                    var py0 = 0, ny1 = 0;

                    d.values.forEach(function(i) {

                        if(+i.value >= 0)
                        {
                            i.py0 = py0;
                            i.py1 = py0 += +i.value;
                        } else {
                            i.ny1 = ny1;
                            i.ny0 = ny1 += +i.value;
                        }
                    });
                });

                var minValue = d3.min(data, function(d) { return d3.min(d.values, function(i) { return +i.ny0}); }) * 1.2,
                    maxValue = d3.max(data, function(d) { return d3.max(d.values, function(i) { return +i.py1}); }),
                    dataMap = {};

                this.x.domain(data.map(function(d) { dataMap[d.name] = {info: d.info, cssClass: d.cssClass}; return d.name; }));
                this.y.domain([minValue < 0? minValue : 0, this.conf.yAxis.atLeastMaxValue > maxValue? this.conf.yAxis.atLeastMaxValue : maxValue]);

                this.color.domain(data[0].values.map(function(d) {return d.name; }));
                this.svg.append("g")
                    .attr("class", "x axis")
                    .attr("transform", "translate(0," + this.height + ")")
                    .call(this.xAxis)
                    .selectAll("text")
                    .style("text-anchor", "end")
                    .attr("dy", "1em")
                    .attr("transform", "rotate(-45)")
                    .attr('title', function(d) { return dataMap[d].info })
                    .attr('class', function(d) { return dataMap[d].cssClass; });


                this.svg.append("g")
                    .attr("class", "y axis")
                    .call(this.yAxis)
                    .append("text")
                    .attr("transform", "rotate(-90)")
                    .attr("y", 6)
                    .attr("dy", ".71em")
                    .style("text-anchor", "end")
                    .text(this.conf.yAxis.caption + ' ' + this.conf.yAxis.wordingIn + ' ' + this.conf.yAxis.refUnit);

                this.svg.selectAll(".stack")
                    .data(data)
                    .enter().append("g")
                    .attr("class", "stack")
                    .attr("transform", function(d, i) { return 'translate('+ (self.x(d.name) + self.x.rangeBand() / 2 - 15) +', 0)'; })
                    .selectAll(".bar")
                    .data(function(d) { return d.values; })
                    .enter().append("rect")
                    .attr("class", "bar")
                    .attr("x", "0")
                    .attr("width", '30px') //self.x.rangeBand())
                    .attr("y", function(d) { return self.y(d.value >= 0? d.py1 : d.ny1); })
                    .attr("height", function(d) { return self.y(d.value >= 0? d.py0 : d.ny0) - self.y(d.value >= 0? d.py1 : d.ny1); })
                    .style("fill", function(d) { return d.fill? d.fill : self.color(d.name); })
                    .on("mouseover", function(d) {
                        var coords = d3.mouse(container);
                        self.tooltip.transition()
                            .duration(200)
                            .style("opacity", .9);

                        self.tooltip.html(d.name.replace(/\n/g, '<br />') +"<br/>"+ d.value + " "+ self.conf.yAxis.refUnit)
                            .style("left", coords[0] + "px")
                            .style("top", (coords[1] - 12) + "px");
                    })
                    .on("mouseout", function(d) {
                        self.tooltip.transition()
                            .duration(500)
                            .style("opacity", 0);
                    });


                this.svg.append("line")
                    .attr('class', 'line0')
                    .attr('x1', 0)
                    .attr('x2', this.width)
                    .attr('y1', this.y(0))
                    .attr('y2', this.y(0));
            };

            /**
             * Returns the svg d3 object
             */
            this.getSvg = function() {
                return this.svg;
            };

            /**
             * Inits the chart
             */
            this._init = function(container, conf) {
                // merge defaults and conf
                this.conf = $.extend(true, this.conf, this._defaults, conf);

                // dimensions
                this.width  = this.conf.width  - this.conf.margin.left - this.conf.margin.right,
                this.height = this.conf.height - this.conf.margin.top  - this.conf.margin.bottom;

                // init scales and axis
                this.x = d3.scale.ordinal()
                    .rangeRoundBands([0, this.width], .2);

                this.y = d3.scale.linear()
                    .range([this.height, 0]);

                this.xAxis = d3.svg.axis()
                    .scale(this.x)
                    .orient("bottom");

                this.yAxis = d3.svg.axis()
                    .scale(this.y)
                    .orient("left")
                    .tickFormat(d3.format(".2s"));

                this.svg = d3.select(container).append("svg")
                    .attr("width", this.width + this.conf.margin.left + this.conf.margin.right)
                    .attr("height", this.height + this.conf.margin.top + this.conf.margin.bottom)
                    .append("g")
                    .attr("transform", "translate(" + this.conf.margin.left + "," + this.conf.margin.top + ")");

                this.tooltip = d3.select(container).append("div")
                    .attr("class", "tooltip")
                    .style("opacity", 0);
            };


            // initialize the chart
            this._init(container, conf);


            if(data) {
                this.updateData(data);
            }
        },


        /**
         * Appends a grouped StackedBarChart to the given container
         */
        GroupedStackedBarChart: function(container, conf, data) {
            if (!(this instanceof arguments.callee))
                throw Error("Constructor called as a function");
            /**
             * Default config object
             */
            this._defaults = {
                width: 500,
                height: 200,
                margin: {top: 10, right: 20, bottom: 30, left: 40},

                yAxis: {
                    caption: '',
                    refUnit: ''
                }
            };

            /**
             * Properties
             */
            this.conf = {};
            this.width  = null;
            this.height = null;
            this.x0 = null;
            this.x1 = null;
            this.y = null;
            this.xAxis = null;
            this.yAxis = null;
            this.svg = null;
            this.color = d3.scale.ordinal().range(["#3b486b", "#ff8c00", "#93cd00"]);

            /**
             * Inits or updates the bar chart with data
             */
            this.updateData = function(data) {

                var self = this;

                data.forEach(function(d) {
                    d.groups.forEach(function(g) {
                        var py0 = 0, ny1 = 0;

                        g.stacks.forEach(function(s) {
                            s.py0 = s.py1 = s.ny0 = s.ny1 = 0;

                            if(+s.value >= 0) {
                                s.py0 = py0;
                                s.py1 = py0 += +s.value;

                            } else {
                                s.ny1 = ny1;
                                s.ny0 = ny1 += +s.value;
                            }
                        });

                        g.textY = py0;
                    });
                });

                var minValue = d3.min(data, function(d) { return d3.min(d.groups, function(g) { return d3.min(g.stacks, function(s) { return +s.ny0; }); }); }) * 1.2,
                    maxValue = d3.max(data, function(d) { return d3.max(d.groups, function(g) { return d3.max(g.stacks, function(s) { return +s.py1; }); }); }) * 1.2,
                    dataMap = {};

                this.x0.domain(data.map(function(d) { dataMap[d.name] = {info: d.info, cssClass: d.cssClass}; return d.name; }));
                this.x1.rangeRoundBands([0, this.x0.rangeBand()],.2);
                this.x1.domain(data[0].groups.map(function(d) { return d.name} ));
                this.y.domain([minValue < 0? minValue : 0, maxValue]);

                this.svg.append("g")
                    .attr("class", "x axis")
                    .attr("transform", "translate(0," + this.height + ")")
                    .call(this.xAxis)

                    .selectAll("text")
                    .style("text-anchor", "end")
                    .attr("dy", "1em")
                    .attr("transform", "rotate(-45)")
                    .attr('title', function(d) { return dataMap[d].info })
                    .attr('class', function(d) { return dataMap[d].cssClass; });


                this.svg.append("g")
                    .attr("class", "y axis")
                    .call(this.yAxis)
                    .append("text")
                    .attr("transform", "rotate(-90)")
                    .attr("y", 6)
                    .attr("dy", ".71em")
                    .style("text-anchor", "end")
                    .text(this.conf.yAxis.caption + ' ' + this.conf.yAxis.wordingIn + ' ' + this.conf.yAxis.refUnit);

                var groups = this.svg.selectAll(".group")
                    .data(data)
                    .enter().append("g")
                    .attr("class", "group")
                    .attr("transform", function(d) { return 'translate('+ self.x0(d.name) +', 0)'; });

                var stacks = groups.selectAll(".stack")
                    .data(function(d) { return d.groups; })
                    .enter().append('g')
                    .attr("class", "stack")
                    .attr("transform", function(d) { return 'translate('+ self.x1(d.name) +', 0)'; });


                stacks.selectAll(".bar")
                    .data(function(d) { return d.stacks; })
                    .enter().append("rect")
                    .attr("class", "bar")
                    .attr("x", "0")
                    .attr("width", self.x1.rangeBand())
                    .attr("y", function(d) { return self.y(d.value >= 0? d.py1 : d.ny1); })
                    .attr("height", function(d) { return self.y(d.value >= 0? d.py0 : d.ny0) - self.y(d.value >= 0? d.py1 : d.ny1); })
                    .style("fill", function(d) { return d.fill? d.fill : self.color(d.name); })
                    .on("mouseover", function(d) {
                        var coords = d3.mouse(container);
                        self.tooltip.transition()
                            .duration(200)
                            .style("opacity", .9);

                        self.tooltip.html(d.name.replace(/\n/g, '<br />') +"<br/>"+ d.value + " "+ self.conf.yAxis.refUnit)
                            .style("left", coords[0] + "px")
                            .style("top", (coords[1] - 12) + "px");
                    })
                    .on("mouseout", function(d) {
                        self.tooltip.transition()
                            .duration(500)
                            .style("opacity", 0);
                    });

                stacks.append("text")
                    .attr("x", function(d) { return self.x1.rangeBand() / 2; })
                    .attr("y", function(d) { return self.y(d.textY); })
                    .attr("dx", "-4")
                    .attr("dy", "-5")
                    .text(function(d) { return d.name; });


                this.svg.append("line")
                    .attr('class', 'line0')
                    .attr('x1', 0)
                    .attr('x2', this.width)
                    .attr('y1', this.y(0))
                    .attr('y2', this.y(0));
            };

            /**
             * Returns the svg d3 object
             */
            this.getSvg = function() {
                return this.svg;
            };

            /**
             * Inits the chart
             */
            this._init = function(container, conf) {
                // merge defaults and conf
                this.conf = $.extend(true, this.conf, this._defaults, conf);

                // dimensions
                this.width  = this.conf.width  - this.conf.margin.left - this.conf.margin.right,
                    this.height = this.conf.height - this.conf.margin.top  - this.conf.margin.bottom;

                // init scales and axis
                this.x0 = d3.scale.ordinal()
                    .rangeRoundBands([0, this.width], .2);

                this.x1 = d3.scale.ordinal();

                this.y = d3.scale.linear()
                    .range([this.height, 0]);

                this.xAxis = d3.svg.axis()
                    .scale(this.x0)
                    .orient("bottom");

                this.yAxis = d3.svg.axis()
                    .scale(this.y)
                    .orient("left")
                    .tickFormat(d3.format(".2s"));

                this.svg = d3.select(container).append("svg")
                    .attr("width", this.width + this.conf.margin.left + this.conf.margin.right)
                    .attr("height", this.height + this.conf.margin.top + this.conf.margin.bottom)
                    .append("g")
                    .attr("transform", "translate(" + this.conf.margin.left + "," + this.conf.margin.top + ")");

                this.tooltip = d3.select(container).append("div")
                    .attr("class", "tooltip")
                    .style("opacity", 0);
            };


            // initialize the chart
            this._init(container, conf);


            if(data) {
                this.updateData(data);
            }
        }

    });
}(window, jQuery, d3));
