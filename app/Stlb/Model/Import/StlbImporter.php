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

namespace Stlb\Model\Import;

use Beibob\Blibs\DataObjectSet;
use Beibob\Blibs\File;
use Beibob\Blibs\Validator;
use Beibob\Blibs\XmlDocument;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Exception;

/**
 * Imports an x81 or csv stlb file
 *
 * @package   stlb
 * @author    Tobias Lode <tobias@beibob.de>
 * @copyright 2013 BEIBOB GbR
 */
class StlbImporter
{
    /**
     * GAEP DP 81
     */
    const GAEB_AWARD_DP_81 = 81;

    /**
     * Catalog names
     */
    const CTLG_COST_GROUP_DIN_276 = 'cost group DIN 276-1 2008-12';

    /**
     * Errors
     */
    const NO_DIN276_CTLG = 1;
    const NO_VALID_DIN276_CTLG_VERSION = 2;
    const NO_VALID_XML_FILE = 3;
    const NO_GAEB_X81_FILE = 4;

    /**
     * Instance
     */
    private static $Instance;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the singleton instance
     *
     * @param  -
     *
     * @return StlbImporter
     */
    public static function getInstance()
    {
        if (!self::$Instance)
            self::$Instance = new StlbImporter();

        return self::$Instance;
    }
    // End getInstance

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Imports from x81 stlb file
     *
     * @param  File      $File
     * @param  Validator $Validator
     *
     * @return DataObjectSet
     */
    public function fromX81File(File $File, Validator $Validator)
    {
        $DataObjectSet = new DataObjectSet();

        $Xml = new XmlDocument();
        $Xml->formatOutput = true;
        $Xml->resolveExternals = false;

        if (!$Xml->load($File->getFilepath()))
            throw new Exception('No valid xml file', self::NO_VALID_XML_FILE);

        $XPath = new DOMXPath($Xml);
        $XPath->registerNamespace('x', $Xml->lookupNamespaceUri($Xml->namespaceURI));

        // check gaeb info
        $GAEBInfos = $XPath->query('//x:Award/x:DP');
        if (!$GAEBInfos->length || $GAEBInfos->item(0)->textContent != self::GAEB_AWARD_DP_81)
            throw new Exception('Not a X81 file', self::NO_GAEB_X81_FILE);

        // lookup catalogs
        $din276CtlgId = false;

        // accept only the newest version of din276
        $Din276CtlgIDs = $XPath->query('//x:BoQInfo/x:Ctlg/x:CtlgType[text()="' . self::CTLG_COST_GROUP_DIN_276 . '"]/../x:CtlgID');
        if ($Din276CtlgIDs->length)
            $din276CtlgId = $Din276CtlgIDs->item(0)->textContent;
        else
            throw new Exception('Found no valid DIN-276 catalog type. Please make sure your file uses catalog `cost group DIN 276-1 2008-12\'. Sorry, can\'t continue', self::NO_VALID_DIN276_CTLG_VERSION);

        $RootBody = $XPath->query('//x:BoQBody[not(ancestor::x:BoQBody)]')->item(0);

        // find all items
        foreach ($XPath->query('//x:Itemlist', $RootBody) as $Itemlist) {
            // build oz prefix from hierachy
            $Ancestors = $XPath->query('./ancestor::x:BoQCtgy', $Itemlist);
            $prefixes = [];
            foreach ($Ancestors as $Ancestor)
                $prefixes[] = $Ancestor->getAttribute('RNoPart');

            $prefix = join('.', array_reverse($prefixes));

            // add items to result list
            foreach ($XPath->query('x:Item', $Itemlist) as $Item) {
                $DO = new \stdClass();

                // oz is prefix + RNoPart
                $DO->oz = $prefix . '.' . $Item->getAttribute('RNoPart');

                // find din276 code from cost group catalog
                $Ctlgs = $XPath->query('.//x:CtlgAssign/x:CtlgID[text()="' . $din276CtlgId . '"]/../x:CtlgCode', $Item);
                $DO->dinCode = $Ctlgs->length ? \trim($Ctlgs->item(0)->textContent) : null;

                $DO->name = \trim($XPath->query('.//x:CompleteText//x:TextOutlTxt', $Item)->item(0)->textContent);
                $DO->description = \trim($XPath->query('.//x:CompleteText/x:DetailTxt/x:Text', $Item)->item(0)->textContent);

                $Qtys = $XPath->query('x:Qty', $Item);
                $DO->quantity = $Qtys->length ? ElcaNumberFormat::fromString(\trim($Qtys->item(0)->textContent), 3) : null;

                $QUs = $XPath->query('x:QU', $Item);
                $DO->refUnit = $QUs->length ? \trim($QUs->item(0)->textContent) : null;

                $Ctlgs = $XPath->query('.//x:Description/x:STLBBau/x:STLBBauCtlg/x:WCtg', $Item);
                $DO->lbNr = $Ctlgs->length ? \trim($Ctlgs->item(0)->textContent) : '';

                $DO->pricePerUnit = null;
                $DO->price = null;

                // rename St refUnit
                if ($DO->refUnit == 'St')
                    $DO->refUnit = Elca::UNIT_STK;

                $DataObjectSet->add($DO);
            }
        }
        return $DataObjectSet;
    }
    // End fromX81File


    /**
     * Imports from csv file
     *
     * @param  File      $File
     * @param  Validator $Validator
     *
     * @return DataObjectSet
     */
    public function fromCsvFile(File $File, Validator $Validator)
    {
        $DataObjectSet = new DataObjectSet();

        while (($data = $File->getCsv(';', '"')) !== false) {
            if (count($data) < 7)
                continue;

            $DO = new \stdClass();
            $DO->oz = $data[0];
            // continue if headline
            if (\utf8_strtoupper($DO->oz) === 'OZ')
                continue;

            $DO->dinCode = $data[6];
            $DO->name = $data[1];
            $DO->description = $data[8];
            $DO->quantity = ElcaNumberFormat::fromString($data[2], 3);
            $DO->refUnit = $data[3];
            $DO->lbNr = $data[7];
            $DO->pricePerUnit = ElcaNumberFormat::fromString($data[4], 2);
            $DO->price = ElcaNumberFormat::fromString($data[5], 2);

            // renaming
            if ($DO->refUnit == 'St')
                $DO->refUnit = Elca::UNIT_STK;

            // validate
            $Validator->setDataObject($DO);
            $Validator->assertNotEmpty('name', null, t('Bitte geben Sie für jedes Stlb Element einen Namen ein.'));
            $DataObjectSet->add($DO);

            if (!$Validator->isValid())
                break;
        }

        return $DataObjectSet;
    }
    // End fromCsvFile


    /**
     * Searches a BoQBody element within the given context
     *
     * @param  DOMDocument $Doc
     * @param              DOMElement , $Context
     *
     * @return DOMElement
     */
    private function findItems(DOMXPath $XPath, $Context = null)
    {
    }
    // End findBoQBody
}
// End StlbImporter