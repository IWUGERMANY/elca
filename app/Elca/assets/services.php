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

use Bnb\Model\Benchmark\BnbBenchmarkSystemModel;
use function DI\get;
use Elca\Model\Import\Xml\Importer;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Model\Processing\ReferenceProjectLcaProcessingObserver;
use Elca\Service\Admin\BenchmarkSystemsService;
use Elca\Service\Assistant\ElementAssistantRegistry;
use Elca\Service\Assistant\Pillar\FoundationAssistant;
use Elca\Service\Assistant\Pillar\PillarAssistant;
use Elca\Service\Assistant\Stairs\StaircaseAssistant;
use Elca\Service\Assistant\Window\DormerAssistant;
use Elca\Service\Assistant\Window\WindowAssistant;
use Elca\Service\ElcaElementImageCache;
use Elca\Service\ElcaTemplateTranslation;
use Elca\Service\Element\ElementService;
use Elca\Service\Event\EventPublisher;
use Elca\Service\ProcessConfigNameTranslator;
use Elca\Service\Project\AccessToken\ProjectAccessTokenMailer;
use Elca\Service\Project\ProjectElementService;
use Elca\Service\Project\ProjectVariant\ProjectVariantService;
use Interop\Container\ContainerInterface;
use Lcc\Model\LccProjectVariantObserver;
use Lcc\Model\Processing\LccProcessingObserver;
use Lcc\Service\LccBenchmarkSystemObserver;

return [
    ElcaLcaProcessor::class => DI\object()
        ->constructor(DI\get('elca.lca_processing_observers')),

    'elca.lca_processing_observers' => [
        DI\get(ReferenceProjectLcaProcessingObserver::class),
        DI\get(LccProcessingObserver::class),
    ],

    ProjectVariantService::class => DI\object()
        ->constructor(DI\get('elca.project_variant_observers')),

    ElementService::class => DI\object()
        ->constructor(DI\get('elca.element_observers')),
    ProjectElementService::class => DI\object()
        ->constructor(DI\get('elca.element_observers')),

    BenchmarkSystemsService::class => DI\object()
        ->constructor(
            DI\get('elca.benchmark_systems'),
            DI\get('elca.benchmark_systems_observers')
        ),
    Importer::class => DI\factory(
        function (ContainerInterface $container) {
            $importer = Importer::getInstance();
            $importer->registerObservers($container->get('elca.import_observers'));

            return $importer;
        }
    ),

    ElcaElementImageCache::class => DI\object(),

    ElementAssistantRegistry::class => DI\object()
        ->constructor(DI\get('elca.element_assistants')),

    WindowAssistant::class    => DI\object(),
    DormerAssistant::class => DI\object(),
    StaircaseAssistant::class => DI\object()->lazy(),
    PillarAssistant::class => DI\object(),
    FoundationAssistant::class => DI\object(),

    'elca.element_assistants'           => [
        DI\get(WindowAssistant::class),
        DI\get(StaircaseAssistant::class),
        DI\get(PillarAssistant::class),
        DI\get(FoundationAssistant::class),
        DI\get(DormerAssistant::class),
    ],
    'elca.element_observers'            => [
        DI\get(WindowAssistant::class),
        DI\get(StaircaseAssistant::class),
        DI\get(PillarAssistant::class),
        DI\get(\Lcc\Model\LccElementObserver::class),
        DI\get(FoundationAssistant::class),
        DI\get(DormerAssistant::class),
    ],
    'elca.project_variant_observers'    => [
        DI\get(WindowAssistant::class),
        DI\get(DormerAssistant::class),
        DI\get(LccProjectVariantObserver::class),
    ],
    'elca.import_observers'         => [
        DI\get(WindowAssistant::class),
        DI\get(DormerAssistant::class),
    ],
    'elca.benchmark_systems' => [
        DI\get(BnbBenchmarkSystemModel::class),
    ],
    'elca.benchmark_systems_observers' => [
        DI\get(LccBenchmarkSystemObserver::class),
    ],
    'templateEngine.substitutions'      => [
        DI\get(ElcaTemplateTranslation::class),
    ],
    'elca.search_and_replace_observers' => [
        DI\get(StaircaseAssistant::class),
    ],

    'bnb.processor' => DI\get(\Bnb\Model\Processing\BnbProcessor::class),

    'elca.event_listeners' => [
        DI\get(ProjectAccessTokenMailer::class),
    ],

    EventPublisher::class => DI\object(EventPublisher::class)
        ->constructor(DI\get('elca.event_listeners')),
];

