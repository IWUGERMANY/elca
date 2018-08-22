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
namespace Elca\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextArea;
use Beibob\HtmlTools\HtmlTextInput;
use Beibob\HtmlTools\HtmlUploadInput;
use Elca\Db\ElcaSvgPattern;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds list of svg patterns
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaAdminSvgPatternView extends HtmlView
{
    /**
     * @var ElcaSvgPattern $Data
     */
    private $Data;

    /**
     * preview dimensions
     */
    const PREVIEW_WIDTH = 500;
    const PREVIEW_HEIGHT = 350;

    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->Data = $this->get('Data', new \stdClass());
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @return void -
     */
    protected function beforeRender()
    {
        /**
         * Add Container
         */
        $Container = $this->appendChild($this->getDiv(['id' => 'content',
                                                            'class' => 'svg-patterns']));

        $formId = 'adminSvgPatternForm';
        $Form = new HtmlForm($formId, '/elca/admin-svg-patterns/save/');
        $Form->setAttribute('id', $formId);
        $Form->addClass('clearfix highlight-changes');

        if($this->Data)
        {
            $Form->setDataObject($this->Data);

            if(isset($this->Data->id))
                $Form->add(new HtmlHiddenField('id', $this->Data->id));
        }

        if($this->has('Validator'))
        {
            $Form->setValidator($this->get('Validator'));
            $Form->setRequest(FrontController::getInstance()->getRequest());
        }

        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('svg-pattern-group');
        $Group->add(new ElcaHtmlFormElementLabel(t('Name'), new HtmlTextInput('name'), true));
        $Group->add(new ElcaHtmlFormElementLabel(t('Notizen'), new HtmlTextArea('description')));

        $Group->add(new ElcaHtmlFormElementLabel(t('Grafikdatei hochladen (.svg, .jpg, .gif, .png)'), new HtmlUploadInput('uploadFile')));
        $Group->add(new HtmlTag('p', t('Bildmuster sollten einen möglichst kleinen und quadratischen Bereich umfassen und nicht mehr als 100px abmessen. Vermeiden Sie unnötige Wiederholungen des Musters in diesem Bereich.')));
        $Group->add(new HtmlTag('p', t('Empfohlen wird das Vektor-Bild-Format SVG. Verwenden Sie bei der äußeren Abmessung ganzzahlige Werte ohne Einheit.')));
        $Group->add(new HtmlTag('p', t('Die Vorschau zeigt die Grafik mit mindestens einer Wiederholung - je nach Ausrichtung der Grafik - zur Kontrolle der Mustereigenschaft.')));
        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');

        if(!isset($this->Data->systemId))
            $ButtonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbrechen')));

        $ButtonGroup->add(new ElcaHtmlSubmitButton(isset($this->Data->id)? 'save' : 'insert', isset($this->Data->id)? t('Speichern') : t('Erstellen')));

        /**
         * File info and download link
         */
        if ($this->Data instanceof ElcaSvgPattern) {
            $Image = $this->Data->getImage();
            if($Image->isInitialized()) {
                $Group = $Form->add(new HtmlFormGroup(''));
                $Group->addClass('svg-pattern-preview-group');

                $FileInfo = new HtmlTag('span', null, ['class' => 'file-info']);
                $FileInfo->add($Link = new HtmlLink($Image->getName(), '/elca/admin-svg-pattern-download/'. $this->Data->getId().'/'));
                $Link->addClass('no-xhr');
                $FileInfo->add(new HtmlTag('span', ' ('. t('Format') .': ' . $this->Data->getWidth() . ' x ' . $this->Data->getHeight() . ' px)'));

                $Group->add(new ElcaHtmlFormElementLabel(t('Vorschau'), $FileInfo));

                $Preview = $Group->add(new HtmlTag('div'));
                $Preview->setAttribute('class', 'pattern-preview');

                $styles = [];
                // set width and height of preview container
                $styles[] = 'width:'.self::PREVIEW_WIDTH .'px';
                $styles[] = 'height:'.self::PREVIEW_HEIGHT .'px';

                /**
                 * Determine pattern orientation
                 */
                $patWidth = $this->Data->getWidth();
                $patHeight = $this->Data->getHeight();

                /**
                 * Depending on pattern orientation, scale
                 * image to enforce at least one repeat
                 */
                $scaleX = $scaleY = 'auto';
                if($patHeight > $patWidth) {
                    $scaleY = '80%';
                } else {
                    $scaleX = '60%';
                }

                $styles[] = 'background-image:url('.$this->Data->getImageUrl().')';
                $styles[] = 'background-size:'. $scaleX. ' '. $scaleY;

                $Preview->setAttribute('style', join(';', $styles));
            }
        }

        $Form->appendTo($Container);
    }
    // End beforeRender

}
// End ElcaAdminSvgPatternView
