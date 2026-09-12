<?php

use App\Marketing\ContentPostStatus;
use App\Services\Marketing\ContentBoardService;

test('el semáforo de carga escala según las entregas del día', function (int $count, string $expected) {
    expect(ContentBoardService::workloadLevel($count))->toBe($expected);
})->with([
    'día libre' => [0, 'empty'],
    'carga baja' => [2, 'light'],
    'carga media' => [4, 'moderate'],
    'carga alta' => [6, 'high'],
    'día saturado' => [9, 'saturated'],
]);

test('la leyenda del semáforo describe los cuatro niveles con carga', function () {
    $legend = ContentBoardService::workloadLegend();

    expect($legend)->toHaveCount(4)
        ->and(collect($legend)->pluck('level')->all())
        ->toBe(['light', 'moderate', 'high', 'saturated']);
});

test('cada estado declara su etiqueta de trabajo por lotes', function () {
    expect(ContentPostStatus::Writing->batchingLabel())->toBe('Copywriting')
        ->and(ContentPostStatus::Design->batchingLabel())->toBe('Diseño')
        ->and(ContentPostStatus::Published->isOpen())->toBeFalse()
        ->and(ContentPostStatus::Idea->isOpen())->toBeTrue();
});
