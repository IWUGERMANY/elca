<?php

use NaWoh\Model\Benchmark\NaWohBenchmarkSystemModel;

return [
    'elca.benchmark_systems' => DI\add([
        DI\get(NaWohBenchmarkSystemModel::class),
    ]),
];
