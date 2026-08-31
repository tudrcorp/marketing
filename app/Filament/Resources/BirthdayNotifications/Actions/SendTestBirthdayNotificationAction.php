<?php

namespace App\Filament\Resources\BirthdayNotifications\Actions;

use App\Filament\Support\ChannelToggleButtons;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\BirthdayNotification;
use App\Services\Marketing\BirthdayNotificationTestSendService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class SendTestBirthdayNotificationAction
{
    public static function make(): Action
    {
        return Action::make('sendTestBirthdayNotification')
            ->label('Envío de prueba')
            ->icon(Heroicon::OutlinedBeaker)
            ->color('info')
            ->visible(fn (BirthdayNotification $record): bool => Gate::check('sendTest', $record)
                && filled($record->copy))
            ->modalHeading('Envío de prueba')
            ->modalDescription('Envía un mensaje de validación a un destinatario de prueba.')
            ->modalIcon(Heroicon::OutlinedBeaker)
            ->modalIconColor('gray')
            ->modalSubmitActionLabel('Enviar prueba')
            ->modalCancelActionLabel('Cancelar')
            ->modalWidth(Width::FourExtraLarge)
            ->extraModalWindowAttributes(['class' => 'marketing-birthday-test-send-modal'])
            ->schema([
                View::make('filament.birthday-notifications.test-send-modal-summary')
                    ->columnSpanFull(),
                Section::make('Canales')
                    ->description('Marca los canales que deseas validar.')
                    ->extraAttributes(['class' => 'marketing-test-send__section'])
                    ->schema([
                        ChannelToggleButtons::make('test_channels', hiddenLabel: true, inline: true)
                            ->default(fn (BirthdayNotification $record): array => $record->channels ?? [])
                            ->tooltips([
                                BirthdayNotificationChannel::WhatsApp->value => 'Prueba el mensaje como lo vería un contacto en WhatsApp.',
                                BirthdayNotificationChannel::Email->value => 'Envía un correo de prueba al destinatario indicado.',
                                BirthdayNotificationChannel::Sms->value => 'Valida el SMS con el copy y la imagen configurados.',
                            ]),
                    ]),
                Section::make('Destinatario')
                    ->description('Datos de contacto para recibir la prueba.')
                    ->extraAttributes(['class' => 'marketing-test-send__section'])
                    ->schema([
                        View::make('filament.birthday-notifications.test-send-destinations-panel')
                            ->columnSpanFull(),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('analista@tudoctorgroup.com')
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            ->extraAttributes(['class' => 'marketing-test-send__contact-field'])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::hasChannel(
                                $get('test_channels') ?? [],
                                BirthdayNotificationChannel::Email,
                            ))
                            ->required(fn (Get $get): bool => self::hasChannel(
                                $get('test_channels') ?? [],
                                BirthdayNotificationChannel::Email,
                            )),
                        TextInput::make('phone')
                            ->label('Número de teléfono')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('+58 412 0000000')
                            ->prefixIcon(Heroicon::OutlinedPhone)
                            ->helperText('Incluye código de país. Se usa para WhatsApp y SMS.')
                            ->extraAttributes(['class' => 'marketing-test-send__contact-field'])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::requiresPhone($get('test_channels') ?? []))
                            ->required(fn (Get $get): bool => self::requiresPhone($get('test_channels') ?? [])),
                    ]),
            ])
            ->authorize(fn (BirthdayNotification $record): bool => Gate::check('sendTest', $record))
            ->action(function (
                array $data,
                BirthdayNotification $record,
                BirthdayNotificationTestSendService $service,
            ): void {
                $result = $service->send(
                    notification: $record,
                    channels: self::normalizeChannels($data['test_channels'] ?? []),
                    email: $data['email'] ?? null,
                    phone: $data['phone'] ?? null,
                    sentBy: auth()->user(),
                );

                if ($result->allSuccessful()) {
                    Notification::make()
                        ->title('Envío de prueba completado')
                        ->body($result->summary())
                        ->success()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Envío de prueba con errores')
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
            ->values()
            ->all();
    }
}
