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
namespace Elca\Model\Event;

trait InvokeEventMethodTrait
{
    /**
     * @param Event $event
     * @return bool
     */
    public function isSubscribedTo(Event $event)
    {
        return method_exists($this, $this->buildMethodName($event));
    }

    /**
     * @param Event $event
     */
    public function handle(Event $event)
    {
        $methodName = $this->buildMethodName($event);

        if (!method_exists($this, $methodName)) {
            throw new UnknownEventMethodException('Event method `'. $methodName .'\' is not implemented');
        }

        call_user_func_array([$this, $methodName], func_get_args());
    }

    /**
     * @param Event $event
     * @return string
     */
    protected function buildMethodName(Event $event)
    {
        return sprintf('on%s', EventName::fromEvent($event));
    }

    /**
     * Check whether the listener is the given parameter.
     *
     * @param mixed $listener
     *
     * @return bool
     */
    public function isListener($listener)
    {
        return $this === $listener;
    }
}