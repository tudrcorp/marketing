<?php

use App\Filament\Resources\ExternalCompanies\Pages\CreateExternalCompany;
use App\Filament\Resources\ExternalCompanies\Pages\ListExternalCompanies;
use App\Filament\Resources\ExternalCompanies\Pages\ViewExternalCompany;
use App\Filament\Resources\ExternalCompanies\Support\ExternalCompanyResponsiblePresentation;
use App\Jobs\SendMassNotificationWhatsAppBatchJob;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\ExternalCompanyType;
use App\Marketing\MarketingPermission;
use App\Models\ExternalCompany;
use App\Models\MarketingRole;
use App\Models\MassNotification;
use App\Models\User;
use App\Services\Marketing\ExistingMassNotificationOptions;
use App\Services\Marketing\ExternalCompanyContactCollector;
use App\Services\Marketing\ExternalCompanyImporter;
use App\Services\Marketing\MarketingAudienceContactCollector;
use App\Services\Marketing\MassNotificationDispatchService;
use App\Services\Marketing\MassNotificationRecipientResolver;
use App\Services\Marketing\SelectedAudienceSummary;
use Carbon\Carbon;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);

    Filament::setCurrentPanel('marketing');
});

function externalCompanyUserWithPermissions(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('external company contact collector uses company phone and attaches responsible as reply contact', function () {
    $company = ExternalCompany::factory()->create([
        'company_name' => 'Medios Andinos',
        'email' => 'contacto@mediosandinos.com',
        'phone' => '04249998877',
        'responsible_name' => 'María López',
        'responsible_phone' => '04141234567',
        'responsible_email' => 'maria@mediosandinos.com',
    ]);

    $recipient = app(ExternalCompanyContactCollector::class)->resolveRecipient($company);

    expect($recipient)->not->toBeNull()
        ->and($recipient->name)->toBe('Medios Andinos')
        ->and($recipient->email)->toBe('contacto@mediosandinos.com')
        ->and($recipient->phone)->toBe('584249998877')
        ->and($recipient->replyPhone)->toBe('584141234567')
        ->and($recipient->replyContactName)->toBe('María López')
        ->and($recipient->audience)->toBe(BirthdayNotificationAudience::Externals);
});

test('external company contact collector falls back to responsible email and phone', function () {
    $company = ExternalCompany::factory()->create([
        'email' => 'no-es-un-correo',
        'phone' => 'invalido',
        'responsible_email' => 'responsable@example.com',
        'responsible_phone' => '04141112222',
        'responsible_name' => 'Ana Pérez',
    ]);

    $recipient = app(ExternalCompanyContactCollector::class)->resolveRecipient($company);

    expect($recipient)->not->toBeNull()
        ->and($recipient->email)->toBe('responsable@example.com')
        ->and($recipient->phone)->toBe('584141112222')
        ->and($recipient->replyContactName)->toBe('Ana Pérez');
});

test('audience contact collector includes each external company', function () {
    ExternalCompany::factory()->create([
        'email' => 'uno@example.com',
        'phone' => '04142221111',
    ]);

    ExternalCompany::factory()->create([
        'email' => 'dos@example.com',
        'phone' => '04143332222',
    ]);

    $recipients = app(MarketingAudienceContactCollector::class)->collect([
        BirthdayNotificationAudience::Externals,
    ]);

    expect($recipients)->toHaveCount(2)
        ->and(collect($recipients)->pluck('phone')->all())->toBe([
            '584142221111',
            '584143332222',
        ]);
});

test('recipient resolver maps selected external companies to their own normalized phone', function () {
    $company = ExternalCompany::factory()->create([
        'phone' => '04145556666',
        'responsible_phone' => '04143334444',
    ]);

    $recipients = app(MassNotificationRecipientResolver::class)->resolveMany(
        BirthdayNotificationAudience::Externals,
        [$company],
    );

    expect($recipients)->toHaveCount(1)
        ->and($recipients[0]->phone)->toBe('584145556666')
        ->and($recipients[0]->replyPhone)->toBe('584143334444');
});

test('dispatch service sends whatsapp to each selected external company', function () {
    Queue::fake();

    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    $company = ExternalCompany::factory()->create([
        'company_name' => 'Prensa del Este',
        'email' => 'envio@example.com',
        'phone' => '04149990000',
        'responsible_name' => 'Ana Pérez',
        'responsible_phone' => '04147778888',
    ]);

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::Externals,
        selectedRecords: Collection::make([$company]),
        data: [
            'title' => 'Aviso importante',
            'copy' => 'Mensaje de prueba para externos.',
            'channels' => [BirthdayNotificationChannel::WhatsApp->value],
        ],
        sentBy: $user,
    );

    expect($result->allSuccessful())->toBeTrue();

    Queue::assertPushed(SendMassNotificationWhatsAppBatchJob::class, function ($job): bool {
        return $job->phones === ['584149990000']
            && str_contains($job->copy, 'Contacto responsable: Ana Pérez (584147778888)')
            && str_contains($job->copy, 'Mensaje de prueba para externos.');
    });
});

