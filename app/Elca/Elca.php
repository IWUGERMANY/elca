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
namespace Elca;

use Beibob\Blibs\Config;
use Beibob\Blibs\ConfigIni;
use Beibob\Blibs\CssLoader;
use Beibob\Blibs\Environment;
use Beibob\Blibs\JsLoader;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectVariant;
use Elca\Model\ElcaModuleInterface;
use Elca\Model\Navigation\ElcaNavigationInterface;
use Elca\Service\ElcaTranslator;
use Exception;

/**
 * Elca module singleton registry class
 *
 * Global constants and string mappings are defined here.
 * Elca also provides a global persistent session namespace
 * to save application state for current projects and variants
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class Elca
{
    /**
     * Name
     */
    const NAME = 'eLCA';
    /**
     * Version
     */
    const VERSION = '1.8.0';
    const VERSION_BBSR = '0.9.7';

    /**
     * Default ini path
     */
    const DEFAULTS_INI_FILE = 'defaults.ini';

    /**
     * Default life time in years
     */
    const DEFAULT_LIFE_TIME = 50;

    /**
     * Default reference project count for early projections
     */
    const DEFAULT_REF_PROJECT_COUNT = 5;

    /**
     * constr measures
     */
    const CONSTR_MEASURE_PRIVATE = 1;
    const CONSTR_MEASURE_PUBLIC  = 2;

    /*
     * elca roles
     *
     * @translate value 'Anwender'
     * @translate value 'Forscher'
     * @translate value 'Administrator'
     * @translate value 'Hochschule'
     */
    const ELCA_ROLES = 'ELCA_ROLES';
    const ELCA_ROLE_ADMIN = 'ADMIN';
    const ELCA_ROLE_STANDARD = 'STANDARD';
    const ELCA_ROLE_BETA = 'BETA';
    const ELCA_ROLE_ORGA = 'ORGA';
    const ELCA_ROLE_TESTING = 'TESTING';
    const ELCA_ROLE_PROPOSE_ELEMENTS = 'PROPOSE_ELEMENTS';

    /**
     * Default user group name
     *
     * @translate value 'Anwender'
     */
