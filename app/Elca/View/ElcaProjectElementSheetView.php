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

use Beibob\Blibs\BlibsDateTime;
use Beibob\Blibs\Url;
use Elca\Db\ElcaCompositeElementSet;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSet;
use Elca\ElcaNumberFormat;
use Elca\Security\ElcaAccess;
use Elca\Service\Assistant\ElementAssistant;

/**
 * Builds a project element sheet
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectElementSheetView extends ElcaElementSheetView
{
    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        $Container = $this->getContainer();
        $this->addClass($Container, 'elca-element-sheet');

        $readOnly = $this->get('readOnly', false);

        $this->addFunction($readOnly ? 'view' : 'edit', '/project-elements/$$itemId$$/', $readOnly ? t('Ansehen') : t('Bearbeiten'), 'default page');

        if ($this->assistant instanceof ElementAssistant) {
            $this->addClass($Container, 'element-assistant');
        }

        if (!$readOnly) {
            $Assignments = ElcaCompositeElementSet::find(['element_id' => $this->element->getId()]);

            if ($Assignments->count()) {
                $compositeElementId = $Assignments[0]->getCompositeElementId();
                $position           = $Assignments[0]->getPosition();
                $this->addElementSelectors();
                $this->addClass($Container, 'composite-element');

                if (!$this->assistant || !$this->assistant->isLockedFunction(
                        ElementAssistant::FUNCTION_ELEMENT_UNASSIGN_ELEMENT,
                        $this->element
                    )
                ) {
                    $this->addFunction(
                        'unassign',
                        '/project-elements/unassignComposite/?id=$$itemId$$&compositeElementId='.$compositeElementId.'&pos='.$position,
                        t('Bauteilverknüpfung lösen')
                    );
                }
            } else {
                $Url = Url::factory(
                    '/project-elements/selectElement/',
                    [
                        'relId' => $this->element->getId(),
                        't'     => $this->element->getElementTypeNode()->getParent()->getNodeId(),
                        'b'     => ElcaElementSelectorView::BUILDMODE_COMPOSITES
                    ]
                );

                if (!$this->element->isComposite()) {
                    if (!$this->assistant || !$this->assistant->isLockedFunction(
                            ElementAssistant::FUNCTION_ELEMENT_ASSIGN_TO_ELEMENT,
                            $this->element
                        )
                    ) {
                        $this->addFunction('assign', (string)$Url, t('Mit Bauteil verknüpfen'));
                    }
                }
            }

            if (!$this->assistant || !$this->assistant->isLockedFunction(
                    ElementAssistant::FUNCTION_ELEMENT_COPY,
                    $this->element
                )
            ) {
                $this->addFunction('copy', '/project-elements/copy/?id=$$itemId$$', t('Kopieren'));
            }


            if ($this->element->isComposite()) {
                if (!$this->assistant || !$this->assistant->isLockedFunction(
                        ElementAssistant::FUNCTION_ELEMENT_DELETE_RECURSIVE,
                        $this->element
                    )
                ) {
                    $this->addFunction(
                        'delete',
                        '/project-elements/delete/?id=$$itemId$$&recursive',
                        t('Bauteil und Komponenten löschen')
                    );
                }
            } else {
                if (!$this->assistant || !$this->assistant->isLockedFunction(
                        ElementAssistant::FUNCTION_ELEMENT_MOVE,
                        $this->element
                    )
                ) {
                    $this->addFunction(
                        'move-element',
                        '/project-elements/moveElement/?id=$$itemId$$',
                        t('Verschieben')
                    );
                }
                if (!$this->assistant || !$this->assistant->isLockedFunction(
                        ElementAssistant::FUNCTION_ELEMENT_DELETE,
                        $this->element
                    )
                ) {
                    $this->addFunction('delete', '/project-elements/delete/?id=$$itemId$$', t('Löschen'));
                }
            }
        }

        /**
         * Append individual content
         */
        $this->addDescription($this->element->getDescription());
        $this->addInfo(ElcaNumberFormat::toString($this->element->getQuantity()), t('Menge'), $this->element->getRefUnit());

        if($this->element->isComposite())
            $ProcessConfigs = ElcaProcessConfigSet::findByCompositeElementId($this->get('itemId'), ['name' => 'ASC']);
        else
            $ProcessConfigs = ElcaProcessConfigSet::findByElementId($this->get('itemId'), ['name' => 'ASC']);

        $this->addInfo(
            implode(', ', $ProcessConfigs->map(function(ElcaProcessConfig $processConfig) {return \processConfigName($processConfig->getId());})),
            \t('Baustoffe'), null, true
        );

        $ownerName = $this->element->getOwnerId() !== ElcaAccess::getInstance()->getUserId()
            ? $this->element->getOwner()->getIdentifier()
            : \t('Ihnen');

        $this->addInfo($ownerName, t('Erstellt von'), null, true);

        $dateTime = BlibsDateTime::factory($this->element->getCreated());
        $this->addInfo($dateTime->getDate()->getDateString(t('DATETIME_FORMAT_DMY') . ', ')  .  $dateTime->getTime()->getTimeString(t('DATETIME_FORMAT_HI')) .' '. t('Uhr'), t('am'));

        $this->addElementImage();
    }
    // End beforeRender
}
// End ElcaProjectElementSheetView
