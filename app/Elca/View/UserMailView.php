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
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\User;
use Beibob\Blibs\UserProfile;
use Elca\Elca;


/**
 * MailView View
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class UserMailView extends MailView
{
    /**
     * @param bool $tplName
     * @param User $User
     *
     * @throws \Exception
     */
    public function __construct($tplName, User $User)
    {
        parent::__construct($tplName, 'elca');

        if (!$User->isInitialized()) {
            throw new \Exception('No User given');
        }

        $this->assign('User', $User);
    }

    /**
     *
     */
    protected function beforeRender()
    {
        $this->assignSalutation();

        switch ($this->tplName) {
            case 'mail/confirmation':
                $hasAuthName = $this->getElementById('hasAuthName', true);
                $OptEmail    = $this->getElementById('OptEmail', true);

                if ($this->get('User')->getAuthName() == $this->get('User')->getEmail() || $this->get(
                        'User'
                    )->getAuthName() == $this->get('User')->getCandidateEmail()
                ) {
                    $hasAuthName->parentNode->removeChild($hasAuthName);
                }

                if (!Environment::getInstance()->getConfig()->elca->auth->uniqueEmail) {
                    $OptEmail->parentNode->removeChild($OptEmail);
                }

                break;
            case 'mail/invitation':
                $hasAuthName = $this->getElementById('hasAuthName', true);
                $hasNoAuthName = $this->getElementById('hasNoAuthName', true);

                if (!Environment::getInstance()->getConfig()->elca->auth->uniqueEmail) {
                    $hasAuthName->parentNode->removeChild($hasAuthName);
                } else {
                    if ($this->get('User')->getAuthName() == $this->get('User')->getEmail() || $this->get(
                            'User'
                        )->getAuthName() == $this->get('User')->getCandidateEmail()
                    ) {
                        $hasAuthName->parentNode->removeChild($hasAuthName);
                    } else {
                        $hasNoAuthName->parentNode->removeChild($hasNoAuthName);
                    }
                }

                break;
        }

    }
    // End beforeRender


    /**
     * @translate value 'Sehr geehrte Frau %firstname% %lastname%'
     * @translate value 'Sehr geehrter Herr %firstname% %lastname%'
     * @translate value 'Guten Tag %firstname% %lastname%'
     *
     */
    protected function assignSalutation()
    {
        if (!$this->get('User') || !$this->get('User')->isInitialized() || !$this->get('User') instanceof User) {
            return $this->assign(
                'salutation',
                trim(t('Guten Tag %firstname% %lastname%', null, ['%firstname%' => '', '%lastname%' => '']))
            );
        }

        if ($this->get('User')->getFirstname() || $this->get('User')->getLastname()) {
            // Nur Vorname: Guten Tag Fabian
            if ($this->get('User')->getFirstname() && !$this->get('User')->getLastname()) {
                $salutation = t(
                    'Guten Tag %firstname% %lastname%',
                    null,
                    [
                        '%firstname%' => $this->get('User')->getFirstname(),
                        '%lastname%'  => $this->get('User')->getLastname(),
                    ]
                );
            } else {
                if ($this->get('User')->getGender() == UserProfile::GENDER_MALE) {
                    $salutation = t(
                        'Sehr geehrter Herr %firstname% %lastname%',
                        null,
                        [
                            '%firstname%' => $this->get('User')->getFirstname(),
                            '%lastname%'  => $this->get('User')->getLastname(),
                        ]
                    );
                } elseif ($this->get('User')->getGender() == UserProfile::GENDER_FEMALE) {
                    $salutation = t(
                        'Sehr geehrte Frau %firstname% %lastname%',
                        null,
                        [
                            '%firstname%' => $this->get('User')->getFirstname(),
                            '%lastname%'  => $this->get('User')->getLastname(),
                        ]
                    );
                } else {
                    $salutation = t(
                        'Guten Tag %firstname% %lastname%',
                        null,
                        [
                            '%firstname%' => $this->get('User')->getFirstname(),
                            '%lastname%'  => $this->get('User')->getLastname(),
                        ]
                    );
                }
            }
        } else {
            $salutation = t('Guten Tag %firstname% %lastname%', null, ['%firstname%' => '', '%lastname%' => '']);
        }

        return $this->assign('salutation', trim($salutation));
    }
    // End assignSalutation

}
// End MailView