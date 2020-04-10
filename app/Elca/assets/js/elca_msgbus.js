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
(function(window, $, undefined) {

    var elca = (window.elca = window.elca || {});
    elca.msgBus = (elca.msgBus = elca.msgBus || new (function () {

            var self = this;

            this.broadcastChannel = new BroadcastChannel('msg-bus');
            this.handlers = {};

            this.registerHandler = function (eventType, handler) {
                console.debug('Register a new event handler for '+ eventType);

                if (typeof this.handlers[eventType] == 'undefined') {
                    this.handlers[eventType] = [];
                }

                this.handlers[eventType].push(handler);
            };

            this.submit = function (eventType, data) {
                console.debug('Submit a new event of type ['+ eventType + ']', data);

                data.event = eventType;
                this.broadcastChannel.postMessage(data);
            };


            this.broadcastChannel.onmessage = function (event) {
                console.debug('Event ['+ (event.data.event || '<unknown>') + '] received ', event.data);

                var data = event.data;

                if (!data.event) {
                    console.error('Message without event type', event);
                    return;
                }

                var eventType = data.event;

                var handlers = self.handlers[eventType] || [];

                console.debug('Found '+ handlers.length + ' handlers for this event');

                handlers.forEach(function (handler) {
                    handler(data);
                });
            };

            console.debug("eLCA msgBus initialized");
        }));

}(window, jQuery));


