<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\MassNotificationAudienceProfile;
use App\Marketing\MassNotificationRecipient;
use App\Models\Client;

class MassNotificationRecipientResolver
{
    public function __construct(
        private ClientGroupContactCollector $clientGroupContactCollector,
    ) {}

    /**
     * @param  list<array<string, mixed>|Client>  $records
     * @return list<MassNotificationRecipient>
     */
    public function resolveMany(BirthdayNotificationAudience $audience, array $records): array
    {
        if ($audience === BirthdayNotificationAudience::ClientGroups) {
            return collect($records)
                ->map(fn (mixed $record): ?MassNotificationRecipient => $this->resolveClientGroupRecord($record))
                ->filter()
                ->values()
                ->all();
        }

        $profile = MassNotificationAudienceProfile::for($audience);

        return collect($records)
            ->map(fn (mixed $record): MassNotificationRecipient => $this->resolve($profile, (array) $record))
            ->values()
            ->all();
    }

    public function resolveClientGroupRecord(mixed $record): ?MassNotificationRecipient
    {
        $client = match (true) {
            $record instanceof Client => $record,
            is_numeric($record) => Client::query()->with('clientGroup')->find((int) $record),
            is_array($record) && filled($record['id'] ?? null) => Client::query()->with('clientGroup')->find((int) $record['id']),
            default => null,
        };

        if ($client === null) {
            return null;
        }

        $client->loadMissing('clientGroup');

        return $this->clientGroupContactCollector->resolveRecipient($client);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public function resolve(MassNotificationAudienceProfile $profile, array $record): MassNotificationRecipient
    {
        return new MassNotificationRecipient(
            sourceId: (string) ($record['id'] ?? ''),
            name: $this->resolveName($profile, $record),
            audience: $profile->audience,
            email: $this->resolveEmail($profile, $record),
            phone: $this->resolvePhone($profile, $record),
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
