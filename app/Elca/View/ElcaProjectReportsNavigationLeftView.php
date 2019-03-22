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

use Beibob\Blibs\HtmlView;
use Elca\Controller\ProjectReportAssetsCtrl;
use Elca\Controller\ProjectReportEffectsCtrl;
use Elca\Controller\ProjectReportsCtrl;
use Elca\Controller\Report\EpdTypesCtrl;
use Elca\Controller\Report\ExtantSavingsCtrl;
use Elca\Elca;
use Elca\Model\Navigation\ElcaNavigation;

/**
 * Builds the project reports navigation on the left
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectReportsNavigationLeftView extends HtmlView
{
    /**
     * Additional navigations
     */
    private $additionalNavigations = [];

    /**
     * Inits the view
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $dbIsEn15804Compliant = Elca::getInstance()->getProject()->getProcessDb()->isEn15804Compliant();

        $Navigation = ElcaNavigation::getInstance('projectReports');
        $Item = $Navigation->add(t('Allgemein'));
        $Item->add(t('Gesamtbilanz'), 'elca', ProjectReportsCtrl::class, 'summary');
        $Item->add(t('Bilanz nach Bauteilgruppen'), 'elca', ProjectReportsCtrl::class, 'summaryElementTypes');
        $Item->add(t('Benchmarks'), 'elca', ProjectReportsCtrl::class, 'benchmarks');

        if ($dbIsEn15804Compliant) {
            $Item->add(t('Bilanz nach EPD Typen'), 'elca', EpdTypesCtrl::class, 'epdTypes');
        }
        $Item->add(t('Bauteilkatalog'), 'elca', ProjectReportsCtrl::class, 'elements');
        $Item->add(t('Eigene Nutzungsdauern'), 'elca', ProjectReportAssetsCtrl::class, 'nonDefaultLifeTime');

        if ($dbIsEn15804Compliant) {
            $Item->add(t('Zusätzliche Indikatoren'), 'elca', ProjectReportsCtrl::class, 'summaryAdditionalIndicators');
        }
        $Item->add(t('Nicht bilanziert'), 'elca', ProjectReportAssetsCtrl::class, 'notCalculatedComponents');

        if (Elca::getInstance()->getProject()->getProjectConstruction()->isExtantBuilding()) {
            $Item->add(t('Eingesparte Umweltwirkungen'), 'elca', ExtantSavingsCtrl::class, 'savings');
        }

        $Item->add(t('Auswertung pro Person'), 'elca', ProjectReportsCtrl::class, 'summaryPerResident');


        $Item = $Navigation->add(t('Massenbilanz'));
        $Item->add(t('Gebäudekonstruktion'), 'elca', ProjectReportAssetsCtrl::class, 'construction');
        $Item->add(t('Anlagentechnik'), 'elca', ProjectReportAssetsCtrl::class, 'systems');
        $Item->add(t('Gebäudebetrieb'), 'elca', ProjectReportAssetsCtrl::class, 'operation');

        $Item->add(t('Ranking Masse'), 'elca', ProjectReportAssetsCtrl::class, 'topAssets');

        $Item = $Navigation->add(t('Wirkungsabschätzung'));
        $Item->add(t('Gebäudekonstruktion'), 'elca', ProjectReportEffectsCtrl::class, 'construction');
        $Item->add(t('Anlagentechnik'), 'elca', ProjectReportEffectsCtrl::class, 'systems');
        $Item->add(t('Gebäudebetrieb'), 'elca', ProjectReportEffectsCtrl::class, 'operation');

        $Item->add(t('Ranking Bauteile'), 'elca', ProjectReportEffectsCtrl::class, 'topElements');
        $Item->add(t('Ranking Baustoffe'), 'elca', ProjectReportEffectsCtrl::class, 'topProcesses');

        $Item = $Navigation->add(t('Variantenvergleich'));
        $Item->add(t('Gesamtbilanz'), 'elca', ProjectReportsCtrl::class, 'compareSummary');
        $Item->add(t('Bilanz nach Bauteilgruppen'), 'elca', ProjectReportsCtrl::class, 'compareElementTypes');

        /**
         * add module navigations
         */
        $Elca = Elca::getInstance();
        foreach($Elca->getAdditionalNavigations() as $ModuleNavigationInterface)
        {
            if(!$ModuleNavigation = $ModuleNavigationInterface->getProjectReportNavigation())
                continue;

            $ModuleFirstItem = $ModuleNavigation->getFirstChild();
            $ModuleItem = $Navigation->add($ModuleFirstItem->getCaption());

            foreach($ModuleFirstItem->getChildren() as $ChildItem)
                $ModuleItem->add($ChildItem->getCaption(), $ChildItem->getModule(), $ChildItem->getCtrlName(), $ChildItem->getAction(), $ChildItem->getArgs(), $ChildItem->getData());
        }

        $this->assign('mainNav', $Navigation);
    }
    // End init


    /**
     * Called before render
     *
     * @return void -
     */
    protected function beforeRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'navLeft']));

        //foreach($this->additionalNavigations as $)

        $Include = $Container->appendChild($this->createElement('include'));
        $Include->setAttribute('name', 'Elca\View\ElcaNavigationLeftView');
		$Include->setAttribute('navigation', '$$mainNav$$');

    }
    // End beforeRender
}
// End ElcaProjectReportsNavgiationLeftView
