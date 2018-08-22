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

declare(strict_types=1);

namespace ImportAssistant\Model\Import;

class Attribute
{
    private $ident;
    private $caption;
    private $numericValue;
    private $textValue;

    /**
     * Attribute constructor.
     *
     * @param $ident
     * @param $caption
     * @param $numericValue
     * @param $textValue
     */
    public function __construct($ident, $caption, $numericValue, $textValue)
    {
        $this->ident        = $ident;
        $this->caption      = $caption;
        $this->numericValue = $numericValue;
        $this->textValue    = $textValue;
    }

    /**
     * @return mixed
     */
    public function ident()
    {
        return $this->ident;
    }

    /**
     * @return mixed
     */
    public function caption()
    {
        return $this->caption;
    }

    /**
     * @return mixed
     */
    public function numericValue()
    {
        return $this->numericValue;
    }

    /**
     * @return mixed
     */
    public function textValue()
    {
        return $this->textValue;
    }
}
