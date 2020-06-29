<?php declare(strict_types=1);

namespace Elca\Service\Assistant\Pillar;

use Beibob\Blibs\Log;
use Elca\Controller\Assistant\FoundationCtrl;
use Elca\Model\Assistant\Configuration;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Service\Assistant\ElementAssistant;
use Elca\View\Assistant\FoundationAssistantImageView;
use Elca\View\DefaultElementImageView;

class FoundationAssistant extends PillarAssistant
{
    const IDENT = 'foundation-assistant';

    /**
     * Constructor
     *
     * @param ElcaLcaProcessor $lcaProcessor
     */
    public function __construct(ElcaLcaProcessor $lcaProcessor, Log $log)
    {
        parent::__construct($lcaProcessor, $log);

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
