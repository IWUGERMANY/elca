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
            debug: false,

            /**
             * Update the hash url, if an action element has this css class
             */
            updateHashUrlCssClass: 'page',

            /**
             * Global initialize method. Will be called once per http request
             */
            initialize: function () {
                elca.msgBus.registerHandler('ifcViewer.selection-changed', function (event) {
                    console.debug("Triggering backend");
                    jBlibs.App.query('/ifcViewer/main/elements/?ifcGuid=' + event.guid);
                });
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
                'ifcViewer': {
                    debug: true,

                    // register controller for matching context (selectors, views, urls)
                    urls: /^\/ifcViewer/,

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

                    initialize: function () {
                    },

                    /**
                     * Views will be called on initial http request and for each matching ajax response context
                     */
                    views: {
                        '#viewerContent.ifc-viewer': 'prepareContent',
                    },

                    /**
                     * Per controller initialization method. Will be called once per http request
                     */
                    prepareContent: function ($context) {

                        var self = this;

                        require(['ElcaIfcViewer'], function(ElcaIfcViewer) {

                            var lastedLoadedGuid;

                            var viewer = new ElcaIfcViewer({
                                src: $context.data('src'),
                                debug: true,
                                onLoadedCallback: function (bimSurfer, model) {

                                    bimSurfer.on("selection-changed", function (selected) {
                                        if (selected.objects.length > 0) {

                                            var oid = selected.objects[0];
                                            var guid = viewer.convertOidToGuid(oid);

                                            if (lastedLoadedGuid && lastedLoadedGuid === guid) {
                                                console.log('Current loaded element equals the selected', guid);
                                                return;
                                            }

                                            console.log("Selection changed for " + oid + " ["+ guid + "]");

                                            elca.msgBus.submit('ifcViewer.selection-changed', {
                                                guid: guid
                                            });
                                        }
                                    });
                                }
                            });

                            elca.msgBus.registerHandler('elca.element-loaded', function (message) {

                                if (!message.guid) {
                                    console.error("ifcViewer received an invalid message: message.guid not set", message);
                                    return;
                                }

                                lastedLoadedGuid = message.guid;
                                viewer.viewElement(message.guid);
                            });
                        });
                    }
                }
            }
        });
    });
}
