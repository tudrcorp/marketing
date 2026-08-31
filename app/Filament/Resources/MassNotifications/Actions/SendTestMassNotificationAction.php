<?php

namespace App\Filament\Resources\MassNotifications\Actions;

use App\Filament\Support\ChannelToggleButtons;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\MassNotification;
use App\Models\User;
use App\Services\Marketing\MassNotificationTestSendService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class SendTestMassNotificationAction
{
    public static function make(): Action
    {
        return Action::make('sendTestMassNotification')
            ->label('Envío de prueba')
            ->icon(Heroicon::OutlinedBeaker)
            ->color('info')
            ->visible(fn (MassNotification $record): bool => Gate::check('sendTest', $record)
                && filled($record->copy)
                && $record->channelEnums() !== [])
            ->modalHeading('Envío de prueba')
            ->modalDescription('Valida la campaña con un destinatario de prueba en los canales seleccionados.')
            ->modalIcon(Heroicon::OutlinedBeaker)
            ->modalIconColor('gray')
            ->modalSubmitActionLabel('Enviar prueba')
            ->modalCancelActionLabel('Cancelar')
            ->modalWidth(Width::FourExtraLarge)
            ->schema([
                Section::make('Canales')
                    ->description('Solo puedes probar los canales configurados en esta notificación.')
                    ->schema([
                        ChannelToggleButtons::make('test_channels', hiddenLabel: true, inline: true)
                            ->options(fn (MassNotification $record): array => collect($record->channelEnums())
                                ->mapWithKeys(fn (BirthdayNotificationChannel $channel): array => [
                                    $channel->value => $channel->getLabel(),
                                ])
                                ->all())
                            ->default(fn (MassNotification $record): array => collect($record->channelEnums())
                                ->map(fn (BirthdayNotificationChannel $channel): string => $channel->value)
                                ->all()),
                    ]),
                Section::make('Destinatario de prueba')
                    ->description('Indica el contacto que recibirá la validación.')
                    ->schema([
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('analista@tudoctorgroup.com')
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
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
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::requiresPhone($get('test_channels') ?? []))
                            ->required(fn (Get $get): bool => self::requiresPhone($get('test_channels') ?? [])),
                    ]),
            ])
            ->authorize(fn (MassNotification $record): bool => Gate::check('sendTest', $record))
            ->action(function (
                Action $action,
                array $data,
                MassNotification $record,
                MassNotificationTestSendService $service,
            ): void {
                /** @var User $sentBy */
                $sentBy = auth()->user();

                $allowedChannels = collect($record->channelEnums())
                    ->map(fn (BirthdayNotificationChannel $channel): string => $channel->value)
                    ->all();

                $channels = array_values(array_intersect(
                    self::normalizeChannels($data['test_channels'] ?? []),
                    $allowedChannels,
                ));

                $result = $service->send(
                    notification: $record,
                    channels: $channels,
                    email: $data['email'] ?? null,
                    phone: $data['phone'] ?? null,
                    sentBy: $sentBy,
                );

                $livewire = $action->getLivewire();

                if ($livewire !== null && method_exists($livewire, 'refreshDeliverySummary')) {
                    $livewire->refreshDeliverySummary();
                }

                if ($result->allSuccessful()) {
                    $includesEmail = in_array(BirthdayNotificationChannel::Email->value, $channels, true);

                    $notification = Notification::make()
                        ->title($includesEmail ? 'Prueba aceptada por el servidor' : 'Envío de prueba completado')
                        ->body($includesEmail
                            ? $result->summary().' Revisa la bandeja del remitente si llega un rebote 550 (buzón inexistente).'
                            : $result->summary());

                    if ($includesEmail) {
                        $notification->warning()->send();
                    } else {
                        $notification->success()->send();
                    }

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
