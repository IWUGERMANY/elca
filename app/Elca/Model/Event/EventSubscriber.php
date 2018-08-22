<?php
namespace Elca\Model\Event;

interface EventSubscriber
{
    /**
     * @param Event $event
     * @return bool
     */
    public function isSubscribedTo(Event $event);

    /**
     * @param Event $event
     */
    public function handle(Event $event);
}
