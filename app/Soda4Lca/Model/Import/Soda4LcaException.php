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

use Exception;

/**
 * Soda4LcaException
 *
 * @package   soda4lca
 * @author    Tobias Lode <tobias@beibob.de>
 * @author    Fabian Möller <fab@beibob.de>
 * @copyright 2013 BEIBOB GbR
 */
class Soda4LcaException extends Exception
{
    /**
     * Error constants
     */
    const UNKNOWN_ERROR = 0;
    const CONNECTION_TIMEOUT = 1;
    const MISSING_REFERENCE_FLOW = 2;
    const PROCESS_CATEGORY_NOT_FOUND = 3;
    const NO_VALID_XML_DOCUMENT = 4;
    const MISSING_EPD_MODULES = 5;
    const INVALID_PROCESS_AFTER_CREATE_OR_UPDATE = 6;
    const MISSING_PROCESS_CATEGORY = 7;
    const MISSING_REF_UNIT = 8;
    const CONNECTION_ERROR = 9;

    /**
     * Translated messages
     *
     * @translate array Soda4Lca\Model\Import\Soda4LcaException::$translatedMessages
     */
    public static $translatedMessages = [self::UNKNOWN_ERROR                          => 'Unbekannter Fehler: %s',
                                         self::CONNECTION_TIMEOUT                     => 'Zeitüberschreitung bei der Verbindung zu Soda4LCA',
                                         self::CONNECTION_ERROR                       => 'Problem bei der Verbindung zu Soda4LCA',
                                         self::MISSING_REFERENCE_FLOW                 => 'Im Quelldatensatz fehlt die Referenz auf den Produktfluss',
                                         self::MISSING_EPD_MODULES                    => 'Im Quelldatensatz sind keine EPD Module definiert',
                                         self::PROCESS_CATEGORY_NOT_FOUND             => 'Die Baustoffkategorie "%s" kann nicht in eLCA gefunden werden',
                                         self::INVALID_PROCESS_AFTER_CREATE_OR_UPDATE => 'Der Baustoffprozess konnte nicht erzeugt oder aktualisert werden',
                                         self::MISSING_PROCESS_CATEGORY               => 'Im Quelldatensatz fehlt eine Klassifikation nach Baustoffkategorie',
                                         self::MISSING_REF_UNIT                       => 'Es wurde keine Referenz auf eine Bezugsgröße gefunden',
                                         self::NO_VALID_XML_DOCUMENT                  => 'Ungültiges XML: %s'
    ];

    /**
     * Additional data
     */
    private $additionalData;


    /**
     * Constructor
     *
     * @param  -
     *
     * @return -
     */
    public function __construct($message, $code = 0, Exception $previous = null, $data = null)
    {
        parent::__construct($message, $code, $previous);
        $this->additionalData = $data;
    }
    // End __construct


    /**
     * Returns the additional data
     *
     * @param  -
     *
     * @return mixed
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }
    // End getAdditionalData


    /**
     * Returns a translated message
     *
     * @param  -
     *
     * @return string
     */
    public function getTranslatedMessage()
    {
        $code = $this->getCode();
        switch ($code) {
            case self::UNKNOWN_ERROR:
            case self::NO_VALID_XML_DOCUMENT:
                return vsprintf(self::$translatedMessages[$code], $this->getMessage());

            case self::PROCESS_CATEGORY_NOT_FOUND:
                return vsprintf(self::$translatedMessages[$code], $this->additionalData->classId);
        }

        return isset(self::$translatedMessages[$code]) ? self::$translatedMessages[$code] : $this->getMessage();
    }
    // End getTranslatedMessage
}

// End Soda4LcaException