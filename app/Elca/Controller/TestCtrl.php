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

namespace Elca\Controller;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlTextArea;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;


/**
 * TestCtrl ${CARET}
 *
 * @package
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class TestCtrl extends AppCtrl
{
    private $View;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);


        if ($this->hasBaseView())
            $this->getBaseView()->disableSidebar();

    }
    // End init

    /**
     *
     */
    private function buildForm()
    {
        $this->View = $this->addView(new HtmlView());
        $Div = $this->View->appendChild($this->View->getDiv(['id' => 'content']));
        $Div->appendChild($this->View->getH1('Test'));

        $Form = new HtmlForm('trans', $this->getActionLink('translate'));
        $Form->add(new HtmlTextArea('query', $this->Request->query));
        $Form->add(new ElcaHtmlSubmitButton('translate', 'translate'));
        $Form->appendTo($Div);

        return $Div;
    }
    // End


    /**
     *
     */
    protected function defaultAction()
    {
        $this->buildForm();
    }
    // End defaultAction

    /**
     *
     */
    protected function translateAction()
    {
        $Container = $this->buildForm();

        if (!$this->Request->query)
            return;

        $TranlateApi = $this->container->get('Elca\Service\MyMemoryTranslator');

        /**
         * @var HtmlView $View
         */
        $P = $Container->appendChild($this->View->getP('Message: ', ['style' => 'font-weight:bold;']));
        $P->appendChild($this->View->getSpan($this->Request->query, ['style' => 'font-weight:normal;']));

        $translation = $TranlateApi->translate($this->Request->query);
        $P = $Container->appendChild($this->View->getP('Translation: ', ['style' => 'font-weight:bold;']));
        $P->appendChild($this->View->getSpan($translation, ['style' => 'font-weight:normal;']));
    }
    // End translateAction


}
// End TestCtrl