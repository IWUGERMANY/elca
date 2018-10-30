<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2016 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * eLCA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * eLCA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace Elca\View\helpers;

use Beibob\Blibs\HtmlDOMFactory;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlFormElement;
use DOMDocument;
use Elca\Db\ElcaProcess;

/**
 * Link to the process selector
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaHtmlProcessSelectorLink extends HtmlFormElement
{
    /**
     * Parameters
     */
    private $processDbId;
    private $processConfigId;
    private $processCategoryNodeId;
    private $processLifeCycleAssignmentId;
    private $lifeCycleIdent;


    /**
     * Sets the processDbId
     */
    public function setProcessDbId($processDbId)
    {
        $this->processDbId = $processDbId;
    }
    // End setProcessDbId


    /**
     * Sets the processConfigId
     */
    public function setProcessConfigId($processConfigId)
    {
        $this->processConfigId = $processConfigId;
    }
    // End setProcessConfigId


    /**
     * Sets the processCategoryNodeId
     */
    public function setProcessCategoryNodeId($processCategoryNodeId)
    {
        $this->processCategoryNodeId = $processCategoryNodeId;
    }
    // End setProcessCategoryNodeId


    /**
     * Sets the processLifeCycleAssignmentId
     */
    public function setProcessLifeCycleAssignmentId($processLifeCycleAssignmentId)
    {
        $this->processLifeCycleAssignmentId = $processLifeCycleAssignmentId;
    }
    // End setProcessLifeCycleAssignmentId


    /**
     * Sets the life cycle ident
     */
    public function setLifeCycleIdent($lifeCycleIdent)
    {
        $this->lifeCycleIdent = $lifeCycleIdent;
    }
    // End setLifeCycleIdent


    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        if($procId = $this->getConvertedTextValue())
        {
            $Process = ElcaProcess::findById($procId);
            $procName = \processName($procId);

            if($Process->getScenarioId())
                $procName .= ' ['. $Process->getScenario()->getDescription() .']';

            $this->processCategoryNodeId = $Process->getProcessCategoryNodeId();
        }
        else {
            $procName = $this->isReadonly() ? '' : t('auswählen');
        }

        $href = Url::factory('/processes/selectProcess/',
                             ['p'  => $procId,
                                   'processConfigId'  => $this->processConfigId,
                                   'processCategoryNodeId'  => $this->processCategoryNodeId,
                                   'processDbId' => $this->processDbId,
                                   'lc' => $this->lifeCycleIdent,
                                   'plcaId' => $this->processLifeCycleAssignmentId
                                   ]);

        $Factory = new HtmlDOMFactory($Document);

        if($this->isReadonly())
            $A = $Factory->getSpan($procName);

        else
        {
            $aAttr = ['href' => $href,
                           'title' => $procName,
                           'rel'   => 'open-modal'];
            $A = $Factory->getA($aAttr, $procName);
            $A->appendChild($Factory->getHiddenInput($this->getName(), $procId));
        }

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($A, $this->getDataObject(), $this->getName());

        foreach($this->getChildren() as $Child)
            $Child->appendTo($A);

        return $A;
    }
    // End build

}
// End ElcaHtmlProcessSelectorLink
