<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2020 m boeneke <boeneke@online-now.de>
 *               Online Now! GmbH
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
namespace Elca\View\Admin\Bauteilkatalog;

use Beibob\Blibs\BlibsDateTime;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\UserStore;
use Elca\Db\ElcaReportSet;
use Elca\View\Report\ElcaAdminBauteilkatalogHeaderFooterView;
use Elca\Controller\Admin\BauteilkatalogCtrl;
use Elca\Security\ElcaAccess;
use Elca\Db\ElcaProcessDbSet;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaConstrDesignSet;


/**
 * Admin view
 *
 * @package elca
 * @author m boeneke <boeneke@online-now.de>
 */
class ElcaAdminBauteilkatalogView extends HtmlView
{
    
    /**
     * Print Header and Footer elements
     */
    private $headerFooterView;
    protected $printHeader;
    protected $printFooter;
    
    const BUILDMODE_SCREEN = 'screen';
    const BUILDMODE_PDF = 'pdf';

    // not in use....
    protected $sheetView = 'Elca\View\ElcaElementSheetView';
    
    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_admin_bauteilkatalog', 'elca');
        
        $this->buildMode = $this->get('buildMode', self::BUILDMODE_SCREEN);
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @internal param $ -
     * @return void -
     */
    protected function beforeRender()
    {
        $infoDiv = $this->getElementById('content');

        $PrintDiv = $infoDiv->appendChild($this->getDiv(['class' => 'button print']));
        $PrintDiv->appendChild($this->getA(['class' => 'no-xhr', 'href' => '#', 'onclick' => 'window.print();return false;'], t('Drucken')));

        $pdfUrl = FrontController::getInstance()->getUrlTo(null, 'pdf', ['a' => FrontController::getInstance()->getAction() ? FrontController::getInstance()->getAction() : BauteilkatalogCtrl::CATALOGACTION]);
        $modalUrl = FrontController::getInstance()->getUrlTo(null, 'pdfModal', ['a' => FrontController::getInstance()->getAction() ? FrontController::getInstance()->getAction() : BauteilkatalogCtrl::CATALOGACTION]);
		// $modalUrlDownload = FrontController::getInstance()->getUrlTo(null, 'pdfModalDownload', ['a' => FrontController::getInstance()->getAction() ? FrontController::getInstance()->getAction() : BauteilkatalogCtrl::CATALOGACTION]);
		
		$PrintDiv->appendChild($this->getA([
			'class' => 'no-xhr', 
			'rel' => 'open-modal', 
			'href' => $modalUrl
		], t('PDF erstellen')));


        
        
		// PDF in work / already exists - project_variant=0, project_id=0, user_id=0
		
        $PDFinfo = ElcaReportSet::findPdfInQueue(0,0, UserStore::getInstance()->getUserId(),FrontController::getInstance()->getUrlTo().BauteilkatalogCtrl::CATALOGACTION.'/');
		if(!$PDFinfo->isEmpty())
		{
			// PDF ready?
			$infoArrayReady = (array)$PDFinfo[0]->ready;
			if (isset($infoArrayReady[0]))
			{
				$PDFreadyDate = BlibsDateTime::factory($infoArrayReady[0]);
				$modalUrlDownload = FrontController::getInstance()->getUrlTo(null, 'pdfModalDownload', ['a' => BauteilkatalogCtrl::CATALOGACTION, 't' => $PDFreadyDate->getDateTimeString(t('DATETIME_FORMAT_DMY'))]);
				$PrintDiv->appendChild($this->getA(['class' => 'no-xhr', 'rel' => 'open-modal','title' => t('Erstellt:').$PDFreadyDate->getDateTimeString(t('DATETIME_FORMAT_DMY') . ' ' . t('DATETIME_FORMAT_HI')), 'href' => $modalUrlDownload], t('PDF anzeigen')));	
				
				$infoArrayKey = (array)$PDFinfo[0]->key;
				if($infoArrayKey[0])
				{	
					
				}
			}
			else
			{
				$PrintDiv->appendChild($this->getSpan(t('PDF wird erstellt'),
				[	'class'=>'pdfcreate blink',
					'data-check'=>'true',
					'data-id' => 0,
					'data-pvid' => 0, 
					'data-uid' => UserStore::getInstance()->getUserId(),
					'data-action' => FrontController::getInstance()->getUrlTo().BauteilkatalogCtrl::CATALOGACTION.'/'	
				]));
			}	
		}
        
        $this->appendPrintTable($infoDiv);
        
    }
    // End afterRender