test('analista can create external company from filament', function () {
    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewExternalCompanies,
        MarketingPermission::ManageExternalCompanies,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateExternalCompany::class)
        ->fillForm([
            'company_name' => 'Medios Andinos',
            'legal_name' => 'Medios Andinos C.A.',
            'document_id' => 'J-12345678-9',
            'phone' => '04142223333',
            'email' => 'contacto@mediosandinos.com',
            'responsible_name' => 'María López',
            'responsible_document_id' => 'V-87654321',
            'responsible_phone' => '04148887777',
            'responsible_email' => 'maria@mediosandinos.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $company = ExternalCompany::query()->where('company_name', 'Medios Andinos')->first();

    expect($company)->not->toBeNull()
        ->and($company->legal_name)->toBe('Medios Andinos C.A.')
        ->and($company->document_id)->toBe('J-12345678-9')
        ->and($company->responsible_phone)->toBe('04148887777')
        ->and($company->created_by_id)->toBe($user->id);

    Livewire::test(ViewExternalCompany::class, ['record' => $company->getKey()])
        ->assertSuccessful();
});

test('analista without permission cannot access external companies list', function () {
    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewPublications,
    ]);

    $this->actingAs($user);

    Livewire::test(ListExternalCompanies::class)
        ->assertForbidden();
});

function externalCompanyRequiredColumnsCsv(array $rows, string $delimiter = ';'): string
{
    $lines = [implode($delimiter, ['Compañía', 'Razón social', 'RIF / CI', 'Teléfono', 'Correo'])];

    foreach ($rows as $row) {
        $lines[] = implode($delimiter, $row);
    }

    return implode("\n", $lines)."\n";
}

function externalCompanyCsv(array $rows, string $delimiter = ';'): string
{
    $headers = [
        'Compañía',
        'Razón social',
        'RIF / CI',
        'Teléfono',
        'Correo',
        'Responsable',
        'RIF / CI del responsable',
        'Teléfono del responsable',
        'Correo del responsable',
    ];

    $lines = [implode($delimiter, $headers)];

    foreach ($rows as $row) {
        $lines[] = implode($delimiter, $row);
    }

    return implode("\n", $lines)."\n";
}

function externalCompanyCsvPath(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'externos').'.csv';
    file_put_contents($path, $contents);

    return $path;
}

test('importer creates external companies from a csv file', function () {
    $path = externalCompanyCsvPath(externalCompanyCsv([
        ['Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com', 'María López', 'V-87654321', '04148887777', 'maria@mediosandinos.com'],
        ['Prensa del Este', 'Prensa del Este C.A.', 'J-98765432-1', '04149990000', 'Envio@Example.com', 'Ana Pérez', 'V-11223344', '04147778888', 'ana@example.com'],
    ]));

    $user = externalCompanyUserWithPermissions([MarketingPermission::ManageExternalCompanies]);

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv', $user->id);

    expect($result->created)->toBe(2)
        ->and($result->updated)->toBe(0)
        ->and($result->failed())->toBe(0)
        ->and(ExternalCompany::query()->count())->toBe(2);

    $company = ExternalCompany::query()->where('document_id', 'J-98765432-1')->first();

    expect($company->company_name)->toBe('Prensa del Este')
        ->and($company->email)->toBe('envio@example.com')
        ->and($company->created_by_id)->toBe($user->id);

    unlink($path);
});

