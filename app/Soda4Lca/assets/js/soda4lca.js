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
                'soda4lca': {
                    // register controller for matching context (selectors, views, urls)
                    urls: /^\/soda4Lca/,


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
                        '#content.soda4lca-database': 'prepareContent',
                        '#processes': 'prepareFilter'
                    },

                    /**
                     * Per controller initialization method. Will be called once per http request
                     */
                    prepareContent: function($context) {
                        this.prepareFilter($context);
                    },

                    prepareFilter: function($context) {
                        $('.soda4lcaReportFilter select', $context).on('change', function() {
                            $(this.form).ajaxSubmit();
                        });
                    }
                }
            }
        });
    });
}
