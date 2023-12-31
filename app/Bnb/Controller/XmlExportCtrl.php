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

use Beibob\Blibs\BlibsDateTime;
use Beibob\Blibs\StringFactory;
use Beibob\Blibs\TextView;
use Bnb\Model\Export\BnbXmlExporter;
use Bnb\View\BnbXmlExportView;
use Elca\Controller\AppCtrl;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaProjectVariantSet;
use Elca\Service\Messages\ElcaMessages;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Project\ProjectVariant\ProjectVariantService;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaProjectNavigationLeftView;

/**
 * BnbExportCtrl
 *
 * @package
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class XmlExportCtrl extends AppCtrl
{
    protected $ProjectVariant;
    protected $OriginalProjectVariant;

    /**
     *
     */
    public function init(array $args = [])
    {
        if ($this->getAction() != 'download')
            parent::init($args);
    }
    // End init


    /**
     * @param bool $addNavigationViews
     * @param null $Validator
     */
    protected function defaultAction($addNavigationViews = true, $Validator = null)
    {
        if (!$this->isAjax())
            return;

        $View = $this->setView(new BnbXmlExportView());
        $View->assign('ProjectVariantSet', ElcaProjectVariantSet::findByProjectId($this->Elca->getProjectId(), ['phase_id' => $this->Elca->getProjectVariant()->getPhaseId()], ['name' => 'ASC']));

        if ($Validator)
            $View->assign('Validator', $Validator);

        if (is_object($this->ProjectVariant) && $this->ProjectVariant->isInitialized()) {
            $View->assign('ProjectVariant', $this->ProjectVariant);
            $View->assign('filename', $this->getDownloadFilename($this->ProjectVariant));
            $View->assign('downloadUrl', $this->FrontController->getUrlTo(null, 'download', ['id' => $this->ProjectVariant->getId()]));
        }

        if (is_object($this->OriginalProjectVariant) && $this->OriginalProjectVariant->isInitialized())
            $View->assign('OriginalProjectVariant', $this->OriginalProjectVariant);

        /**
         * Add navigation
         */
        if ($addNavigationViews) {
            $this->addView(new ElcaProjectNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('eBNB'), null, t('Export')));
        }
    }
    // End defaultAction

    /**
     * @param ElcaProjectVariant $ProjectVariant
     *
     * @return mixed|string
     */
    protected function getDownloadFilename(ElcaProjectVariant $ProjectVariant)
    {
        $filename = \utf8_strtolower($ProjectVariant->getName());
        $filename = str_replace(' ', 'ABCDEFGHIJKLMNOPQRST', $filename);
        $filename = StringFactory::toAscii($filename, '_');
        $filename = str_replace('ABCDEFGHIJKLMNOPQRST', '_', $filename);

        $filename = str_replace('_' . BlibsDateTime::factory($ProjectVariant->getCreated())->getDateTimeString('d.m.Y'), '', $filename);
        $filename .= '_' . BlibsDateTime::factory($ProjectVariant->getCreated())->getDateTimeString('Ymd');
        $filename .= '.xml';

        return $filename;
    }
    // End getDownloadFilename


    /**
     *
     */
    protected function createAction()
    {
        if (!$this->isAjax() || !$this->Request->isPost())
            return;

        if ($this->Request->has('export')) {
            $Validator = new ElcaValidator($this->Request);
            $Validator->assertNumber('projectVariantId', null, t('Bitte wählen Sie eine Projektvariante'));
            $ProjectVariant = ElcaProjectVariant::findById($this->Request->projectVariantId);

            $Validator->assertTrue('projectVariantId', $ProjectVariant->isInitialized(), t('Bitte wählen Sie eine Projektvariante'));
            if ($Validator->isValid()) {
                if ($this->Request->has('copyVariant')) {
                    $this->OriginalProjectVariant = $ProjectVariant;

                    $ProjectVariant = $this
                        ->container->get(ProjectVariantService::class)
                                   ->copy($this->Elca->getProjectId())
                    ;

                    $ProjectVariant->setName(t('eBNB Export') . ' [' . BlibsDateTime::factory($ProjectVariant->getCreated())->getDateTimeString('d.m.Y') . ']');
                    $ProjectVariant->update();
                }
                $this->ProjectVariant = $ProjectVariant;
            } else {
                foreach (array_unique($Validator->getErrors()) as $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }

            $this->defaultAction(false, !$Validator->isValid() ? $Validator : null);
        }
    }
    // End createAction

    /**
     *
     */
    protected function downloadAction()
    {
        $ProjectVariant = ElcaProjectVariant::findById($this->Request->id);

        if (!$ProjectVariant->isInitialized()) {
            $this->Response->setHeader('X-Redirect: ' . (string)$this->FrontController->getUrlTo());
            return;
        }


        // getXml liefert eine XmlView. Diese wird mit this->setView gesetzt, header feuern fertig

        $View = new TextView();


        $BnbXmlExporter = new BnbXmlExporter($ProjectVariant);
        $View->setContent($BnbXmlExporter->getXml());

        $this->Response->setContent((string)$View);
        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: attachment; filename=\"" . $this->getDownloadFilename($ProjectVariant) . "\"");
        $this->Response->setHeader('Content-Type: text/xml');
    }
    // End downloadAction

}

// End BnbExportCtrl