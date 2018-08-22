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

namespace Elca\Security;

class EncryptedPassword
{
    private $encryptedPassword;

    /**
     * @param $passwordPlain
     * @return EncryptedPassword
     */
    public static function fromPlainPassword($passwordPlain)
    {
        $jumble       = md5(time()).md5(getmypid());
        $salt         = '$2a$07$'.\utf8_substr($jumble, 0, CRYPT_SALT_LENGTH);
        return new self(crypt($passwordPlain, $salt));
    }

    /**
     * @param $encryptedPassword
     */
    public function __construct($encryptedPassword)
    {
        $this->encryptedPassword = $encryptedPassword;
    }

    /**
     * @param $passwordPlain
     * @return bool
     */
    public function isValid($passwordPlain)
    {
        return crypt($passwordPlain, $this->encryptedPassword) === $this->encryptedPassword;
    }

    /**
     * @return string
     */
    public function value()
    {
        return $this->encryptedPassword;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->encryptedPassword;
    }


    /**
     * @param EncryptedPassword $otherEncryptedPassword
     * @return bool
     */
    public function equals(EncryptedPassword $otherEncryptedPassword)
    {
        return $this->encryptedPassword === $otherEncryptedPassword->value();
    }

}
