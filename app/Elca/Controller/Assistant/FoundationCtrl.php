<?php declare(strict_types=1);

namespace Elca\Controller\Assistant;

use Beibob\Blibs\Interfaces\Viewable;
use Elca\Service\Assistant\Pillar\FoundationAssistant;
use Elca\View\Assistant\FoundationAssistantView;

class FoundationCtrl extends PillarCtrl
{
    const CONTEXT = 'assistant/foundation';

    /**
     * @var FoundationAssistant
     */
    protected $assistant;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->assistant = $this->container->get(FoundationAssistant::class);
    }

    /**
     * @return Viewable
     */
    protected function getAssistantView() : Viewable
    {
        return new FoundationAssistantView();
    }

}
