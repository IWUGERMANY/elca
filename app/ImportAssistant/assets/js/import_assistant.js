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
                'importAssistant': {
                    // register controller for matching context (selectors, views, urls)
                    urls: /^\/importAssistant/,


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
                        '#content.project-import.import-assistant': 'prepareContent',
                        '#content.admin-mappings': ['prepareContent', 'prepareAdminMappings'],
                        '#content.admin-mapping-edit': 'prepareAdminEditMapping',
                        '#adminMaterialMappings': null,
                        '.import-assistant-mapping-selector': 'prepareAdminEditMapping'
                    },

                    /**
                     * Per controller initialization method. Will be called once per http request
                     */
                    prepareContent: function ($context) {
                        var self = this;

                        $('.tab-item').not('.active').hide();

                        $('.elca-tab a').on('click', function (e) {
                            e.preventDefault();

                            var href = $(this).attr('href');

                            $('.elca-tab').removeClass('active');
                            $(this).closest('.elca-tab').addClass('active');

                            $('.tab-item').hide();
                            $(href).show();

                            $('form input[name="activeTab"]').val($(href).attr('id'));
                            return false;
                        })
                    },

                    prepareAdminMappings: function ($context) {
                        var $adminMaterialMappings = $('#adminMaterialMappings'),
                            me = this;

                        var table = $adminMaterialMappings.DataTable({
                            dom: '<"dt-wrapper"flpBtip>',
                            lengthMenu: [[25, 50, 100], [25, 50, 100]],
                            ajax: {
                                url: "/importAssistant/admin/mappings/json/",
                                dataSrc: "mappings"
                            },
                            columns: [
                                {"data": "materialName"},
                                {"data": "processConfig"},
                                {"data": "epdSubTypes"},
                                {"data": "processDbs"},
                                {"data": "mappingMode"},
                                {
                                    "data": "id",
                                    "orderable": false,
                                    "render": function (data, type, row, meta) {
                                        return '<a class="delete-mapping" href="/importAssistant/admin/mappings/delete/?id=' + data + '">' + $adminMaterialMappings.data('delete-caption') + '</a> | ' +
                                            '<a class="page edit-mapping default" href="/importAssistant/admin/mappings/edit/?id=' + data + '">' + $adminMaterialMappings.data('edit-caption') + '</a>';
                                    }
                                }
                            ],
                            stateSave: true,
                            drawCallback: function (settings, json) {
                                // this is necessary to make the links work with xhr
                                me._prepare($adminMaterialMappings);
                            },
                            autoWidth: false,
                            language: {
                                "lengthMenu": $adminMaterialMappings.data('per-page-caption'), //"Display _MENU_ records per page",
                                "zeroRecords": $adminMaterialMappings.data('zero-records-caption'), // "Nothing found - sorry",
                                "info": $adminMaterialMappings.data('current-page-caption'), //"Showing page _PAGE_ of _PAGES_",
                                "infoEmpty": $adminMaterialMappings.data('zero-records-caption'), //"No records available",
                                "search": $adminMaterialMappings.data('search-caption'),
                                "paginate": {
                                    "first":      $adminMaterialMappings.data('first-caption'),
                                    "last":       $adminMaterialMappings.data('last-caption'),
                                    "next":       $adminMaterialMappings.data('next-caption'),
                                    "previous":   $adminMaterialMappings.data('previous-caption'),
                                },
                                "buttons": {
                                    "selectNone": $adminMaterialMappings.data('select-none-caption'),
                                },
                                select: {
                                    rows: {
                                        1: $adminMaterialMappings.data('selected-row-caption'), // 1: "Only 1 row selected"
                                        0: '', //$adminMaterialMappings.data('no-row-selected-caption'),
                                        _: $adminMaterialMappings.data('selected-rows-caption') //"You have selected %d rows",
                                    }
                                }
                            },
                            select: {
                                style:    'multi',
                                selector: 'td:not(:last-child)'
                            },
                            buttons: [
                                'selectNone',
                                {
                                    name: 'deleteSelected',
                                    text: $adminMaterialMappings.data('delete-selected-caption'),
                                    action: function ( e, dt, node, config ) {
                                        var selected = table.rows ({selected:true}),
                                            data = selected.data().toArray();
                                        var ids = data.map(function(a) {return a.id;});

                                        jBlibs.App.query('/importAssistant/admin/mappings/deleteMultiple/', {
                                            ids: ids
                                        }, {
                                            httpMethod: 'POST'
                                        });
                                    },
                                    enabled: false,
                                },
                            ]
                        });

                        $adminMaterialMappings.on('select.dt deselect.dt', function (e) {
                            if (table.rows({ selected: true }).count() > 0) {
                                table.button(1).enable();
                            }
                            else {
                                table.button(1).disable();
                            }
                        })

                        // $adminMaterialMappings.find('tbody').on('click', 'td:not(:first)', function(e) {
                        //     jBlibs.App.updateHashUrl(
                        //         $(this).parent().find('td > a.default').attr('href')
                        //     );
                        // });
                    },

                    prepareAdminEditMapping: function ($context) {
                        var $siblingMappings = $('#siblingMappings'),
                            $multipleMappings = $('#multipleMappings'),
                            $siblingRatios = $('.siblingRatio', $context);

                        checkModeVisibility();
                        $('#mappingMode input').on('change', function (e) {
                            checkModeVisibility();
                        });

                        var $siblingRatio1 = $('input[name="siblingRatio[0]"]', $context),
                            $siblingRatio2 = $('input[name="siblingRatio[1]"]', $context);

                        $siblingRatio1.on('change', function (e) {
                            $siblingRatio2.val(100 - Math.max(0, Math.min(100, $siblingRatio1.val())));
                        });
                        $siblingRatio2.on('change', function (e) {
                            $siblingRatio1.val(100 - Math.max(0, Math.min(100, $siblingRatio2.val())));
                        });

                        /**
                         * Mark changed input elements and save buttons
                         */
                        $('input:not(.mark-no-change),textarea:not(.mark-no-change),select:not(.mark-no-change)', $context).on('change input', function (e) { // IE needs HTML5 input-Event
                            if ($(this).closest('form.highlight-changes').length < 1)
                                return;

                            $(this).addClass('changed');
                            $(this.form).addClass('changed');
                        });

                        if ($('.changed', $context).length) {
                            $('.changed', $context).eq(0).closest('form.highlight-changes').addClass('changed');
                        }


                        function checkModeVisibility() {
                            var mode = $('#mappingMode input:checked').val();
                            if (mode === '1') {
                                $multipleMappings.hide();
                                $siblingMappings.show();
                                $siblingRatios.show();
                            }
                            else if (mode === '2') {
                                $multipleMappings.show();
                                $siblingMappings.show();
                                $siblingRatios.hide();
                            }
                            else {
                                $multipleMappings.hide();
                                $siblingMappings.hide();
                                $siblingRatios.hide();
                            }
                        }
                    }
                }
            }
        });
    });
}
