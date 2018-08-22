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

use Beibob\Blibs\BlibsGettext;
use Beibob\Blibs\Environment;
use Elca\Service\ElcaLocale;
use Elca\Service\ElcaTranslator;

$appDir = realpath('./app');
$config = Environment::getInstance()->getConfig();
try {

    $cleanUp = false;
    if (in_array('cleanup', $argv))
    {
        $cleanUp = true;
        if (count($argv) > 1)
            throw new \Exception('INVALID CALL. Cleanup is only possible by running over ./app. cleanup should therefore be the one and only argument.');
    }

    loginfo('----------------------------------------------------------------');
    if ($cleanUp)
        loginfo('Clean up message file');
    else
        loginfo('Generating eLCA message file!');
    loginfo('----------------------------------------------------------------');


    if (count($argv) && !$cleanUp)
    {
        $files = [];
        addFiles($files, $argv);
    }
    else
    {
        $dirIterator = new RecursiveDirectoryIterator($config->appDir);
        $iterator = new RecursiveIteratorIterator($dirIterator);
        $files = new RegexIterator($iterator, '/^.+\.(php|tpl)$/u', RecursiveRegexIterator::MATCH);
    }

    $Translator = new ElcaTranslator(new ElcaLocale(ElcaLocale::FALLBACK_LOCALE));
    $used = [];
    foreach ($files as $filePath => $match) {
        $parser = new BlibsGettext(null);
        $parser->setFile($filePath);
        $messages = $parser->parse();

        if (!$cleanUp) {
            foreach ($parser->getLog() as $msg) {
                loginfo($msg);
            }
        }
        foreach ($messages as $msgId => $msgStr) {
            $Translator->trans($msgId);

            if ($cleanUp)
                $used[$msgId] = $msgStr;
        }
        if (count($Translator->getNewMessages()))
            loginfo('    ' . count($Translator->getNewMessages()) . ' messages added to eLCA message file');


    }

    if ($cleanUp)
    {
        loginfo(count($used) . ' used messages found!');
        $unused = [];
        $all = $Translator->getCatalogue()->all();
        loginfo('   Searching unused messages');
        foreach ($all['messages'] as $msgId => $msgStr)
        {
            if (!isset($used[$msgId]))
            {
                $unused[$msgId] = $msgStr;
                loginfo('        Message `' . $msgId . "' not used");
            }
        }
        loginfo('        ' . count($unused) . ' unused messages found. Removing');
        $Translator->remove($unused);
    }




}
catch (\Exception $Exception)
{
    logerror($Exception->getMessage());
}


function addFiles(&$fileList, $files)
{
    foreach ($files as $file)
    {
        if (is_object($file))
            $file = $file->getPathName();
        if (is_dir($file))
        {
            if (substr($file,0,1) != '/')
                $file = realpath('.') . '/' . $file;

            $dirIterator = new RecursiveDirectoryIterator($file);
            $iterator = new RecursiveIteratorIterator($dirIterator);
            $regexIterator = new RegexIterator($iterator, '/^.+\.(php|tpl)$/u', RecursiveRegexIterator::MATCH);

            foreach ($dirIterator as $k => $v)
            {
                if (basename($k) != '.' && basename($k) != '..' && is_dir($k))
                    addFiles($fileList, [$k]);
            }
            addFiles($fileList, $regexIterator);

        }
        else
        {
            if (substr($file,0,1) == '/')
                $fileList[$file] = basename($file);
            else
                $fileList[realpath('.') . '/' . $file] = basename($file);
        }
    }
}



