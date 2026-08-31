<?php

namespace App\Filament\Resources\CorporateEvents\Actions;

use App\Filament\Resources\CorporateEvents\Support\CorporateEventSharePresentation;
use App\Filament\Support\ChannelToggleButtons;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\CorporateEvent;
use App\Services\Marketing\CorporateEventRegistrationShareService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ShareCorporateEventRegistrationAction
{
    public static function make(): Action
    {
        return Action::make('shareRegistration')
            ->label('Compartir inscripción')
            ->icon(Heroicon::OutlinedShare)
            ->color('info')
            ->slideOver()
            ->modalHeading('Compartir enlace de inscripción')
            ->modalDescription('Personaliza el mensaje y envíalo directamente al destinatario.')
            ->modalSubmitActionLabel('Enviar invitación')
            ->modalCancelActionLabel('Cancelar')
            ->modalWidth(Width::FourExtraLarge)
            ->extraModalWindowAttributes(['class' => 'marketing-corporate-event-share-modal'])
            ->schema([
                View::make('filament.corporate-events.share-registration-panel')
                    ->viewData(fn (CorporateEvent $record): array => [
                        'record' => $record,
                    ])
                    ->columnSpanFull(),
                Section::make('Mensaje')
                    ->description('Ajusta el texto antes de enviarlo. Para correo se usará en la plantilla junto con los datos del evento.')
                    ->extraAttributes(['class' => 'marketing-corporate-event-share__section'])
                    ->schema([
                        Textarea::make('message')
                            ->hiddenLabel()
                            ->rows(10)
                            ->required()
                            ->default(fn (CorporateEvent $record): string => app(CorporateEventSharePresentation::class)->shareMessage($record))
                            ->extraAttributes(['class' => 'marketing-corporate-event-share__message-field'])
                            ->columnSpanFull(),
                    ]),
                Section::make('Destinatario')
                    ->description('Elige los canales y el contacto que recibirá la invitación.')
                    ->extraAttributes(['class' => 'marketing-corporate-event-share__section'])
                    ->schema([
                        ChannelToggleButtons::make('channels', hiddenLabel: true, inline: true)
                            ->default([BirthdayNotificationChannel::Email->value])
                            ->tooltips([
                                BirthdayNotificationChannel::WhatsApp->value => 'Envía el mensaje personalizado por WhatsApp.',
                                BirthdayNotificationChannel::Email->value => 'Envía un correo con plantilla, logo y botón de inscripción.',
                                BirthdayNotificationChannel::Sms->value => 'Envía el mensaje personalizado por SMS.',
                            ]),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('invitado@ejemplo.com')
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            ->extraAttributes(['class' => 'marketing-corporate-event-share__contact-field'])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::hasChannel(
                                $get('channels') ?? [],
                                BirthdayNotificationChannel::Email,
                            ))
                            ->required(fn (Get $get): bool => self::hasChannel(
                                $get('channels') ?? [],
                                BirthdayNotificationChannel::Email,
                            )),
                        TextInput::make('phone')
                            ->label('Número de teléfono')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('+58 412 0000000')
                            ->prefixIcon(Heroicon::OutlinedPhone)
                            ->helperText('Incluye código de país. Se usa para WhatsApp y SMS.')
                            ->extraAttributes(['class' => 'marketing-corporate-event-share__contact-field'])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::requiresPhone($get('channels') ?? []))
                            ->required(fn (Get $get): bool => self::requiresPhone($get('channels') ?? [])),
                    ]),
            ])
            ->visible(fn (CorporateEvent $record): bool => filled($record->registration_url))
            ->authorize('update')
            ->action(function (
                array $data,
                CorporateEvent $record,
                CorporateEventRegistrationShareService $service,
            ): void {
                $channels = self::normalizeChannels($data['channels'] ?? []);

                if ($channels === []) {
                    Notification::make()
                        ->title('Canal no válido')
                        ->body('Selecciona al menos un canal de envío.')
                        ->warning()
                        ->send();

                    return;
                }

                $result = $service->sendToChannels(
                    event: $record,
                    channels: $channels,
                    message: $data['message'] ?? '',
                    email: $data['email'] ?? null,
                    phone: $data['phone'] ?? null,
                    sentBy: auth()->user(),
                );

                if ($result->allSuccessful()) {
                    Notification::make()
                        ->title($result->hasQueued() ? 'Invitación en camino' : 'Invitación enviada')
                        ->body($result->summary())
                        ->success()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title($result->failedResults() === $result->results ? 'No se pudo enviar la invitación' : 'Invitación enviada con errores')
                    ->body($result->failureMessage() ?? $result->summary())
                    ->warning()
                    ->send();
            });
    }

    /**
     * @param  list<string|BirthdayNotificationChannel>  $channels
     */
    private static function requiresPhone(array $channels): bool
    {
        return self::hasChannel($channels, BirthdayNotificationChannel::WhatsApp)
            || self::hasChannel($channels, BirthdayNotificationChannel::Sms);
    }

    /**
     * @param  list<string|BirthdayNotificationChannel>  $channels
     */
    private static function hasChannel(array $channels, BirthdayNotificationChannel $channel): bool
    {
        return in_array($channel->value, self::normalizeChannels($channels), true);
    }

    /**
     * @param  list<string|BirthdayNotificationChannel>  $channels
     * @return list<string>
     */
    private static function normalizeChannels(array $channels): array
    {
        return collect($channels)
            ->map(fn (string|BirthdayNotificationChannel $channel): string => $channel instanceof BirthdayNotificationChannel
                ? $channel->value
                : $channel)
            ->filter(fn (string $channel): bool => BirthdayNotificationChannel::tryFrom($channel) !== null)
            ->unique()
            ->values()
            ->all();
    }
}
