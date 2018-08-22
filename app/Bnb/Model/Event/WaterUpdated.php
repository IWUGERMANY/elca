<?php
namespace Bnb\Model\Event;

use Bnb\Db\BnbWater;
use DateTime;
use Elca\Model\Event\Event;

class WaterUpdated implements Event
{
    /**
     * @var DateTime
     */
    private $occurredOn;

    /**
     * @var int
     */
    private $projectVariantId;

    /**
     * WaterUpdated constructor.
     *
     * @param BnbWater $water
     * @param int      $projectVariantId
     */
    public function __construct($projectVariantId)
    {
        $this->projectVariantId = $projectVariantId;

        $this->occurredOn = new DateTime();
    }

    /**
     * @return int
     */
    public function projectVariantId()
    {
        return $this->projectVariantId;
    }

    /**
     * @return DateTime
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }
}