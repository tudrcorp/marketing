<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\MassNotificationAudienceProfile;
use App\Marketing\MassNotificationRecipient;

class MarketingAudienceContactCollector
{
    private const int PAGE_SIZE = 100;

    public function __construct(
        private ClientGroupContactCollector $clientGroupContactCollector,
    ) {}

    /**
     * @param  list<BirthdayNotificationAudience>  $audiences
     * @return list<MassNotificationRecipient>
     */
    public function collect(array $audiences): array
    {
        $seenEmails = [];
        $seenPhones = [];
        $recipients = [];

        foreach ($audiences as $audience) {
            foreach ($this->collectForAudience($audience) as $recipient) {
                $emailKey = $recipient->email ? mb_strtolower($recipient->email) : null;
                $phoneKey = $recipient->phone;

                if ($emailKey !== null && isset($seenEmails[$emailKey])) {
                    continue;
                }

                if ($phoneKey !== null && isset($seenPhones[$phoneKey])) {
                    continue;
                }

                if ($emailKey !== null) {
                    $seenEmails[$emailKey] = true;
                }

                if ($phoneKey !== null) {
                    $seenPhones[$phoneKey] = true;
                }

                $recipients[] = $recipient;
            }
        }

        return $recipients;
    }

    /**
     * @return list<MassNotificationRecipient>
     */
    private function collectForAudience(BirthdayNotificationAudience $audience): array
    {
        if ($audience === BirthdayNotificationAudience::ClientGroups) {
            return $this->clientGroupContactCollector->collect();
        }

        $profile = MassNotificationAudienceProfile::for($audience);

        /** @var MarketingPaginatedApiService $apiService */
        $apiService = app($profile->apiService);
        $recipients = [];
        $page = 1;

        do {
            $paginator = $apiService->paginate(page: $page, perPage: self::PAGE_SIZE);

            foreach ($paginator->items() as $record) {
                $recipient = $this->resolveRecipient($profile, $record);

                if ($recipient !== null) {
                    $recipients[] = $recipient;
                }
            }

            $page++;
        } while ($page <= $paginator->lastPage());

        return $recipients;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveRecipient(
        MassNotificationAudienceProfile $profile,
        array $record,
    ): ?MassNotificationRecipient {
        $email = $this->resolveEmail($profile, $record);
        $phone = $this->resolvePhone($profile, $record);

        if ($email === null && $phone === null) {
            return null;
        }

        return new MassNotificationRecipient(
            sourceId: (string) ($record['id'] ?? ''),
            name: $this->resolveName($profile, $record),
            audience: $profile->audience,
            email: $email,
            phone: $phone,
        );
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveEmail(MassNotificationAudienceProfile $profile, array $record): ?string
    {
        foreach ($profile->emailFields as $field) {
            $email = mb_strtolower(trim((string) ($record[$field] ?? '')));

            if ($this->isValidEmail($email)) {
                return $email;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolvePhone(MassNotificationAudienceProfile $profile, array $record): ?string
    {
        foreach ($profile->phoneFields as $field) {
            $phone = $this->normalizePhone((string) ($record[$field] ?? ''));

            if (filled($phone)) {
                return $phone;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveName(MassNotificationAudienceProfile $profile, array $record): string
    {
        foreach ($profile->nameFields as $field) {
            $name = trim((string) ($record[$field] ?? ''));

            if (filled($name)) {
                return $name;
            }
        }

        return 'Destinatario';
    }

    private function isValidEmail(string $email): bool
    {
        return filled($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function normalizePhone(string $phone): ?string
    {
        $normalized = preg_replace('/\s+/', '', trim($phone));

        return filled($normalized) ? $normalized : null;
    }
}
