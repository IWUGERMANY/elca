<?php
namespace Elca\Model\Element;

use Elca\Db\ElcaElementComponent;

interface SearchAndReplaceObserver
{
    /**
     * @param ElcaElementComponent $elementComponent
     * @param                      $searchProcessConfigId
     * @param                      $replaceProcessConfigId
     * @return mixed
     */
    public function onElementComponenentSearchAndReplace(ElcaElementComponent $elementComponent, $searchProcessConfigId, $replaceProcessConfigId);
}
