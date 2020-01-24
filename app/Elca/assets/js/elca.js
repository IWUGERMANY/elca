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
            /**
             * This prevents the caching of the page's content, so
             * hitting back after logout won't show any previously shown content
             * and forces the browser to re-load the page
             * @see http://www.hunlock.com/blogs/Mastering_The_Back_Button_With_Javascript
             */
            window.onunload = window.onbeforeunload = function () {
            };

            /**
             * Use json based cookies in any case
             */
            Cookies.json = true;

            /**
             * Helper for internal links
             * Internal links should not start with a protocol
             */
            $.extend($.expr[':'], {
                internal: function (obj) {
                    return !/^.+?:\/\//.test($(obj).attr('href'));
                }
            });

            $.fn.extend({
                highlightMissingTranslation: function () {
                    return this.each(function () {
                        if (this.nodeType == 3) {
                            this.textContent = this.textContent.replace(/\_\_\*\*/, '');
                            this.textContent = this.textContent.replace(/\*\*\_\_/, '');
                            $(this).wrap('<span class="mi-tr"/>');
                        }
                        else {
                            switch (this.tagName) {
                                case 'INPUT':
                                    $(this).attr('value', $(this).attr('value').replace(/\*\*\_\_/, ''))
                                        .attr('value', $(this).attr('value').replace(/\_\_\*\*/, ''));
                                    $(this).addClass('mi-tr');

                                    break;
                                case 'OPTION':
                                    var clean;
                                    clean = $(this).text().replace(/\*\*\_\_/, '');
                                    clean = clean.replace(/\_\_\*\*/, '');
                                    $(this).html(clean);
                                    $(this).addClass('mi-tr');
                                    $(this).closest('select').addClass('mi-tr');
                                    break;
                                default:
                                    if ($(this).attr('title')) {
                                        $(this).attr('title', $(this).attr('title').replace(/\*\*\_\_/, ''))
                                            .attr('title', $(this).attr('title').replace(/\_\_\*\*/, '')).addClass('mi-tr');
                                    }
                                    else if ($(this).attr('alt')) {
                                        $(this).attr('alt', $(this).attr('alt').replace(/\*\*\_\_/, ''))
                                            .attr('alt', $(this).attr('alt').replace(/\_\_\*\*/, '')).addClass('mi-tr');
                                    }
                                    else if ($(this).attr('label')) {
                                        $(this).attr('label', $(this).attr('label').replace(/\*\*\_\_/, ''))
                                            .attr('label', $(this).attr('label').replace(/\_\_\*\*/, '')).addClass('mi-tr');
                                    }
                                    else
                                        console.log('could not highlight missing translation for [' + this.tagName + ']');
                            }
                        }
                    });
                },

                /**
                 * Feature to toggle visibility of a section by click on other element.
                 * Togglestates are stored in session
                 *
                 * Usage: <a href="" class="toggler">Toggle</a>
                 *        <div class="show-or-hide-me hidden">Show or hide me</div>
                 *
                 *        $('.toggler').toggleSection($('.show-or-hide-me'), 'some-cookie-namespace-name');
                 *        $('.toggler').click(function(event) {
                 *          $(this).toggleSection('.show-or-hide-me'), 'some-cookie-namespace-name', event);
                 *        });
                 *
                 * @param elementToToggle  element to hide or show
                 * @param cookieName       name of cookie where current toggle states get stored
                 * @param clicked          click event if toggler was clicked. If not given function do not toggle
                 *                         section, but set initial state
                 */
                toggleSection: function (elementToToggle, cookieName, clicked, callback) {
                    var toggler = this;
                    var key = elementToToggle.attr('id');
                    if (!key) {
                        console.log('Toggle section failed! Toggler needs id');
                        return;
                    }

                    if (Cookies && !Cookies.get(cookieName))
                        Cookies.set(cookieName, {}, {path: '/'});

                    if (Cookies && typeof Cookies.get(cookieName)[key] == 'undefined') {
                        var initialValue = Cookies.get(cookieName);
                        initialValue[key] = elementToToggle.is(':visible');
                        Cookies.set(cookieName, initialValue, {path: '/'});
                    }

                    var cookieValue = Cookies.get(cookieName);
                    if (!clicked) {
                        if (cookieValue[key]) {
                            toggler.addClass('open');
                            elementToToggle.show();
                        }
                        else {
                            toggler.removeClass('open');
                            elementToToggle.hide();
                        }
                    }
                    else {
                        clicked.preventDefault();
                        toggler.toggleClass('open');
                        elementToToggle.toggle();
                        cookieValue[key] = !cookieValue[key];
                        Cookies.set(cookieName, cookieValue, {path: '/'});
                    }

                    if (callback)
                        callback(toggler.hasClass('open'));
                }
            });


            /**
             * helper to trigger a function, when target element reached bottom of viewport
             */
            $.fn.atEndOfViewport = function (callback, interval) {
                return this.each(function () {
                    var $this = $(this),
                        t = interval || 200;

                    if ($this.visible()) {
                        callback.call($this);
                        return;
                    }

                    var timer = window.setInterval(function () {
                        if ($this.visible()) {
                            window.clearInterval(timer);

                            // check if still in dom
                            if ($this.closest('html').length) {
                                callback.call($this);
                            }
                        }
                    }, t);
                });
            };

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
            'default': {
                debug: false,

                /**
                 * Per controller initialization method. Will be called once per http request
                 */
                initialize: function () {
                    /**
                     * load the main content from hash url
                     * or from the currenturl, except for login
                     */
                    jBlibs.App.query(jBlibs.App.getHashUrl() || document.URL, {_isBaseReq: true});

                    // Highlight missing translations
                    $('body.hl-mi-tr *')
                        .contents()
                        .filter(function () {
                            var regex = /\_\_\*\*(.+?)\*\*\_\_/m;
                            return this.nodeType === 3 && regex.test(this.textContent);
                        }).highlightMissingTranslation();

                },

                onLoad: function (e, xhr, ajaxOpts) {
                    /**
                     * Send hash url along with request
                     */
                    var hashUrl = jBlibs.App.getHashUrl();

                    if (hashUrl) {
                        xhr.setRequestHeader('X-Hash-Url', hashUrl);
                    }
                },

                onSuccess: function (e, xhr, settings, response) {
                    if (typeof(response) !== 'object')
                        return;

                    // extract current projectId from url
                    var result = document.URL.match(/\/projects\/(\d+)/),
                        projectId = null;

                    if (!result)
                        return;

                    projectId = result[1];
                    if (response.projectId == projectId || response.projectId == null || projectId == null)
                        return;

                    if (confirm('Die aktuelle Ansicht ist nicht mehr aktuell! Möglicherweise arbeiten Sie mit mehreren Tabs und in unterschiedlichen Projekten. Wollen Sie zum aktuellen Projekt wechseln?'))
                        window.location.href = '/projects/' + response.projectId + '/';
                },

                onError: function (xhr, status, response) {
                },

                /**
                 * The operating context within the current document is #inner.
                 * All controls will only work in this context
                 */
                controls: {
                    'a:not(.no-xhr):not([rel])': {
                        click: function (e) {
                            if ($(this).hasClass('confirm') && $(this).closest('form.changed').length &&
                                !confirm('Durch diese Aktionen gehen ungespeicherte Änderungen verloren. Möchten Sie wirklich fortfahren?'))
                                return false;

                            return $(this).attr('href');
                        }
                    },
                    'select.lc-select,select.db-select': {
                        change: function (e) {
                            var url = $(this).data('url');
                            return url + '&' + $(this).serialize();
                        }
                    }
                },

                /**
                 * Views will be called on initial http request and for each matching ajax response context
                 */
                views: {
                    '#content,#tabContent,#contentHead': 'prepareContent',
                    '#elcaSheetsContainer,ul.pageable,ul.elements': ['prepareContent', 'preparePageable', 'prepareElementImages', 'prepareFilterTags'],
                    '#content.elca-filter-sheets': ['prepareFilterList', 'preparePageable', 'prepareElementImages', 'prepareFilterTags'],
                    '#tabContent.elements,#tabContent.project-elements': ['prepareElement', 'prepareLifeTimeInput'],
                    '#tabContent.tab-lca,div.process-assignment,div.process-group': 'prepareLcaToggle',
                    '#content.ElcaProcessesCtrl .tab-general': 'prepareProcessConfigGeneral',
                    'div.process-assignment,div.process-group': 'prepareContent',
                    'div.element-section,#element-layers,#element-components,div.element-component,div.element': ['prepareElementComponents', 'prepareLifeTimeInput'],
                    '#content.elements.overview': 'prepareElementOverview',
                    'div.ElcaHtmlProcessConfigSelectorLink-section.refProcessConfigId': ['prepareContent', 'prepareAdminBenchmarkVersion'],
                    '#tabContent.elca-admin-benchmark-version': 'prepareAdminBenchmarkVersion',
                    '#osit': 'highlightMissingTranslations',
                    '#msgBox': null,
                    '#elca-modal-content': 'prepareContent',
                    '#conversions': null,
                    '#projectNavigation': null,
                    '#content.process-databases': 'initTableScroll',
                    '#content.process-sanity': 'prepareProcessSanity',
                    '#processSanityTable': null,
                    '#tabContent.tab-general,#content.svg-patterns,#content.svg-pattern-assignments,ul.svg-pattern-assignments-process-configs': 'prepareSvgPatternSelect',
                    'ul.svg-pattern-assignments-process-configs': 'prepareContent',
                    '#content.svg-pattern-assignments': 'preparePatternAssignments',
                    '#content.project-final-energy': 'prepareFinalEnergy',
                    '#content.report.report-assets': 'prepareElementImages',
                    '#content.report.report-effects-elements': 'prepareElementImages',
                    '#content.report.report-effects': 'prepareDetailInfoToggle',
                    '#content.project.general-default,#content.project.general-create': 'prepareProjectGeneralView',
                    '#content.project-transports,ul.transport-means': 'prepareProjectTransports',
                    'ul.transport-means': 'prepareContent',
                    'div.ElcaHtmlProcessConfigSelectorLink-section.assistant': 'prepareContent',
                    'div.ElcaHtmlElementSelectorLink-section.assistant': 'prepareContent',
                    '#templateElement': ['prepareContent', 'prepareElementImages'],
                    '#tabContent.tab-window-assistant': 'prepareWindowAssistant',
                    '#tabContent.tab-dormer-assistant': 'prepareWindowAssistant',
                    '#tabContent.tab-staircase-assistant': 'prepareStaircaseAssistant',
                    '#tabContent.pillar-assistants': 'preparePillarAssistant',
                    '#subscribeForm': 'prepareSubscribeForm',
                    '#forgotForm': 'prepareForgotForm',
                    '#content.project-search-and-replace-processes': 'prepareSearchAndReplace',
                    '#content.elca-project-life-cycle-usage,#tabContent.tab-lca.elca-admin-benchmark-version': 'prepareLifeCycleUsageCheckboxes',
                    'body.projects': 'prepareContent',
                    '#projectProcessConfigSanity': 'prepareContent',
                    '#content.replace-components, #content.replace-elements': ['prepareReplaceElementComponents', 'prepareElementImages'],
                    '#content.project-csv-import.preview': 'prepareElementImages'
                },

                prepareSearchAndReplace: function ($context) {
                    this.prepareContent($context);

                    $('.header a.all', $context).on('click', function (e) {
                        e.preventDefault();
                        $('input[type="checkbox"]', $(this).closest('fieldset')).attr('checked', 'checked').prop('checked', true);
                        return false;
                    });

                    $('.header a.invert', $context).on('click', function (e) {
                        e.preventDefault();
                        $('input[type="checkbox"]', $(this).closest('fieldset')).each(function () {
                            if ($(this).is(':checked')) {
                                $(this).removeAttr('checked').prop('checked', false);
                            } else {
                                $(this).attr('checked', 'checked').prop('checked', true);
                            }
                        });
                        return false;
                    });
                },

                prepareReplaceElementComponents: function ($context) {
                    var $buttons = $context.find('.buttons'),
                        $checkboxes = $context.find('input[type=checkbox]')
                    ;

                    checkVisibility();
                    $checkboxes.on('change', function () {
                        checkVisibility();
                    });

                    function checkVisibility() {
                        if ($checkboxes.is(':checked')) {
                            $buttons.show();
                        }
                        else {
                            $buttons.hide();
                        }
                    }
                },

                prepareLifeCycleUsageCheckboxes: function ($context) {
                    this.connectLifeCycleUsageCheckboxes($context, 'construction');
                    this.connectLifeCycleUsageCheckboxes($context, 'maintenance');
                },

                connectLifeCycleUsageCheckboxes: function ($context, type) {
                    var $a13 = $('input[name="' + type + '[A1-3]"]', $context),
                        $a1 = $('input[name="' + type + '[A1]"]', $context),
                        $a2 = $('input[name="' + type + '[A2]"]', $context),
                        $a3 = $('input[name="' + type + '[A3]"]', $context),
                        $d = $('input[name$="[D]"]', $context);

                    var allChecked = function () {
                        return $a1.is(':checked') && $a2.is(':checked') && $a3.is(':checked');
                    };
                    var noneChecked = function () {
                        return !$a1.is(':checked') && !$a2.is(':checked') && !$a3.is(':checked');
                    };
                    var onChangeSingleA = function (e) {
                        if ($(e.target).is(':checked')) {
                            if (allChecked()) {
                                $a13.prop('checked', true);
                                $a13.prop('disabled', false);
                                $a1.prop('disabled', true);
                                $a2.prop('disabled', true);
                                $a3.prop('disabled', true);
                            } else {
                                $a13.prop('disabled', true);
                            }
                        } else {
                            if (noneChecked()) {
                                $a13.prop('disabled', false);
                            } else {
                                $a13.prop('checked', false);
                            }
                        }
                    };

                    var checkNonComplianceNotice = function (e) {
                        if ($d.filter(':checked').length > 0) {
                            $('#nonComplianceNotice').removeClass('hidden');
                        }
                        else {
                            $('#nonComplianceNotice').addClass('hidden');
                        }
                    };

                    $a13.on('change', function () {
                        $a1.prop('checked', $(this).prop('checked'));
                        $a2.prop('checked', $(this).prop('checked'));
                        $a3.prop('checked', $(this).prop('checked'));

                        $a1.prop('disabled', $(this).prop('checked'));
                        $a2.prop('disabled', $(this).prop('checked'));
                        $a3.prop('disabled', $(this).prop('checked'));
                    });

                    $a1.on('change', onChangeSingleA);
                    $a2.on('change', onChangeSingleA);
                    $a3.on('change', onChangeSingleA);
                    $d.on('change', checkNonComplianceNotice);
                },

                prepareSubscribeForm: function ($context) {
                    this.prepareContent($context);

                    if (!$('.elca-login.show-subscribe-form').length) {
                        var startLeft = 500;
                        var startWidth = $('.disclaimer').width();

                        $('.login-wrapper').css({
                            width: 250,
                            paddingLeft: 0,
                            position: 'absolute',
                            left: startLeft,
                            top: 0
                        });
                        $('.disclaimer').animate({width: 879}, {
                            duration: 800,
                            easing: 'easeInOutExpo',
                            step: function (step) {
                                $('.login-wrapper').css({left: (startLeft + step - startWidth)});
                            },
                            complete: function () {

                                $('.login-wrapper p.login-only, .login-wrapper .login-form-container').hide();
                                $('.login-wrapper').css({width: 600});
                                $('.subscribe-form-container').show();
                                $('.disclaimer').animate({width: 250}, {
                                    easing: 'easeInOutBack',
                                    duration: 1000,
                                    step: function (step) {
                                        $('.login-wrapper').css({left: (startLeft + step - startWidth)});

                                        if (step < 500)
                                            $('.disclaimer-content-wrapper').css({width: 'auto'});
                                    },
                                    complete: function () {
                                        $('.elca-login').addClass('show-subscribe-form');
                                    }
                                });
                            }
                        });
                    }

                },

                prepareForgotForm: function ($context) {
                    this.prepareContent($context);

                    if (!$('.elca-login.show-forgot-form').length) {
                        var startLeft = 500;
                        var startWidth = $('.disclaimer').width();

                        $('.login-wrapper').css({
                            width: 250,
                            paddingLeft: 0,
                            position: 'absolute',
                            left: startLeft,
                            top: 0
                        });
                        $('.disclaimer').animate({width: 879}, {
                            duration: 800,
                            easing: 'easeInOutExpo',
                            step: function (step) {
                                $('.login-wrapper').css({left: (startLeft + step - startWidth)});
                            },
                            complete: function () {

                                $('.login-wrapper p.login-only, .login-wrapper .login-form-container').hide();
                                $('.login-wrapper').css({width: 250});
                                $('.forgot-form-container').show();
                                $('.disclaimer').animate({width: startWidth}, {
                                    easing: 'swing',
                                    duration: 1000,
                                    step: function (step) {
                                        $('.login-wrapper').css({left: (startLeft + step - startWidth)});
                                    },
                                    complete: function () {
                                        $('.elca-login').addClass('show-forgot-form');
                                    }
                                });
                            }
                        });
                    }

                },

                /**
                 * Highlight missing translations
                 */
                highlightMissingTranslations: function ($context) {
                    $('body.hl-mi-tr *')
                        .not('option')
                        .contents()
                        .filter(function () {
                            var regex = /\_\_\*\*(.+?)\*\*\_\_/m;
                            return this.nodeType === 3 && regex.test(this.textContent);
                        }).highlightMissingTranslation();

                    $('body.hl-mi-tr input[value*="__**"][value*="**__"]').highlightMissingTranslation();

                    $('body.hl-mi-tr option, body.hl-mi-tr optgroup').filter(function () {
                        var regex = /\_\_\*\*(.+?)\*\*\_\_/m;
                        return regex.test($(this).text());
                    }).highlightMissingTranslation();

                    $('body.hl-mi-tr *[title*="__**"][title*="**__"]').highlightMissingTranslation();

                },

                /**
                 * Do something with the current context
                 */
                prepareContent: function ($context) {
                    var self = this;

                    this.prepareSheets($context);
                    this.highlightMissingTranslations($context);

                    $('#languageChooser li a').off('click').on('click', function (e) {

                        var url = $.url(this);
                        var extendedUrl;
                        if (url.param('origin')) {
                            var old = url.attr('source');
                            url = $.url(old.substring(0, old.indexOf('&origin')));
                        }
                        extendedUrl = $.url(url.attr('relative') + '&origin=' + $.base64.encode($.url().attr('relative')));

                        $(this).attr('href', extendedUrl.attr('source'));
                    });


                    /**
                     * Numeric input fields should only allow numbers
                     */
                    $("input.numeric-input", $context).each(function () {
                        var $numIn = $(this);
                        $numIn.numeric({
                            decimal: $numIn.data('decimal'),
                            precision: $numIn.data('precision'),
                            decimalPlaces: $numIn.data('scale'),
                            negative: $numIn.data('negative')
                        });
                    });

                    /**
                     * Support for indeterminate state checkboxes
                     */
                    $('input.indeterminate[type="checkbox"]', $context).each(function () {
                        this.indeterminate = true;
                        var $this = $(this),
                            oldVal = $(this).val();
                        $this
                            .val('indeterminate')
                            .change(function () {
                                $this.val(oldVal)
                            });
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

                    $('form input:text', $context).on('keypress', function (e) {
                        var code = e.charCode || e.keyCode; // use charCode for firefox
                        if (code == 13) {
                            e.preventDefault();
                            return false;
                        }
                    });

                    /**
                     * Handle ajax forms
                     */
                    $('form:not(.no-xhr)', $context).each(function () {
                        var $form = $(this),
                            opts = {
                                success: function (data) {
                                    $form.removeClass('changed');
                                    $('.changed', $form).removeClass('changed');
                                }
                            };

                        /**
                         * IE<10 hack... force json data through iframe
                         */
                        // if($form.attr('enctype') == 'multipart/form-data') { // && $.browser.msie && $.browser.version < 10) {
                        //     opts['iframe'] = true;
                        //     opts['data'] = {"_form2iframe": true}; // this is needed to request ie<10 workaround data
                        //     opts['dataType'] = 'json'; // force datatype to json
                        // }

                        $form.ajaxForm(opts);
                    });

                    /**
                     * Multiselectbox helpers
                     */
                    $('.ElcaHtmlMultiSelectbox-section', $context).each(function () {

                        var $select = $(this);

                        var $links = $('span.select-helpers', $select);

                        if ($select.find('select:disabled').length)
                            return;

                        $links.click(function (e) {
                            e.preventDefault();

                            var rel = $(e.target).attr('rel');
                            if (e.target.tagName != 'A')
                                rel = $(e.target).parent().attr('rel');

                            switch (rel) {
                                case 'all':
                                    $('option', $select).attr('selected', true).prop('selected', true);
                                    break;
                                case 'invert':
                                    $('option', $select).each(function () {
                                        if ($(this).is(':selected')) {
                                            $(this).removeAttr('selected').prop('selected', false);
                                        } else {
                                            $(this).attr('selected', 'selected').prop('selected', true);
                                        }
                                    });
                                    break;
                            }
                            return false;
                        });
                    });

                    $('#section-layers a.show-dimension-icon').click(function (e) {
                        e.preventDefault();
                        $('#section-layers').addClass('show-dimension');
                    });
                    $(':input', '#section-layers .element-component .length').each(function () {
                        if ($(this).val() != 1) {
                            $('#section-layers').addClass('show-dimension');
                        }
                    });
                    $(':input', '#section-layers .element-component .width').each(function () {
                        if ($(this).val() != 1) {
                            $('#section-layers').addClass('show-dimension');
                        }
                    });

                    $('.select-text', $context).on('click', function() {
                        var range, selection,
                            valueNode = $('.selection-value', this).get(0);

                        if (window.getSelection && document.createRange) {
                            selection = window.getSelection();
                            range = document.createRange();
                            range.selectNodeContents(valueNode);
                            selection.removeAllRanges();
                            selection.addRange(range);
                        } else if (document.selection && document.body.createTextRange) {
                            range = document.body.createTextRange();
                            range.moveToElementText(valueNode);
                            range.select();
                        }
                    });

                    /**
                     * Scroll to top
                     */
                    if ($context.is('#content'))
                        jBlibs.App.scrollTo($context);

                },

                prepareSheets: function ($context) {
                    /**
                     * Map main functions on sheets to onclick event
                     */
                    $('div.elca-sheet:not(".active") div.elca-sheet-content', $context).click(function (e) {
                        var $defaultLink = $('div.function-panel a.default', $(this).parent());

                        if ($defaultLink.hasClass('no-xhr'))
                            window.location.href = $defaultLink.attr('href');
                        else
                            $defaultLink.click();
                    });
                },

                prepareSortable: function ($context) {
                    /**
                     * Sortable
                     */
                    $("ol.sortable").sortable({
                        handle: 'div.drag-handle',
                        axis: 'y',
                        opacity: 0.8,
                        containment: 'div.element-section',
                        placeholder: 'drag-placeholder',
                        items: 'li.sortable-item',
                        update: function (e, ui) {
                            var itemIds = [];
                            $('li', this).each(function () {
                                itemIds.push($(this).attr('id'));
                            });

                            jBlibs.App.query($(this).data('sort-handler-url'), {
                                elementId: $(this).data('element-id'),
                                positions: itemIds,
                                startIndex: $(this).data('start-index')
                            }, {
                                httpMethod: 'POST'
                            });
                        }
                    });
                },

                prepareElement: function ($context) {
                    this.prepareSortable($context);
                    this.prepareElementSectionToggle($context);
                    this.prepareElementImages($context, true);

                    this.prepareCompositeElement($context);
                },

                prepareCompositeElement: function ($context) {
                    $('#compositeElementForm', $context).each(function () {
                        var opaqueArea = $('input[name="opaqueArea"]', this).val(),
                            $refreshButton = $('input[name="refreshOpaqueElements"]', this);

                        $refreshButton.hide();

                        $('#element-composite .quantity input', this).each(function () {
                            if ($(this).val() !== opaqueArea) {
                                $refreshButton.show();

                                return false;
                            }
                        });
                    });
                },

                prepareElementImages: function ($context, overlayOnClick) {
                    var me = this,
                        $images = $('.element-image:not(.embedded)', $context),
                        len = $images.length,
                        $progressElt = $('#progress'),
                        $printButton = $('div.print.button a', $context);

                    if (len > 0) {
                        $printButton.fadeTo('fast', 0.1);
                    } else {
                        window.status = 'ready_to_print';
                    }

                    $images.each(function (i) {
                        var $imgContainer = $(this),
                            load = 0;

                        if ($.browser.msie && $.browser.version < 9) {
                            $imgContainer.append('<p class="not-supported">Leider wird die Bauteilgrafik nicht vom Internet Explorer Version 8 oder kleiner unterstützt.</p>');
                            return;
                        }

                        var containerId = $imgContainer.data('container-id');

                        if (!containerId) {
                            containerId = $imgContainer.data('element-id');
                        }

                        if ($imgContainer.is(':empty')) {
                            $imgContainer.append('<div id="element-image-' + containerId + '"></div>');
                        }

                        if (overlayOnClick) {
                            $imgContainer.off('click').click(function (e) {
                                if ($('div', this).is(':empty'))
                                    return;

                                jBlibs.App.query($imgContainer.data('url'), {
                                    elementId: $imgContainer.data('element-id'),
                                    w: 950,
                                    h: 700,
                                    m: true
                                });
                            });
                        }

                        // relax loading
                        window.setTimeout(function () {
                            jBlibs.App.query($imgContainer.data('url'), {
                                elementId: $imgContainer.data('element-id'),
                                w: $imgContainer.innerWidth(),
                                h: $imgContainer.innerHeight(),
                                c: containerId
                            });

                            if (len >= 1) {
                                load = (i + 1) / len;
                                $progressElt.html('Bauteilgrafiken werden geladen ' + Math.round(load * 100) + '%');

                                if (load === 1) {
                                    $printButton.fadeTo('normal', 1);

                                    window.setTimeout(function () {
                                        $progressElt.fadeOut('fast');
                                        window.status = 'ready_to_print';
                                    }, 1000);
                                }
                                else {
                                    $progressElt.fadeIn();
                                }
                            }
                        }, i * 200);
                    });
                },

                prepareLifeTimeInput: function ($context) {

                    /**
                     * special life time element with hover and focus overlay
                     */
                    $('div.ElcaHtmlLifeTimeInput-section', $context).each(function () {
                        var $el = $(this),
                            $input = $('input.numeric-input', this),
                            $lifeTimeInfoField = $('input[type=text]', this),
                            $radios = $('input[type=radio]', this),
                            values = [];

                        $radios.each(function () {
                            if (!$(this).data('has-text-input')) {
                                values.push($(this).attr('value'));
                            }
                        });

                        $radios.on('change', function (e) {
                            var value = $(this).val();

                            if (value != $input.val()) {
                                $input.val(value);
                                $input.addClass('changed')
                            }
                        });

                        $lifeTimeInfoField.on('blur', function (e) {
                            if ($(this).val() !== '') {
                                $input.addClass('changed')
                            }
                        });

                        $input
                            .on('focus', function (e) {
                                $el.addClass('active');
                            })
                            .on('blur', function (e) {

                                window.setTimeout(function () {
                                    $el.removeClass('active');
                                }, 200);
                            })
                            .on('change', function (e) {
                                var pos = $.inArray($(this).val(), values),
                                    result = pos !== -1 ? result : values.length;

                                $radios
                                    .prop('checked', false)
                                    .attr('checked', false);

                                $radios
                                    .eq(result)
                                    .prop('checked', true);

                                if (pos !== -1) {
                                    $radios.focus();
                                } else {
                                    $lifeTimeInfoField.focus();
                                }
                            });

                        if ($el.hasClass('error')) {
                            $el.addClass('active');
                        }


                    });

                    /**
                     * lifeTimeDelay is sensitive to isExtant
                     */
                    $('.form-section.isExtant input', $context).change(function () {

                        var $this = $(this),
                            $elementComponent = $this.closest('.element-component'),
                            $lifeTimeDelayElt = $elementComponent.find('.lifeTimeDelay input');

                        if ($this.is(':checked')) {
                            $lifeTimeDelayElt
                                .removeAttr('disabled')
                                .removeAttr('readonly')
                                .removeClass('read-only');

                            $elementComponent.addClass('is-extant');
                        } else {

                            $lifeTimeDelayElt.val(0);
                            $lifeTimeDelayElt
                                .attr('readonly', 'readonly')
                                .attr('disabled', 'disabled')
                                .addClass('read-only');

                            $elementComponent.removeClass('is-extant');
                        }
                    });

                    $('.element-component a.show-lifeTimeDelay-icon', $context).click(function (e) {
                        e.preventDefault();
                        $(this).closest('.element-component').addClass('show-lifeTimeDelay');
                    });

                    $('.element-component .lifeTimeDelay :input', $context).each(function () {
                        if ($(this).val() != 0) {
                            $(this).closest('.element-component').addClass('show-lifeTimeDelay');
                        }
                    });
                },

                prepareElementOverview: function ($context) {
                    $('#elementImportLink').on('click', function (e) {
                        e.preventDefault();
                        $('#importElements').toggle();
                        $(this).toggleClass('open');
                        return false;
                    });
                },
                prepareElementComponents: function ($context) {
                    /**
                     * Focus on element size or quantity if its value is empty
                     */
                    $('.form-section.size .form-elt input, .form-section.quantity .form-elt input', $context).each(function () {
                        var $elt = $(this);
                        if ($elt.length == 1 && $elt.val() === '') {
                            $elt.focus();
                            return false;
                        }
                    });

                    /**
                     * Prepare content
                     */
                    this.prepareContent($context);
                    this.prepareSortable($context);
                    this.prepareElementSectionToggle($context);

                    this.prepareCompositeElement($context);
                },

                /**
                 * Prepare sections toggle
                 */
                prepareElementSectionToggle: function ($context) {
                    var me = this;
                    if ($context.is('.element-section')) {
                        me.addSectionToggle($('.legend', $context));
                    }
                    else {
                        $('.element-section .legend', $context).each(function () {
                            me.addSectionToggle($(this));
                        });
                    }
                },

                addSectionToggle: function ($legend) {
                    var context = $legend.closest('div.element-section').attr('id'),
                        cookieName = 'elca.section-' + context,
                        $toggler = $('<div class="section-toggle"></div>');

                    $legend.after($toggler);

                    if (Cookies && Cookies.get(cookieName)) {
                        $toggler.addClass('closed');
                        $toggler.nextAll().hide();
                    }

                    $toggler.click(function (e) {
                        $toggler.toggleClass('closed');
                        $toggler.nextAll().toggle();

                        if (!Cookies)
                            return;

                        Cookies.set(cookieName, $toggler.hasClass('closed'));
                    });
                },

                prepareProcessConfigGeneral: function ($context) {

                    var $checkbox = $('.form-section.opAsSupply input', $context),
                        fnToggle = function (opAsSupply) {
                            if (opAsSupply) {
                                $('.form-section.opInvertValues', $context).show();
                            } else {
                                $('.form-section.opInvertValues', $context).hide();
                            }
                        };

                    if (!$checkbox.length)
                        return;

                    fnToggle($checkbox.is(':checked'));

                    $checkbox.change(function () {
                        fnToggle($checkbox.is(':checked'));
                    });
                },

                prepareLcaToggle: function ($context) {
                    $('div.toggle-link', $context).each(function () {

                        var lc = $(this).data('lc'),
                            cookieName = 'elca.lcaToggle.' + lc;

                        if (!lc)
                            return;

                        if (Cookies && !Cookies.get(cookieName)) {
                            $(this).removeClass('open');
                            $(this).nextAll('table').hide();
                        }

                        $(this).off('click').on('click', function (e) {
                            $(this).toggleClass('open');
                            $(this).nextAll('table').toggle();
                            Cookies.set(cookieName, $(this).hasClass('open'));
                        });
                    });
                },

                prepareFilterList: function ($context) {
                    /**
                     * list search filter
                     */
                    var keyTimeout,
                        minCharCount = 3,
                        $input = $('input.list-search', $context),
                        $hint = $('<span class="hint">Mindestens ' + minCharCount + ' Zeichen eingeben!</span>'),
                        loading = false;

                    $hint.hide();
                    $input.parent().append($hint);
                    $input
                        .attr('autocomplete', 'off')
                        .on('focus', function () {
                            // save old value if it gets focus
                            $input.data('prevVal', $input.val());
                        })
                        .off('change').on('change', function (e) {
                        if (loading) return;

                        if ($input.val().length > minCharCount - 1) {
                            $hint.fadeOut('fast');

                            $(this.form).ajaxForm({
                                success: function (data, $form) {
                                    loading = false;
                                }
                            });

                            loading = true;
                            $input.closest('form').submit();
                        }
                        else {
                            $hint.fadeIn('fast');
                        }
                    })
                        .on('input propertychange', function (e) {
                            window.clearTimeout(keyTimeout);
                            keyTimeout = window.setTimeout(function () {
                                $input.change();
                            }, 500);

                            var inputLen = $input.val().length,
                                prevInputLen = $input.data('prevVal').length;

                            if (!loading && inputLen < 1 && prevInputLen != 0) {
                                // reload list when field gets empty
                                $input.closest('form').submit();
                            }

                            $input.data('prevVal', $input.val());
                        });

                    $('.filter-form select,.filter-form input:not(.list-search)', $context).on('change', function () {
                        $(this.form).submit();
                    });
                },

                prepareFilterTags: function ($context) {
                    $('.filter-tags a.remove-filter[rel="reset"]', $context).on('click', function(e) {
                        e.preventDefault();

                        var cssClass = $(this).parent().attr('class'),
                            tag = $(this).parent().find('.tag-content').text();

                        if (cssClass !== 'keyword') {
                            var $filterControl = $('.filter-form [name='+ cssClass +']');

                            if ($filterControl.is('select')) {
                                if (!$filterControl.is('[disabled]')) {
                                    $filterControl.val('');
                                }
                            }
                            else if ($filterControl.is('input[type=radio]')) {
                                $filterControl.prop('checked', false);
                                $filterControl.first().prop('checked', true);
                            }
                        }
                        else {
                            var $search = $('.filter-form input.list-search');
                            $search.val($search.val().replace(tag, '').trim());
                            $(this).parent('.keyword').remove();
                        }

                        $('.filter-form').submit();
                        return false;
                    });
                },

                preparePageable: function ($context) {
                    var $ul;
                    if ($context.is('.pageable')) {
                        $ul = $context;
                    } else {
                        $ul = $('ul.pageable', $context);
                    }

                    var nextPageId = $ul.data('next-page-id'),
                        nextPageClass = $ul.data('next-page-class'),
                        nextPageUrl = $ul.data('next-page-url');

                    $ul.after('<ul id="' + nextPageId + '" class="' + nextPageClass + ' pageable"></ul>');

                    $('li a.next-page', $context).atEndOfViewport(function () {
                        var $item = this;
                        $item.addClass('loading');
                        jBlibs.App.query(nextPageUrl, null, {
                            complete: function () {
                                $item.parent('li').remove();
                            }
                        });
                    });
                },

                initTableScroll: function ($context) {
                    var winWidth = $(window).width(),
                        winHeight = $(window).height(),
                        offset = $context.offset();

                    $('table', $context).tableScroll({
                        height: winHeight - offset.top - 150
                    });
                },

                prepareSvgPatternSelect: function ($context) {
                    $('div.elca-svg-pattern-select', $context).each(function () {
                        var $bgImage = $('span.image', this),
                            img = new Image(),
                            $img = $(img),
                            $imgContainer = $('.cell.select', this),
                            $opt = $('option:selected', this);

                        $imgContainer.css('height', $imgContainer.height());

                        setPatternImage($bgImage, img, $opt, $imgContainer.width(), $imgContainer.height());

                        $(this).change(function (e) {
                            setPatternImage($bgImage, img, $('option:selected', this), $imgContainer.width(), $imgContainer.height());
                        })
                    });

                    function setPatternImage($bgImage, img, $opt, maxWidth, maxHeight) {
                        $(img).load(function (e) {
                            $bgImage.css('background-image', 'url(' + $opt.data('svg-pattern-url') + ')');

                            if (img.naturalHeight > img.naturalWidth) {
                                $bgImage.css('background-size', '80% auto');
                            } else {
                                $bgImage.css('background-size', 'auto 80%');
                            }
                        });
                        img.src = $opt.data('svg-pattern-url');
                    }
                },

                preparePatternAssignments: function ($context) {

                    // handle toggles
                    $('li > .form-section .label-holder', $context).click(function () {
                        var $labelHolder = $(this),
                            $li = $labelHolder.parent().parent();

                        $li.toggleClass('open');

                        if ($li.data('url') && !$li.data('loaded')) {
                            var data = {},
                                $changedSelect = $('select.changed', $labelHolder.siblings('.ElcaHtmlSvgPatternSelect'));

                            if ($changedSelect.length) {
                                data['nodeChangedToPatternId'] = $changedSelect.val();
                            }

                            jBlibs.App.query($li.data('url'), data, {
                                complete: function () {
                                    $li.data('loaded', true);
                                }
                            });
                        } else {
                            jBlibs.App.query('/elca/admin-svg-patterns/toggleCategory/', {
                                nodeId: $li.data('id'),
                                state: $li.hasClass('open') ? 1 : 0
                            });
                        }
                    });

                    // handle pattern select changes
                    $('ul.level2 > li', $context).each(function () {
                        var $li = $(this);

                        $('.categoryPatternId select', this).change(function (e) {
                            var $srcSelect = $(this);
                            $('.configPatternId select', $li).each(function () {
                                var $dstSelect = $(this);

                                if (!$dstSelect.data('orig-value')) {
                                    $dstSelect
                                        .val($srcSelect.val())
                                        .change();
                                }
                            });
                        });
                    });
                },

                prepareFinalEnergy: function ($context) {
                    /**
                     * Focus on first input element
                     */
                    $('.final-energy-row.new .form-section:first input', $context).each(function () {
                        var $elt = $(this);
                        if ($elt.length == 1 && $elt.val() === '') {
                            $elt.focus();
                            return false;
                        }
                    });

                    /**
                     * toggle process-database tables
                     */
                    $('.toggle-link', $context).each(function () {
                        $(this).click(function (e) {
                            console.log('click');
                            var $parent = $(this).parent().attr('id'),
                                $table = $('#' + $parent + ' .results');
                            if ($table.is(':hidden')) {
                                $('#' + $parent + ' .toggle').val(1);
                                $(this).addClass('open');
                                $table.show();
                            }
                            else {
                                $('#' + $parent + ' .toggle').val(0);
                                $(this).removeClass('open');
                                $table.hide();
                            }
                            return false;
                        });
                    });

                    /**
                     * set toggled process-database tables visible
                     */
                    $('.toggle', $context).each(function () {
                        var $parent = $(this).parent().attr('id'),
                            $table = $('#' + $parent + ' .results');

                        if ($(this).val() == 1) {
                            $('#' + $parent + ' .toggle-link').addClass('open');
                            $table.show();
                        }
                    });

                    $('form', $context).ajaxForm({
                        beforeSerialize: function ($form, opts) {

                            opts.data = {
                                ngf: $('#enEvNgfAndVersion .ngf input').val(),
                                enEvVersion: $('#enEvNgfAndVersion .enEvVersion input').val()
                            };
                        }
                    });
                },

                prepareDetailInfoToggle: function ($context) {
                    $('div.element-details-wrapper h3', $context).click(function () {
                        var $header = $(this),
                            $elementDetails = $header.next('.element-details');

                        // load data
                        if (!$header.data('loaded')) {
                            $header.addClass('loading');
                            jBlibs.App.query($header.data('url'), null, {
                                complete: function () {
                                    $header.removeClass('loading');
                                    $header.data('loaded', true);
                                }
                            });
                        }

                        // toggle visibility
                        $header.toggleClass('open');
                        $elementDetails.toggle();
                    });
                },

                prepareAdminBenchmarkVersion: function ($context) {
                    $('#adminBenchmarkVersionForm input:radio[name=useReferenceModel]', $context).on('change', function (e) {
                        $(this.form).submit();
                    });

                    $('a[rel=clearFields]', $context).on('click', function (e) {
                        e.preventDefault();
                        $('.indicator-values input[value!=""]', $context)
                            .val(null)
                            .change();
                        return false;
                    });
                    $('a[rel=useDefaults]', $context).on('click', function (e) {
                        e.preventDefault();
                        $('.indicator-values input', $context).each(function () {
                            var $this = $(this);

                            if ($this.data('default') != undefined && $this.val() != $this.data('default')) {
                                $this
                                    .val($this.data('default'))
                                    .change();
                            }
                        });
                        return false;
                    });

                    if ($context.is('.form-section.ElcaHtmlProcessConfigSelectorLink-section.changed')) {
                        $('#adminBenchmarkVersionForm').addClass('changed');
                    }

                    $('.HtmlMultiSelectbox-section select', $context).selectize({
                            create: false,
                            allowEmptyOption: false,
                            closeAfterSelect: false,
                            plugins: [{
                                name: 'remove_button',
                                options: {
                                    'className': 'no-xhr',
                                }
                            }]
                        });

                    $('#adminBenchmarksForm .input-container span.use-computed-value', $context).on('click', function (e) {
                        var $input = $(this).siblings('input');
                        $input.val($input.data('computed-value'));
                    });
                },

                prepareProjectGeneralView: function ($context) {
                    var $processDbSelect = $('.fieldset:not(.read-only) #selectProcessDb'),
                        $benchmarkVersionSelect = $('#selectBenchmarkSystem'),
                        $constrClassSelect = $('select[name=constrClassId]'),
                        $selectedConstrClassOption = $('option:selected', $constrClassSelect),
                        $constrClassOptions = $('option', $constrClassSelect).detach(),
                        $projectLifeTime = $('input[name=lifeTime]', $context),
                        $livingSpaceElt = $('.form-section.livingSpace', $context),
                        processDbIdOrig = $processDbSelect.val(),
                        processDbId = null;

                    $constrClassSelect.change(function() {
                        $selectedConstrClassOption = $('option:selected', $constrClassSelect);
                    });

                    updateProcessDb();
                    $benchmarkVersionSelect.change(function () {
                        if (updateProcessDb()) {
                            if (processDbId == processDbIdOrig) {
                                $processDbSelect.removeClass('changed');
                            } else {
                                $processDbSelect.addClass('changed');
                            }
                        }
                    });

                    function updateProcessDb() {
                        var $selectedVersion = $('option:selected', $benchmarkVersionSelect),
                            processDbId = $selectedVersion.data('process-db-id'),
                            constrClassIds = $selectedVersion.data('constr-class-ids') || [],
                            displayLivingSpace = parseInt($selectedVersion.data('display-living-space')),
                            projectLifeTime = $selectedVersion.data('project-life-time');

                        if ($selectedConstrClassOption.length === 0) {
                            $selectedConstrClassOption = $('option:first', $constrClassSelect);
                        }

                        if (processDbId) {
                            $processDbSelect
                                .val(processDbId)
                                .attr('disabled', 'disabled')
                                .addClass('read-only');
                        } else {
                            $processDbSelect
                                .removeAttr('disabled')
                                .removeClass('read-only');
                        }

                        if (constrClassIds.length > 0) {
                            $constrClassSelect.empty().append($constrClassOptions.filter(function (index) {
                                return $.inArray(parseInt($(this).attr('value')), constrClassIds) !== -1;
                            }));

                            if ($('option[value='+$selectedConstrClassOption.val()+']', $constrClassSelect).length) {
                                $constrClassSelect.val($selectedConstrClassOption.val());
                            }
                            else {
                                $constrClassSelect.val($('option:first', $constrClassSelect).val());
                                $selectedConstrClassOption = $('option:selected', $constrClassSelect);
                                $constrClassSelect.addClass('changed');
                            }
                        }
                        else {
                            $constrClassSelect.empty().append($constrClassOptions);
                            $constrClassSelect.val($selectedConstrClassOption.attr('value'));
                        }

                        var $livingSpaceInput = $livingSpaceElt.find('input');
                        if (displayLivingSpace) {
                            if (!$selectedVersion.val()) {
                                $livingSpaceElt.find('span.required').hide();
                            }
                            else {
                                $livingSpaceElt.find('span.required').show();
                            }

                            $livingSpaceElt.removeClass('hidden');
                            $livingSpaceInput
                                .removeClass('read-only')
                                .prop('readonly', false);
                        }
                        else {
                            $livingSpaceElt.addClass('hidden');
                            $livingSpaceInput
                                .addClass('read-only')
                                .prop('readonly', true);
                        }

                        if (projectLifeTime) {
                            if ($projectLifeTime.val() != projectLifeTime) {
                                $projectLifeTime.addClass('changed');
                            }

                            $projectLifeTime
                                .val(projectLifeTime)
                                .addClass('read-only')
                                .prop('readonly', true);

                        } else {
                            $projectLifeTime
                                .removeClass('read-only')
                                .prop('readonly', false);

                            if (!$projectLifeTime.val()) {
                                $projectLifeTime
                                    .val(50)
                                    .addClass('changed');
                            }
                        }

                        return processDbId;
                    }
                },

                prepareWindowAssistant: function ($context) {
                    $('a[rel=toggle-mullion-transom-details]', $context).on('click', function (e) {
                        e.preventDefault();

                        $('.mullions-transoms-dims', $context).toggle('hide');
                        $(this).toggleClass('closed');

                        return false;
                    });

                    $('.hasTopLight input', $context).on('change', function () {
                        if ($(this).is(':checked')) {
                            $('.topLightHeight input', $context)
                                .removeAttr('readonly')
                                .removeAttr('disabled')
                                .removeClass('read-only');
                        } else {
                            $('.topLightHeight input', $context)
                                .attr('readonly', 'readonly')
                                .addClass('read-only');
                        }
                    })
                },

                prepareStaircaseAssistant: function ($context) {

                    // construction type
                    var $typeElts = $('input[name="type"]', $context);
                    showConstruction($typeElts.filter(':checked').val());
                    updateTrapezoidElements();

                    $typeElts.on('change', function (e) {
                        showConstruction($typeElts.filter(':checked').val());
                        updateTrapezoidElements();
                    });

                    var $solid1Share = $('input[name="solidMaterial1Share"]', $context),
                        $solid2Share = $('input[name="solidMaterial2Share"]', $context);

                    $solid1Share.on('change', function (e) {
                        $solid2Share.val(100 - Math.max(0, Math.min(100, $solid1Share.val())));
                    });
                    $solid2Share.on('change', function (e) {
                        $solid1Share.val(100 - Math.max(0, Math.min(100, $solid2Share.val())));
                    });


                    var $alternativeLengthLink = $('.calcLength a[rel="set"]', $context).on('click', function (e) {
                        e.preventDefault();

                        var $altLength = $('.construction:visible input.alternative-length', $context);
                        $altLength
                            .val($('.construction:visible span.stepsLength', $context).text())
                            .addClass('changed');

                        return false;
                    });


                    function showConstruction(ident) {
                        if (!ident) return;

                        $('div.construction.fieldset', $context).each(function () {
                            var $elt = $(this);
                            if ($elt.hasClass(ident)) {
                                $elt.show();
                            } else {
                                $elt.hide();
                            }
                        });

                        $('div.type-images img', $context).each(function () {
                            var $elt = $(this);
                            if ($elt.hasClass(ident)) {
                                $elt.show();
                            } else {
                                $elt.hide();
                            }
                        });
                    }

                    function updateTrapezoidElements() {
                        var $isTrapezoid = $('.form-section.isTrapezoid input', $context),
                            $length2 = $('.form-section.coverLength2 input', $context);

                        if ($typeElts.filter(':checked').val() == 'middle-holm') {
                            $('.form-section.isTrapezoid', $context).show();
                            $('.form-section.coverLength2', $context).show();

                            disable($length2, !$isTrapezoid.is(':checked'));
                            $isTrapezoid.on('change', function () {
                                disable($length2, !$isTrapezoid.is(':checked'));
                            });
                        } else {
                            $('.form-section.isTrapezoid', $context).hide();
                            $('.form-section.coverLength2', $context).hide();
                        }
                    }

                    function disable($elt, state) {
                        if (state) {
                            $elt.attr('readonly', 'readonly')
                                .addClass('read-only');
                        } else {
                            $elt.removeAttr('readonly')
                                .removeClass('read-only');
                        }
                    }
                },

                preparePillarAssistant: function ($context) {

                    // construction type
                    var $typeElts = $('input[name="shape"]', $context);
                    showConstruction($typeElts.filter(':checked').val());

                    $typeElts.on('change', function (e) {
                        showConstruction($typeElts.filter(':checked').val());
                    });

                    var $share1 = $('input[name="material1Share"]', $context),
                        $share2 = $('input[name="material2Share"]', $context);

                    $share1.on('change', function (e) {
                        $share2.val(100 - Math.max(0, Math.min(100, $share1.val())));
                    });
                    $share2.on('change', function (e) {
                        $share1.val(100 - Math.max(0, Math.min(100, $share2.val())));
                    });

                    function showConstruction(ident) {
                        if (!ident) return;

                        $('div.properties.fieldset .shape-properties', $context).each(function () {
                            var $elt = $(this);
                            if ($elt.hasClass(ident)) {
                                $elt.show();
                            } else {
                                $elt.hide();
                            }
                        });

                        $('div.type-images img', $context).each(function () {
                            var $elt = $(this);
                            if ($elt.hasClass(ident)) {
                                $elt.show();
                            } else {
                                $elt.hide();
                            }
                        });
                    }
                },

                prepareProjectTransports: function ($context) {

                    $('#transports .matProcessConfigId select', $context).change(function () {

                        var selValue = $(this).val(),
                            $opt = $('option[value=' + selValue + ']', this),
                            quantity = $opt.data('quantity'),
                            name = $opt.text(),
                            $liTransport = $(this).parents('li.transport');


                        $liTransport.find('.name input').val(selValue ? name : '');
                        $liTransport.find('.quantity input').val(quantity);
                    });

                    if ($context.is('ul')) {
                        var relId = $context.data('rel-id');
                        $('li#transport-mean-' + relId + ' .distance input', $context).focus();
                    }


                    /**
                     * toggle process-database tables
                     */
                    $('.toggle-link', $context).each(function () {
                        $(this).click(function (e) {
                            var $parent = $(this).parent().attr('id'),
                                $table = $('#' + $parent + ' .process-databases');

                            if ($table.is(':hidden')) {
                                $('#' + $parent + ' .toggle').val(1);
                                $(this).addClass('open');
                                $table.show();
                            }
                            else {
                                $('#' + $parent + ' .toggle').val(0);
                                $(this).removeClass('open');
                                $table.hide();
                            }
                            return false;
                        });
                    });

                    /**
                     * set toggled process-database tables visible
                     */
                    $('.toggle', $context).each(function () {
                        var $parent = $(this).parent().attr('id'),
                            $table = $('#' + $parent + ' .process-databases');

                        if ($(this).val() == 1) {
                            $('#' + $parent + ' .toggle-link').addClass('open');
                            $table.show();
                        }
                    });
                },
                'prepareProcessSanity': function ($context) {
                    var url = $.url(jBlibs.App.getHashUrl()),
                        reference = url.param('r');
                    var $processSanityTable = $('#processSanityTable'),
                        me = this;

                    var epdModulesFilter = $('#epdModulesFilter').selectize({
                        create: false,
                        allowEmptyOption: false,
                        closeAfterSelect: false,
                        plugins: [{
                            name: 'remove_button',
                            options: {
                                'className': 'no-xhr',
                            }
                        }]
                    });

                    var table = $processSanityTable.DataTable({
                        dom: '<"dt-wrapper"lpBtip>',
                        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, $processSanityTable.data('all-caption')]],
                        ajax: {
                            url: "/sanity/processes/sanitiesJson/",
                            dataSrc: "processes"
                        },
                        columns: [
                            {"data": "refNum"},
                            {
                                "data": "processConfigName",
                                "render": function (data, type, row, meta) {
                                    return '<a class="page" href="/processes/'+ row.processConfigId + '/?back=ref'+row.id+'">'+data+'</a>';
                                }
                            },
                            {"data": "processDbName"},
                            {"data": "epdTypes"},
                            {"data": "epdModules"},
                            {"data": "status"},
                            {
                                "data": "isFalsePositive",
                                "orderable": false,
                                "render": function (data, type, row, meta) {
                                    if (type !== 'display') {
                                        return data;
                                    }
                                    return '<input name="falsePositive" type="checkbox" value="' + row.id + '" ' + (data ? 'checked="checked"' : '') + '/>';
                                }
                            },
                            {
                                "data": "isReference",
                                "orderable": false,
                                "render": function (data, type, row, meta) {
                                    if (type !== 'display') {
                                        return data;
                                    }

                                    return '<input name="isReference" type="checkbox" value="' + row.processConfigId + '" '+ (data ? 'checked="checked"' : '') + '/>';
                                }
                            }
                        ],
                        'createdRow': function( row, data, dataIndex ) {
                            $(row).attr('id', 'ref'+data.id);

                            if (!data.isReference) {
                                $(row).addClass('inactive');
                            }
                        },
                        'columnDefs': [
                            {
                                'targets': [6, 7],
                                'createdCell':  function (td, cellData, rowData, row, col) {
                                    $(td).data('filter', cellData);
                                }
                            }
                        ],
                        stateSave: true,
                        drawCallback: function () {
                            // this is necessary to make the links work with xhr
                            me._prepare($processSanityTable);
                        },
                        initComplete: function (settings, json) {
                            var dataTable = this;

                            inputFilter(1, 'tr.filter th.process-config input');
                            selectFilter(2, 'tr.filter th.process-db select');
                            selectFilter(3, 'tr.filter th.epd-sub-type select');
                            selectizeFilter(4, epdModulesFilter);
                            selectFilter(5, 'tr.filter th.error-status select');
                            checkboxFilter(6, 'tr.filter th.false-positive input');
                            checkboxFilter(7, 'tr.filter th.is-reference input');

                            if (reference) {
                                var $row = $('#'+reference),
                                    $window = $(window);

                                if ($row.length) {
                                    $window.scrollTop($row.offset().top - ($window.height() / 2));
                                    $row.addClass('glow');
                                }
                            }

                            table.$('td input').on('change', function () {
                                $(this).addClass('changed');

                                table.button(1).enable();
                            });

                            function selectFilter(columnIndex, selector) {
                                var column = dataTable.api().column(columnIndex),
                                    savedState = dataTable.api().column(columnIndex).search();

                                var select = $(selector)
                                    .on('change', function () {
                                        var val = $(this).val();
                                        column
                                            .search(val ? val : '', false, false)
                                            .draw();

                                        $(column.header()).toggleClass('active-filter', !!val);
                                    });

                                if (savedState) {
                                    select.find('option').each(function() {
                                        var value = $(this).val();
                                        if (savedState === value) {
                                            select.val(value);
                                        }
                                    });

                                    $(column.header()).addClass('active-filter');
                                }
                            }

                            function selectizeFilter(columnIndex, selectize) {
                                var column = dataTable.api().column(columnIndex),
                                    savedState = dataTable.api().column(columnIndex).search();

                                selectize
                                    .off('change')
                                    .on('change', function() {
                                        var value = selectize.val(),
                                            regex;

                                        if (value) {
                                            regex = value
                                                .sort()
                                                .map(function (item) {
                                                    return $.fn.dataTable.util.escapeRegex(item);
                                                })
                                                .join('.+?');
                                        }
                                        column
                                            .search(regex ? '(' + regex + ')' : '', true, false)
                                            .draw();

                                        $(column.header()).toggleClass('active-filter', !!value);
                                    });

                                if (savedState.length) {
                                    var selectedOpts = savedState.replace(/\\(.)/g, '$1').replace(/^\((.+)\)$/, '$1').split(/\.\+\?/);
                                    selectize[0].selectize.setValue(selectedOpts, true);

                                    $(column.header()).addClass('active-filter');

                                }
                            }

                            function inputFilter(columnIndex, selector) {
                                var column = dataTable.api().column(columnIndex),
                                    savedState = dataTable.api().column(columnIndex).search(),
                                    $input = $(selector);


                                $input.off('keyup change').on('keyup change', function() {
                                    if (savedState !== this.value) {
                                        column
                                            .search(this.value, true, false)
                                            .draw();
                                    }

                                    $(column.header()).toggleClass('active-filter', !!this.value);
                                });

                                if (savedState.length) {
                                    $input.val(savedState);
                                    $(column.header()).addClass('active-filter');
                                }
                            }

                            function checkboxFilter(columnIndex, selector) {
                                var column = dataTable.api().column(columnIndex),
                                    savedState = dataTable.api().column(columnIndex).search(),
                                    $input = $(selector),
                                    clickTimer = null;

                                $input.off('click').on('click', function(e) {
                                    if (clickTimer !== null) {
                                        return;
                                    }
                                    clickTimer = window.setTimeout(function() {
                                        var state = $input.is(':checked') ? 'true' : 'false';

                                        column
                                            .search(state, false, false)
                                            .draw();

                                        clickTimer = null;
                                    }, 300);
                                    $(column.header()).addClass('active-filter');
                                });

                                $input.off('dblclick').on('dblclick', function (e) {
                                    clearTimeout(clickTimer);
                                    clickTimer = null;

                                    $input.prop('indeterminate', true);

                                    column
                                        .search('', false, false)
                                        .draw();

                                    $(column.header()).removeClass('active-filter');
                                });

                                if (savedState.length) {
                                    $input.prop('checked', savedState === 'true');
                                    $(column.header()).addClass('active-filter');
                                }
                                else {
                                    $input.prop('indeterminate', true);
                                    $(column.header()).removeClass('active-filter');
                                }
                            }
                        },
                        autoWidth: false,
                        language: {
                            "lengthMenu": $processSanityTable.data('per-page-caption'), //"Display _MENU_ records per page",
                            "zeroRecords": $processSanityTable.data('zero-records-caption'), // "Nothing found - sorry",
                            "info": $processSanityTable.data('current-page-caption'), //"Showing page _PAGE_ of _PAGES_",
                            "infoFiltered": $processSanityTable.data('info-filtered-caption'),
                            "infoEmpty": $processSanityTable.data('zero-records-caption'), //"No records available",
                            "search": $processSanityTable.data('search-caption'),
                            "paginate": {
                                "first":      $processSanityTable.data('first-caption'),
                                "last":       $processSanityTable.data('last-caption'),
                                "next":       $processSanityTable.data('next-caption'),
                                "previous":   $processSanityTable.data('previous-caption'),
                            },
                            "decimal": ",",
                            "thousands": ".",
                            "buttons": {
                                "selectNone": $processSanityTable.data('select-none-caption'),
                            },
                            select: {
                                rows: {
                                    1: $processSanityTable.data('selected-row-caption'), // 1: "Only 1 row selected"
                                    0: '', //$adminMaterialMappings.data('no-row-selected-caption'),
                                    _: $processSanityTable.data('selected-rows-caption') //"You have selected %d rows",
                                }
                            }
                        },
                        buttons: [
                            {
                                name: 'clearAllFilters',
                                text: $processSanityTable.data('clear-all-filters-caption'),
                                action: function ( e, dt, node, config ) {
                                    table.columns().every(function() {
                                        this.search('');
                                    });
                                    $processSanityTable.find('.filter select,.filter input[type="text"]').val('');
                                    $processSanityTable.find('.filter select,.filter input[type="checkbox"]').prop('indeterminate', true);
                                    $processSanityTable.find('th').removeClass('active-filter');
                                    epdModulesFilter[0].selectize.clear(true);
                                    table.state.clear();
                                    table.state.save();
                                    table.search('').draw();
                                }
                            },
                            {
                                name: 'Speichern',
                                text: $processSanityTable.data('save-caption'),
                                action: function ( e, dt, node, config ) {
                                    var falsePositive =
                                        table.$('input[name="falsePositive"].changed').map(function () {
                                            return {id: this.value, value: $(this).is(':checked')};
                                        }).toArray();
                                    var isReference = table.$('input[name="isReference"].changed').map(function () {
                                        return {id: this.value, value: $(this).is(':checked')};
                                    }).toArray();

                                    jBlibs.App.query('/sanity/processes/save/', {
                                        falsePositive: falsePositive,
                                        isReference: isReference
                                    }, {
                                        httpMethod: 'POST',
                                        success: function() {
                                            table.$('input[name="isReference"].changed').each(function() {
                                                var tr = $(this).parents('tr');
                                                if ($(this).is(':checked')) {
                                                    tr.removeClass('inactive');
                                                }
                                                else {
                                                    tr.addClass('inactive');
                                                }
                                            });
                                            table.$('td input.changed').removeClass('changed');
                                        }
                                    });

                                    return false;
                                },
                                enabled: false

                            }
                        ]
                    });
                }
            },
            // End of default controller

            /*************************************************************************************
             *
             * This is the controller for the modal box
             */
            'modalBox': {
                /**
                 * Cache store for auto complete entries
                 * (currently not used)
                 */
                autoComplete: {
                    cache: {},
                    lastXhr: null
                },

                /**
                 * Initialize controller
                 */
                initialize: function () {
                    /**
                     * Register modified autocomplete version
                     */
                    $.widget('custom.catcomplete', $.ui.autocomplete, {
                        _renderMenu: function (ul, items) {
                            var self = this, currentCategory = '';

                            $.each(items, function (index, item) {
                                if (item.category != currentCategory) {
                                    ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>");
                                    currentCategory = item.category;
                                }
                                self._renderItemData(ul, item);
                            });
                        }
                    });
                },

                /**
                 * Views
                 */
                views: {
                    '#elca-modal-content': 'prepareContent',
                    '#elca-modal-content.process-selector': 'prepareProcessSelector',
                    '#elca-modal-content.process-config-selector-modal': 'prepareProcessConfigSelector',
                    '#elca-modal-content.element-selector,div.element': 'prepareElementSelector',
                    '#elca-modal-content.template-element-selector,div.element': 'prepareTemplateElementSelector',
                    '#content.elements,#content.project-elements': null,
                    '#content.elca-admin-benchmark-version,div.ElcaHtmlProcessConfigSelectorLink-section.refProcessConfigId': null,
                    '#tabContent': null,  // for open-modal links
                    'div.process-assignment,div.process-group': null,
                    'div.element-section,#element-layers,#element-components,div.element-component': null,
                    '#content.project-final-energy': null,
                    '#content.project-transports,ul.transport-means': null,
                    '#elca-modal-content.processing': 'prepareProcessing',
                    'div.ElcaHtmlProcessConfigSelectorLink-section.assistant': null,
                    'div.ElcaHtmlElementSelectorLink-section.assistant': null,
                    '#templateElement': null,
                    '#content.report': null,
                    '#elca-modal-content.pdf-gen': 'preparePdf',
                    '#elca-modal-content.project-access': 'prepareProjectAccess',
                    '#content.elca-project': null,
                    '#projectProcessConfigSanity': null,
                    '#content.project-import': null,
                    '#content.admin-mapping-edit': null,
                    '.import-assistant-mapping-selector': null,
                    '#content.replace-components, #content.replace-elements': null,
                    '#content.project-csv-import.preview': null
                },

                /**
                 * Controls
                 */
                controls: {
                    'a[rel=open-modal]': {
                        click: {
                            target: 'href',
                            success: function () {
                                $('#elca-modal').fadeIn('fast');
                            }
                        }
                    },
                    'a[rel=close-modal]': {
                        click: function () {
                            $('#elca-modal').fadeOut('fast', function () {
                                $('#elca-modal-content').empty();
                            });
                            return false; // no action, just close
                        }
                    }
                },

                prepareProjectAccess: function ($context) {
                    /**
                     * Close modal box on click on submit button
                     */
                    $('#projectAccessForm input[type=submit]').click(function (e) {
                        $('#elca-modal').fadeOut('fast');
                        return true;
                    });
                },
                preparePdf: function ($context) {
                    $('div.spinning-wheel', $context).each(function () {
                        var $container = $(this);

                        if ($container.data('action')) {
                            jBlibs.App.query($container.data('action'), null, {
                                complete: function (xhr, response) {
                                    $context.removeClass('spin').addClass('done');
                                    jBlibs.App._replaceView(response.responseJSON);
                                },
                                error: function (xhr, textStatus, errThrown) {
                                    alert('Error: ' + xhr.responseText);
                                }
                            });
                        }

                        if ($container.data('close-after-time-in-ms')) {
                            setTimeout(function() {
                                $('#elca-modal').fadeOut('fast', function () {
                                    $('#elca-modal-content').empty();
                                });
                            }, $container.data('close-after-time-in-ms'));
                        }
                    });
                },

                /**
                 * Prepare modal content
                 */
                prepareContent: function ($context) {
                    var me = this;
                    if (!$('#elca-modal-content').is(':empty')) {
                        $('#elca-modal').fadeIn('fast');
                    }

                    /**
                     * Close modal box on click on submit button
                     */
                    $('.modal-selector-form input[type=submit]').click(function (e) {
                        me.clearCache();
                        $('#elca-modal').fadeOut('fast');
                        return true;
                    });
                },
                prepareProcessSelector: function ($context) {
                    var me = this;

                    $('#elca-process-search', $context).catcomplete({
                        minLength: 2,
                        source: function (request, response) {
                            var term = request.term;
                            $.ajax({
                                global: false,
                                url: this.element.data('url'),
                                dataType: 'json',
                                data: {
                                    term: request.term,
                                    lc: this.element.data('life-cycle'),
                                    processDbId: this.element.data('process-db-id')
                                },
                                success: function (data, status, xhr) {
                                    response(data.results);
                                }
                            });
                        },
                        select: function (event, ui) {
                            $('#processSelectorForm select[name=id]').val(''); // unset id
                            $('#processSelectorForm input[name=p]').val(ui.item.id);
                            $('#processSelectorForm select[name=processCategoryNodeId]').val(ui.item.catId).change();
                            return false;
                        }
                    });
                },

                prepareProcessConfigSelector: function ($context) {
                    var me = this,
                        $buttons = $('.buttons.fieldset', $context);

                    $('#elca-process-config-search', $context).catcomplete({
                        minLength: 2,
                        source: function (request, response) {
                            var term = request.term;
                            $.ajax({
                                global: false,
                                url: this.element.data('url'),
                                dataType: 'json',
                                data: {
                                    term: request.term,
                                    u: this.element.data('in-unit'),
                                    b: this.element.data('build-mode'),
                                    db: this.element.data('db'),
                                    compatdbs: this.element.data('compatdbs'),
                                    filterByProjectVariantId: this.element.data('filter-project-variant-id'),
                                    epdSubType: this.element.data('epd-sub-type')
                                },
                                success: function (data, status, xhr) {
                                    response(data.results);
                                }
                            });
                        },
                        select: function (event, ui) {
                            $('#processConfigSelectorForm select[name=id]').val(''); // unset id
                            $('#processConfigSelectorForm input[name=sp]').val(ui.item.id);
                            $('#processConfigSelectorForm select[name=processCategoryNodeId]').val(ui.item.catId).change();
                            return false;
                        }
                    });

                    $(document).one('ajaxSend', function (e) {
                        $buttons.find('input').prop('disabled', true);
                        $buttons.css('opacity', .4);
                    });

                    /** Reparse content of info message. The tee-function returns some strong tags here **/
                    $('#layer-info').html(function () {
                        return $(this).text();
                    });
                },

                prepareElementSelector: function ($context) {
                    var me = this,
                        $buttons = $('.buttons.fieldset', $context);

                    $('#elca-element-search', $context).catcomplete({
                        minLength: 2,
                        source: function (request, response) {
                            var term = request.term,
                                searchMode = $('#elementSelectorForm input[name=mode]').fieldValue()[0],
                                searchScope = $('#elementSelectorForm input[name=scope]').fieldValue()[0];

                            $.ajax({
                                global: false,
                                url: this.element.data('url'),
                                dataType: 'json',
                                data: {
                                    term: request.term,
                                    ce: this.element.data('rel-id'),
                                    m: searchMode,
                                    scope: searchScope,
                                    compatdbs: this.element.data('compatdbs')
                                },
                                success: function (data, status, xhr) {
                                    response(data.results);
                                }
                            });
                        },
                        select: function (event, ui) {
                            $('#elementSelectorForm *[name=id]').val(ui.item.id);
                            $('#elementSelectorForm select[name=elementTypeNodeId]').val(ui.item.catId).change();
                            //$('#elementSelectorForm select[name=id] option').removeAttr('selected');

                            //$('#elementSelectorForm select[name=id]').val(ui.item.id);
                            return false;
                        }
                    });

                    $(document).one('ajaxSend', function (e) {
                        $buttons.find('input').prop('disabled', true);
                        $buttons.css('opacity', .4);
                    });
                },

                prepareTemplateElementSelector: function ($context) {
                    var me = this,
                        $buttons = $('.buttons.fieldset', $context);
                    var $elementTemplateSelectorForm = $('#templateElementSelectorForm');

                    $('#elca-element-search', $context).catcomplete({
                        minLength: 2,
                        source: function (request, response) {
                            var term = request.term,
                                searchScope = $elementTemplateSelectorForm.find('input[name=scope]').fieldValue()[0];

                            $.ajax({
                                global: false,
                                url: this.element.data('url'),
                                dataType: 'json',
                                data: {
                                    term: request.term,
                                    elementTypeNodeId: this.element.data('element-type-node-id'),
                                    scope: searchScope,
                                    compatdbs: this.element.data('compatdbs')
                                },
                                success: function (data, status, xhr) {
                                    response(data.results);
                                }
                            });
                        },
                        select: function (event, ui) {
                            $elementTemplateSelectorForm.find('*[name=id]').val(ui.item.id);
                            $elementTemplateSelectorForm.find('select[name=elementTypeNodeId]').val(ui.item.catId).change();
                            return false;
                        }
                    });

                    $(document).one('ajaxSend', function (e) {
                        $buttons.find('input').prop('disabled', true);
                        $buttons.css('opacity', .4);
                    });
                },

                /**
                 * Prepare modal content
                 */
                prepareProcessing: function ($context) {
                    $('div.spinning-wheel', $context).each(function () {
                        var $container = $(this);

                        if ($container.data('action')) {
                            jBlibs.App.query($container.data('action'), null, {
                                complete: function () {
                                    $context.removeClass('spin').addClass('done');

                                    if ($container.data('reload')) {
                                        jBlibs.App.query(jBlibs.App.getHashUrl());
                                    }

                                    window.setTimeout(function () {
                                        $('#elca-modal').fadeOut('fast', function () {
                                            $('#elca-modal-content').empty();

                                        });
                                    }, 2000);
                                },
                                error: function (xhr, textStatus, errThrown) {
                                    alert('Error: ' + xhr.responseText);
                                }
                            });
                        }
                    });
                },

                clearCache: function () {
                    this.autoComplete.cache = {};
                }
            },
            // End controller modalBox

            /*************************************************************************************
             *
             * This is the controller for the msg box
             */
            'msgBox': {
                onLoad: function (e, xhr, settings) {
                },

                /**
                 * Views
                 */
                views: {
                    '#msgBox': function ($msgBox) {
                        if (!$msgBox.is('.notice, .info, .error, .confirm'))
                            return $msgBox.hide();

                        $msgBox.slideDown('fast');

                        if (!$msgBox.is('.info, .error, .confirm')) {
                            window.setTimeout(function () {
                                $msgBox.slideUp('fast');
                                $msgBox.removeClass('info notice error confirm');
                            }, 2000);
                        }
                        $msgBox.unbind('click');

                        if ($msgBox.is('.confirm')) {
                            $('a, span.cancel', $msgBox).click(function (e) {
                                $(this).parents('li').remove();

                                if ($('a', $msgBox).length == 0) {
                                    $msgBox.slideUp();
                                    $msgBox.removeClass('info notice error confirm');
                                }

                                $(document).off('keydown');

                                e.preventDefault();
                                return false;
                            });

                            $(document).keypress(function (e) {
                                var code = e.keyCode ? e.keyCode : e.which;
                                switch (code) {
                                    case 13:
                                        $('a.confirm', $msgBox).click();
                                        e.preventDefault();
                                        break;
                                    case 27:
                                        e.preventDefault();
                                        break;
                                }
                            });
                        }
                        else {
                            $msgBox.click(function () {
                                $msgBox.slideUp('fast');
                                $msgBox.removeClass('info notice error confirm');
                            });

                            window.setTimeout(function () {
                                $msgBox.off('click');
                                $msgBox.slideUp();
                            }, 8000);
                        }

                    }
                }
            },
            // End controller msgBox

            /*************************************************************************************
             *
             * This is the navigation controller, which handles navigation issues
             * for the left vertical navigation and tabs within the #content section
             */
            'navigation': {
                /**
                 * Views
                 */
                views: {
                    '#navLeft': ['initNavLeft', 'highlightMissingTranslations'],
                    '#navLeft.elements': ['initElementsNav', 'highlightMissingTranslations'],
                    '#navLeft.project-elements': 'initProjectElementsNav',
                    '#tabContent': ['setActiveTab', 'highlightMissingTranslations']
                },

                /**
                 * Controls
                 */
                controls: {
                    'a.page:not(.no-xhr)': {
                        click: 'href'
                    }
                },

                // Highlight missing translations

                highlightMissingTranslations: function ($context) {
                    $('body.hl-mi-tr *')
                        .contents()
                        .filter(function () {
                            var regex = /\_\_\*\*(.+?)\*\*\_\_/m;
                            return this.nodeType === 3 && regex.test(this.textContent);
                        }).highlightMissingTranslation();
                },

                /**
                 * Tab navigations
                 */
                setActiveTab: function ($context) {
                    // mark active tab
                    $.each($context.attr('class').split(/ /), function (i, tabId) {
                        if (!tabId.match(/tab-/))
                            return;

                        $('#content .elca-tabs .elca-tab').removeClass('active').filter('#' + tabId).addClass('active');
                        return false;
                    });
                },

                /**
                 * Navigation left initialization
                 */
                initNavLeft: function ($context) {
                    var me = this;

                    /**
                     * Open marked nav item
                     */
                    $('li.open ul.nav-toggle-item:hidden', $context).show();

                    /**
                     * Register handlers for toggle functions
                     */
                    $('h4.nav-toggle', $context).click(function (e) {
                        var toggleItem = $(this).next('ul');

                        if (toggleItem.is(":hidden")) {
                            // close other
                            $('li.open ul.nav-toggle-item:not(:hidden)', $context).each(function () {
                                $(this).slideUp('fast');
                                $(this).parent('li').removeClass('open');
                            });

                            $(this).parent('li').addClass('open');
                            toggleItem.slideDown('fast');
                        } else {

                            $(this).parent('li').removeClass('open');
                            toggleItem.slideUp('fast');
                        }
                    });
                },

                initProjectElementsNav: function ($context) {
                    var $compareForm = $context.find('form[name="compareWithReferenceProjectForm"]');
                    var $compareToggle = $compareForm.find('.compare input');
                    var $indicatorSelect = $compareForm.find('.indicatorId');

                    compareWithRefProjectForm(true);

                    $compareForm.on('change', function (e) {
                        compareWithRefProjectForm();

                        $compareForm.ajaxSubmit();
                    });

                    function compareWithRefProjectForm(hideImmediately) {
                        if ($compareToggle.is(':checked')) {
                            $indicatorSelect.fadeIn();
                        }
                        else {
                            if (!hideImmediately) {
                                $indicatorSelect.fadeOut();
                            }
                            else {
                                $indicatorSelect.hide();
                            }
                        }
                    }

                    $context.find('li.navigation').each(function () {
                        var $li = $(this),
                            value = $li.data('ref-project'),
                            deviation = $li.data('ref-project-deviation');

                        if (value) {
                            var $refIndicator = $('<span class="ref-project '+ value +'"></span>');
                            $refIndicator.attr('title', deviation + ' %');
                            $(this).append($refIndicator);
                        }
                    });
                },

                initElementsNav: function ($context) {
                    $('h3', $context).each(function (index) {
                        var cookieName = 'elca.elementsNavToggle.' + index;

                        if (Cookies.get(cookieName) == undefined)
                            Cookies.set(cookieName, true);

                        if (Cookies && !Cookies.get(cookieName)) {
                            $(this).addClass('close');
                            $(this).next().hide();
                        }

                        $(this).off('click').on('click', function (e) {
                            $(this).toggleClass('close');
                            $(this).next().toggle();
                            Cookies.set(cookieName, !$(this).hasClass('close'));
                        });

                    });
                }
            },
            // End of navigation controller

            /*************************************************************************************
             * Charts controller
             */
            'charts': {
                views: {
                    '#content.report': 'prepareCharts',
                    '#content.report-summary-benchmarks': 'prepareBenchmarks',
                    '#content.project-final-energy': 'prepareFinalEnergyKwkPieChart'
                },

                prepareCharts: function ($context) {
                    var self = this,
                        $charts = $('div.chart', $context),
                        chartCount = $charts.length,
                        $progressElt = $('#progress'),
                        $printButton = $('div.print.button a', this);

                    if (chartCount > 0) {
                        $printButton.fadeTo('fast', 0.1);
                    } else {
                        window.status = 'ready_to_print';
                    }

                    console.debug('start loading charts: ' + chartCount);

                    $charts.each(function (i) {

                        var $this = $(this),
                            url = $this.data('url'),
                            load = 0;

                        window.setTimeout(function () {
                            if ($this.hasClass('bar-chart')) {
                                self.updateBarChart(url, $this);
                            }
                            else if ($this.hasClass('stacked-bar-chart')) {
                                self.updateStackedBarChart(url, $this);
                            }
                            else if ($this.hasClass('grouped-stacked-bar-chart')) {
                                self.updateGroupedStackedBarChart(url, $this);
                            }
                            else if ($this.hasClass('pie-chart')) {
                                self.prepareFinalEnergyKwkPieChart($context);
                            }
                            load = (i + 1) / chartCount;

                            $progressElt.html($('#diagramsLoading').text() + ' ' + Math.round(load * 100) + '%');

                            if (load === 1) {
                                $printButton.fadeTo('normal', 1);
                                window.setTimeout(function () {
                                    $progressElt.fadeOut('fast');
                                    window.status = 'ready_to_print';
                                }, 1000);
                            } else {
                                $progressElt.fadeIn();
                            }
                        }, i * 200);
                    });

                    $('.reportTopForm input', $context).off('keypress');
                    $('.reportForm select,.reportForm input,.reportTopForm input,.reportTopForm select', $context).on('change ', function () {
                        $(this.form).submit();
                    });
                },

                updateBarChart: function (url, $container) {
                    // load data and properties from given url
                    d3.json(url).header('X-Requested-With', 'XMLHttpRequest').get(function (error, response) {

                        var conf = response.config,
                            data = response.data || [],
                            bGroup = null;

                        if (!data.length)
                            return;

                        var barChart = new elca.charts.BarChart($container[0], conf);

                        /**
                         * Add a group for visual benchmark zones, This should be rendered
                         * behind the bars, therefor the group has to be inserted before the
                         * bars were rendered
                         */
                        if ($container.hasClass('benchmark-chart')) {
                            bGroup = barChart.svg.append("g");
                        }

                        /**
                         * Render bars from data
                         */
                        barChart.updateData(data);

                        /**
                         * Add visual benchmark zones
                         */
                        if ($container.hasClass('benchmark-chart')) {

                            $.each([{c: 'gold', from: 80, to: 100},
                                {c: 'silver', from: 70, to: 80},
                                {c: 'bronze', from: 60, to: 70}], function () {
                                bGroup.append("line")
                                    .attr("class", "benchmark " + this.c)
                                    .attr("x1", 20)
                                    .attr("y1", Math.floor(barChart.y(this.from)))
                                    .attr("x2", barChart.width - 20)
                                    .attr("y2", Math.floor(barChart.y(this.from)));
                            });
                        }
                    });
                },

                updateStackedBarChart: function (url, $container) {
                    var self = this;

                    // load data and properties from given url
                    d3.json(url).header('X-Requested-With', 'XMLHttpRequest').get(function (error, response) {

                        var conf = response.config,
                            data = response.data || [],
                            bGroup;

                        if (!data.length)
                            return;

                        $container.fadeOut(function () {
                            $container.empty();

                            var charts = new elca.charts.StackedBarChart($container[0], conf, data);

                            /**
                             * Add a group for visual benchmark zones, This should be rendered
                             * behind the bars, therefor the group has to be inserted before the
                             * bars were rendered
                             */
                            if ($container.hasClass('benchmark-chart')) {
                                bGroup = charts.svg.append("g");
                            }

                            /**
                             * Render bars from data
                             */
                            charts.updateData(data);

                            /**
                             * Add visual benchmark zones
                             */
                            if ($container.hasClass('benchmark-chart')) {

                                $.each([
                                    {c: 'max', from: 100},
                                    {c: 'p80', from: 80},
                                    {c: 'p60', from: 60},
                                    {c: 'p40', from: 40},
                                    {c: 'p20', from: 20},
                                    ], function () {
                                    bGroup.append("line")
                                        .attr("class", "benchmark " + this.c)
                                        .attr("x1", 5)
                                        .attr("y1", Math.floor(charts.y(this.from)))
                                        .attr("x2", charts.width - 5)
                                        .attr("y2", Math.floor(charts.y(this.from)));
                                });
                            }


                            $container.fadeIn();
                        });
                    });
                },

                updateGroupedStackedBarChart: function (url, $container) {
                    var self = this;

                    // load data and properties from given url
                    d3.json(url).header('X-Requested-With', 'XMLHttpRequest').get(function (error, response) {

                        var conf = response.config,
                            data = response.data || [];

                        if (!data.length)
                            return;

                        $container.fadeOut(function () {
                            $container.empty();

                            var charts = new elca.charts.GroupedStackedBarChart($container[0], conf, data);
                            $container.fadeIn();
                        });
                    });
                },

                prepareBenchmarks: function ($context) {

                    if ($('body.pdf').length)
                        return;

                    var $container = $('.indicator-charts', $context),
                        $cycler = $('.cycler', $container);

                    $container.append('<div id="indicator-charts-pager" class="cycle-pager">Indikatoren </div>');

                    $cycler.cycle({
                        timeout: 0,
                        slides: '> .ref-model-chart',
                        pager: '#indicator-charts-pager'
                    });

                },

                prepareFinalEnergyKwkPieChart: function ($context) {
                    var $chart = $('.pie-chart', $context);

                    if ($chart.length === 0) {
                        return;
                    }

                    var data = $chart.data('values');

                    var self = this;

                    var width = 100,
                        height = 100,
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
                        .attr("width", width + '%')
                        .attr("height", height + '%')
                        .attr("viewBox", "0 0 100 100")
                        .append("g")
                        .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

                    var charsPerLine = 8;
                    var textBaseSize = 8;

                    data.forEach(function (d) {
                        d.value = +d.value;
                        d.text = (d.name + ' ' + (Math.round(d.value * 100 * 10) / 10) + "%").replace('.', ',')

                        var newEmSize = charsPerLine / d.text.length;
                        d.fontSize = newEmSize < 1 ? (newEmSize * textBaseSize) + "px" : "1px";
                    });

                    this.tooltip = d3.select($chart[0])
                        .append("div")
                        .attr("class", "tooltip")
                        .style("opacity", 0);

                    var g = svg.selectAll(".arc")
                        .data(pie(data))
                        .enter().append("g")
                        .attr("class", "arc");

                    g.append("path")
                        .attr("d", arc)
                        .style("fill", function (d) {
                            return d.data.class === 'undefined' ? '#FF0000' : color(d.data.name);
                        })
                        .on("mouseover", function (d) {
                            var coords = d3.mouse($chart[0]);
                            self.tooltip.transition()
                                .duration(200)
                                .style("opacity", .9);

                            self.tooltip.text(d.data.text)
                                .style("left", coords[0] + "px")
                                .style("top", (coords[1] - 12) + "px");
                        })
                        .on("mouseout", function (d) {
                            self.tooltip.transition()
                                .duration(500)
                                .style("opacity", 0);
                        });

                    g.append("text")
                        .attr("transform", function (d) {
                            return "translate(" + arc.centroid(d) + ")";
                        })
                        .attr("dy", ".35em")
                        .style("text-anchor", "middle")
                        .style("font-size", function(d) {
                            return d.data.fontSize;
                        })
                        .text(function (d) {
                            return d.data.text;
                        });
                },

            }
        }
    });

});
