<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\MassNotificationRecipient;
use App\Models\Client;

class ClientGroupContactCollector
{
    /**
     * @return list<MassNotificationRecipient>
     */
    public function collect(): array
    {
        return Client::query()
            ->with('clientGroup')
            ->whereHas('clientGroup')
            ->get()
            ->map(fn (Client $client): ?MassNotificationRecipient => $this->resolveRecipient($client))
            ->filter()
            ->values()
            ->all();
    }

    public function resolveRecipient(Client $client): ?MassNotificationRecipient
    {
        $group = $client->clientGroup;

        if ($group === null) {
            return null;
        }

        $email = mb_strtolower(trim($client->email));
        $phone = MarketingPhoneNormalizer::tryNormalize($client->phone);
        $replyPhone = MarketingPhoneNormalizer::tryNormalize($group->responsible_phone);

        if (! $this->isValidEmail($email) && $phone === null) {
            return null;
        }

        return new MassNotificationRecipient(
            sourceId: (string) $client->getKey(),
            name: $client->full_name,
            audience: BirthdayNotificationAudience::ClientGroups,
            email: $this->isValidEmail($email) ? $email : null,
            phone: $phone,
            replyPhone: $replyPhone,
            replyContactName: $group->responsible_name,
        );
    }

    private function isValidEmail(string $email): bool
    {
        return filled($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
