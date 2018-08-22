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
namespace Elca\Service\Messages;

use Elca\Model\MessageBag\MessageBag;

/**
 * Handles system messages
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaMessages implements MessageBag
{
    /**
     * Instance
     */
    private static $Instance;

    /**
     * Holds all messages
     */
    private $messages = [];

    /**
     * Constructs the object
     */
    public function __construct()
    {
        $this->messages[self::TYPE_NOTICE]  = [];
        $this->messages[self::TYPE_INFO]    = [];
        $this->messages[self::TYPE_ERROR]   = [];
        $this->messages[self::TYPE_CONFIRM] = [];
    }

    /**
     * Adds a message
     *
     * @param string $message
     * @param int    $type
     * @param string $confirmUrl - only for message type CONFIRM
     */
    public function add($message, $type = self::TYPE_NOTICE, $confirmUrl = null)
    {
        if ($confirmUrl) {
            $this->messages[$type][$confirmUrl] = $message;
        } else {
            $this->messages[$type][] = $message;
        }
    }

    /**
     * Returns true if there are messages
     *
     * @param  int $type
     * @return boolean
     */
    public function has($type = null)
    {
        if ($type === null) {
            return (!empty($this->messages[self::TYPE_NOTICE]) ||
                    !empty($this->messages[self::TYPE_INFO]) ||
                    !empty($this->messages[self::TYPE_ERROR]) ||
                    !empty($this->messages[self::TYPE_CONFIRM])
            );
        }

        return !empty($this->messages[$type]);
    }


    /**
     * Returns all messages or of a certain type
     *
     * @param  int $type
     * @return array
     */
    public function get($type = null)
    {
        if ($type === null) {
            return $this->messages;
        }

        return $this->messages[$type];
    }

    /**
     * @param null $type
     */
    public function clear($type = null)
    {
        if ($type === null) {
            $this->messages = [];
        }

        $this->messages[$type] = [];
    }

    /**
     * @param MessageBag $bag
     */
    public function appendBag(MessageBag $bag)
    {
        foreach ($bag->get() as $type => $messages) {
            foreach ($messages as $url => $message) {
                if ($type === self::TYPE_CONFIRM) {
                    $this->add($message, $type, $url);
                } else {
                    $this->add($message, $type);
                }
            }
        }
    }
}