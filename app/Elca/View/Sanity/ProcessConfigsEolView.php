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

namespace Elca\View\Sanity;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlDataLink;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTag;
use Elca\Db\ElcaProcessDbSet;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Moeller <fab@beibob.de>
 *
 */
class ProcessConfigsEolView extends HtmlView
{
    /**
     * @var \stdClass
     */
    private $data;

    protected function init(array $args = [])
    {
        parent::init($args);

        $this->data = new \stdClass();
        $this->data->processDbId = $this->get('processDbId');

        $this->setTplName('sanity/elca_process_configs_eol');
    }

    protected function beforeRender()
    {
        $container = $this->getElementById('formContainer', true);

        $form = new HtmlForm('processConfigEolForm', '/sanity/process-configs-eol/save/');
        $form->setAttribute('id', 'processLcaConfig');
        $form->setRequest(FrontController::getInstance()->getRequest());
        $form->setDataObject($this->data);

        if ($this->readOnly) {
            $form->setReadonly();
        }

        $group = $form->add(new HtmlFormGroup(t('Export von Baustoffe der gewählten Datenbank, die ... zugeordnet sind')));
        $group->addClass('export-eol-process-configs');
        $group->add(new ElcaHtmlFormElementLabel(t('Baustoffdatenbank'), $select = new HtmlSelectbox('processDbId')));
        $select->add(new HtmlSelectOption(t('-- Bitte wählen --'), ''));

        $processDbs = ElcaProcessDbSet::findEn15804Compatibles([], ['version' => 'ASC']);
        foreach ($processDbs as $processDb) {
            $select->add(new HtmlSelectOption($processDb->getName(), $processDb->getId()));
        }

        $ul = $group->add(new HtmlTag('ul', null, ['class' => 'export-buttons']));
        $li = $ul->add(new HtmlTag('li'));
        $li->add(new ElcaHtmlSubmitButton('onlyC3', 'nur C3'));
        $li = $ul->add(new HtmlTag('li'));
        $li->add(new ElcaHtmlSubmitButton('onlyC4', 'nur C4'));
        $li = $ul->add(new HtmlTag('li'));
        $li->add(new ElcaHtmlSubmitButton('c3AndC4', 'C3 + C4'));
        $li = $ul->add(new HtmlTag('li'));
        $li->add(new ElcaHtmlSubmitButton('noEol', 'kein EOL'));

        $form->appendTo($container);
    }
}

