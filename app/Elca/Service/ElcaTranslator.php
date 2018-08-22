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

namespace Elca\Service {

    use Beibob\Blibs\Environment;
    use Beibob\Blibs\Log;
    use Symfony\Component\Translation\Dumper\PoFileDumper;
    use Symfony\Component\Translation\Loader\PoFileLoader;
    use Symfony\Component\Translation\MessageSelector;
    use Symfony\Component\Translation\Translator;


    /**
     * ElcaTranslator ${CARET}
     *
     * @package
     * @author Tobias Lode <tobias@beibob.de>
     * @author Fabian Möller <fab@beibob.de>
     */
    class ElcaTranslator extends Translator
    {
        /**
         * @var array
         */
        private $newMessages = array();

        /**
         * @var string
         */
        private $i18nDir;

        /**
         * @var array
         */
        private $log = array();

        /**
         * Constructor.
         *
         * @param string               $locale   The locale
         * @param MessageSelector|null $selector The message selector for pluralization
         * @param string|null          $cacheDir The directory to use for the cache
         * @param bool                 $debug    Use cache in debug mode ?
         *
         * @throws \InvalidArgumentException If a locale contains invalid characters
         *
         * @api
         */
        public function __construct(
            ElcaLocale $locale,
            MessageSelector $selector = null,
            $cacheDir = null,
            $debug = false
        ) {
            $this->i18nDir = Environment::getInstance()->getConfig()->toDir('appDir').'Elca/assets/i18n/';
            $this->setFallbackLocales([ElcaLocale::FALLBACK_LOCALE]);

            if (!is_dir($this->i18nDir)) {
                mkdir($this->i18nDir);
            }

            if (!file_exists($this->i18nDir.'messages.'.ElcaLocale::FALLBACK_LOCALE.'.po')) {
                touch($this->i18nDir.'messages.'.ElcaLocale::FALLBACK_LOCALE.'.po');
            }

            parent::__construct((string)$locale, $selector, $cacheDir, $debug);

            $this->addLoader('po', new PoFileLoader());
            foreach (ElcaLocale::getSupportedLocales() as $supportedLocale) {
                $this->addResource('po', $this->i18nDir.'messages.'.$supportedLocale.'.po', $supportedLocale);
            }
        }
        // End __construct

        /**
         * @param array  $ids
         * @param string $domain
         */
        public function remove(array $ids, $domain = 'messages')
        {
            foreach (ElcaLocale::getSupportedLocales() as $locale) {
                $all = $this->getCatalogue($locale)->all($domain);
                foreach ($ids as $id => $str) {
                    unset($all[$id]);
                }

                $this->getCatalogue($locale)->replace($all, $domain);

                $dumper = new PoFileDumper();
                $dumper->dump($this->getCatalogue($locale), array('path' => $this->i18nDir));
            }
        }
        // End remove

        /**
         * @param string $id
         * @param array  $parameters
         * @param null   $domain
         * @param null   $locale
         *
         * @return string
         */
        public function trans($id, array $parameters = array(), $domain = null, $locale = null)
        {
            if (null === $domain) {
                $domain = 'messages';
            }

            if (!$id) {
                return $id;
            }

            /**
             * Skip markers of untranslated texts if given
             */
            $matches = [];
            preg_match('/\_\_\*\*(.+?)\*\*\_\_/ums', $id, $matches);
            if (count($matches) && isset($matches[1])) {
                $id = $matches[1];
            }


            if (!$this->getCatalogue()->has($id, $domain) && !isset($this->newMessages[$id])) {
                Log::getInstance()->debug('MsgId `'.$id."' not found in [".$this->getLocale().']');
                $this->newMessages[$id] = $id;
            }

            $config = Environment::getInstance()->getInstance()->getConfig();

            $message = parent::trans($id, $parameters, $domain, $locale);
            if ($this->getLocale() != 'de' && $message == $id) {
                if ($config->translate->highlightMissingTranslations) {
                    $message = sprintf('__**%s**__', $message);
                }
            }

            return $message;
        }
        // End trans

        /**
         * @return mixed
         */
        public function getLog()
        {
            return $this->log;
        }
        // End getLog

        /**
         * @param $msg
         *
         * @return mixed
         */
        public function log($msg)
        {
            $this->log[] = $msg;

            return $msg;
        } // End log

        /**
         * @return array
         */
        public function getNewMessages()
        {
            return $this->newMessages;
        }
        // End getNewMessages

        /**
         *
         */
        public function __destruct()
        {
            $config = Environment::getInstance()->getConfig();
            if (!count($this->newMessages) || !$config->translate->autoWriteMessageFiles) {
                return;
            }

            foreach (ElcaLocale::getSupportedLocales() as $locale) {
                foreach ($this->newMessages as $id => $message) {
                    $this->getCatalogue($locale)->set($id, $message);
                }

                $dumper = new PoFileDumper();
                $dumper->dump($this->getCatalogue($locale), array('path' => $this->i18nDir));
            }
        }
        // End __destruct
    }
}
// End ElcaTranslator

namespace {

    use Beibob\Blibs\Environment;

    /**
     * @param       $id
     * @param null  $domain
     * @param array $parameters
     * @param null  $locale
     *
     * @return mixed
     * @throws \DI\NotFoundException
     */
    function t($id, $domain = null, $parameters = array(), $locale = null)
    {
        return Environment::getInstance()->getContainer()->get('Elca\Service\ElcaTranslator')->trans(
            $id,
            $parameters,
            $domain,
            $locale
        );
    }
    // End t
}
