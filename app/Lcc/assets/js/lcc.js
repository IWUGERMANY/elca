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
if (typeof jQuery != 'undefined') {
    $(window).load(function () {

        jBlibs.App.init({
            /**
             * Debug output through console.log
             */
            debug: true,

            /**
             * Update the hash url, if an action element has this css class
             */
            updateHashUrlCssClass: 'page',

            /**
             * Global initialize method. Will be called once per http request
             */
            initialize: function () {
            },

            /**
             * Controllers
             */
            controllers: {

                /*************************************************************************************
                 *
                 * This is the default controller, which handles requests
                 * withing /project*, /element* and /process* realms.
                 */
                'lcc': {
                    debug: false,

                    // register controller for matching context (selectors, views, urls)
                    urls: /(^\/lcc|tab=lcc)/,


                    /**
                     * The operating context within the current document is #inner.
                     * All controls will only work in this context
                     */
                    controls: {
                        'a:not(.no-xhr):not([rel])': {
                            click: function (e) {
                                return $(this).attr('href');
                            }
                        }
                    },

                    /**
                     * Views will be called on initial http request and for each matching ajax response context
                     */
                    views: {
                        '#content.lcc': ['prepareContent'],
                        '#tabContent.tab-lcc': 'prepareCostsTable',
                        '#content.lcc-report-summary': 'prepareSummaryReport',
                        '#content.lcc-report-progression': 'prepareProgressionReport',
                        '#tabContent.elca-admin-benchmark-version': null
                    },

                    /**
                     * Per controller initialization method. Will be called once per http request
                     */
                    prepareContent: function ($context) {
                        var self = this;

                        $('.collapsible.close').each(function () {
                            self.toggleSection(this, true);
                        });
                        $('.collapsible div.legend', $context).on('click', function (e) {
                            self.toggleSection($(this).closest('.lcc-section'));
                        });

                        $('.lcc-section .form-section.kguAlt input', $context).on('change', function () {
                            var $self = $(this),
                                $section = $self.closest('.lcc-section');

                            if ($self.is(':checked') && $self.val() == 0) {
                                $section.find('.alt-group').hide();
                                $section.find('.list-group').show();
                            }
                            else {
                                $section.find('.alt-group').show();
                                $section.find('.list-group').hide();
                                $section.removeClass('collapsible');
                                $section.find('div.legend').off('click');
                            }
                        });

                        $('.energy-source-costs')
                            .each(setEnergySourceCosts)
                            .on('change', setEnergySourceCosts);

                        function setEnergySourceCosts() {
                            $(this).parent().siblings('input.refValue').val($('option:selected', this).data('costs'));
                        }

                    },

                    prepareCostsTable: function ($context) {

                        /**
                         * initialize toggle feature
                         */
                        var self = this;
                        $('.element-costs .toggle-link').each(function() {
                            $(this).toggleSection($('.costs-table', $(this).parent()), 'costs-table-visibility', null, self.initHorizontalTableScroll);
                        });

                        $('.element-costs .toggle-link').click(function (e) {
                            $(this).toggleSection($('.costs-table', $(this).parent()), 'costs-table-visibility', e, self.initHorizontalTableScroll);
                        });

                        $('#section-summary .section-toggle').each(function (e) {
                            $(this).click(function (e) {
                                self.initHorizontalTableScroll(!$(this).hasClass('closed'));
                            });

                            self.initHorizontalTableScroll(!$(this).hasClass('closed'));
                        });

                        $('#compositeElementForm .form-section.costs input', $context).each(function() {
                            var self = $(this),
                                replacements = $('.replacements', self.closest('div.element'));

                            if (self.val()) {
                                replacements.show();

                                self.off('focus');
                                self.off('blur');

                            } else {
                                replacements.hide();

                                self.on('focus', function() {
                                    replacements.show();
                                });
                                self.on('blur', function() {
                                    if (!self.val()) {
                                        replacements.hide();
                                    }
                                });
                            }
                        });
                    },

                    initHorizontalTableScroll: function(isOpen) {
                        if (!isOpen)
                            return;

                        var maxWidth = 0;
                        $('.tab-lcc .costs-table-width').each(function() {
                            var $component = $(this);
                            $('.costs-table table', $component).css('width', 'auto');

                            /** Calculate and set natural table container width **/
                            $('.costs-table table', $component).hide();
                            maxWidth = $component.first().outerWidth();

                            /** Reduce by width of fixed columns **/
                            $('.costs-table table tbody tr.firstRow .fixed', $component).each(function() {
                                maxWidth -= $(this).outerWidth();
                            });
                            maxWidth -= maxWidth * 0.03;

                            $('.costs-table table', $component).show();
                            /** disable scroll feature if not need, or fix width of first thead row to center wording **/
                            if ($('.costs-table table', $component).outerWidth() >= maxWidth) {
                                $('.costs-table .scroll-wrapper', $component).addClass('scroll');
                                $('.costs-table .scroll-wrapper', $component).css('width', maxWidth);
                                $('.costs-table table thead .legendRow .lastColumn', $component).css('width', maxWidth);
                                $('.costs-table table', $component).css('width', 'auto');
                            }
                            else
                            {
                                $('.costs-table table', $component).css('width', '98%');
                            }

                        });
                    },

                    toggleSection: function (section, doNotToggleClass) {
                        var $section = $(section),
                            $legend = $('div.legend', section),
                            $state = $section.find('input.toggle-state');

                        if ($('input.changed', $section).length > 0) {
                            $legend.siblings(':not(div.totals)').each(function () {
                                if (!$(this).is('div.headline') && !$(this).find('input.changed,input[type=submit]').length) {
                                    $(this).toggle();
                                }
                            });
                            $legend.css('color', 'red');
                        } else {
                            $legend.siblings(':not(div.totals,div.alt-group,.form-section.kguAlt)').toggle();
                        }

                        if (!doNotToggleClass)
                            $section.toggleClass('close');

                        $state.val($section.hasClass('close') ? 1 : 0);
                    },

                    prepareSummaryReport: function ($context) {
                        var $chart = $('.pie-chart', $context),
                            data = $chart.data('values');

                        var width = 500,
                            height = 500,
                            radius = Math.min(width, height) / 2;

                        var color = d3.scale.ordinal()
                            .range(["#98abc5", "#8a89a6", "#7b6888", "#6b486b", "#a05d56", "#d0743c", "#ff8c00"]);

                        var arc = d3.svg.arc()
                            .outerRadius(radius - 10)
                            .innerRadius(0);

                        var pie = d3.layout.pie()
                            .sort(null)
                            .value(function (d) {
                                return d.value;
                            });

                        var svg = d3.select($chart[0]).append("svg")
                            .attr("width", width)
                            .attr("height", height)
                            .append("g")
                            .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

                        data.forEach(function (d) {
                            d.value = +d.value;
                        });

                        var g = svg.selectAll(".arc")
                            .data(pie(data))
                            .enter().append("g")
                            .attr("class", "arc")
                            .attr("title", function (d) {
                                return d.data.tooltip;
                            });

                        g.append("path")
                            .attr("d", arc)
                            .style("fill", function (d) {
                                return color(d.data.name);
                            });

                        g.append("text")
                            .attr("transform", function (d) {
                                return "translate(" + arc.centroid(d) + ")";
                            })
                            .attr("dy", ".35em")
                            .style("text-anchor", "middle")
                            .text(function (d) {
                                return d.data.name;
                            });
                    },

                    prepareProgressionReport: function ($context) {
                        var $chart = $('.line-chart', $context),
                            series = $chart.data('values');

                        var margin = {top: 20, right: 200, bottom: 30, left: 50},
                            width = $context.innerWidth() * .95 - margin.left - margin.right,
                            height = 400 - margin.top - margin.bottom;

                        var color = d3.scale.category10();

                        $('div.tooltip').remove();
                        var div = d3.select("body").append("div")
                            .attr("class", "tooltip")
                            .style("opacity", 0);

                        var x = d3.scale.linear()
                            .range([0, width]);

                        var y = d3.scale.linear()
                            .range([height, 0]);

                        var xAxis = d3.svg.axis()
                            .scale(x)
                            .orient("bottom");

                        var yAxis = d3.svg.axis()
                            .scale(y)
                            .orient("left");

                        // values
                        var line = d3.svg.line()
                                .x(function (d) {
                                    return x(d.lifeTime);
                                })
                                .y(function (d) {
                                    return y(d.val);
                                }),

                            stepLine = d3.svg.line()
                                .x(function (d) {
                                    return x(d.lifeTime);
                                })
                                .y(function (d) {
                                    return y(d.val);
                                })
                                .interpolate('step-after');

                        var svg = d3.select($chart[0]).append("svg")
                            .attr("width", width + margin.left + margin.right)
                            .attr("height", height + margin.top + margin.bottom)
                            .append("g")
                            .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

                        // data
                        var allVals = [],
                            maxLifeTime = 0;

                        series.forEach(function (item) {
                            item.values.forEach(function (d) {
                                d.lifeTime = +d.lifeTime;
                                d.val = +d.val;
                                allVals.push(d.val);

                                if (maxLifeTime < d.lifeTime) {
                                    maxLifeTime = d.lifeTime;
                                }
                            });
                        });

                        // no data!
                        if (!allVals.length)
                            return;

                        color.domain(series.map(function (d) {
                            return d.name;
                        }));

                        x.domain([0, maxLifeTime]);
                        y.domain(d3.extent(allVals));

                        svg.append("g")
                            .attr("class", "x axis")
                            .attr("transform", "translate(0," + height + ")")
                            .call(xAxis);

                        svg.append("g")
                            .attr("class", "y axis")
                            .call(yAxis)
                            .append("text")
                            .attr("x", 5)
                            .attr("y", 6)
                            .attr("dy", "2px")
                            .style("text-anchor", "start")
                            .text('€');

                        // series
                        var item = svg.selectAll('.item')
                            .data(series)
                            .enter().append('g')
                            .attr('class', 'item');

                        item.append("path")
                            .attr("class", "line")
                            .attr("d", function (d) {
                                if (d.id.search('KGU') === 0) {
                                    return stepLine(d.values)
                                } else {
                                    return line(d.values);
                                }
                            })
                            .style("stroke", function (d) {
                                return color(d.name);
                            });

                        item.append("text")
                            .datum(function (d) {
                                return {name: d.name, value: d.values[d.values.length - 1]};
                            })
                            .attr("transform", function (d) {
                                return d.value ? ("translate(" + x(d.value.lifeTime) + "," + y(d.value.val) + ")") : '';
                            })
                            .attr("x", 10)
                            .attr("dy", ".35em")
                            .text(function (d) {
                                return d.name;
                            });

                        // dots
                        var dot = item.append("g")
                            .attr("class", "dots")
                            .style("fill", function (d) {
                                return color(d.name);
                            });

                        dot.selectAll('circle')
                            .data(function (d) {
                                return d.values;
                            })
                            .enter().append('circle')
                            .attr("r", '3')
                            .attr("cx", function (d, i) {
                                return x(d.lifeTime);
                            })
                            .attr("cy", function (d) {
                                return y(d.val)
                            })
                            .on("mouseover", function (d) {
                                div.transition()
                                    .duration(200)
                                    .style("opacity", .9);
                                div.html(d.lifeTime + ': ' + d.tooltip)
                                    .style("left", (d3.event.pageX) + "px")
                                    .style("top", (d3.event.pageY - 28) + "px");
                            })
                            .on("mouseout", function (d) {
                                div.transition()
                                    .duration(500)
                                    .style("opacity", 0);
                            });
                    }
                }
            }
        });
    });
}