    /**
     * Appends the print table to the given container and
     * returns the content td to append the main content
     *
     * @param  \DOMElement
     * @return DOMElement
     */
    protected function appendPrintTable(\DOMElement $Container)
    {
        if (null === $this->headerFooterView) {
            $this->headerFooterView = new ElcaAdminBauteilkatalogHeaderFooterView();
            $this->headerFooterView->process([], 'elca');

            $this->printHeader = $this->headerFooterView->getElementById('printHeader', true);
            $this->printFooter = $this->headerFooterView->getElementById('printFooter', true);
        }

        $PrintTable = $Container->appendChild($this->getTable(['class' => 'print-table']));

        if (!FrontController::getInstance()->getRequest()->get('pdf') && $this->buildMode!=self::BUILDMODE_PDF)
        {
            $Header = $PrintTable->appendChild($this->getTHead());
            $Tr = $Header->appendChild($this->getTr());
            $Td = $Tr->appendChild($this->getTd(['class' => 'print-td']));
            $Td->appendChild($this->importNode($this->printHeader, true));

            $Footer = $PrintTable->appendChild($this->getTFoot());
            $Tr = $Footer->appendChild($this->getTr());
            $Td = $Tr->appendChild($this->getTd(['class' => 'print-td']));
            $Td->appendChild($this->importNode($this->printFooter, true));
        }

        $Body = $PrintTable->appendChild($this->getTBody());
        $Tr = $Body->appendChild($this->getTr());
        $TdB = $Tr->appendChild($this->getTd(['class' => 'print-content']));

        $DivDruck = $TdB->appendChild($this->getDiv(['id' => 'elcaAdminBauteilkatalog', 'class' => 'report-elements']));
        
        // get active Process DBs (ÖBD) - sort by uid desc ("newest" first)
        $processDbs = ElcaProcessDbSet::findActive();
        foreach($processDbs as $processDb) {
            $Elements = $this->getElementSet( $processDb->getId() );
            $this->appendCatalogItem($DivDruck, $Elements, $processDb->getId(), $this->sheetView);
        }
        
        return $TdB;
    }
    // End appendPrintTable
    
    
    
    
    
     /**
     * Appends the Bauteilkatalog Item to the given container and
     * returns the content to append the item content
     *
     * @param  \DOMElement
     * @return DOMElement
     */
    protected function appendCatalogItem(\DOMElement $Container, ElcaElementSet $Elements, $processDbId, $sheetView = 'Elca\View\ElcaElementSheetView')
    {
        $eltCount = $Elements->count();
        if ($eltCount) {
        
            $processDbs = ElcaProcessDbSet::findByIds([$processDbId])->getArrayBy('name');
            
            
            foreach ($Elements as $index => $Element) {
                
                $elementType = ElcaElementType::findByNodeId($Element->getElementTypeNodeId());
                
                $DesignName = [];
                foreach( ElcaConstrDesignSet::findByElementId($Element->getId()) as $Design ) {
                    $DesignName[] = $Design->getName();
                }
                  
                $DivDruckItem = $Container->appendChild($this->getDiv(['class' => 'elcaAdminBauteilkatalogItem']));
                
                // Bauteil Name
                $DivDruckItem->appendChild($this->getH1($Element->getName(), ['class' => 'itemName'])); 
                
                $DivDruckItemDl = $DivDruckItem->appendChild($this->getDl(['class' => 'clearfix']));

                // Version
                $DivDruckItemDl->appendChild($this->getDt(['class' => ''], t('Version') . ': '));
                $DivDruckItemDl->appendChild($this->getDd(['class' => ''], $processDbs[0]));
                
                // Kostengruppe
                $DivDruckItemDl->appendChild($this->getDt(['class' => ''], t('Kostengruppe') . ': '));
                $DivDruckItemDl->appendChild($this->getDd(['class' => ''], $elementType->getDinCode()));
                
                // Bauweise
                $DivDruckItemDl->appendChild($this->getDt(['class' => ''], t('Bauweise') . ': '));
                $DivDruckItemDl->appendChild($this->getDd(['class' => ''], ( !empty($DesignName) ? implode(', ',$DesignName) :'-')));
                
                // Typ
                $DivDruckItemDl->appendChild($this->getDt(['class' => ''],  t('Typ') . ': '));
                $DivDruckItemDl->appendChild($this->getDd(['class' => ''], ($Element->isPublic() ? t('Öffentlich'):'').($Element->isReference() ? ' / '.t('Referenz') :'')));
                
                // ID
                $DivDruckItemDl->appendChild($this->getDt(['class' => ''],  t('ID') . ': '));
                $DivDruckItemDl->appendChild($this->getDd(['class' => ''], $Element->getId()));
                
                // UUID
                $DivDruckItemDl->appendChild($this->getDt(['class' => ''],  t('UUID') . ': '));
                $DivDruckItemDl->appendChild($this->getDd(['class' => ''], $Element->getUuid()));
                                
                if($Element->getDescription()) {
                // UUID
                    $DivDruckItemDl->appendChild($this->getDt(['class' => ''],  t('Beschreibung') . ': '));
                    $DivDruckItemDl->appendChild($this->getDd(['class' => ''], $Element->getDescription()));
            
                }    
                $DivDruckItem->appendChild($this->getBr());
                
                // Images und Patterns hinzufügen
                // kein PDF-Inhalt, wenn Aktivierung Images - Problem mit SVG XML Erstellung
                $this->appendElementImage($DivDruckItem, $Element->getId());
                
            }    
        }    
        
        return $Container;
    }
    
    
    
