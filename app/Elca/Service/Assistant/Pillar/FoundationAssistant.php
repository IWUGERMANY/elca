<?php declare(strict_types=1);

namespace Elca\Service\Assistant\Pillar;

use Elca\Controller\Assistant\FoundationCtrl;
use Elca\Controller\Assistant\PillarCtrl;
use Elca\Model\Assistant\Configuration;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Service\Assistant\ElementAssistant;
use Elca\View\Assistant\FoundationAssistantImageView;
use Elca\View\Assistant\PillarElementImageView;
use Elca\View\DefaultElementImageView;

class FoundationAssistant extends PillarAssistant
{
    const IDENT = 'foundation-assistant';

    /**
     * Constructor
     *
     * @param ElcaLcaProcessor $lcaProcessor
     */
    public function __construct(ElcaLcaProcessor $lcaProcessor)
    {
        parent::__construct($lcaProcessor);

        $this->setConfiguration(
            new Configuration(
                [
                    322,
                ],
                static::IDENT,
                'Streifenfundament',
                FoundationCtrl::class,
                'default',
                [
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_SIZE,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_AREA_RATIO,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_LENGTH,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_WIDTH,
                    ElementAssistant::PROPERTY_COMPONENT_PROCESS_CONFIG_ID,
                    ElementAssistant::PROPERTY_COMPONENT_QUANTITY,
                    ElementAssistant::PROPERTY_COMPONENT_CONVERSION_ID,
                    ElementAssistant::PROPERTY_REF_UNIT,
                ],
                [
                    ElementAssistant::FUNCTION_COMPONENT_DELETE,
                    ElementAssistant::FUNCTION_COMPONENT_DELETE_COMPONENT,
                    ElementAssistant::FUNCTION_COMPONENT_ADD_COMPONENT_SIBLING,
                    ElementAssistant::FUNCTION_COMPONENT_ADD_LAYER,
                ]
            )
        );
    }

    /**
     * @param $elementId
     * @return null|string
     */
    protected function getDefaultName($elementId)
    {
        return 'Streifenfundament';
    }

    /**
     * @param int $elementId
     *
     * @return DefaultElementImageView
     */
    public function getElementImageView($elementId)
    {
        return new FoundationAssistantImageView();
    }
}
