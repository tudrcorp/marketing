<?php

namespace App\Services\Marketing;

use App\Mail\MassNotificationEmailAttachments;
use App\Mail\MassNotificationEmailRenderer;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\MassNotification;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class MassNotificationTestSendService
{
    public function __construct(
        private NotificationDispatchLogger $dispatchLogger,
        private MarketingApiTraceRecorder $apiTraceRecorder,
    ) {}

    /**
     * @param  list<string>  $channels
     */
    public function send(
        MassNotification $notification,
        array $channels,
        ?string $email,
        ?string $phone,
        User $sentBy,
    ): BirthdayNotificationTestSendResult {
        $results = collect($channels)
            ->map(fn (string $channel): ?BirthdayNotificationChannel => BirthdayNotificationChannel::tryFrom($channel))
            ->filter()
            ->unique()
            ->map(fn (BirthdayNotificationChannel $channel): BirthdayNotificationTestChannelResult => $this->sendToChannel(
                notification: $notification,
                channel: $channel,
                email: $email,
                phone: $phone,
                sentBy: $sentBy,
            ))
            ->values()
            ->all();

        return new BirthdayNotificationTestSendResult($results);
    }

    private function sendToChannel(
        MassNotification $notification,
        BirthdayNotificationChannel $channel,
        ?string $email,
        ?string $phone,
        User $sentBy,
    ): BirthdayNotificationTestChannelResult {
        try {
            $result = match ($channel) {
                BirthdayNotificationChannel::Email => $this->sendEmail($notification, $email, $sentBy),
                BirthdayNotificationChannel::WhatsApp,
                BirthdayNotificationChannel::Sms => $this->sendPhoneChannel($notification, $phone, $channel, $sentBy),
            };
        } catch (Throwable $exception) {
            $result = new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: $exception->getMessage(),
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Excepción no controlada: '.$exception->getMessage()),
            );
        }

        $recipient = $channel === BirthdayNotificationChannel::Email ? $email : $phone;

        $this->dispatchLogger->logChannelResult(
            base: new NotificationDispatchLogData(
                source: NotificationDispatchSource::MassTest,
                status: NotificationDispatchStatus::Failed,
                title: '[Prueba] '.$notification->title,
                summary: $result->message ?? 'Sin mensaje',
                channel: $channel,
                recipient: $recipient,
                massNotificationId: $notification->getKey(),
                sentById: $sentBy->getKey(),
                technicalDetail: $result->apiTrace,
            ),
            successful: $result->successful,
            message: $result->message ?? 'Sin mensaje',
            sent: $result->successful ? 1 : 0,
            total: 1,
        );

        return $result;
    }

    private function sendEmail(
        MassNotification $notification,
        ?string $email,
        User $sentBy,
    ): BirthdayNotificationTestChannelResult {
        if (! filled($email)) {
            return new BirthdayNotificationTestChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: false,
                message: 'Debes indicar un correo electrónico.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: falta correo del destinatario.'),
            );
        }

        if (! filled($notification->copy) && ! filled($notification->attachment)) {
            return new BirthdayNotificationTestChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: false,
                message: 'La notificación debe tener copy o adjunto para enviar el correo.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: la campaña no tiene copy ni adjunto.'),
            );
        }

        $endpoint = $this->bulkEmailsEndpoint();
        $payload = [
            'recipients' => json_encode([$email]),
            'copy' => app(MassNotificationEmailRenderer::class)->render(
                notification: $notification,
                sentByName: $sentBy->name,
            ),
            'subject' => $notification->title,
        ];

        try {
            $response = MassNotificationEmailAttachments::attach(
                app(MarketingApiHttpFactory::class)->emailClient(),
                $notification,
            )->post($endpoint, $payload);

            $apiTrace = $this->apiTraceRecorder->fromResponse(
                label: 'Correo masivo (prueba)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                response: $response,
            );

            /** @var array{success?: bool, failed?: int, failures?: list<array<string, mixed>>, message?: string, reason?: string}|null $payload */
            $payload = $response->json();
            $payloadFailed = is_array($payload) && (
                ($payload['success'] ?? true) === false
                || (int) ($payload['failed'] ?? 0) > 0
            );

            if (! $response->successful() || $payloadFailed) {
                $failureReason = null;

                if (is_array($payload) && is_array($payload['failures'][0] ?? null)) {
                    $failureReason = $payload['failures'][0]['reason'] ?? null;
                }

                return new BirthdayNotificationTestChannelResult(
                    channel: BirthdayNotificationChannel::Email,
                    successful: false,
                    message: filled($failureReason)
                        ? (string) $failureReason
                        : $this->resolveApiErrorMessage(is_array($payload) ? $payload : null, $response->body()),
                    apiTrace: $apiTrace,
                );
            }

            return new BirthdayNotificationTestChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: true,
                message: "El servidor aceptó el correo hacia {$email}. Si el buzón no existe, Gmail puede enviar un rebote al remitente en unos minutos.",
                apiTrace: $apiTrace,
            );
        } catch (ConnectionException $exception) {
            return new BirthdayNotificationTestChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: false,
                message: 'No se pudo conectar con el API de correos.',
                apiTrace: $this->apiTraceRecorder->fromConnectionError(
                    label: 'Correo masivo (prueba)',
                    endpoint: $endpoint,
                    method: 'POST',
                    request: $payload,
                    exception: $exception,
                ),
            );
        }
    }

    private function sendPhoneChannel(
        MassNotification $notification,
        ?string $phone,
        BirthdayNotificationChannel $channel,
        User $sentBy,
    ): BirthdayNotificationTestChannelResult {
        if ($channel === BirthdayNotificationChannel::Sms) {
            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: 'El canal SMS aún no está configurado. Usa WhatsApp.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: canal SMS no habilitado en este entorno.'),
            );
        }

        if (! filled($phone)) {
            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: 'Debes indicar un número de teléfono.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: falta teléfono del destinatario.'),
            );
        }

        if (! filled($notification->copy) && ! filled($notification->attachment)) {
            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: 'La notificación debe tener copy o adjunto para enviar el mensaje.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: la campaña no tiene copy ni adjunto.'),
            );
        }

        try {
            $normalizedPhone = MarketingPhoneNormalizer::normalize($phone);
        } catch (\InvalidArgumentException $exception) {
            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: $exception->getMessage(),
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: '.$exception->getMessage()),
            );
        }

        $endpoint = $this->birthdayTestEndpoint();
        $requestPayload = [
            'channel' => $channel->value,
            'phone' => $normalizedPhone,
            'copy' => $notification->copy,
            'image_url' => $notification->attachmentPublicUrl(),
            'notification_id' => $notification->getKey(),
            'sent_by_id' => $sentBy->getKey(),
            'is_test' => true,
        ];

        try {
            $response = app(MarketingApiHttpFactory::class)
                ->client($this->timeout())
                ->post($endpoint, $requestPayload);

            $apiTrace = $this->apiTraceRecorder->fromResponse(
                label: 'Mensajería (prueba masiva)',
                endpoint: $endpoint,
                method: 'POST',
                request: $requestPayload,
                response: $response,
            );

            if (! $response->successful()) {
                return new BirthdayNotificationTestChannelResult(
                    channel: $channel,
                    successful: false,
                    message: $this->resolveApiErrorMessage($response->json(), $response->body()),
                    apiTrace: $apiTrace,
                );
            }

            $payload = $response->json();

            if (is_array($payload) && ($payload['success'] ?? true) === false) {
                return new BirthdayNotificationTestChannelResult(
                    channel: $channel,
                    successful: false,
                    message: $this->resolveApiErrorMessage($payload, $response->body()),
                    apiTrace: $apiTrace,
                );
            }

            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: true,
                message: "Mensaje de prueba enviado a {$normalizedPhone}.",
                apiTrace: $apiTrace,
            );
        } catch (ConnectionException $exception) {
            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: 'No se pudo conectar con el API de mensajería.',
                apiTrace: $this->apiTraceRecorder->fromConnectionError(
                    label: 'Mensajería (prueba masiva)',
                    endpoint: $endpoint,
                    method: 'POST',
                    request: $requestPayload,
                    exception: $exception,
                ),
            );
        }
    }

    private function bulkEmailsEndpoint(): string
    {
        $path = config('services.marketing_api.bulk_emails_path', '/api/emails/bulk');

        return rtrim(config('services.marketing_api.base_url'), '/').$path;
    }

    private function birthdayTestEndpoint(): string
    {
        $path = config('services.marketing_api.birthday_test_send_path', '/api/notifications/birthday/test');

        return rtrim(config('services.marketing_api.base_url'), '/').$path;
    }

    private function timeout(): int
    {
        return (int) config('services.marketing_api.timeout', 5);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function resolveApiErrorMessage(?array $payload, string $body): string
    {
        if (is_array($payload)) {
            foreach (['reason', 'message', 'error', 'detail'] as $key) {
                if (filled($payload[$key] ?? null)) {
                    return (string) $payload[$key];
                }
            }
        }

        return filled($body) ? $body : 'El API rechazó el envío de prueba.';
    }
}
