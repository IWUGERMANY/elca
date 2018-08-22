<?php
/**
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

declare(strict_types = 1);

namespace Elca\Model\MessageBag;


/**
 * Handles system messages
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Moeller <fab@beibob.de>
 *
 */
interface MessageBag
{
    /**
     * Message types
     *
     * NOTICE - displays a message for 2 seconds
     * INFO   - displays a info message. The user has to click on the message to hide it
     * ERROR  - displays a error message. The user has to click on the message to hide it
     * CONFIRM - display a confirm message. The user has to accept or decline the message.
     */
    const TYPE_NOTICE = 0;
    const TYPE_INFO   = 1;
    const TYPE_ERROR  = 2;
    const TYPE_CONFIRM = 3;

    /**
     * Adds a message
     *
     * @param string $message
     * @param int    $type
     * @param string $confirmUrl - only for message type CONFIRM
     */
    public function add($message, $type = self::TYPE_NOTICE, $confirmUrl = null);

    /**
     * Returns true if there are messages
     *
     * @param  int $type
     * @return boolean
     */
    public function has($type = null);

    /**
     * Returns all messages or of a certain type
     *
     * @param  int $type
     * @return array
     */
    public function get($type = null);

    /**
     * @param null $type
     */
    public function clear($type = null);

    /**
     * @param MessageBag $bag
     */
    public function appendBag(MessageBag $bag);
}