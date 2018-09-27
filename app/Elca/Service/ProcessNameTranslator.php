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

use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProcessName;

class ProcessNameTranslator
{
    /**
     * @var ElcaLocale
     */
    private $locale;

    public function __construct(ElcaLocale $locale)
    {
        $this->locale = $locale;
    }

    /**
     * @param string $id
     * @param array  $parameters
     * @param null   $domain
     * @param null   $locale
     *
     * @return string
     */
    public function trans(int $processId, string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale->getLocale();
        }

        $processName = ElcaProcessName::findByProcessIdAndLang($processId, $locale);

        return $processName->isInitialized()
            ? $processName->getName()
            : ElcaProcess::findById($processId)->getName();
    }
}

