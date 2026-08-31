<?php

use App\Services\Marketing\MarketingLegalSuppliersApiService;
use Illuminate\Support\Facades\Http;

it('paginates legal suppliers from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/suppliers*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 783, 'totalPages' => 79],
            'data' => [
                [
                    'name' => "\nCENTRO PROFESIONAL COLONIAL C.A",
                    'status_convenio' => 'GENERAL',
                    'status_sistema' => 'SIN RESPUESTA DE AFILIACION',
                    'rif' => 'J313713406',
                    'razon_social' => "\nCENTRO PROFESIONAL COLONIAL C.A",
                    'personal_phone' => null,
                    'local_phone' => '02468711339',
                    'correo_principal' => 'centroprofesional_nomina@hotmail.com',
                ],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingLegalSuppliersApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(783)
        ->and($paginator->items()[0]['id'])->toBe('J313713406')
        ->and($paginator->items()[0]['name'])->toBe('CENTRO PROFESIONAL COLONIAL C.A');
});

it('assigns a synthetic id when rif is missing', function () {
    Http::fake([
        'http://localhost:4000/api/suppliers*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'name' => 'LABORATORIO CLINICO CAC, C.A.',
                    'status_convenio' => 'GENERAL',
                    'status_sistema' => 'EN PROCESO',
                    'rif' => null,
                    'razon_social' => 'LABORATORIO CLINICO CAC, C.A.',
                    'personal_phone' => null,
                    'local_phone' => '02123623656',
                    'correo_principal' => null,
                ],
            ],
        ], 200),
    ]);

    $item = app(MarketingLegalSuppliersApiService::class)->paginate()->items()[0];

    expect($item['id'])->toStartWith('supplier-');
});

it('finds a legal supplier by rif', function () {
    Http::fake([
        'http://localhost:4000/api/suppliers*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'name' => 'CENTRO PROFESIONAL COLONIAL C.A',
                    'rif' => 'J313713406',
                    'razon_social' => 'CENTRO PROFESIONAL COLONIAL C.A',
                ],
            ],
        ], 200),
    ]);

    expect(app(MarketingLegalSuppliersApiService::class)->find('J313713406'))
        ->not->toBeNull()
        ->and(app(MarketingLegalSuppliersApiService::class)->find('J313713406')['rif'])
        ->toBe('J313713406');
});
