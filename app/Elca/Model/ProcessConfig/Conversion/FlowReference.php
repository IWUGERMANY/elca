<?php


namespace Elca\Model\ProcessConfig\Conversion;


class FlowReference
{
    /**
     * @var string
     */
    private $flowUuid;

    /**
     * @var string|null
     */
    private $flowVersion;

    public static function from(string $flowUuid, string $flowVersion = null)
    {
        if (!$flowUuid) {
            throw new \InvalidArgumentException("FlowUuid must not be null ");
        }

        return new self($flowUuid, $flowVersion);
    }

    public function __construct(string $flowUuid, string $flowVersion = null)
    {
        $this->flowUuid    = $flowUuid;
        $this->flowVersion = $flowVersion;
    }

    public function flowUuid(): string
    {
        return $this->flowUuid;
    }

    public function flowVersion(): ?string
    {
        return $this->flowVersion;
    }

    public function __toString()
    {
        if (null !== $this->flowVersion) {
            return sprintf("FlowReference{flowUuid=%s,flowVersion=%s}", $this->flowUuid, $this->flowVersion);
        }

        return sprintf("FlowReference{flowUuid=%s}", $this->flowUuid);
    }


    /**
     * @return bool
     * @var self $object
     */
    public function equals(self $object)
    {
        return $this == $object;
    }
}