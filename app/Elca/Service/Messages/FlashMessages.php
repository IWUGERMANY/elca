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

use Beibob\Blibs\SessionNamespace;
use Elca\Model\MessageBag\MessageBag;

class FlashMessages implements MessageBag
{
    /**
     * @var SessionNamespace
     */
    private $sessionNamespace;

    /**
     * FlashMessages constructor.
     *
     * @param SessionNamespace $sessionNamespace
     */
    public function __construct(SessionNamespace $sessionNamespace = null)
    {
        if (null === $sessionNamespace) {
            $sessionNamespace = (object)['messages' => null];
        }

        $this->sessionNamespace = $sessionNamespace;

        if (!$this->messageBagIsInitialized()) {
            $this->sessionNamespace->messages = new ElcaMessages();
        }
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
        if (!$this->messageBagIsInitialized()) {
            return;
        }

        $this->sessionNamespace->messages->add($message, $type, $confirmUrl);
    }

    /**
     * Returns true if there are messages
     *
     * @param  int $type
     * @return boolean
     */
    public function has($type = null)
    {
        if (!$this->messageBagIsInitialized()) {
            return false;
        }

        return $this->sessionNamespace->messages->has($type);
    }

    /**
     * Returns all messages or of a certain type
     *
     * @param  int $type
     * @return array
     */
    public function get($type = null)
    {
        if (!$this->messageBagIsInitialized()) {
            return [];
        }

        $messages = $this->sessionNamespace->messages->get($type);
        $this->sessionNamespace->messages->clear($type);

        return $messages;
    }

    /**
     * @param null $type
     */
    public function clear($type = null)
    {
        if (!$this->messageBagIsInitialized()) {
            return;
        }

        $this->sessionNamespace->messages->clear($type);
    }

    /**
     * @param MessageBag $bag
     */
    public function appendBag(MessageBag $bag)
    {
        if (!$this->messageBagIsInitialized()) {
            return;
        }

        $this->sessionNamespace->messages->appendBag($bag);
    }

    /**
     * @return bool
     */
    protected function messageBagIsInitialized(): bool
    {
        return isset($this->sessionNamespace->messages);
    }
}
