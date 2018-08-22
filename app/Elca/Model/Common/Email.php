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

namespace Elca\Model\Common;

use UnexpectedValueException;

class Email
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param $email
     */
    public function __construct(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new UnexpectedValueException('Email address is not valid: '. $email);
        }

        $this->value = $email;
    }

    /**
     * @return string
     */
    public function value() : string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return (string)$this->value;
    }

    /**
     * @param Email $other
     * @return bool
     */
    public function equals(Email $other) : bool
    {
        return $this->value === $other->value;
    }

    /**
     * @param Email $other
     * @return bool
     */
    public function isEquivalent(Email $other)
    {
        return \utf8_strtolower($this->value) === \utf8_strtolower($other->value);
    }
}
