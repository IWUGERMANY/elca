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
use Beibob\Blibs\Interfaces\TemplateSubstitution;
use Beibob\Blibs\Interfaces\Viewable;


/**
 * ElcaTemplateTranslation ${CARET}
 *
 * @package
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class ElcaTemplateTranslation implements TemplateSubstitution
{
    /**
     * @var ElcaTranslator $Translator
     */
    private $Translator;

    /**
     * @param ElcaTranslator $Translator
     */
    public function __construct(ElcaTranslator $Translator)
    {
        $this->Translator = $Translator;
    }
    // End __constructor

    /**
     * @param Viewable $View
     */
    public function setView(Viewable $View)
    {
        $this->View = $View;

        if (!$this->View->has('locale'))
            $this->View->assign('locale', $this->Translator->getLocale());
    }
    // End setView

    /**
     * @param string $content
     *
     * @return mixed
     */
    public function substituteTemplateData($content)
    {
        return preg_replace_callback('/\_\((.+?)\)\_/ums', [$this, 'substituteCallback'], $content);
    }
    // End substituteTemplateData

    /**
     * Substitute callback method
     *
     * @param  array $matches
     * @return string
     */
    protected function substituteCallback(array $matches)
    {
        $parameters = $this->psplit($matches[1]);

        $msgId = array_shift($parameters);
        $msgId = str_getcsv($msgId, '*$|]}{[|$*', '\'');
        $msgId = array_shift($msgId);

        $args = [];
        foreach ($parameters as $i => $arg)
            $args['%' . $i . '%'] = $arg;

        $domain = 'messages';
        return $this->Translator->trans($msgId, $args, $domain);
    }
    // End substituteCallback

    /**
     * Preserves string literals after character split
     *
     * @param  string $split_character - the character to split at
     * @param  string $text - the text to split
     * @return array
     */
    protected function psplit($text, $split_character = ':')
    {
        $parameters = mb_split($split_character, $text);

        $ret = [];
        $i = 0;
        foreach ($parameters as $p) {
            if (!isset($ret[$i]) || !$ret[$i]) {
                $ret[$i] = $p;
            } else {
                if (\mb_substr_count($ret[$i], "'") % 2 == 1) {
                    $ret[$i] .= ($split_character . $p);
                } else {
                    $ret[++$i] = $p;
                }
            }
        }
        return $ret;
    }

    // End substituteCallback
}
// End ElcaTemplateTranslation