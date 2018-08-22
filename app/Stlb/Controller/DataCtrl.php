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
namespace Stlb\Controller;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Environment;
use Beibob\Blibs\File;
use Beibob\Blibs\NestedNode;
use Beibob\Blibs\StringFactory;
use Elca\Controller\AppCtrl;
use Elca\Service\Messages\ElcaMessages;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaProjectNavigationLeftView;
use Exception;
use Stlb\Db\StlbElement;
use Stlb\Db\StlbElementSet;
use Stlb\Model\Import\StlbImporter;
use Stlb\View\StlbDataView;
use Stlb\View\StlbFooterView;

/**
 * Project data controller
 *
 * @package elca
 * @author  Patrick Kocurek <patrick@kocurek.de>
 */
class DataCtrl extends AppCtrl
{
    /**
     * Temp dir
     */
    private $tmpDir;


    /**
     * Will be called on finalization
     *
     * @param  -
     *
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init();
        $Config = Environment::getInstance()->getConfig();

        if (isset($Config->tmpDir)) {
            $baseDir = $Config->toDir('baseDir');
            $tmpDir = $Config->toDir('tmpDir', true);
            $this->tmpDir = $baseDir . $tmpDir;
        } else
            $this->tmpDir = '/tmp';
    }
    // End init


    /**
     * Default action. Will display validator error messages if there
     *
     * @param  - optional ElcaValidator
     *
     * @return -
     */
    protected function defaultAction(ElcaValidator $Validator = null, $addNavigationViews = true)
    {
        if (!$this->isAjax())
            return;

        $View = $this->addView(new StlbDataView());
        if ($Validator)
            $View->assign('Validator', $Validator);

        $StlbElementSet = StlbElementSet::find(['project_id' => $this->Elca->getProjectId()], ['oz' => 'ASC']);
        $View->assign('StlbElementSet', $StlbElementSet);

        /**
         * Add navigation
         */
        if ($addNavigationViews) {
            $this->addView(new ElcaProjectNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('StLB-Daten verwalten'), null, t('StLB')));
        }
    }
    // End defaultAction


    /**
     * Builds the content for the footer
     *
     * @param  -
     *
     * @return -
     */
    protected function footerAction()
    {
        if (!$this->isAjax())
            return;

        $projectId = $this->Elca->getProjectId();
        $typeNodeId = $this->Request->elementtypenodeid;
        $isOpen = $this->Request->isopen;

        $dinCode = NestedNode::findById($typeNodeId)->getIdent();

        /**
         * Skip if there are no elements at all
         */
        $counts = StlbElementSet::countByProjectIdAndDinCode($projectId, $dinCode);

        if (!$counts['total'])
            return;

        $Elements = StlbElementSet::find(['din_code'   => $dinCode,
                                          'project_id' => $projectId,
                                          'is_visible' => true]);

        $View = $this->addView(new StlbFooterView());
        $View->assign('buildMode', $isOpen ? StlbFooterView::BUILDMODE_OPEN : StlbFooterView::BUILDMODE_CLOSED);
        $View->assign('StlbElementSet', $Elements);
        $View->assign('countVisible', $counts['visible']);
        $View->assign('countTotal', $counts['dinCode']);
        $View->assign('dinCode', $dinCode);
        $View->assign('pid', $projectId);
    }
    // End footerAction


    /**
     * save action
     * saves data from importfile into stlb.elements table
     * deletes entries with same din number for same product before writing them in
     *
     * @param  -
     *
     * @return -
     *
     * order of file columns is
     * [0] => OZ;
     * [1] => Kurztext;
     * [2] => Menge;
     * [3] => ME;
     * [4] => Einheitspreis;
     * [5] => Gesamtbetrag;
     * [6] => DIN276-1_08;
     * [7] => LB-Nr.;
     * [8] => Langtext
     */
    protected function saveAction()
    {
        if (isset($this->Request->save)) {
            $Validator = new ElcaValidator($this->Request);
            $Validator->assertTrue('importFile', File::uploadFileExists('importFile'), t('Bitte geben Sie eine Datei für den Import an!'));

            if (isset($_FILES['importFile']))
                $Validator->assertTrue('importFile', preg_match('/\.(csv|x81)$/iu', (string)$_FILES['importFile']['name'], $extMatches), t('Bitte nur CSV oder X81 Dateien importieren'));

            if ($Validator->isValid()) {
                $File = File::fromUpload('importFile', $this->tmpDir);

                try {
                    switch ($type = \utf8_strtolower($extMatches[1])) {
                        case 'x81':
                            $DataObjectSet = StlbImporter::getInstance()->fromX81File($File, $Validator);
                            break;

                        default:
                        case 'csv':
                            $DataObjectSet = StlbImporter::getInstance()->fromCsvFile($File, $Validator);
                            break;
                    }
                } catch (Exception $Exception) {
                    switch ($Exception->getCode()) {
                        case StlbImporter::NO_VALID_DIN276_CTLG_VERSION:
                            $msg = t('In der X81 Datei wird eine alte DIN-276 Katalogversion verwendet. Bitte verwenden Sie "cost group DIN 276-1 2008-12"');
                            break;

                        case StlbImporter::NO_VALID_XML_FILE:
                            $msg = t('Die Datei "%filename%" ist kein valides XML Dokument', null, ['%filename%' => basename($File->getFilepath())]);
                            break;

                        case StlbImporter::NO_GAEB_X81_FILE:
                            $msg = t('Die Datei "%filename%" ist nicht im GAEB-X81-Datenaustauschformat', null, ['%filename%' => basename($File->getFilepath())]);
                            break;

                        default:
                            $msg = $Exception->getMessage();
                    }
                    $Validator->setError('importFile', $msg);
                }
            }

            /**
             * Import file uses
             */
            if ($Validator->isValid()) {
                /**
                 * Check utf8
                 */
                $text = $DataObjectSet->join(' ', 'description');
                $isUTF8 = StringFactory::isUTF8($text);

                // validator is ok -> löschen der distinct din für project_id
                // eintragen der DO's in tabelle
                $Dbh = DbHandle::getInstance();
                foreach ($DataObjectSet as $DO) {
                    try {
                        $Dbh->begin();
                        $Element = StlbElement::create($this->Elca->getProjectId(),
                            $DO->dinCode, // din code
                            $isUTF8 ? $DO->name : utf8_encode($DO->name), // name -> kurztext
                            $isUTF8 ? $DO->description : utf8_encode($DO->description), // description -> langtext
                            $DO->quantity, // quantity -> menge
                            $DO->refUnit, // refUnit -> ME
                            $DO->oz, // oz -> OZ
                            $DO->lbNr, // lbNr -> LB-Nr
                            $DO->pricePerUnit, // pricePerUnit -> Einheitspreis
                            $DO->price // price -> Gesamtbetrag
                        );
                        $Dbh->commit();
                    } catch (Exception $Exception) {
                        $this->messages->add($Exception->getMessage(), ElcaMessages::TYPE_ERROR);
                        $Dbh->rollback();
                    }
                }
            } else {
                foreach ($Validator->getErrors() as $property => $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }

            /**
             * Render default views
             */
            $this->defaultAction($Validator, false);
        } elseif (isset($this->Request->delete)) {
            $this->messages->add(t('Sollen die Daten wirklich gelöscht werden?'),
                ElcaMessages::TYPE_CONFIRM,
                $this->getActionLink('delete', ['confirmed' => '']));
        }
    }
    // end saveAction


    /**
     * Delete action
     */
    protected function deleteAction()
    {
        if (!$this->Request->has('confirmed'))
            return;

        if (!$projectId = $this->Elca->getProjectId())
            return;

        if (StlbElementSet::deleteByProjectId($projectId))
            $this->messages->add(t('Alle Datensätze wurden entfernt'));

        /**
         * Render default views
         */
        $this->defaultAction(null, false);
    }
    // End deleteAction


    /**
     * hide action
     * hides one Element from View. Only in open buildmode
     *
     */
    protected function hideAction()
    {
        if (!$this->Request->id)
            return;

        $Element = StlbElement::findById($this->Request->id);
        if (!$Element->isInitialized())
            return;

        $dinCode = $Element->getDinCode();
        $projectId = $Element->getProjectId();

        $Element->setIsVisible(false);
        $Element->update();

        $Elements = StlbElementSet::find(['din_code' => $dinCode, 'project_id' => $projectId, 'is_visible' => true]);
        $countVisible = StlbElementSet::dbCount(['is_visible' => true, 'project_id' => $projectId, 'din_code' => $dinCode]);
        $countTotal = StlbElementSet::dbCount(['project_id' => $projectId, 'din_code' => $dinCode]);

        $View = $this->addView(new StlbFooterView());
        $View->assign('buildMode', StlbFooterView::BUILDMODE_OPEN);
        $View->assign('StlbElementSet', $Elements);
        $View->assign('countVisible', $countVisible);
        $View->assign('countTotal', $countTotal);
        $View->assign('dinCode', $dinCode);
        $View->assign('pid', $projectId);
    }
    // End hideAction


    /**
     * show all action
     * shows all Elements that were hidden before. Only in open buildmode
     *
     */
    protected function showAllAction()
    {
        if (!$this->Request->dinCode)
            return;

        $dinCode = $this->Request->dinCode;

        $projectId = $this->Elca->getInstance()->getProjectId();

        StlbElementSet::markAllAsVisible($projectId, $dinCode);

        $Elements = StlbElementSet::find(['din_code' => $dinCode, 'project_id' => $projectId, 'is_visible' => true]);
        $countVisible = StlbElementSet::dbCount(['is_visible' => true, 'project_id' => $projectId, 'din_code' => $dinCode]);
        $countTotal = StlbElementSet::dbCount(['project_id' => $projectId, 'din_code' => $dinCode]);

        $View = $this->addView(new StlbFooterView());
        $View->assign('buildMode', StlbFooterView::BUILDMODE_OPEN);
        $View->assign('StlbElementSet', $Elements);
        $View->assign('countVisible', $countVisible);
        $View->assign('countTotal', $countTotal);
        $View->assign('dinCode', $dinCode);
        $View->assign('pid', $projectId);
    }
    // End showAllAction
}
// End DataCtrl
