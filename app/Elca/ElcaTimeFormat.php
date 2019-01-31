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

namespace Elca;
use Beibob\Blibs\Environment;
use Elca\Service\ElcaLocale;


/**
 * ElcaTimeFormat ${CARET}
 *
 * @package
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class ElcaTimeFormat
{
    public static function toString(DateTime $Date)
    {
        /**
         * @var ElcaLocale $locale
         */
        $locale = Environment::getInstance()->getContainer()->get('Elca\Service\ElcaLocale');
        setlocale(LC_TIME, $locale->getLocaleVariants());
        return strftime('%c', $Date->format('U'));
    }

}
// End ElcaTimeFormat