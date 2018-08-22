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
    $(window).load(function() {

        jBlibs.App.init({
            /**
             * Debug output through console.log
             */
            debug: false,

            /**
             * Update the hash url, if an action element has this css class
             */
            updateHashUrlCssClass: 'page',

            /**
             * Global initialize method. Will be called once per http request
             */
            initialize: function() {
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
                'bnbWater': {
                    // register controller for matching context (selectors, views, urls)
                    urls: /^\/bnb/,


                    /**
                     * The operating context within the current document is #inner.
                     * All controls will only work in this context
                     */
                    controls: {
                        'a:not(.no-xhr):not([rel])': {
                            click: function(e) {
                                return $(this).attr('href');
                            }
                        }
                    },

                    /**
                     * Views will be called on initial http request and for each matching ajax response context
                     */
                    views: {
                        '#content.bnb-water': 'prepareContent',
                        '#content.bnb-4-1-4': 'prepare414'
                    },

                    /**
                     * Per controller initialization method. Will be called once per http request
                     */
                    prepareContent: function($context) {
                        var self = this;

                        $('.collapsible.close').each(function() { self.toggleSection(this, true); });
                        $('.collapsible div.legend', $context).on('click', function(e) {
                            self.toggleSection( $(this).closest('.bnb-section') );
                        });

                        $('.bnb-section .form-section.kguAlt input', $context).on('change', function() {
                            var $self = $(this),
                                $section = $self.closest('.bnb-section');

                            if($self.is(':checked') && $self.val() == 0) {
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
                    },

                    toggleSection: function(section, doNotToggleClass) {
                        var $section = $(section),
                            $legend = $('div.legend', section),
                            $state = $section.find('input.toggle-state');

                        if($('input.changed', $section).length > 0) {
                            $legend.siblings(':not(div.totals)').each(function() {
                                if(!$(this).is('div.headline') && !$(this).find('input.changed,input[type=submit]').length) {
                                    $(this).toggle();
                                }
                            });
                            $legend.css('color', 'red');
                        } else {
                            $legend.siblings(':not(div.totals,div.alt-group,.form-section.kguAlt)').toggle();
                        }

                        if(!doNotToggleClass)
                            $section.toggleClass('close');

                        $state.val($section.hasClass('close')? 1 : 0);
                    },

                    prepare414: function($context) {
                        $('.element-types-container', $context).css('max-height', $(window).height() - $('#content').offset().top - 150);
                    }
                }
            }
        });
    });
}
