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
namespace Bnb\Controller;

use Bnb\View\BnbEolSeparationRecyclingView;
use Elca\Controller\AppCtrl;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementAttributeSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Service\Messages\ElcaMessages;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaProjectNavigationLeftView;

/**
 * Water controller
 *
 * @package bnb
 * @author Tobias Lode <tobias@beibob.de>
 */
class EolSeparationRecyclingCtrl extends AppCtrl
{
    /**
     * Default action
     *
     * @param bool $addNavigationViews
     * @param -
     * @return void -
     */
    protected function defaultAction($addNavigationViews = true, $Validator = null)
    {
        if(!$this->isAjax())
            return;

        $Attributes = ElcaElementAttributeSet::findEolSeparationRecyclingByProjecVariantId($this->Elca->getProjectVariantId(),
                                                                                           null,
                                                                                           ['element_type_node_din_code' => 'ASC',
                                                                                                 'element_name' => 'ASC'
                                                                                           ]);

        $Data = new \stdClass();
        $Data->eol = [];
        $Data->separation = [];
        $Data->recycling = [];

        $Data->projectVariantId = $this->Elca->getProjectVariantId();

        /** @var ElcaElementAttribute $Attribute */
        foreach ($Attributes as $Attribute) {
            $elementId = $Attribute->getElementId();
            list (,,$property) = explode('.', $Attribute->getIdent());
            $Data->$property += [$elementId => $Attribute->getNumericValue()];
        }

        $View = $this->setView(new BnbEolSeparationRecyclingView());
        $View->assign('Data', $Data);
        $View->assign('readOnly', !$this->Access->canEditProject($this->Elca->getProject()));

        if ($Validator)
            $View->assign('Validator', $Validator);

        /**
         * Add navigation
         */
        if($addNavigationViews)
        {
            $this->addView(new ElcaProjectNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Rückbau, Trennung und Verwertung (4.1.4)'), null, t('Technische Qualität')));
        }
    }
    // End defaultAction


    /**
     *
     */
    protected function saveAction()
    {
        if (!$this->isAjax())
            return;

        if (!$this->checkProjectAccess() || !$this->Access->canEditProject($this->Elca->getProject())) {
            return;
        }

        if ($this->Request->has('save')) {

            $Validator = new ElcaValidator($this->Request);


            foreach ([Elca::ELEMENT_ATTR_EOL => 'eol',
                           Elca::ELEMENT_ATTR_SEPARATION=> 'separation',
                           Elca::ELEMENT_ATTR_RECYCLING => 'recycling'] as $ident => $reqKey) {
                $data = $this->Request->getArray($reqKey);

                foreach ($data as $elementId => $value) {

                    $value = ElcaNumberFormat::fromString($value);

                    $Validator->assertNumberRange($reqKey.'['.$elementId.']', 0, 5, t('Der Wert ist unzulässig und muss zwischen 0 und 5 liegen'));

                    $Attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, $ident);
                    if ($Attr->isInitialized()) {
                        $Attr->setNumericValue($value);
                        $Attr->update();
                    } else {
                        ElcaElementAttribute::create($elementId, $ident, Elca::$elementBnbAttributes[$ident], $value);
                    }
                }
            }

            if ($Validator->isValid()) {
                $this->messages->add(t('Die Werte wurden gespeichert'));
            } else {
                $errors = $Validator->getErrors();
                $this->messages->add(array_shift($errors), ElcaMessages::TYPE_ERROR);
            }
        }

        $this->defaultAction(false, $Validator);
    }
    // End saveAction


}
// End EolSeparationRecyclingCtrl