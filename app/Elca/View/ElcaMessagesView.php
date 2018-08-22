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

use Beibob\Blibs\HtmlView;
use Elca\Model\MessageBag\MessageBag;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Messages\FlashMessages;

/**
 * Builds the messages view
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaMessagesView extends HtmlView
{
    /**
     * CSS class message type mapping
     */
    private static $messageTypes = [
        ElcaMessages::TYPE_ERROR   => 'error',
        ElcaMessages::TYPE_INFO    => 'info',
        ElcaMessages::TYPE_NOTICE  => 'notice',
        ElcaMessages::TYPE_CONFIRM => 'confirm',
    ];

    /**
     * @var ElcaMessages
     */
    private $messages;

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->messages = $this->get('messages');

        if (!$this->messages instanceof MessageBag) {
            $this->messages = new ElcaMessages();
        }
    }

    /**
     * Renders the view
     */
    protected function afterRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'msgBox']));

        if (!$this->messages->has()) {
            return;
        }

        $Content = $Container->appendChild($this->getDiv(['class' => 'content-wrapper']));
        $UlElt   = $Content->appendChild($this->getUl());

        foreach (self::$messageTypes as $msgType => $cssClass) {
            if (!$this->messages->has($msgType)) {
                continue;
            }

            $this->addClass($Container, $cssClass);

            foreach ($this->messages->get($msgType) as $confirmUrl => $message) {
                $Li = $UlElt->appendChild($this->getLi(['class' => $cssClass.' clearfix']));
                $Li->appendChild($this->getP($message, ['class' => 'message']));

                if ($msgType == ElcaMessages::TYPE_CONFIRM) {
                    $P = $Li->appendChild($this->getP(''));
                    $P->appendChild(
                        $this->getA(
                            [
                                'href'  => $confirmUrl,
                                'class' => 'confirm  no-history',
                            ],
                            t('Ja')
                        )
                    );

                    $P->appendChild($this->getSpan(t('Nein'), ['class' => 'cancel']));
                }
            }
        }
    }
    // End render

}
// End ElcaMessagesView
