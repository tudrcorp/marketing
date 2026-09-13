<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\MassNotificationRecipient;
use App\Models\ExternalCompany;

class ExternalCompanyContactCollector
{
    /**
     * @return list<MassNotificationRecipient>
     */
    public function collect(): array
    {
        return ExternalCompany::query()
            ->get()
            ->map(fn (ExternalCompany $company): ?MassNotificationRecipient => $this->resolveRecipient($company))
            ->filter()
            ->values()
            ->all();
    }

    public function resolveRecipient(ExternalCompany $company): ?MassNotificationRecipient
    {
        $email = $this->firstValidEmail([
            $company->email,
            $company->responsible_email,
        ]);
        $phone = MarketingPhoneNormalizer::tryNormalize($company->phone)
            ?? MarketingPhoneNormalizer::tryNormalize($company->responsible_phone);
        $replyPhone = MarketingPhoneNormalizer::tryNormalize($company->responsible_phone);

        if ($email === null && $phone === null) {
            return null;
        }

        return new MassNotificationRecipient(
            sourceId: (string) $company->getKey(),
            name: $company->company_name,
            audience: BirthdayNotificationAudience::Externals,
            email: $email,
            phone: $phone,
            replyPhone: $replyPhone,
            replyContactName: $company->responsible_name,
        );
    }

    /**
     * @param  list<string|null>  $emails
     */
    private function firstValidEmail(array $emails): ?string
    {
        foreach ($emails as $email) {
            $normalized = mb_strtolower(trim((string) $email));

            if ($this->isValidEmail($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    private function isValidEmail(string $email): bool
    {
        return filled($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
