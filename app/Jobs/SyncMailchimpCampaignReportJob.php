<?php

namespace App\Jobs;

use App\Marketing\NotificationDispatchStatus;
use App\Models\NotificationDispatchLog;
use App\Services\Marketing\MarketingApiHttpFactory;
use App\Services\Marketing\MarketingApiTraceRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class SyncMailchimpCampaignReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $massNotificationId,
        public string $campaignId,
    ) {
        $this->onQueue('email');
    }

    public function handle(MarketingApiHttpFactory $httpFactory, MarketingApiTraceRecorder $apiTraceRecorder): void
    {
        $endpoint = rtrim((string) config('services.marketing_api.base_url'), '/')
            .config('services.marketing_api.mass_email_campaigns_path', '/api/emails/campaigns')
            .'/'.$this->campaignId;

        try {
            $response = $httpFactory->emailClient()->acceptJson()->get($endpoint);
        } catch (ConnectionException $exception) {
            Log::warning('No se pudo consultar el reporte de Mailchimp.', [
                'campaign_id' => $this->campaignId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $apiTrace = $apiTraceRecorder->fromResponse(
            label: 'Reporte Mailchimp',
            endpoint: $endpoint,
            method: 'GET',
            request: ['campaign_id' => $this->campaignId],
            response: $response,
        );

        if (! $response->successful()) {
            Log::warning('Mailchimp no devolvió el reporte de la campaña.', [
                'campaign_id' => $this->campaignId,
                'status' => $response->status(),
            ]);

            return;
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];
        $events = is_array($payload['events'] ?? null) ? $payload['events'] : [];
        $fromEvents = $this->tallyWebhookEvents($events);
        $emailsSent = (int) ($payload['emails_sent'] ?? 0);
        $unsubscribed = max((int) ($payload['unsubscribed'] ?? 0), $fromEvents['unsubscribed']);
        $hardBounces = max((int) (data_get($payload, 'bounces.hard') ?? 0), $fromEvents['hard_bounces']);
        $softBounces = max((int) (data_get($payload, 'bounces.soft') ?? 0), $fromEvents['soft_bounces']);
        $opens = max((int) ($payload['opens'] ?? 0), $fromEvents['opens']);
        $clicks = max((int) ($payload['clicks'] ?? 0), $fromEvents['clicks']);

        $log = NotificationDispatchLog::query()
            ->where('mass_notification_id', $this->massNotificationId)
            ->where('channel', 'email')
            ->get()
            ->first(function (NotificationDispatchLog $log): bool {
                $campaignId = data_get($log->technical_detail, 'mailchimp_campaign_id')
                    ?? data_get($log->technical_detail, 'api_calls.0.response.campaign_id');

                return $campaignId === $this->campaignId;
            });

        if ($log === null) {
            return;
        }

        $summary = sprintf(
            'Mailchimp: %d enviados, %d abiertos, %d clics, %d rebotes duros, %d bajas.',
            $emailsSent,
            $opens,
            $clicks,
            $hardBounces,
            $unsubscribed,
        );

        $status = $log->status;

        if ($hardBounces > 0 || $unsubscribed > 0 || $softBounces > 0) {
            $status = $emailsSent > 0
                ? NotificationDispatchStatus::Partial->value
                : NotificationDispatchStatus::Failed->value;
        } elseif ($emailsSent > 0) {
            $status = NotificationDispatchStatus::Sent->value;
        }

        $technicalDetail = is_array($log->technical_detail) ? $log->technical_detail : [];
        $technicalDetail['mailchimp_campaign_id'] = $this->campaignId;
        $technicalDetail['mailchimp_report'] = [
            'emails_sent' => $emailsSent,
            'opens' => $opens,
            'clicks' => $clicks,
            'bounces' => [
                'hard' => $hardBounces,
                'soft' => $softBounces,
            ],
            'unsubscribed' => $unsubscribed,
            'events' => $events,
        ];
        $existingCalls = is_array($technicalDetail['api_calls'] ?? null) ? $technicalDetail['api_calls'] : [];
        $technicalDetail['api_calls'] = array_values(array_merge(
            $existingCalls,
            $apiTrace['api_calls'] ?? [],
        ));

        $log->forceFill([
            'status' => $status,
            'summary' => mb_substr($summary, 0, 500),
            'sent_count' => $emailsSent > 0 ? $emailsSent : $log->sent_count,
            'technical_detail' => $technicalDetail,
        ])->save();
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return array{opens: int, clicks: int, hard_bounces: int, soft_bounces: int, unsubscribed: int}
     */
    private function tallyWebhookEvents(array $events): array
    {
        $types = collect($events)
            ->map(fn (mixed $event): string => mb_strtolower((string) data_get($event, 'type')))
            ->filter()
            ->all();

        $countExact = function (string ...$needles) use ($types): int {
            return count(array_filter($types, fn (string $type): bool => in_array($type, $needles, true)));
        };

        return [
            'opens' => $countExact('open'),
            'clicks' => $countExact('click'),
            'hard_bounces' => $countExact('hard_bounce', 'hard-bounce', 'cleaned', 'rejected'),
            'soft_bounces' => $countExact('soft_bounce', 'soft-bounce'),
            'unsubscribed' => $countExact('unsubscribe', 'unsub'),
        ];
    }
}
