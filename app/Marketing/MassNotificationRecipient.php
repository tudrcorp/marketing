<?php

namespace App\Marketing;

readonly class MassNotificationRecipient
{
    public function __construct(
        public string $sourceId,
        public string $name,
        public BirthdayNotificationAudience $audience,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $replyPhone = null,
        public ?string $replyContactName = null,
    ) {}
}
