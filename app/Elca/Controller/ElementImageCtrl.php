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
namespace Elca\Controller;

use Beibob\Blibs\PageActionController;
use Beibob\Blibs\SvgView;
use Elca\Db\ElcaElement;
use Elca\View\DefaultElementImageView;
use Elca\View\ElementImageView;


/**
 * Main index controller
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElementImageCtrl extends AppCtrl
{
    /**
     * @var
     */
    private $assistantRegistry;

    /**
     * @var ElcaElement
     */
    private $imageCache;

    private $elementId;
    private $pdfMode = false;
    private $legend = false;

    /**
     * @param array $args
     *
     * @throws \DI\NotFoundException
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->assistantRegistry = $this->container->get('Elca\Service\Assistant\ElementAssistantRegistry');
        $this->imageCache = $this->container->get('Elca\Service\ElcaElementImageCache');

        if (isset($args['elementId']))
            $this->elementId = $args['elementId'];

        if (isset($args['pdf']))
            $this->pdfMode = true;

        if (isset($args['legend']))
            $this->legend = true;
    }


    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function defaultAction()
    {
        $elementId = $this->elementId;
        $containerId = $this->Request->get('c', $elementId);

        if ($this->Request->has('elementId'))
            $elementId = $this->Request->elementId;

        if (!$elementId)
            throw new \Exception('No elementId given');

        $ElcaElement = ElcaElement::findById($elementId);

        if (!$ElcaElement->isInitialized())
            throw new \Exception('Element not found');


        $legend = (bool) $this->Request->has('legend') ? $this->Request->legend : $this->legend;

        $viewBuildMode = $this->Request->has('pdf') || $this->pdfMode ? DefaultElementImageView::BUILDMODE_PDF : DefaultElementImageView::BUILDMODE_SCREEN;

        if ($viewBuildMode != DefaultElementImageView::BUILDMODE_PDF && $svg = $this->imageCache->get($elementId, $legend))
        {
            $elementImageView = new SvgView();
            $elementImageView->loadXML($svg);
        }
        else
        {
            $elementImageView = null;

            try {
                $assistant = $this->assistantRegistry->getAssistantForElement($ElcaElement);
                if ($assistant) {
                    $elementImageView = $assistant->getElementImageView($ElcaElement->getId());
                }
            } catch (\Exception $exception) {
                $this->Log->error('Could not initialize ElementAssistant: ' . $exception->getMessage(), __METHOD__);
            }

            if ($elementImageView === null || !$elementImageView instanceof ElementImageView) {
                $elementImageView = new DefaultElementImageView();
            }

            $elementImageView->setBuildmode($viewBuildMode);

            $width = $this->Request->has('width') ? $this->Request->width : ElementImageView::CANVAS_WIDTH;
            $height = $this->Request->has('height') ? $this->Request->height : ElementImageView::CANVAS_HEIGHT;

            $elementImageView->setElementId($ElcaElement->getId());
            $elementImageView->setDimension($width, $height);
            if (!$legend)
                $elementImageView->disableLegend();


            $elementImageView->process();

            if ($viewBuildMode != ElementImageView::BUILDMODE_PDF)
                $this->imageCache->set($elementId, $elementImageView->__toString(), $legend);
        }

        // Build a wrapper div
        $Div = $elementImageView->appendChild($elementImageView->createElement('div', null, ['id' => 'element-image-' . $containerId]));
        $SvgElt = $elementImageView->getElementsByTagName('svg');
        $Div->appendChild($elementImageView->removeChild($SvgElt->item(0)));

        if ($this->pdfMode)
            PageActionController::setBaseView($elementImageView);
        else
            $this->addView($elementImageView);


    }
    // End defaultAction

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElementImageCtrl




