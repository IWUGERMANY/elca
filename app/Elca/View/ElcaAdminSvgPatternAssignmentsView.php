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
namespace Elca\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlTag;
use Elca\Db\ElcaProcessCategory;
use Elca\Db\ElcaProcessCategorySet;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSet;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaHtmlSvgPatternSelect;

/**
 * Builds list of svg patterns
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 */
class ElcaAdminSvgPatternAssignmentsView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_PROCESS_CONFIGS = 'process-configs';


    /** @var string $buildMode current buildMode */
    private $buildMode;

    /** @var object $Data */
    private $Data;

    /** @var  int $processCategoryNodeId */
    private $processCategoryNodeId;

    /** @var array $openedNodes */
    private $openedNodes = [];

    /** @var int $nodeChangeToPatternId */
    private $nodeChangeToPatternId;

    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->Data = $this->get('Data', new \stdClass());
        $this->buildMode = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->openedNodes = $this->get('openedNodes', []);
        $this->processCategoryNodeId = $this->get('processCategoryNodeId');
        $this->nodeChangeToPatternId = $this->get('nodeChangeToPatternId');
    }
    // End init



    /**
     * Renders the view
     */
    protected function beforeRender()
    {
        switch ($this->buildMode) {
            case self::BUILDMODE_DEFAULT:
                $this->buildDefault();
                break;

            case self::BUILDMODE_PROCESS_CONFIGS:
                $this->buildProcessConfigs();
                break;
        }
    }
    // End beforeRender


    /**
     * Builds the default view
     */
    protected function buildDefault()
    {
        /**
         * Add a container
         */
        $Container = $this->appendChild($this->getDiv(['id' => 'content',
                                                            'class' => 'svg-pattern-assignments']));

        $formId = 'adminSvgPatternAssignmentForm';
        $Form = new HtmlForm($formId, '/elca/admin-svg-patterns/saveAssignments/');
        $Form->setAttribute('id', $formId);
        $Form->addClass('clearfix highlight-changes');

        if ($this->Data) {
            $Form->setDataObject($this->Data);
        }

        if ($this->has('Validator'))
        {
            $Form->setValidator($this->get('Validator'));
            $Form->setRequest(FrontController::getInstance()->getRequest());
        }

        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');
        $ButtonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbrechen')));
        $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));

        $Group = $Form->add(new HtmlFormGroup(t('Zuordnung von Schraffuren zu Baustoffkategorien und -konfigurationen')));

        $Categories = ElcaProcessCategorySet::findByParent(ElcaProcessCategory::findRoot());

        $Ul = $Group->add(new HtmlTag('ul', null, ['class' => 'level1']));

        /** @var ElcaProcessCategory $Category */
        foreach ($Categories as $Category) {
            $catId = $Category->getNodeId();
            $openState = isset($this->openedNodes[$catId]) && $this->openedNodes[$catId]? 'open' : '';

            $Li = $Ul->add(new HtmlTag('li', null, ['class' => $openState, 'data-id' => $catId]));
            $Label = $Li->add(new ElcaHtmlFormElementLabel($Category->getRefNum().' '. t($Category->getName())));
            $Label->addClass('level1-item');

            $InnerUl = $Li->add(new HtmlTag('ul', null, ['class' => 'level2']));
            foreach (ElcaProcessCategorySet::findByParent($Category) as $SubCategory) {
                $subCatId = $SubCategory->getNodeId();
                $openState = isset($this->openedNodes[$subCatId]) && $this->openedNodes[$subCatId]? 'open' : '';

                $InnerLi = $InnerUl->add(new HtmlTag('li', null, ['class' => $openState, 'data-id' => $subCatId]));

                $InnerLi->add($label = new ElcaHtmlFormElementLabel($SubCategory->getRefNum().' '. t($SubCategory->getName()), new ElcaHtmlSvgPatternSelect('categoryPatternId['.$subCatId.']')));

                if ($openState) {
                    $ProcessConfigs = ElcaProcessConfigSet::find(['process_category_node_id' => $subCatId], ['name' => 'ASC']);
                    $this->appendProcessConfigs($InnerLi, $subCatId, $ProcessConfigs);
                } else {
                    $this->appendProcessConfigs($InnerLi, $subCatId, null, true);
                }
            }
        }
        $Form->appendTo($Container);
    }
    // End render


    /**
     *
     */
    protected function buildProcessConfigs()
    {
        $ProcessConfigs = ElcaProcessConfigSet::findByProcessCategoryNodeId($this->processCategoryNodeId, null, ['name' => 'ASC']);

        $Form = new HtmlForm('dummyForm', '/elca/admin-svg-patterns/saveAssignments/');
        $Form->setDataObject($this->Data);

        $Ul = $this->appendProcessConfigs($Form, $this->processCategoryNodeId, $ProcessConfigs);

        // do not append. just render form...
        $Form->build($this);

        // and bind only the ul to the container
        $Ul->appendTo($this);
    }
    // End appendProcessConfigs


    /**
     * @param HtmlElement          $Container
     * @param int                  $processCategoryNodeId
     * @param ElcaProcessConfigSet $ProcessConfigs
     * @param bool                 $isClosed
     * @return HtmlElement
     */
    protected function appendProcessConfigs(HtmlElement $Container, $processCategoryNodeId, ElcaProcessConfigSet $ProcessConfigs = null, $isClosed = false)
    {
        $Ul = $Container->add(new HtmlTag('ul', null, ['id' => 'level3_'. $processCategoryNodeId, 'class' => 'svg-pattern-assignments-process-configs level3']));

        if ($isClosed) {
            $Container->setAttribute('data-url', '/elca/admin-svg-patterns/assignments/?nodeId='. $processCategoryNodeId);
            $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'item']));
            $Li->add(new HtmlTag('span', null, ['class' => 'spinner']));

        } else {

            if ($ProcessConfigs->count()) {

                $defaultSvgPatternId = ElcaProcessCategory::findByNodeId($processCategoryNodeId)->getSvgPatternId();

                /** @var ElcaProcessConfig $ProcessConfig */
                foreach ($ProcessConfigs as $ProcessConfig) {
                    $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'item']));
                    $label = $Li->add(new ElcaHtmlFormElementLabel($ProcessConfig->getName(), $SvgPatternSelect = new ElcaHtmlSvgPatternSelect('configPatternId['.$ProcessConfig->getId().']')));
                    $SvgPatternSelect->setDefaultSvgPatternId($defaultSvgPatternId);

                    // mark inherited svg patterns as changed, if nodeChangedToPatternId is given
                    if (is_null($ProcessConfig->getSvgPatternId()) && $this->nodeChangeToPatternId) {
                       $SvgPatternSelect->setIsChanged();
                    }
                }

                $ButtonLi = $Ul->add(new HtmlTag('li', null, ['class' => 'buttons']));
                $ButtonLi->add(new ElcaHtmlSubmitButton('cancel', t('Abbrechen')));
                $ButtonLi->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));

            } else {
                $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'item']));
                $Li->add(new ElcaHtmlFormElementLabel(t('Keine Baustoffkonfigurationen')));
            }
        }

        return $Ul;
    }
    // End appendProcessConfigs

}
// End ElcaAdminSvgPatternAssignmentsView
