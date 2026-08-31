<?php

use App\Filament\Resources\ClientGroups\Pages\CreateClientGroup;
use App\Filament\Resources\ClientGroups\Pages\ListClientGroups;
use App\Filament\Resources\ClientGroups\Pages\ViewClientGroup;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MarketingPermission;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\MarketingRole;
use App\Models\User;
use App\Services\Marketing\ClientGroupContactCollector;
use App\Services\Marketing\MarketingAudienceContactCollector;
use App\Services\Marketing\MassNotificationDispatchService;
use App\Services\Marketing\MassNotificationRecipientResolver;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);

    Filament::setCurrentPanel('marketing');
});

function clientGroupUserWithPermissions(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('client group contact collector uses client phone and attaches responsible as reply contact', function () {
    $group = ClientGroup::factory()->create([
        'responsible_name' => 'María López',
        'responsible_phone' => '04141234567',
    ]);

    $client = Client::factory()->for($group)->create([
        'full_name' => 'Acme Corp',
        'email' => 'cliente@example.com',
        'phone' => '04249998877',
    ]);

    $recipient = app(ClientGroupContactCollector::class)->resolveRecipient($client->fresh('clientGroup'));

    expect($recipient)->not->toBeNull()
        ->and($recipient->name)->toBe('Acme Corp')
        ->and($recipient->email)->toBe('cliente@example.com')
        ->and($recipient->phone)->toBe('584249998877')
        ->and($recipient->replyPhone)->toBe('584141234567')
        ->and($recipient->replyContactName)->toBe('María López')
        ->and($recipient->audience)->toBe(BirthdayNotificationAudience::ClientGroups);
});

test('audience contact collector includes each client phone for client groups audience', function () {
    $group = ClientGroup::factory()->create([
        'responsible_phone' => '04141112222',
    ]);

    Client::factory()->for($group)->create([
        'email' => 'uno@example.com',
        'phone' => '04142221111',
    ]);

    Client::factory()->for($group)->create([
        'email' => 'dos@example.com',
        'phone' => '04143332222',
    ]);

    $recipients = app(MarketingAudienceContactCollector::class)->collect([
        BirthdayNotificationAudience::ClientGroups,
    ]);

    expect($recipients)->toHaveCount(2)
        ->and(collect($recipients)->pluck('phone')->all())->toBe([
            '584142221111',
            '584143332222',
        ]);
});

test('recipient resolver maps selected clients to their own normalized phone', function () {
    $group = ClientGroup::factory()->create([
        'responsible_phone' => '04143334444',
    ]);

    $client = Client::factory()->for($group)->create([
        'phone' => '04145556666',
    ]);

    $recipients = app(MassNotificationRecipientResolver::class)->resolveMany(
        BirthdayNotificationAudience::ClientGroups,
        [$client->fresh('clientGroup')],
    );

    expect($recipients)->toHaveCount(1)
        ->and($recipients[0]->phone)->toBe('584145556666')
        ->and($recipients[0]->replyPhone)->toBe('584143334444');
});

test('dispatch service sends whatsapp to each selected client even when phones are duplicated', function () {
    Queue::fake([\App\Jobs\SendMassNotificationWhatsAppBatchJob::class]);

    $user = clientGroupUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    $group = ClientGroup::factory()->create([
        'responsible_name' => 'Ana Pérez',
        'responsible_phone' => '04147778888',
    ]);

    $sharedPhone = '04149990000';

    $clients = collect(range(1, 3))
        ->map(fn (int $index): Client => Client::factory()->for($group)->create([
            'full_name' => "Cliente {$index}",
            'email' => "cliente{$index}@example.com",
            'phone' => $sharedPhone,
        ]))
        ->all();

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::ClientGroups,
        selectedRecords: Collection::make($clients),
        data: [
            'title' => 'Aviso importante',
            'copy' => 'Mensaje de prueba para el grupo.',
            'channels' => [BirthdayNotificationChannel::WhatsApp->value],
        ],
        sentBy: $user,
    );

    expect($result->queued)->toBeTrue()
        ->and($result->allSuccessful())->toBeTrue()
        ->and($result->notification->recipient_ids)->toHaveCount(3);

    Queue::assertPushed(\App\Jobs\SendMassNotificationWhatsAppBatchJob::class, function ($job): bool {
        return $job->phones === ['584149990000', '584149990000', '584149990000'];
    });
});

test('dispatch service sends whatsapp to each client phone for client group recipients', function () {
    Queue::fake();

    $user = clientGroupUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    $group = ClientGroup::factory()->create([
        'responsible_name' => 'Ana Pérez',
        'responsible_phone' => '04147778888',
    ]);

    $client = Client::factory()->for($group)->create([
        'email' => 'envio@example.com',
        'phone' => '04149990000',
    ]);

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::ClientGroups,
        selectedRecords: Collection::make([$client->fresh('clientGroup')]),
        data: [
            'title' => 'Aviso importante',
            'copy' => 'Mensaje de prueba para el grupo.',
            'channels' => [BirthdayNotificationChannel::WhatsApp->value],
        ],
        sentBy: $user,
    );

    expect($result->allSuccessful())->toBeTrue();

    Queue::assertPushed(\App\Jobs\SendMassNotificationWhatsAppBatchJob::class, function ($job): bool {
        return $job->phones === ['584149990000']
            && str_contains($job->copy, 'Contacto responsable: Ana Pérez (584147778888)')
            && str_contains($job->copy, 'Mensaje de prueba para el grupo.');
    });
});

test('analista can create client group and register clients from filament', function () {
    $user = clientGroupUserWithPermissions([
        MarketingPermission::ViewClientGroups,
        MarketingPermission::ManageClientGroups,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateClientGroup::class)
        ->fillForm([
            'name' => 'Cartera Norte',
            'color' => '#2563eb',
            'responsible_name' => 'María López',
            'responsible_email' => 'maria@example.com',
            'responsible_phone' => '04142223333',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $group = ClientGroup::query()->where('name', 'Cartera Norte')->first();

    expect($group)->not->toBeNull()
        ->and($group->responsible_phone)->toBe('04142223333')
        ->and($group->created_by_id)->toBe($user->id);

    Livewire::test(ViewClientGroup::class, ['record' => $group->getKey()])
        ->assertSuccessful();

    Client::factory()->for($group)->create([
        'full_name' => 'Inversiones Delta CA',
        'document_id' => 'J-12345678-9',
        'email' => 'delta@example.com',
        'phone' => '04148887777',
    ]);

    expect($group->fresh()->clients)->toHaveCount(1)
        ->and($group->clients()->first()->full_name)->toBe('Inversiones Delta CA');
});

test('analista without permission cannot access client groups list', function () {
    $user = clientGroupUserWithPermissions([
        MarketingPermission::ViewPublications,
    ]);

    $this->actingAs($user);

    Livewire::test(ListClientGroups::class)
        ->assertForbidden();
});