//    const DEFAULT_USER_GROUP_NAME = 'Anwender';

    /**
     * Limit project count for default user
     */
    const MAX_PROJECTS_PER_USER = 2;

    /**
     * Docs file path
     */
    const DOCS_FILEPATH = 'docs/';

    /**
     * NOTES-de.md, NOTES-en.md file
     */
    const MD_NOTES_FILENAME_PATTERN = 'NOTES-%s.md';

    /**
     * Handbook file
     *
     * i.e. eLCA_handbuch_de.pdf oder eLCA_cookbook_tr.pdf
     * Jedenfalls wird hinten immer die aktuelle Sprache dran geklebt, wollt ich nur sagen
     */
    const HANDBOOK_FILENAME_PATTERN = 'eLCA_%s_%s.pdf';

    /**
     * HISTORY.md file
     */
    const MD_HISTORY_FILENAME = 'HISTORY.md';
    const MD_HISTORY_FILEPATH = '/';

    /**
     * Units
     */
    const UNIT_KG  = 'kg';
    const UNIT_M3  = 'm3';
    const UNIT_M2  = 'm2';
    const UNIT_M   = 'm';
    const UNIT_STK = 'Stück';
    const UNIT_KWH = 'kWh';
    const UNIT_MJ  = 'MJ';
    const UNIT_TKM = 't*km';

    /**
     * Extra element attributes
     */
    const ELEMENT_ATTR_U_VALUE = 'elca.uValue';
    const ELEMENT_ATTR_R_W = 'elca.rW';
    const ELEMENT_ATTR_OZ = 'elca.oz';
	const ELEMENT_ATTR_IFCGUID = 'elca.ifcguid';
    const ELEMENT_ATTR_EOL = 'elca.bnb.eol';
    const ELEMENT_ATTR_SEPARATION = 'elca.bnb.separation';
    const ELEMENT_ATTR_RECYCLING = 'elca.bnb.recycling';

    const ELEMENT_COMPONENT_ATTR_UNKNOWN = 'elca.unknown';

    /**
     * @translate array Elca\Elca::$elementAttributes
     */
    public static $elementAttributes = [Elca::ELEMENT_ATTR_U_VALUE => 'U-Wert',
                                             Elca::ELEMENT_ATTR_R_W => 'R\'w',
                                             Elca::ELEMENT_ATTR_OZ => 'OZ',
											 Elca::ELEMENT_ATTR_IFCGUID => 'IFC-GUID'
                                             ];

    /**
     * @translate array Elca\Elca::$elementBnbAttributes
     */
    public static $elementBnbAttributes = [Elca::ELEMENT_ATTR_EOL => 'Rückbau',
                                                Elca::ELEMENT_ATTR_SEPARATION => 'Trennung',
                                                Elca::ELEMENT_ATTR_RECYCLING => 'Verwertung'
                                                ];

    /**
     * default config objects
     */
    private $defaults = [];

    /**
     * Namespace
     */
    private $Namespace;

    /**
     * Construction measure mapping
     *
     * @translate array Elca\Elca::$constrMeasures
     */
    public static $constrMeasures = [self::CONSTR_MEASURE_PRIVATE => 'Private Baumaßnahme',
                                          self::CONSTR_MEASURE_PUBLIC  => 'Öffentliche Baumaßnahme'];

    /**
     * Unit mapping
     *
     * @translate value 'kg'
     * @translate value 'm³'
     * @translate value 'm²'
     * @translate value 'm'
     * @translate value 'Stück'
     * @translate value 'kWh'
     * @translate value 'MJ'
     * @translate value 'tkm'
     * @translate array Elca\Elca::$units
     */
    public static $units = [self::UNIT_KG       => 'kg',
                                 self::UNIT_M3  => 'm³',
                                 self::UNIT_M2  => 'm²',
                                 self::UNIT_M   => 'm',
                                 self::UNIT_STK => 'Stück',
                                 self::UNIT_KWH => 'kWh',
                                 self::UNIT_MJ  => 'MJ',
                                 self::UNIT_TKM => 'tkm'
                                 ];

    /**
     * Life cycle phase name mapping
     *
     * @translate array Elca\Elca::$lcPhases
     */
    public static $lcPhases = [
        ElcaLifeCycle::PHASE_PROD => 'Herstellung',
        ElcaLifeCycle::PHASE_OP   => 'Betrieb',
        ElcaLifeCycle::PHASE_MAINT => 'Instandhaltung',
        ElcaLifeCycle::PHASE_EOL  => 'Entsorgung',
        ElcaLifeCycle::PHASE_REC  => 'Rückgewinnung'
    ];

    /**
     * eLCA extension modules\
     * @var ElcaModuleInterface[] $modules
     */
    private $modules = [];

    /**
     * eLCA navigation
     * @var ElcaNavigationInterface[] $navigations
     */
    private $navigations = [];

    private $Translator;

    /**
     * Constructor
     *
     * @return Elca
     */
    public function __construct(ElcaTranslator $Translator)
    {
        $environment = Environment::getInstance();
        $session = $environment->getSession();

        $this->Namespace = $session->getNamespace('elca', true);
        $this->Translator = $Translator ? $Translator : $environment->getInstance()->getContainer()->get(ElcaTranslator::class);

        /**
         * Load additional css and js files
         */
        CssLoader::getInstance()->register('elca', 'elca.css?'.Elca::VERSION);
        CssLoader::getInstance()->register('elca', 'print.css?'.Elca::VERSION, 'print');
        JsLoader::getInstance()->register('elca', 'elca.min.js?'.Elca::VERSION);
    }
    // End __construct

    /**
     * Returns the singelton
     *
     * @param  -
     * @return Elca
     */
    public static function getInstance()
    {
        return Environment::getInstance()->getContainer()->get(get_class());
    }
    // End getInstance

    /**
     * @return mixed
     */
    public function getTranslator()
    {
        return $this->Translator;
    }
    // End getTranslator

    /**
     * @param mixed $Translator
     */
    public function setTranslator($Translator)
    {
        $this->Translator = $Translator;
    }
    // End setTranslator

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->Translator->getLocale();
    }
    // End getLocale

    /**
     * Returns the default config
     *
     * @param  string $section
     * @return Config
     */
    public function getDefaults($section)
    {
        if(!isset($this->defaults[$section]))
        {
            $Config = Environment::getInstance()->getConfig();
            $configPath = $Config->toDir('configDir') . self::DEFAULTS_INI_FILE;
            $this->defaults[$section] = new ConfigIni($configPath, $section);
        }

        return $this->defaults[$section];
    }
    // End getDefaults


    /**
     * Sets the current projectId
     *
     * @param  int $projectId
     * @return void -
     */
    public function setProjectId($projectId)
    {
        $this->Namespace->projectId = $projectId;

        if (    $this->getProject()->getId() == $projectId   // project id of session and parameter are equal
            &&  $this->getProjectVariantId()                 // variant id is set in session
            &&  $this->getProjectVariant()->getProjectId()  ==  $this->getProject()->getId() // project variant belongs to session project
          )
          return; // keep actual session project variant

        $this->setProjectVariantId($this->getProject()->getCurrentVariantId());

    }
    // End setProjectId


    /**
     * Returns true if a projectId isset
     *
     * @return boolean
     */
    public function hasProjectId()
    {
        return (bool)$this->Namespace->projectId;
    }
    // End hasProjectId



    /**
     * Returns the current projectId
     *
     * @return int
     */
    public function getProjectId()
    {
        return $this->Namespace->projectId;
    }
    // End getProjectId



    /**
     * Returns the current project
     *
     * @return ElcaProject
     */
    public function getProject()
    {
        return ElcaProject::findById($this->Namespace->projectId);
    }
    // End getProject



    /**
     * Unsets the projectId
     *
     * @return void -
     */
    public function unsetProjectId()
    {
        unset($this->Namespace->projectId);
        $this->unsetProjectVariantId();
    }
    // End unsetProjectId



    /**
     * Sets the current projectVariantId
     *
     * @param  int $projectVariantId
     * @return void -
     */
    public function setProjectVariantId($projectVariantId)
    {
        $this->Namespace->projectVariantId = $projectVariantId;
    }
    // End setProjectVariantId



    /**
     * Returns true if a projectVariantId isset
     *
     * @return boolean
     */
    public function hasProjectVariantId()
    {
        return (bool)$this->Namespace->projectVariantId;
    }
    // End hasProjectVariantId



    /**
     * Returns the current projectVariantId
     *
     * @return int
     */
    public function getProjectVariantId()
    {
        return $this->Namespace->projectVariantId;
    }
    // End getProjectVariantId



    /**
     * Returns the current project variant
     *
     * @return ElcaProjectVariant
     */
    public function getProjectVariant()
    {
        return ElcaProjectVariant::findById($this->Namespace->projectVariantId);
    }
    // End getProjectVariant


    /**
     * Unsets the projectVariantId
     *
     * @return void -
     */
    public function unsetProjectVariantId()
    {
        unset($this->Namespace->projectVariantId);
    }
    // End unsetProjectVariantId


    /**
     * Returns a list of registered elca modules
     */
    public function getModules()
    {
        return $this->modules;
    }
    // End getModules


    /**
     * Registers a navigation interface
     */
    public function registerAdditionalNavigation(ElcaNavigationInterface $Navigation)
    {
        return $this->navigations[] = $Navigation;
    }
    // End registerAdditionalNavigation


    /**
     * Returns a list of registered navigations
     * @return ElcaNavigationInterface[]
     */
    public function getAdditionalNavigations()
    {
        return $this->navigations;
    }
    // End getNavigations


    /**
     * @return int
     */
    public function getProjectLimit()
    {
        $environment = Environment::getInstance();
        $config = $environment->getConfig();

        if (isset($config->elca) &&
            isset($config->elca->maxProjectsPerUser) &&
            is_numeric($config->elca->maxProjectsPerUser) &&
            (int)$config->elca->maxProjectsPerUser > 0
        ) {

            return (int)$config->elca->maxProjectsPerUser;
        }

        return self::MAX_PROJECTS_PER_USER;
    }

    /**
     * @return string
     */
    public function getHandbookFilepath()
    {
        $environment = Environment::getInstance();
        $config = $environment->getConfig();
        $baseDir = $config->toDir('baseDir');

        return sprintf($baseDir . self::DOCS_FILEPATH . self::HANDBOOK_FILENAME_PATTERN, t('users_manual'), $this->getLocale());
    }
    // End getHandbookFilepath

    /**
     * Init Modules
     *
     */
    public function initModules()
    {
        $Environment = Environment::getInstance();
        $Config = $Environment->getConfig();

        /**
         * Init modules
         */
        if(isset($Config->elca) && isset($Config->elca->modules))
        {
            foreach($Config->elca->toList('modules') as $name)
            {
                if(!isset($Config->elca->$name))
                    throw new Exception('Module `'. $name .'\' has no configuration. Define `elca.'.$name.'\' in etc/config.ini');

                $ModuleConfig = $Config->elca->$name;

                if(!isset($ModuleConfig->class))
                    throw new Exception('Module `'. $name .'\' has no module class defined. Define `elca.'.$name.'.class = ModuleClassName\' in etc/config.ini and read the documentation how to add a module extension to eLCA');

                // First register the module
                $Environment->registerModule($name);

                $clsName = $ModuleConfig->class;

                if(!class_exists($clsName))
                    throw new \Exception('Module class `'. $clsName .'\' as defined in etc/config.ini `elca.'.$name.'.class = '.$clsName.'\' does not exist. Read the documentation how to add a module extension to eLCA');

                $this->modules[$name] = new $clsName();
                $this->modules[$name]->init();
            }
        }
    }
    // End initModules
}
// End Elca
