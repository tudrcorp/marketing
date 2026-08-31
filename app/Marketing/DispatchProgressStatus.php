<?php

namespace App\Marketing;

enum DispatchProgressStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Partial = 'partial';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return $this !== self::Processing;
    }

    public function label(): string
    {
        return match ($this) {
            self::Processing => 'Enviando…',
            self::Completed => 'Completado',
            self::Partial => 'Con advertencias',
            self::Failed => 'Fallido',
        };
    }
}
