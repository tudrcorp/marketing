<?php

namespace App\Filament\Support;

use App\Marketing\BirthdayNotificationAudience;
use Filament\Forms\Components\CheckboxList;

class AudienceCheckboxList
{
    public static function make(
        string $name = 'audiences',
        ?string $label = 'Grupos destinatarios',
        bool $hiddenLabel = false,
    ): CheckboxList {
        $field = CheckboxList::make($name)
            ->options(BirthdayNotificationAudience::class)
            ->columns(2)
            ->bulkToggleable()
            ->required()
            ->minItems(1)
            ->extraAttributes(['class' => 'marketing-audience-checkbox-list'])
            ->columnSpanFull();

        if ($hiddenLabel) {
            $field->hiddenLabel();
        } elseif ($label !== null) {
            $field->label($label);
        }

        return $field;
    }
}
