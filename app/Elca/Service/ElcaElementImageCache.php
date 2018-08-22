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
namespace Elca\Service;

use Beibob\Blibs\Log;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;

class ElcaElementImageCache
{
    /**
     *
     */
    const SVG_IMAGE_CACHE_ATTRIBUTE_IDENT = 'elca.svg-cache-leg-';


    /**
     * @param      $elementId
     * @param bool $legend
     *
     * @return null|string
     * @throws \Exception
     */
    public function get($elementId, $legend = true)
    {
        $ElcaElement = ElcaElement::findById($elementId);

        if (!$ElcaElement->isInitialized())
            throw new \Exception('Element not found');

        $ElcaElementAttribute = ElcaElementAttribute::findByElementIdAndIdent($ElcaElement->getId(), self::getAttributeIdent($legend));
        if ($ElcaElementAttribute->isInitialized())
            return $ElcaElementAttribute->getTextValue();

        return null;
    }

    /**
     * @param      $elementId
     * @param      $svg
     * @param bool $legend
     *
     * @return ElcaElementAttribute
     * @throws \Exception
     */
    public function set($elementId, $svg, $legend = true)
    {
        $ElcaElement = ElcaElement::findById($elementId);

        if (!$ElcaElement->isInitialized())
            throw new \Exception('Element not found');

        if (!$svg)
            throw new \Exception('Invalid svg');

        $ElcaElementAttribute = ElcaElementAttribute::findByElementIdAndIdent($ElcaElement->getId(), self::getAttributeIdent($legend));
        if ($ElcaElementAttribute->isInitialized())
        {
            $ElcaElementAttribute->setTextValue($svg);
            $ElcaElementAttribute->update();
        }
        else
        {
            $ElcaElementAttribute = ElcaElementAttribute::create($ElcaElement->getId(), self::getAttributeIdent($legend), 'text_value', null, $svg);
        }

        return $ElcaElementAttribute;
    }

    /**
     * @param $elementId
     *
     * @return bool
     */
    public function clear($elementId)
    {
        $ElcaElement = ElcaElement::findById($elementId);
        if (!$ElcaElement->isInitialized())
            return false;

        $msg = 'Clear image cache for elementId [' . $elementId . '] (with legend: ';
        $ElcaElementAttribute = ElcaElementAttribute::findByElementIdAndIdent($ElcaElement->getId(), self::getAttributeIdent(true));
        if ($ElcaElementAttribute->isInitialized())
        {
            $ElcaElementAttribute->delete();
            $msg .= 'CLEARED';
        }
        else {
            $msg .= 'NOT FOUND';
        }

        $msg .= ', without legend: ';
        $ElcaElementAttribute = ElcaElementAttribute::findByElementIdAndIdent($ElcaElement->getId(), self::getAttributeIdent(false));
        if ($ElcaElementAttribute->isInitialized())
        {
            $ElcaElementAttribute->delete();
            $msg .= 'CLEARED';
        }
        else {
            $msg .= 'NOT FOUND';
        }

        Log::getInstance()->debug($msg . ')');

        /**
         * Also clear cache for all elements that uses this element as a component
         */
        if ($ElcaElement->hasCompositeElement(true))
        {
            foreach ($ElcaElement->getCompositeElements() as $CompositeElement) {
                $this->clear($CompositeElement->getCompositeElementId());
            }
        }

        return true;
    }

    /**
     * @param bool $legend
     *
     * @return string
     */
    protected static function getAttributeIdent($legend = true)
    {
        $ident = self::SVG_IMAGE_CACHE_ATTRIBUTE_IDENT;
        $ident .=  $legend ? '1' : '0';
        return $ident;
    }
}