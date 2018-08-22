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

use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\HttpRequest;
use Beibob\Blibs\UserStore;
use DOMDocument;
use Elca\Elca;
use Elca\Service\ElcaTranslator;
use Parsedown;

/**
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 */
class ElcaIndexView extends HtmlView
{
    /**
     * @var string $notes
     */
    private $notes;

    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_index', 'elca');
        $this->notes = $this->get('notes', null);
    }
    // End init


    /**
     * Called after rendering the template
     */
    protected function afterRender()
    {
        $NotesElt = $this->getElementById('notes', true);

        if($this->notes) {

            $Parsedown = new Parsedown();
            $html = '<div>'.$Parsedown->text($this->notes).'</div>';

            $DomDoc = new DOMDocument();
            $DomDoc->loadXML($html);

            if($ImportedNode = $this->importNode($DomDoc->firstChild, true)) {

                $NotesElt->appendChild($ImportedNode);

                $Links = $this->query('//a', $NotesElt);

                foreach($Links as $Link) {
                    $Link->setAttribute('target', '_blank');
                    $Link->setAttribute('class', 'no-xhr');
                }
            }

            if (UserStore::getInstance()->hasAdminPrivileges())
            {
                $config = Environment::getInstance()->getConfig();
                $baseDir = $config->toDir('baseDir');
                $notesFilepath = $baseDir . Elca::DOCS_FILEPATH . sprintf(Elca::MD_NOTES_FILENAME_PATTERN, Elca::getInstance()->getLocale());

                if (file_exists($notesFilepath))
                    $NotesElt->appendChild($this->getA(['href' => '/exports/downloadNotesFile/', 'class' => 'no-xhr'], t('%notesFile% herunterladen', null, ['%notesFile%' => basename($notesFilepath)])));
            }
        }
        else {
            $NotesElt->parentNode->removeChild($NotesElt);
        }
    }
    // End afterRender
}
// End ElcaIndexView
