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
use Beibob\Blibs\File;
use Beibob\Blibs\SessionNamespace;
use Beibob\Blibs\User;
use Beibob\Blibs\UserStore;

class ElcaSessionRecovery
{
    /**
     * @var
     */
    protected $Session;

    /**
     * @var User
     */
    protected $User;

    protected $recoveryDir;

    const EXPIRE_TIME = 120;
    const SESSION_RECOVER_DIRNAME = '/session-recover';

    /**
     *
     */
    public function __construct()
    {
        $this->recoveryDir = Environment::getInstance()->getConfig()->baseDir . self::SESSION_RECOVER_DIRNAME;

        if (!is_dir($this->recoveryDir))
            mkdir($this->recoveryDir);

        $this->Session = Environment::getInstance()->getSession();
        $this->User = UserStore::getInstance()->getUser();
    }

    public function recover()
    {
        $data = $this->readData();

        foreach ($data as $namespaceName => $Namespace)
            $this->Session->addNamespace($Namespace);
    }

    /**
     * @param SessionNamespace $Namespace
     */
    public function storeNamespace(SessionNamespace $Namespace)
    {
        $this->cleanup();

        $data = $this->readData();

        $data[$Namespace->getName()] = $Namespace;

        $this->writeData($data);
    }

    protected function writeData($data = array())
    {
        $cryptId = $this->User->getCryptId('modified');
        $recoverFile = File::factory($this->recoveryDir . '/' . $cryptId, 'w+');
        $recoverFile->write(serialize($data));
    }

    protected function readData()
    {
        $cryptId = $this->User->getCryptId('modified');
        $recoverFile = File::factory($this->recoveryDir . '/' . $cryptId);
        if (!$recoverFile->exists())
            return [];

        return unserialize(file_get_contents($recoverFile->getFilepath()));
    }

    private function cleanup()
    {
        $handle = opendir($this->recoveryDir);

        while (false !== ($entry = readdir($handle))) {
            if ($entry == '..' || $entry == '.')
                continue;

            $File = File::factory($this->recoveryDir . '/' . $entry);
            if ($File->exists()) {
                if ($File->getMTime() <= time() - self::EXPIRE_TIME)
                    $File->delete();
            }
        }

    }
}
