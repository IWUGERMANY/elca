<?php
namespace Elca\Model\Event;

class EventName
{
    /**
     * @var string
     */
    private $typeClass;

    /**
     * @param $event
     * @return static
     */
    public static function fromEvent($event)
    {
        return new static(get_class($event));
    }

    /**
     * EventName constructor.
     *
     * @param string $typeClass
     */
    public function __construct($typeClass)
    {
        $this->typeClass = $typeClass;
    }

    /**
     * @return string
     */
    public function name()
    {
        $class = $this->typeClass;

        if (\utf8_substr($class, -5) === 'Event') {
            $class = \utf8_substr($class, 0, -5);
        }
        if (\utf8_strpos($class, '\\') === false) {
            return $class;
        }

        $parts = explode('\\', $class);
        return end($parts);
    }

    /**
     * @return string
     */
    public function typeClass()
    {
        return $this->typeClass;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name();
    }
}