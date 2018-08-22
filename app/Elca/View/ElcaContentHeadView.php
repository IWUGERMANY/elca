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
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Elca\Db\ElcaProjectPhase;
use Elca\Db\ElcaProjectPhaseSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaProjectVariantSet;
use Elca\Elca;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds the content head
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaContentHeadView extends HtmlView
{
    /**
     * Inits the view
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_content_head');
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Called before render
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        /**
         * headline management
         */
        if(!$this->get('Project'))
        {
            $this->removeChild($this->getElementById('contentHead'));

            /**
             * Append null content
             */
            $this->appendChild($this->getText());
        }
        else
        {
            $this->getElementById('contentHead')->removeAttribute('class');
            $Elca = Elca::getInstance();
            $Access = ElcaAccess::getInstance();
            $Project = $this->get('Project');

            // get current Project Variant
            $currentVariantId = $Elca->getProjectVariantId() ? $Elca->getProjectVariantId() : $Project->getCurrentVariantId();
            $ProjectVariant = ElcaProjectVariant::findById($currentVariantId);

            // get latest Project Variant
            $latestVariantId = $Project->getCurrentVariantId();
            $LatestProjectVariant = ElcaProjectVariant::findById($latestVariantId);

            // get all Project Phases with projectPhase.constr_measure == project.constr_measure
            $ProjectPhasesSet = ElcaProjectPhaseSet::find(['constr_measure' => $Project->getConstrMeasure() ], ['step' => 'ASC'] );

            // get all existing project phases for the project
            $existingPhaseIds = [];
            $ProjectVariantsSet = ElcaProjectVariantSet::find(['project_id' => $ProjectVariant->getProjectId()]);
            foreach ($ProjectVariantsSet as $Variant) {
                $ProjectPhase = ElcaProjectPhase::findById($Variant->getPhaseId());
                $existingPhaseIds[] = $ProjectPhase->getId();
            }

            $Ul = $this->getElementById('projectPhases');
            foreach ($ProjectPhasesSet as $Phase)
            {
                $linkName = ucfirst(t($Phase->getName()));
                $isLink = false;
                $args = [];

                // case 1 : phase is actual phase
                if ($Phase->getId() == $ProjectVariant->getPhaseId())
                    $args['class'] = 'active';

                // case 2 : phase already exists - link to it
                elseif(in_array($Phase->getId(), $existingPhaseIds))
                {
                    $args['href'] = FrontController::getInstance()->getUrlTo('Elca\Controller\ProjectsCtrl', 'changePhase', ['id' => $Project->getId(), 'phase' => $Phase->getId()]);
                    $isLink = true;
                }

                // case 3 : phase does not exist and its step is greater than the current phase step . create button
                elseif (
                   $Phase->getStep() > $Project->getCurrentVariant()->getPhase()->getStep() &&
                   $Access->isProjectOwnerOrAdmin($Project)
                )
                {
                    $linkName = '+ ' . $linkName;
                    $args['href'] = FrontController::getInstance()->getUrlTo('Elca\Controller\ProjectsCtrl', 'newPhase', ['id' => $Project->getId(), 'phase' => $Phase->getId()]);
                    $args['class'] = 'greyed_out';
                    $isLink = true;
                }
                // phase does not exist and is first phase
                elseif (0 === $Phase->getStep()) {
                    continue;
                }
                // phase does not exist
                else
                    $args['class'] = 'greyed_out';

                $Li = $Ul->appendChild($this->getLi());
                if ($isLink)
                    $Li->appendChild($this->getA($args, $linkName));
                else
                    $Li->appendChild($this->getSpan($linkName, $args));
            }

            // drop down mit project varianten
            $phaseId =  Elca::getInstance()->getProjectVariant()->getPhaseId();
            $ProjectVariantSet = ElcaProjectVariantSet::find(['project_id' => $Project->getId(), 'phase_id' => $phaseId]  );

            $Container = $this->getElementById('projectVariants');
            $Form = new HtmlForm('generalForm', '/projects/changeVariant/');
            $Form->addClass('highlight-changes');
            $Select = $Form->add(new ElcaHtmlFormElementLabel(t('Varianten'), new HtmlSelectbox('projectVariant')));
            $Select->setAttribute('id','selectProjectVariant');
            foreach ($ProjectVariantSet as $Variant) {
                $Option = $Select->add(new HtmlSelectOption($Variant->getName(), $Variant->getId()));
                if ($Variant->getId() == Elca::getInstance()->getProjectVariantId() ) {
                    $Option->setAttribute('selected', 'selected');
                }
            }

            $Form->add(new ElcaHtmlSubmitButton('save', t('Auswählen'), true));

            $Form->appendTo($Container);
        }
    }
    // End beforeRender
}
// End ElcaContentHeadView
