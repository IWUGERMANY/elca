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

namespace Soda4Lca\Model\Import;

use Beibob\Blibs\Environment;
use Beibob\Blibs\Log;
use DOMDocument;
use DOMXPath;

/**
 * Provides an interface to soda4lca service
 *
 * @package soda4lca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 * @copyright 2014 BEIBOB GbR
 */
class Soda4LcaConnector
{
    /**
     * URL pathes
     */
    const DATASTOCKS = 'datastocks';
    const PROCESSES = 'processes';
    const FLOWS = 'flows';
    const FLOWPROPERTIES = 'flowproperties';
    const UNITGROUPS = 'unitgroups';

    /**
     * Formats
     */
    const FORMAT_XML = 'XML';
    const FORMAT_HTML = 'HTML';

    /**
     * Instance
     */
    private static $Instance;

    /**
     * Log
     */
    private $Log;

    /**
     * Base url
     */
    private $baseUrl;

    /**
     * Source uri of the last processed api call
     */
    private $sourceUri;

    /**
     * Cache
     */
    private $cache = [];


    /**
     * Returns the singleton instance
     *
     * @return Soda4LcaConnector
     */
    public static function getInstance()
    {
        if(!self::$Instance)
            self::$Instance = new Soda4LcaConnector();

        return self::$Instance;
    }
    // End getInstance


    /**
     * DataStocks
     *
     * @return DOMXPath
     */
    public function getDataStocks()
    {
        $XPath = $this->callApi($this->baseUrl . self::DATASTOCKS);
        return $XPath;
    }
    // End getDataStocks


    /**
     * Processes
     *
     * @param  string $dataStockUuid
     * @param  int $startIndex
     * @param  int $pageSize
     * @return DOMXPath
     */
    public function getProcesses($dataStockUuid = null, $startIndex = 0, $pageSize = 10)
    {
        if($dataStockUuid)
            $XPath = $this->callApi($this->baseUrl . self::DATASTOCKS .'/'. $dataStockUuid .'/'. self::PROCESSES, ['startIndex' => $startIndex, 'pageSize' => $pageSize], true);
        else
            $XPath = $this->callApi($this->baseUrl . self::PROCESSES, ['startIndex' => $startIndex, 'pageSize' => $pageSize], true);

        return $XPath;
    }
    // End getProcesses


    /**
     * Process
     *
     * @param  string $uuid
     * @param  string $format
     * @return DOMXPath
     */
    public function getProcess($uuid, $version = null, $format = self::FORMAT_XML)
    {
        $query = ['format' => $format];

        if ($version) {
            $query['version'] = $version;
        }

        return $this->callApi($this->baseUrl . self::PROCESSES . '/' . $uuid, $query, true);
    }
    // End getProcess


    /**
     * Flow
     *
     * @param  string $uuid
     * @param  string $format
     * @return DOMXPath
     */
    public function getFlow($uuid, $version = null, $format = self::FORMAT_XML)
    {
        $query = ['format' => $format];

        if ($version) {
            $query['version'] = $version;
        }

        return $this->callApi($this->baseUrl . self::FLOWS . '/' . $uuid, $query);
    }
    // End getFlow


    /**
     * FlowProperty
     *
     * @param  string $uuid
     * @param  string $format
     * @return DOMXPath
     */
    public function getFlowProperty($uuid, $version = null, $format = self::FORMAT_XML)
    {
        $query = ['format' => $format];

        // 04.12.19 Disabled. Retrieving a flow property with version leads to this messge:
        // "A flow property data set with the uuid xxxx cannot be found in the database
//        if ($version) {
//            $query['version'] = $version;
//        }

        return $this->callApi($this->baseUrl . self::FLOWPROPERTIES . '/' . $uuid, $query);
    }
    // End getFlowProperty


    /**
     * Flow descendants
     *
     * @param  string $uuid
     * @param  string $format
     * @return DOMXPath
     */
    public function getFlowDescendants($uuid, $format = self::FORMAT_XML)
    {
        return $this->callApi($this->baseUrl . self::FLOWS .'/'. $uuid .'/descendants', ['format' => $format]);
    }
    // End getFlow


    /**
     * UnitGroup
     *
     * @param  string $uuid
     * @param  string $format
     * @return DOMXPath
     */
    public function getUnitGroup($uuid, $version, $format = self::FORMAT_XML)
    {
        $query = ['format' => $format];

        if ($version) {
            $query['version'] = $version;
        }

        return $this->callApi($this->baseUrl . self::UNITGROUPS . '/' . $uuid, $query);
    }
    // End getUnitGroup


    /**
     * Returns the sourceUri
     *
     * @return string
     */
    public function getSourceUri()
    {
        return $this->sourceUri;
    }
    // End getSourceUri


    /**
     * API call
     *
     * @param string $url
     * @param array  $data
     * @return DOMXPath
     * @throws Soda4LcaException
     */
    private function callApi($url, array $data = null, $force = false)
    {
        $this->sourceUri = $url;

        if($data)
            $url = sprintf("%s?%s", $url, http_build_query($data));

        /**
         * Caching
         */
        if(!$force && isset($this->cache[$url]))
           return $this->cache[$url];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if(!$xml = curl_exec($curl))
        {
            $this->Log->error('Connected to '. $url .' failed: '. curl_error($curl), __METHOD__);
            throw new Soda4LcaException('Could not connect to '. $url .': '. curl_error($curl), Soda4LcaException::CONNECTION_TIMEOUT);
        }

        $info = curl_getinfo($curl);
        curl_close($curl);

        $domDocument = new DOMDocument();
        if(!$domDocument->loadXml($xml))
        {
            $this->Log->error('Connected to '. $url .' failed: no valid xml: '. $xml, __METHOD__);
            $substr = \substr($xml, 0, 500);
            $message    = 'Xml document could not be loaded for url `' . $url . '\': ' . $substr;
            if ($substr !== $xml) {
                $message .= '...';
            }
            throw new Soda4LcaException($message, Soda4LcaException::NO_VALID_XML_DOCUMENT);
        }

        $this->Log->debug('Connected to '. $url .' succeeded in '. $info['total_time'] .' seconds. Retrieved '.$info['size_download'].' bytes with '.$info['speed_download'].' bytes per second', __METHOD__);

        return $this->cache[$url] = new DOMXPath($domDocument);
    }
    // End callApi


    /**
     * Constructor
     *
     * @param  -
     * @return -
     */
    private function __construct()
    {
        $this->Log = Log::getInstance();

        $Config = Environment::getInstance()->getConfig();
        $this->baseUrl = $Config->elca->soda4Lca->toDir('baseUrl');
    }
    // End __construct


    /**
     * Returns the micro time
     */
    private static function getMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }
    // End getMicrotime

}
// End Soda4LcaConnector