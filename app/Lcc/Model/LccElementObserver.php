<?php
namespace Lcc\Model;

use Elca\Db\ElcaElement;
use Elca\Model\Element\ElementObserver;
use Lcc\Db\LccElementCost;
use Lcc\Db\LccProjectVersion;
use Lcc\Db\LccVersion;
use Lcc\LccModule;

/**
 * LccElementObserver
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class LccElementObserver implements ElementObserver
{
    /**
     * @param ElcaElement $element
     * @param ElcaElement $copiedElement
     * @return mixed
     */
    public function onElementCopy(ElcaElement $element, ElcaElement $copiedElement)
    {
        $elementCost = LccElementCost::findByElementId($element->getId());

        if (!$elementCost->isInitialized()) {
            return;
        }

        $elementCost->copy($copiedElement->getId());

        if ($projectVariantId = $copiedElement->getProjectVariantId()) {
            $version = LccProjectVersion::findByPK($projectVariantId, LccModule::CALC_METHOD_DETAILED);

            if (!$version->isInitialized()) {
                $version = LccProjectVersion::create(
                    $projectVariantId,
                    LccModule::CALC_METHOD_DETAILED,
                    LccVersion::findRecent(LccModule::CALC_METHOD_DETAILED)->getId()
                );
            }
            $version->computeLcc();
        }
    }

    /**
     * @param ElcaElement $elementToDelete
     * @param bool        $deleteRecursive
     * @return bool
     */
    public function onElementDelete(ElcaElement $elementToDelete)
    {
    }

    /**
     * @param int  $elementId
     * @param null $projectVariantId
     */
    public function afterDeletion($elementId, $projectVariantId = null)
    {
        if ($projectVariantId === null) {
            return;
        }

        $version = LccProjectVersion::findByPK($projectVariantId, LccModule::CALC_METHOD_DETAILED);
        $version->computeLcc();
    }

    /**
     * @param ElcaElement $createdElement
     * @return mixed
     */
    public function onElementCreate(ElcaElement $createdElement)
    {
    }

    /**
     * @param ElcaElement $updatedElement
     * @return mixed
     */
    public function onElementUpdate(ElcaElement $updatedElement)
    {
    }
}