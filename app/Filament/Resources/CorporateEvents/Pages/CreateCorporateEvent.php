<?php

namespace App\Filament\Resources\CorporateEvents\Pages;

use App\Filament\Concerns\SetsMarketingAuthor;
use App\Filament\Resources\CorporateEvents\CorporateEventResource;
use App\Marketing\CorporateEventStatus;
use App\Services\Marketing\CorporateEventRegistrationUrlService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCorporateEvent extends CreateRecord
{
    use SetsMarketingAuthor;

    protected static string $resource = CorporateEventResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data['status'] = CorporateEventStatus::Draft->value;
        $data['code'] = $this->generateCode($data['title'] ?? 'EVT');

        $urlService = app(CorporateEventRegistrationUrlService::class);
        $token = $urlService->generateToken();
        $data['registration_token'] = $token;
        $data['registration_url'] = $urlService->buildUrl($token);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function generateCode(string $title): string
    {
        $prefix = Str::upper(Str::substr(Str::slug($title, ''), 0, 4));

        return $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
    }
}
