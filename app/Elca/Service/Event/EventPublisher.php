<?php
namespace Elca\Service\Event;

use Elca\Model\Event\Event;
use Elca\Model\Event\EventSubscriber;

class EventPublisher
{
    /**
     * @var EventSubscriber[]
     */
    private $subscriber;

    /**
     * EventPublisher constructor
     */
    public function __construct(array $subscriber = [])
    {
        $this->subscriber = $subscriber;
    }

    /**
     * @param EventSubscriber $subscriber
     */
    public function subscribe(EventSubscriber $subscriber)
    {
        $this->subscriber[] = $subscriber;
    }

    /**
     * @param Event $event
     */
    public function publish(Event $event)
    {
        foreach ($this->subscriber as $subscriber) {
            if ($subscriber->isSubscribedTo($event)) {
                $subscriber->handle($event);
            }
        }
    }

    /**
     * @param array $events
     */
    public function publishAll(array $events)
    {
        foreach ($events as $event) {
            $this->publish($event);
        }
    }
}
