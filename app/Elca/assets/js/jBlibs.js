/**
 * This file is part of blibs - mvc development framework
 *
 * Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
 *                    Fabian Möller <fab@beibob.de>
 *                    BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * blibs is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * blibs is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with blibs. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Blibs js framework
 *
 * inspired by balupton's ajaxy http://balupton.github.com/jquery-ajaxy/demo/
 */
(function(window, $, undefined) {

    var jBlibs = (window.jBlibs = window.jBlibs || {});

    jBlibs.App = {

        /**
         * Root url
         */
        rootUrl: null,

        /**
         * options
         */
        options: {
            debug: false,
            controllers: {},
            updateHashUrlCssClass: null
        },

        /**
         *  Global initialization. Called once per request
         */
        init: function(opts) {
            var me = this;

            /**
             * Merge-in some options
             */
            if('debug' in opts)
                this.options.debug = opts.debug;

            if('updateHashUrlCssClass' in opts)
                this.options.updateHashUrlCssClass = opts.updateHashUrlCssClass;

            /**
             * Global initialization if not yet done
             */
            this._globalInitialize();

            /**
             * Call initialize function
             */
            if($.isFunction(opts.initialize))
                opts.initialize();

            /**
             * Initialize all new controllers
             */
            $.each(opts.controllers, function(name) {

                // extend methods
                var ctrl = me.options.controllers[name] = $.extend(true, {}, me._controller, this);
	            if(ctrl._initialize(name)) {
                    // prepare body context
		            ctrl._prepare($('body'));
                }
            });

            $.extend(true, this.options.controllers, opts.controllers);

            /**
             * Chain
             */
            return this;
        },


        /**
         * Initialize framework
         */
        _globalInitialize: function() {
            if(this._isInitialized)
                return false;

            /**
             * Init rootUrl
             */
            this.rootUrl = window.location.protocol + '//' + window.location.hostname;

            /**
             * Bind hashchange event
             */
            this._bindHashChange();

            /**
             * Global ajax settings
             */
            this._ajaxSetup();

            /**
             * Mark initialized
             */
            this._isInitialized = true;

            return true;
        },

        // controller template
        _controller: {
            debug: false,

            _ctrlName: null,

            _initialize: function(ctrlName) {

                if(this._isInitialized)
                    return true;

                // check if this controller matches the current url
                if(!this._urlMatches())
                    return false;

                this._ctrlName = ctrlName;

                if(this.debug || jBlibs.App.options.debug)
                    window.console.log('Initialize '+ this._ctrlName);

                if($.isFunction(this.initialize)) {
                    this.initialize();
                }

                this._isInitialized = true;
                return true;
            },

            _prepare: function($view) {
                var me = this,
                viewId = $view.attr('id');

                // check if this controller matches the current url
                if(!this._urlMatches()) {
                    return;
                }

                // prepare registered views
                $.each(this.views, function(selector, fn) {
                    var $viewContext, $context;

                    // check if view is within current context
                    if($view.is(selector)) {
//                        window.console.log('Check if selector '+ me._ctrlName +': '+ selector +' matches: ', $view, $view.is(selector));
                        $viewContext = $view;
                    }
                    else {
                        return;
                    }

                    // some debug output
                    if(me.debug || jBlibs.App.options.debug) {
                        window.console.log('Prepare '+ me._ctrlName +' controller view '+ selector + ' within context', $viewContext, fn);
                    }

                    // no view? nothing to do here
                    if(!$viewContext.length || $viewContext.is(':empty'))
                        return;

                    /**
                     * Register all controls within the given context
                     */
                    if(me.controls && $.isPlainObject(me.controls)) {
                        $context = $viewContext;

                        // iterate over selectors within controls
                        $.each(me.controls, function(selector, events) {
                            // some debug output
                            // if(me.debug || jBlibs.App.options.debug) {
                            // window.console.log('Prepare '+ me._ctrlName +' control '+ selector + ' within context', $context);
                            // }
                            //console.log(selector, events);

                            if($.isPlainObject(events)) {
                                // iterate over events
                                $.each(events, function(eventName, config) {
                                    // unbind + bind selector to specified event
                                    $(selector, $context).off(eventName).on(eventName, function(e) {
                                        // determine target url
                                        var url, successFn, errorFn, data;

                                        if(typeof config == 'object') {
                                            if(config.target) {
                                                url = me._getTargetUrl(config.target, this, e);
                                            }
                                            if(config.success) {
                                                successFn = config.success;
                                            }
                                            if(config.error) {
                                                errorFn = config.error;
                                            }
                                            if(config.data) {
                                                data = config.data;
                                            }
                                        }
                                        else {
                                            url = me._getTargetUrl(config, this, e);
                                        }

                                        // if one method returned a url, apply default action
                                        if(url) {
                                            if(jBlibs.App.options.updateHashUrlCssClass &&
                                               $(this).hasClass(jBlibs.App.options.updateHashUrlCssClass)) {
                                                jBlibs.App.updateHashUrl(url, true);
                                            }

                                            jBlibs.App.query(url, data, {success: successFn, error: errorFn});
                                        }

                                        e.preventDefault();
                                        return false;
                                    });
                                });
                            }
                            else if($.isFunction(events)) {
                                // select within context and call selector function
                                events.call(me, $(selector, $context));
                            }
                        });

                    }

                    // call view function
                    switch(typeof fn) {
                    case 'function':
                        fn.call(me, $viewContext);
                        break;

                    case 'object':
                        if(Array.isArray(fn)) {
                            $.each(fn, function(i, fnName) {
                                if($.isFunction(me[fnName])) {
                                    me[fnName].call(me, $viewContext);
                                }
                            });
                        }
                        break;

                    case 'string':
                        if($.isFunction(me[fn])) {
                            me[fn].call(me, $viewContext);
                        }
                        break;
                    }
                });

                /**
                 * Chain
                 */
                return this;
            },

            _getTargetUrl: function(target, elt, event) {
                var url;

                switch(typeof target) {
                case 'function': // target is returned by function
                    url = target.call(elt, event, this);
                    break;
                case 'string':  // target is defined by function or attribute name
                    if($.isFunction(this[target])) {
                        url = this[target].call(elt, event, this);
                    }
                    else {
                        url = $(elt).attr(target);
                    }
                    break;
                }

                return url;
            },

            /**
             * Checks if the ctrl matches the configured urls
             */
            _urlMatches: function() {
                // no specified urls matchs always
                if(!this.urls)
                    return true;

                var url = jBlibs.App.getHashUrl() || document.URL.replace(jBlibs.App.rootUrl, ''),
                matches = this.urls,
                isAMatch = false;

    		    switch(typeof matches) {
    		        // Objects
    		    case 'object':
    		        if (matches.test || false && matches.exec || false ) {
    			        // Regular Expression
    			        isAMatch = matches.test(url);
    			        break;
    		        }
    		    case 'array':
		            $.each(matches, function(i, match){
    			        isAMatch = match === url;
    			        if(isAMatch)
    			            return false;
    		        });
    		        break;

    		        // Exact
    		    case 'number':
    		    case 'string':
    		        isAMatch = (String(matches) === url);
    		        break;
    		    }

    		    return isAMatch;
            }
        },
        // End _controller template

        _ajaxSetup: function() {
            var me = this;
            $(document).ajaxSend(function(e, xhr, settings) {
                me._startLoading();
                $.each(me.options.controllers, function(ctrlName) {
                    if(this.onLoad && $.isFunction(this.onLoad)) {
                        this.onLoad.call(this, e, xhr, settings);
                    }
                });
            });

            $(document).ajaxSuccess(function(e, xhr, settings) {
                me._checkHeader(xhr);

                var response = $.parseJSON(xhr.responseText);
                me._replaceView(response);

                $.each(me.options.controllers, function(ctrlName) {
                    if(this.onSuccess && $.isFunction(this.onSuccess)) {
                        this.onSuccess.call(this, e, xhr, settings, response);
                    }
                });

                if($.isFunction(settings.extSuccess)) {
                    settings.extSuccess.call(me, response, xhr.statusText, xhr);
                }
            });

            $(document).ajaxError(function(e, xhr, settings, response) {
                $.each(me.options.controllers, function(ctrlName) {
                    if(this.onError && $.isFunction(this.onError)) {
                        this.onError.call(this, e, xhr, settings, response);
                    }
                });

                if($.isFunction(settings.extError)) {
                    settings.extError.call(me, xhr, xhr.statusText);
                }
            });

            $(document).ajaxComplete(function(e, xhr, settings) {
                me._stopLoading();

                if($.isFunction(settings.extComplete)) {
                    settings.extComplete(me, xhr, xhr.statusText);
                }
            });
        },

        query: function(href, data, opts) {
            var me = this;

            if(!opts)
                opts = {};

            /**
             * if href is given, catch some html.
             * if there is also a response, it will be added, too
             */
            return $.ajax({
                url: href,
                data: data,
                type: opts.httpMethod || 'GET',
                // overwrite defaults to handle those events _after_ jblibs finnished view replacements
                extSuccess: opts.success,
                extError: opts.error,
		        extComplete: opts.complete
            });
        },
        // End query

        updateHashUrl: function(href, justUpdate, replace) {
            if(href == window.location.href)
                return;

            var url = $.url(href),
            hash = '!';

            /**
             * If no path is specified, then just update the query string.
             * Replaces each single param!
             */
            if(!url.attr('path')) {
                var currentUrl = $.url(this.getHashUrl());
                hash += currentUrl.attr('path');

                if(url.attr('query')) {
                    var currentParams = currentUrl.attr('query')? currentUrl.param() : {},
                    newParams = url.param();

                    $.extend(currentParams, newParams);
                    hash += '?'+ $.param(currentParams);
                }
            }
            else {
                hash += url.attr('path');

                if(url.attr('query'))
                    hash += '?'+ url.attr('query');
            }

            jBlibs.App.disableHashchange = justUpdate;

            if(replace) {
                window.location.replace('#'+hash);
            }
            else {
                window.location.hash = hash;
            }
        },
        // End updateHashUrl

        getHashUrl: function() {
            if(window.location.hash.charAt(1) !== '!')
                return false;

            return window.location.hash.replace('#!', '');
        },
        // End getHash

        scrollTo: function(element, margin, force)
        {
            var position = $(element).offset();

            if(!margin)
                margin = 0;

            if(force || $(window).scrollTop() > (position.top + $(element).height()))
            {
	            if(this.debug)
	                console.log('scroll to '+ Math.max(position.top - margin, 0));
                window.scrollTo(position.left, Math.max(position.top - margin, 0));
            }
        },
        // End scrollTo

        _bindHashChange: function() {
            var me = this;
            $(window).bind( 'hashchange', function(e) {
                if(!me.disableHashchange)
                    me.query(me.getHashUrl());

                me.disableHashchange = false;
            });
        },
        // End bindHashChange

        _checkHeader: function(xhr, response) {
            if(xhr.getResponseHeader('X-Redirect')) {
                var oldPath = window.location.href.replace(new RegExp(this.rootUrl), '');

                if(oldPath != xhr.getResponseHeader('X-Redirect'))
                    window.location.replace(xhr.getResponseHeader('X-Redirect'));
                else
                    window.location.reload();
                return;
            }

            else if(xhr.getResponseHeader('X-Reload'))
                return window.location.reload();

            else if(xhr.getResponseHeader('X-Load-Hash'))
                return jBlibs.App.updateHashUrl(xhr.getResponseHeader('X-Load-Hash'), false);

            else if(xhr.getResponseHeader('X-Update-Hash'))
                return jBlibs.App.updateHashUrl(xhr.getResponseHeader('X-Update-Hash'), true);

            else if(xhr.getResponseHeader('X-Replace-Hash'))
                return jBlibs.App.updateHashUrl(xhr.getResponseHeader('X-Replace-Hash'), true, true);

            else if(xhr.getResponseHeader('X-Reload-Hash'))
                return jBlibs.App.query(jBlibs.App.getHashUrl() );

            else if(xhr.getResponseHeader('X-Auth'))
                return window.location.reload();
        },
        // End checkHeader

        /**
         * Expects an response object with multiple views
         * Each view must have an outer container with an id within the current document.
         * This container will be replaced by the reponse view content
         */
        _replaceView: function(response) {
            if(!response || typeof response !== 'object')
                return;

            var self = this;

            for(var name in response)
            {
                switch(name)
                {
                case 'redirect':
                    window.location.replace(response[name]);
                    break;

                case 'noAccess':
			        var redirectTo = response[name];

			        if(typeof redirectTo != 'string' || !redirectTo.match(/^\//))
			            redirectTo = '/';

			        setTimeout(function() {
                        window.location.replace(redirectTo);
			        }, 2000);
                    break;

                case 'run':
                    eval(response[name]);
                    break;

                case 'title':
                    break;

                default:
                    var $response = $(response[name]);

                    $view = $response.filter(function() {
                        return this.nodeType === 1;
                    }).eq(0);

                    var wrapperId = $view.attr('id');
                    if (wrapperId) {

                        $("#"+wrapperId).replaceWith($view);

                        $.each(self.options.controllers, function(ctrlName) {
                            if(this._initialize(ctrlName)) {
                                this._prepare($view, name);
                            }
                        });
                    }
                    break;
                }
            }

            /**
             * Chain
             */
            return this;
        },

        _loadingTimer: null,

        _startLoading: function() {
            var self = this;
            if(this._loadingTimer == null) {
                this._loadingTimer = window.setTimeout(function() {
                    $('body').addClass('loading');
                    self._loadingTimer = null;
                }, 500);
            }
        },
        // End startLoading

        _stopLoading: function() {
            $('body').removeClass('loading');
            window.clearTimeout(this._loadingTimer);
            this._loadingTimer = null;
        }
        // End stopLoading
    };

}(window, jQuery));
