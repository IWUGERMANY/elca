<?php


namespace Elca\Model\Common;


use Elca\Model\Exception\InvalidArgumentException;

final class Optional
{
    private $value;

    public static function of($value) : Optional
    {
        if (null === $value) {
            throw new InvalidArgumentException("Value must not be null");
        }

        return new Optional($value);
    }

    public static function ofNullable($value) : Optional
    {
        return new Optional($value);
    }

    public static function ofEmpty() : Optional
    {
        return new Optional();
    }

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function get()
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return null === $this->value;
    }

    public function isPresent(): bool
    {
        return !$this->isEmpty();
    }

    public function ifPresent(\Closure $lambda) : void
    {
        if (null !== $this->value) {
            $lambda($this->value);
        }
    }

    public function map(\Closure $lambda) : Optional
    {
        if (null === $this->value) {
            return self::ofEmpty();
        }

        return Optional::ofNullable($lambda($this->value));
    }

    public function orElse($value)
    {
        if (null !== $this->value) {
            return $this->value;
        }

        return \is_callable($value)
            ? $value()
            : $value;
    }


    public function equals(Optional $object)
    {
        return $this->value === $object->value;
    }
}