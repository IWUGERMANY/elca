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

use Beibob\Blibs\Environment;
use Beibob\Blibs\Exception;
use Beibob\Blibs\FrontController;


/**
 * ElcaLocale ${CARET}
 *
 * @package
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class ElcaLocale
{
    /**
     *
     */
    const FALLBACK_LOCALE = 'de';

    /**
     * @var array
     */
    private static $supportedLocales = ['de', 'en', 'es'];

    /**
     * Needed to pick the best matching locale for use in setlocle()
     *
     * @var array
     */
    private static $localeVariants = ['de' => ['de_DE.UTF8', 'de_DE'],
                                      'en' => ['en_US.UTF8', 'en_US'],
                                      'es' => ['es_ES.UTF-8', 'es_ES'],
    ];

    /**
     * @var \Beibob\Blibs\SessionNamespace
     */
    private $Namespace;

    /**
     * @param null $locale
     *
     * @throws \Exception
     */
    public function __construct($locale = null)
    {
        $environment = Environment::getInstance();
        $session = $environment->getSession();
        $this->Namespace = $session->getNamespace('elca.locale', true);

        if (!is_null($locale))
            $this->setLocale($locale);

        if (!isset($this->Namespace->locale))
            $this->setLocale($this->getUserLanguage());
    }
    // End __construct


    /**
     * @return array
     */
    public static function getSupportedLocales()
    {
        return self::$supportedLocales;
    }
    // End getSupportedLocales


    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->Namespace->locale;
    }
    // End getLocale

    /**
     *
     */
    public function getLocaleVariants(ElcaLocale $ElcaLocale = null)
    {
        if (is_null($ElcaLocale))
            $ElcaLocale = $this;

        $short = $ElcaLocale->getLocale();

        if (!isset(self::$localeVariants[$short]))
            throw new Exception('locale variants for `' . $short . "' not defined'");

        return self::$localeVariants[$short];
    }
    // End getLocaleVariants

    /**
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        if (!in_array($locale, self::$supportedLocales))
            throw new \Exception('Try to set unsupported locale `' . $locale . "'");

        $this->Namespace->locale = $locale;
    }
    // End setLocale


    /**
     * magic __toString method
     *
     * @param  -
     *
     * @return string
     */
    public function __toString()
    {
        return isset($this->Namespace->locale) ? $this->Namespace->locale : self::FALLBACK_LOCALE;
    }
    // End __toString


    /**
     * @return bool|string
     */
    protected function getUserLanguage()
    {
        $userLanguages = Environment::getInstance()->getAcceptLanguageInformation();

        $locale = false;
        $i = 0;
        while (!$locale) {
            if (!isset($userLanguages[$i]))
                $locale = self::FALLBACK_LOCALE;
            elseif (isset($userLanguages[$i]['locale']) && in_array($userLanguages[$i]['locale'], self::$supportedLocales))
                $locale = $userLanguages[$i]['locale'];
            else
                $locale = false;

            $i++;
        }

        return $locale;
    }
    // End getUserLanguage
}

// End ElcaLocale