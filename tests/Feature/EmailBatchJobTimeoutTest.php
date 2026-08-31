<?php

use App\Jobs\SendBirthdayEmailBatchJob;
use App\Jobs\SendCorporateEventInvitationEmailJob;
use App\Jobs\SendMassNotificationEmailBatchJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

/**
 * Los tres jobs que hablan con el endpoint bulk deben pedirle al worker más margen que el
 * timeout HTTP del propio API; con el valor por defecto del worker (60 s) el job muere a
 * media entrega y el lote queda enviado del lado del API pero sin traza aquí.
 */
$emailJobs = [
    'masivas' => fn (): object => new SendMassNotificationEmailBatchJob(
        massNotificationId: 1,
        emails: ['destinatario@tdg.test'],
        subject: 'Asunto',
        copy: '<p>Copy</p>',
        batchNumber: 1,
        totalBatches: 1,
        sentById: 1,
        source: 'mass_audience',
    ),
    'cumpleaños' => fn (): object => new SendBirthdayEmailBatchJob(
        birthdayNotificationId: 1,
        recipients: ['destinatario@tdg.test'],
        batchNumber: 1,
        totalBatches: 1,
    ),
    'invitación de evento' => fn (): object => new SendCorporateEventInvitationEmailJob(
        corporateEventId: 1,
        email: 'destinatario@tdg.test',
        message: 'Mensaje',
        sentByName: 'Analista',
        sentById: 1,
    ),
];

it('da al job de correo más margen que el timeout HTTP del API', function (Closure $makeJob) {
    config()->set('services.marketing_api.email_timeout', 300);

    $job = $makeJob();

    expect($job->timeout)->toBe(360)
        ->and($job->timeout)->toBeGreaterThan(config('services.marketing_api.email_timeout'))
        ->and($job->queue)->toBe('email');
})->with($emailJobs);

it('acompaña al timeout configurado del API', function (Closure $makeJob) {
    config()->set('services.marketing_api.email_timeout', 600);

    expect($makeJob()->timeout)->toBe(660);
})->with($emailJobs);

it('encola el job de correo con el timeout en el payload', function () {
    config()->set('services.marketing_api.email_timeout', 300);

    Queue::connection('database')->push(new SendBirthdayEmailBatchJob(
        birthdayNotificationId: 1,
        recipients: ['destinatario@tdg.test'],
        batchNumber: 1,
        totalBatches: 1,
    ));

    $payload = json_decode(DB::table('jobs')->value('payload'), associative: true);

    expect($payload['timeout'])->toBe(360);
});