test('importer updates the existing company when the document id already exists', function () {
    $existing = ExternalCompany::factory()->create([
        'company_name' => 'Medios Andinos',
        'document_id' => 'J-12345678-9',
        'phone' => '04140000000',
    ]);

    $path = externalCompanyCsvPath(externalCompanyCsv([
        ['Medios Andinos Digital', 'Medios Andinos C.A.', 'J123456789', '04142223333', 'contacto@mediosandinos.com', 'María López', 'V-87654321', '04148887777', 'maria@mediosandinos.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and(ExternalCompany::query()->count())->toBe(1);

    expect($existing->fresh()->company_name)->toBe('Medios Andinos Digital')
        ->and($existing->fresh()->phone)->toBe('04142223333');

    unlink($path);
});

test('importer reports invalid rows without discarding the valid ones', function () {
    $path = externalCompanyCsvPath(externalCompanyCsv([
        ['Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com', 'María López', 'V-87654321', '04148887777', 'maria@mediosandinos.com'],
        ['Sin Correo', 'Sin Correo C.A.', 'J-55555555-5', '04145556666', 'no-es-un-correo', 'Luis Díaz', 'V-99887766', '04143334444', 'luis@example.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(1)
        ->and($result->failed())->toBe(1)
        ->and($result->errors[0]['line'])->toBe(3)
        ->and($result->errors[0]['company'])->toBe('Sin Correo')
        ->and(ExternalCompany::query()->count())->toBe(1);

    unlink($path);
});

test('importer rejects a file without the required columns', function () {
    $path = externalCompanyCsvPath("Compañía;Teléfono\nMedios Andinos;04142223333\n");

    expect(fn () => app(ExternalCompanyImporter::class)->import($path, 'csv'))
        ->toThrow(RuntimeException::class, 'Faltan columnas obligatorias');

    unlink($path);
});

test('importer reads comma separated files as well', function () {
    $path = externalCompanyCsvPath(externalCompanyCsv([
        ['Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com', 'María López', 'V-87654321', '04148887777', 'maria@mediosandinos.com'],
    ], ','));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(1);

    unlink($path);
});

test('analista can import external companies from the filament list page', function () {
    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewExternalCompanies,
        MarketingPermission::ManageExternalCompanies,
    ]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->createWithContent('externos.csv', externalCompanyCsv([
        ['Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com', 'María López', 'V-87654321', '04148887777', 'maria@mediosandinos.com'],
    ]));

    Livewire::test(ListExternalCompanies::class)
        ->assertActionExists('importExternalCompanies')
        ->callAction('importExternalCompanies', ['archivo' => $file])
        ->assertHasNoActionErrors()
        ->assertSuccessful();

    expect(ExternalCompany::query()->where('document_id', 'J-12345678-9')->exists())->toBeTrue();
});

test('external company import template lists every required column', function () {
    $template = ExternalCompanyImporter::template();

    expect($template)->toContain('Compañía')
        ->toContain('Razón social')
        ->toContain('RIF / CI del responsable')
        ->toContain('Correo del responsable');
});

test('importer accepts files that only bring the required columns', function () {
    $path = externalCompanyCsvPath(externalCompanyRequiredColumnsCsv([
        ['Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(1)
        ->and($result->failed())->toBe(0);

    $company = ExternalCompany::query()->where('document_id', 'J-12345678-9')->first();

    expect($company->company_name)->toBe('Medios Andinos')
        ->and($company->type)->toBe(ExternalCompanyType::Company)
        ->and($company->responsible_name)->toBe('TDG-MAR-R'.now()->format('d-m-Y').'001')
        ->and($company->responsible_document_id)->toBe('001')
        ->and($company->responsible_phone)->toBeNull()
        ->and($company->responsible_email)->toBeNull();

    unlink($path);
});

test('importer keeps the stored responsible data when the file omits those columns', function () {
    $existing = ExternalCompany::factory()->create([
        'document_id' => 'J-12345678-9',
        'responsible_name' => 'María López',
        'responsible_phone' => '04148887777',
        'responsible_email' => 'maria@mediosandinos.com',
    ]);

    $path = externalCompanyCsvPath(externalCompanyRequiredColumnsCsv([
        ['Medios Andinos Digital', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->updated)->toBe(1);

    $existing->refresh();

    expect($existing->company_name)->toBe('Medios Andinos Digital')
        ->and($existing->responsible_name)->toBe('María López')
        ->and($existing->responsible_phone)->toBe('04148887777')
        ->and($existing->responsible_email)->toBe('maria@mediosandinos.com');

    unlink($path);
});

test('importer still requires the five mandatory columns', function () {
    $path = externalCompanyCsvPath("Compañía;RIF / CI;Teléfono;Correo\nMedios Andinos;J-12345678-9;04142223333;contacto@mediosandinos.com\n");

    expect(fn () => app(ExternalCompanyImporter::class)->import($path, 'csv'))
        ->toThrow(RuntimeException::class, 'Razón social');

    unlink($path);
});

test('importer rejects rows without the mandatory values', function () {
    $path = externalCompanyCsvPath(externalCompanyRequiredColumnsCsv([
        ['Medios Andinos', '', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(0)
        ->and($result->failed())->toBe(1)
        ->and($result->errors[0]['messages'][0])->toContain('Razón social');

    unlink($path);
});

function externalCompanyTypedCsv(array $rows, string $delimiter = ';'): string
{
    $lines = [implode($delimiter, ['Tipo', 'Compañía', 'Razón social', 'RIF / CI', 'Teléfono', 'Correo'])];

    foreach ($rows as $row) {
        $lines[] = implode($delimiter, $row);
    }

    return implode("\n", $lines)."\n";
}

test('importer registers natural persons when the type column says so', function () {
    $path = externalCompanyCsvPath(externalCompanyTypedCsv([
        ['Persona natural', 'Luis Díaz', '', 'V-87654321', '04249876543', 'luis.diaz@example.com'],
        ['Empresa', 'Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(2)
        ->and($result->failed())->toBe(0);

    $person = ExternalCompany::query()->where('document_id', 'V-87654321')->first();
    $company = ExternalCompany::query()->where('document_id', 'J-12345678-9')->first();

    expect($person->type)->toBe(ExternalCompanyType::NaturalPerson)
        ->and($person->company_name)->toBe('Luis Díaz')
        ->and($person->legal_name)->toBeNull()
        ->and($person->isNaturalPerson())->toBeTrue()
        ->and($company->type)->toBe(ExternalCompanyType::Company)
        ->and($company->legal_name)->toBe('Medios Andinos C.A.');

    unlink($path);
});

test('importer requires a legal name only for companies', function () {
    $path = externalCompanyCsvPath(externalCompanyTypedCsv([
        ['Empresa', 'Medios Andinos', '', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(0)
        ->and($result->failed())->toBe(1)
        ->and($result->errors[0]['messages'][0])->toContain('Razón social');

    unlink($path);
});

test('importer rejects rows with an unknown type', function () {
    $path = externalCompanyCsvPath(externalCompanyTypedCsv([
        ['Cooperativa', 'Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(0)
        ->and($result->failed())->toBe(1)
        ->and($result->errors[0]['messages'][0])->toContain('Tipo');

    unlink($path);
});

test('importer treats rows without a type column as companies', function () {
    $path = externalCompanyCsvPath(externalCompanyCsv([
        ['Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com', 'María López', 'V-87654321', '04148887777', 'maria@mediosandinos.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(1)
        ->and(ExternalCompany::query()->first()->type)->toBe(ExternalCompanyType::Company);

    unlink($path);
});

test('analista can create a natural person without legal name or responsible data', function () {
    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewExternalCompanies,
        MarketingPermission::ManageExternalCompanies,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateExternalCompany::class)
        ->fillForm([
            'type' => ExternalCompanyType::NaturalPerson->value,
            'company_name' => 'Luis Díaz',
            'document_id' => 'V-87654321',
            'phone' => '04249876543',
            'email' => 'luis.diaz@example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $person = ExternalCompany::query()->where('document_id', 'V-87654321')->first();

    expect($person)->not->toBeNull()
        ->and($person->type)->toBe(ExternalCompanyType::NaturalPerson)
        ->and($person->legal_name)->toBeNull()
        ->and($person->responsible_name)->toBeNull()
        ->and($person->created_by_id)->toBe($user->id);

    Livewire::test(ViewExternalCompany::class, ['record' => $person->getKey()])
        ->assertSuccessful();
});

test('a company still requires legal name and responsible data in the form', function () {
    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewExternalCompanies,
        MarketingPermission::ManageExternalCompanies,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateExternalCompany::class)
        ->fillForm([
            'type' => ExternalCompanyType::Company->value,
            'company_name' => 'Medios Andinos',
            'document_id' => 'J-12345678-9',
            'phone' => '04142223333',
            'email' => 'contacto@mediosandinos.com',
        ])
        ->call('create')
        ->assertHasFormErrors(['legal_name', 'responsible_name', 'responsible_phone']);
});

test('mass notification recipient falls back to the natural person phone when there is no responsible', function () {
    $person = ExternalCompany::factory()->naturalPerson()->create([
        'company_name' => 'Luis Díaz',
        'email' => 'luis.diaz@example.com',
        'phone' => '04249876543',
        'responsible_name' => null,
        'responsible_phone' => null,
        'responsible_email' => null,
    ]);

    $recipient = app(ExternalCompanyContactCollector::class)->resolveRecipient($person);

    expect($recipient)->not->toBeNull()
        ->and($recipient->name)->toBe('Luis Díaz')
        ->and($recipient->email)->toBe('luis.diaz@example.com')
        ->and($recipient->phone)->toBe('584249876543')
        ->and($recipient->replyPhone)->toBeNull()
        ->and($recipient->replyContactName)->toBeNull();
});

test('external companies table lists both types and can filter by type', function () {
    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewExternalCompanies,
        MarketingPermission::ManageExternalCompanies,
    ]);

    $this->actingAs($user);

    $company = ExternalCompany::factory()->create(['company_name' => 'Medios Andinos']);
    $person = ExternalCompany::factory()->naturalPerson()->create(['company_name' => 'Luis Díaz']);

    Livewire::test(ListExternalCompanies::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$company, $person])
        ->filterTable('type', ExternalCompanyType::NaturalPerson->value)
        ->assertCanSeeTableRecords([$person])
        ->assertCanNotSeeTableRecords([$company]);
});

test('importer assigns a generated responsible to every row that lacks one', function () {
    $this->travelTo(Carbon::parse('2026-09-13 10:00:00'));

    $path = externalCompanyCsvPath(externalCompanyTypedCsv([
        ['Empresa', 'Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com'],
        ['Persona natural', 'Luis Díaz', '', 'V-87654321', '04249876543', 'luis.diaz@example.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(2)
        ->and($result->responsibleReference)->toBe('TDG-MAR-R13-09-2026001')
        ->and($result->responsibleLine())->toContain('TDG-MAR-R13-09-2026001');

    $companies = ExternalCompany::query()->get();

    expect($companies)->toHaveCount(2);

    foreach ($companies as $company) {
        expect($company->responsible_name)->toBe('TDG-MAR-R13-09-2026001')
            ->and($company->responsible_document_id)->toBe('001');
    }

    unlink($path);
});

test('each import of the same day reserves the next correlative', function () {
    $this->travelTo(Carbon::parse('2026-09-13 10:00:00'));

    $first = externalCompanyCsvPath(externalCompanyTypedCsv([
        ['Empresa', 'Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com'],
    ]));
    $second = externalCompanyCsvPath(externalCompanyTypedCsv([
        ['Persona natural', 'Luis Díaz', '', 'V-87654321', '04249876543', 'luis.diaz@example.com'],
    ]));

    $importer = app(ExternalCompanyImporter::class);

    expect($importer->import($first, 'csv')->responsibleReference)->toBe('TDG-MAR-R13-09-2026001')
        ->and($importer->import($second, 'csv')->responsibleReference)->toBe('TDG-MAR-R13-09-2026002');

    expect(ExternalCompany::query()->where('document_id', 'V-87654321')->value('responsible_document_id'))->toBe('002');

    unlink($first);
    unlink($second);
});

test('importer keeps the responsible that the file brings and does not reserve a correlative', function () {
    $this->travelTo(Carbon::parse('2026-09-13 10:00:00'));

    $path = externalCompanyCsvPath(externalCompanyCsv([
        ['Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com', 'María López', 'V-87654321', '04148887777', 'maria@mediosandinos.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->responsibleReference)->toBeNull()
        ->and($result->responsibleLine())->toBeNull();

    $company = ExternalCompany::query()->first();

    expect($company->responsible_name)->toBe('María López')
        ->and($company->responsible_document_id)->toBe('V-87654321');

    unlink($path);
});

test('importer does not overwrite the responsible of an already registered external', function () {
    $this->travelTo(Carbon::parse('2026-09-13 10:00:00'));

    $existing = ExternalCompany::factory()->create([
        'document_id' => 'J-12345678-9',
        'responsible_name' => 'María López',
        'responsible_document_id' => 'V-87654321',
    ]);

    $path = externalCompanyCsvPath(externalCompanyTypedCsv([
        ['Empresa', 'Medios Andinos', 'Medios Andinos C.A.', 'J-12345678-9', '04142223333', 'contacto@mediosandinos.com'],
    ]));

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->updated)->toBe(1)
        ->and($result->responsibleReference)->toBeNull()
        ->and($existing->fresh()->responsible_name)->toBe('María López');

    unlink($path);
});

test('importer completes the responsible of an existing external that had none', function () {
    $this->travelTo(Carbon::parse('2026-09-13 10:00:00'));

    $existing = ExternalCompany::factory()->naturalPerson()->create([
        'document_id' => 'V-87654321',
        'responsible_name' => null,
        'responsible_document_id' => null,
    ]);

    $path = externalCompanyCsvPath(externalCompanyTypedCsv([
        ['Persona natural', 'Luis Díaz', '', 'V-87654321', '04249876543', 'luis.diaz@example.com'],
    ]));

    app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($existing->fresh()->responsible_name)->toBe('TDG-MAR-R13-09-2026001')
        ->and($existing->fresh()->responsible_document_id)->toBe('001');

    unlink($path);
});

test('externals table is grouped by responsible with a stable color per responsible', function () {
    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewExternalCompanies,
        MarketingPermission::ManageExternalCompanies,
    ]);

    $this->actingAs($user);

    ExternalCompany::factory()->create(['responsible_name' => 'TDG-MAR-R13-09-2026001']);
    ExternalCompany::factory()->create(['responsible_name' => 'María López']);

    Livewire::test(ListExternalCompanies::class)
        ->assertSuccessful()
        ->assertSee('TDG-MAR-R13-09-2026001')
        ->assertSee('María López')
        // El encabezado del grupo pinta el punto de color del responsable.
        ->assertSee('Importación del 13-09-2026 · correlativo 001.', false)
        ->assertSee('background-color:'.ExternalCompanyResponsiblePresentation::hex('TDG-MAR-R13-09-2026001'), false);

    expect(ExternalCompanyResponsiblePresentation::hex('TDG-MAR-R13-09-2026001'))
        ->toBe(ExternalCompanyResponsiblePresentation::hex('TDG-MAR-R13-09-2026001'))
        ->and(ExternalCompanyResponsiblePresentation::hex('María López'))
        ->not->toBe(ExternalCompanyResponsiblePresentation::hex('TDG-MAR-R13-09-2026001'))
        ->and(ExternalCompanyResponsiblePresentation::label(null))->toBe('Sin responsable');
});

test('responsible presentation explains where a generated responsible comes from', function () {
    expect(ExternalCompanyResponsiblePresentation::origin('TDG-MAR-R13-09-2026007'))
        ->toBe('Importación del 13-09-2026 · correlativo 007.')
        ->and(ExternalCompanyResponsiblePresentation::origin('María López'))
        ->toBe('Responsable registrado desde el panel.')
        ->and(ExternalCompanyResponsiblePresentation::importReference('María López'))->toBeNull()
        ->and((string) ExternalCompanyResponsiblePresentation::groupDescription('TDG-MAR-R13-09-2026007'))
        ->toContain(ExternalCompanyResponsiblePresentation::hex('TDG-MAR-R13-09-2026007'));
});

test('analista can send existing mass notifications to the selected externals', function () {
    Queue::fake();

    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewExternalCompanies,
        MarketingPermission::ManageExternalCompanies,
        MarketingPermission::ManageMassNotifications,
    ]);

    $this->actingAs($user);

    $company = ExternalCompany::factory()->create([
        'company_name' => 'Medios Andinos',
        'email' => 'contacto@mediosandinos.com',
        'phone' => '04142223333',
    ]);

    $first = MassNotification::factory()->create([
        'title' => 'Campaña de bienvenida',
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
    ]);
    $second = MassNotification::factory()->create([
        'title' => 'Campaña de cierre',
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
    ]);

    Livewire::test(ListExternalCompanies::class)
        ->assertTableBulkActionExists('sendExistingMassNotifications')
        ->callTableBulkAction('sendExistingMassNotifications', [$company], [
            'mass_notifications' => [$first->getKey(), $second->getKey()],
        ])
        ->assertHasNoTableBulkActionErrors();

    Queue::assertPushed(SendMassNotificationWhatsAppBatchJob::class, 2);

    // Las campañas seleccionadas se reutilizan: no se crean campañas nuevas.
    expect(MassNotification::query()->count())->toBe(2);
});

test('sending existing mass notifications requires picking at least one', function () {
    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewExternalCompanies,
        MarketingPermission::ManageExternalCompanies,
        MarketingPermission::ManageMassNotifications,
    ]);

    $this->actingAs($user);

    $company = ExternalCompany::factory()->create();

    Livewire::test(ListExternalCompanies::class)
        ->callTableBulkAction('sendExistingMassNotifications', [$company], [
            'mass_notifications' => [],
        ])
        ->assertHasTableBulkActionErrors(['mass_notifications']);
});

test('the send modal summarises how many recipients were selected', function () {
    expect(SelectedAudienceSummary::text(1))
        ->toBe('1 destinatario seleccionado. Cada notificación que marques se envía a todos ellos.');

    expect(SelectedAudienceSummary::text(70))
        ->toStartWith('70 destinatarios seleccionados.');

    // Un solo grupo no aporta nada al total: no se desglosa.
    expect(SelectedAudienceSummary::text(70, ['María López' => 70]))
        ->not->toContain('María López');

    $summary = SelectedAudienceSummary::text(70, ['María López' => 40, 'Pedro Ruiz' => 30]);

    expect($summary)->toStartWith('70 destinatarios seleccionados.')
        ->and($summary)->toContain('María López: 40 · Pedro Ruiz: 30');

    // Con muchos grupos se detallan los primeros y el resto se resume.
    $many = [];

    foreach (range(1, 9) as $i) {
        $many['Responsable '.$i] = $i;
    }

    expect(SelectedAudienceSummary::text(array_sum($many), $many))
        ->toContain('y 3 grupos más');
});

test('the mass notification picker only offers a capped slice of the history', function () {
    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewExternalCompanies,
        MarketingPermission::ManageExternalCompanies,
        MarketingPermission::ManageMassNotifications,
    ]);

    $this->actingAs($user);

    // La más antigua queda fuera del tramo reciente que se renderiza.
    $oldest = MassNotification::factory()->create([
        'title' => 'Campana historica del archivo',
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
    ]);

    MassNotification::factory()->count(40)->create([
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
    ]);

    // Sin mensaje redactado no se puede enviar: nunca debe ofrecerse.
    $withoutCopy = MassNotification::factory()->create([
        'title' => 'Campana sin mensaje redactado',
        'copy' => '',
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
    ]);

    $options = new ExistingMassNotificationOptions;

    $listed = $options->labels('', []);

    expect($listed)->toHaveCount(ExistingMassNotificationOptions::LIMIT)
        ->and($listed)->not->toHaveKey($oldest->getKey())
        ->and($listed)->not->toHaveKey($withoutCopy->getKey())
        ->and($options->total())->toBe(41)
        ->and($options->helperText('', []))->toContain('Usa el buscador');

    // El buscador alcanza el resto del histórico sin cargarlo entero.
    expect($options->labels('historica', []))->toBe([$oldest->getKey() => 'Campana historica del archivo']);

    // Lo ya marcado sigue visible aunque no coincida con la búsqueda en curso.
    $marked = $options->labels('sin-coincidencias', [$oldest->getKey()]);

    expect($marked)->toHaveKey($oldest->getKey())
        ->and($options->descriptions('sin-coincidencias', [$oldest->getKey()])[$oldest->getKey()])
        ->toContain('ya marcada');
});

test('a campaign outside the recent slice can still be sent after searching for it', function () {
    Queue::fake();

    $user = externalCompanyUserWithPermissions([
        MarketingPermission::ViewExternalCompanies,
        MarketingPermission::ManageExternalCompanies,
        MarketingPermission::ManageMassNotifications,
    ]);

    $this->actingAs($user);

    $company = ExternalCompany::factory()->create([
        'email' => 'contacto@mediosandinos.com',
        'phone' => '04142223333',
    ]);

    $oldest = MassNotification::factory()->create([
        'title' => 'Campana historica del archivo',
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
    ]);

    MassNotification::factory()->count(30)->create([
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
    ]);

    // Aunque no esté en el tramo reciente, la campaña marcada sigue siendo una opción válida.
    Livewire::test(ListExternalCompanies::class)
        ->callTableBulkAction('sendExistingMassNotifications', [$company], [
            'mass_notifications' => [$oldest->getKey()],
        ])
        ->assertHasNoTableBulkActionErrors();

    Queue::assertPushed(SendMassNotificationWhatsAppBatchJob::class, 1);
});

test('the downloaded template can be imported back without edits', function () {
    $path = externalCompanyCsvPath(ExternalCompanyImporter::template());

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->failed())->toBe(0)
        ->and($result->created)->toBe(2);

    expect(ExternalCompany::query()->where('document_id', 'J-123456789')->value('type'))
        ->toBe(ExternalCompanyType::Company)
        ->and(ExternalCompany::query()->where('document_id', 'V-87654321')->value('type'))
        ->toBe(ExternalCompanyType::NaturalPerson);

    unlink($path);
});

test('importer reads the template headers exported as a comma separated sheet', function () {
    $headers = 'Tipo,Compañía / Nombre y apellido,Razón social,RIF / CI,Teléfono,Correo,Responsable,RIF / CI del responsable,Teléfono del responsable,Correo del responsable';
    $row = 'Empresa,A Que Frank C.A.,A Que Frank C.A.,J-5023248-7,+584244134012,frank@example.com,,,,';

    $path = externalCompanyCsvPath($headers."\r\n".$row."\r\n");

    $result = app(ExternalCompanyImporter::class)->import($path, 'csv');

    expect($result->created)->toBe(1)
        ->and($result->failed())->toBe(0);

    $company = ExternalCompany::query()->first();

    expect($company->company_name)->toBe('A Que Frank C.A.')
        ->and($company->document_id)->toBe('J-5023248-7')
        ->and($company->responsible_name)->toBe('TDG-MAR-R'.now()->format('d-m-Y').'001');

    unlink($path);
});
