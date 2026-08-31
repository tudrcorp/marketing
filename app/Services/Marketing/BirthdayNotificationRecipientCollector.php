<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationAudienceProfile;
use App\Marketing\BirthdayNotificationRecipient;
use App\Models\BirthdayNotification;
use App\Support\BirthdayDate;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BirthdayNotificationRecipientCollector
{
    private const int PAGE_SIZE = 100;

    /**
     * @return list<BirthdayNotificationRecipient>
     */
    public function collect(BirthdayNotification $notification, ?Carbon $referenceDate = null): array
    {
        $referenceDate ??= Carbon::today();
        $seenEmails = [];
        $recipients = [];

        foreach ($notification->audienceEnums() as $audience) {
            foreach ($this->collectForAudience($audience, $referenceDate) as $recipient) {
                $normalizedEmail = mb_strtolower($recipient->email);

                if (isset($seenEmails[$normalizedEmail])) {
                    continue;
                }

                $seenEmails[$normalizedEmail] = true;
                $recipients[] = $recipient;
            }
        }

        return $recipients;
    }

    /**
     * @return list<BirthdayNotificationRecipient>
     */
    private function collectForAudience(
        BirthdayNotificationAudience $audience,
        Carbon $referenceDate,
    ): array {
        $profile = BirthdayNotificationAudienceProfile::for($audience);

        if ($profile === null) {
            return [];
        }

        /** @var MarketingPaginatedApiService $apiService */
        $apiService = app($profile->apiService);
        $recipients = [];
        $page = 1;

        do {
            $paginator = $apiService->paginate(page: $page, perPage: self::PAGE_SIZE);

            foreach ($paginator->items() as $record) {
                $recipient = $this->resolveRecipient($profile, $record, $referenceDate);

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
        BirthdayNotificationAudienceProfile $profile,
        array $record,
        Carbon $referenceDate,
    ): ?BirthdayNotificationRecipient {
        if (! $this->hasBirthdayToday($profile, $record, $referenceDate)) {
            return null;
        }

        $email = $this->resolveEmail($profile, $record);

        if ($email === null) {
            return null;
        }

        return new BirthdayNotificationRecipient(
            email: $email,
            name: $this->resolveName($profile, $record),
            audience: $profile->audience,
            sourceId: (string) ($record['id'] ?? ''),
        );
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function hasBirthdayToday(
        BirthdayNotificationAudienceProfile $profile,
        array $record,
        Carbon $referenceDate,
    ): bool {
        foreach ($profile->birthDateFields as $field) {
            if (BirthdayDate::isBirthdayToday($record[$field] ?? null, $referenceDate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveEmail(BirthdayNotificationAudienceProfile $profile, array $record): ?string
    {
        foreach ($profile->emailFields as $field) {
            $email = trim((string) ($record[$field] ?? ''));

            if ($this->isValidEmail($email)) {
                return mb_strtolower($email);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveName(BirthdayNotificationAudienceProfile $profile, array $record): string
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
}
