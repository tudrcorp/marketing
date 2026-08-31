<?php

namespace App\Marketing;

readonly class BirthdayNotificationRecipient
{
    public function __construct(
        public string $email,
        public string $name,
        public BirthdayNotificationAudience $audience,
        public string $sourceId,
    ) {}
}