    /**
     * Appends the container for the element image
     *
     * @param  DOMElement $Container
     * @return -
     */
    protected function appendElementImage(\DOMElement $Container, $elementId)
    {
        $attr = array('elementId' => $elementId, 'legend' => '1', 'w' => '630', 'h' => '250');
        
        if (FrontController::getInstance()->getRequest()->has('pdf') || $this->buildMode==self::BUILDMODE_PDF)
        {
            $Div = $Container->appendChild($this->getDiv(['class' => 'element-image embedded']));
            $Div->appendChild($this->createElement('include', null, ['name' => 'Elca\Controller\ElementImageCtrl', 'elementId' => $elementId, 'legend' => 1, 'pdf' => 1]));

        }
        else
        {
            $Container->appendChild($this->getDiv(['class' => 'element-image',
                                                   'data-element-id' => $elementId,
                                                   'data-url' => FrontController::getInstance()->getUrlTo('Elca\Controller\ElementImageCtrl', null, $attr)]));
        }
    }
    // End appendElementImage    
    
    /**
     * Appends the container for the element effects cahrt
     *
     * @param  DOMElement $Container
     * @param  array $dataSet
     * @return -
     */
    protected function appendElementEffectsChart(DOMElement $Container, $elementId, $indicatorId, $aggregated = false)
    {
        $args = ['e' => $elementId,
                      'i' => $indicatorId,
                      'a' => (int)$aggregated];

        $attributes = ['class' => 'chart stacked-bar-chart',
                            'data-url' => Url::factory('/elca/project-report-effects/elementChart/', $args)];;

        $Container->appendChild($this->getDiv($attributes));
    }
    
    
    /**
     * Returns the element set
     *
     * @return ElcaElementSet
     */
    protected function getElementSet($processDbId=null)
    {
        $Access = ElcaAccess::getInstance();
        $filter = [
            // 'element_type_node_id' => null,
            'project_variant_id'   => null
        ];

        
        $filter['is_public'] = true;
        // $filter['is_reference'] = true;
        // $filter['constr_design_id'] = null;
        // $filter['constr_catalog_id'] = null;
        
        $elements = ElcaElementSet::searchExtended(
            [],
            $filter,
            true, // $this->elementType->isCompositeLevel(),
            $Access->hasAdminPrivileges(),
            $Access->getUserGroupIds(),
            $processDbId ? $processDbId : null,
            ['element_type_node_id' => 'ASC', 'name' => 'ASC']
            // ,
            // self::PAGE_LIMIT + 1,
            // $this->page * self::PAGE_LIMIT
        );

        return $elements;
    }
    // End getElementSet
    
    
    
    
    
    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaAdminBauteilkatalogView
