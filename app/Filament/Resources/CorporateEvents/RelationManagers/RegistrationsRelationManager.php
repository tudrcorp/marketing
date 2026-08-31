<?php

namespace App\Filament\Resources\CorporateEvents\RelationManagers;

use App\Marketing\CorporateEventRegistrationStatus;
use App\Models\CorporateEventRegistration;
use App\Services\Marketing\CorporateEventRegistrationService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Inscripciones';

    protected static ?string $modelLabel = 'inscripción';

    protected static ?string $pluralModelLabel = 'inscripciones';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->label('Nombre y apellido o razón social')
                    ->required()
                    ->maxLength(180),
                TextInput::make('document_id')
                    ->label('Cédula o RIF')
                    ->maxLength(20),
                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->maxLength(180),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(30),
                TextInput::make('company')
                    ->label('Empresa')
                    ->maxLength(180),
                Select::make('status')
                    ->label('Estado')
                    ->options(CorporateEventRegistrationStatus::class)
                    ->default(CorporateEventRegistrationStatus::Registered->value)
                    ->required()
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Inscripciones y asistencia')
            ->description('Gestiona cupos, confirmaciones y métricas de asistencia.')
            ->defaultSort('registered_at', 'desc')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Participante')
                    ->searchable()
                    ->sortable()
                    ->icon(Heroicon::OutlinedUser),
                TextColumn::make('document_id')
                    ->label('Cédula / RIF')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->placeholder('—'),
                TextColumn::make('company')
                    ->label('Empresa')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => CorporateEventRegistrationStatus::tryFrom((string) $state)?->getLabel() ?? (string) $state)
                    ->color(fn (?string $state): string => CorporateEventRegistrationStatus::tryFrom((string) $state)?->getColor() ?? 'gray'),
                TextColumn::make('registered_at')
                    ->label('Inscrito')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(CorporateEventRegistrationStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva inscripción')
                    ->authorize('manageRegistrations')
                    ->using(function (array $data, CorporateEventRegistrationService $service): CorporateEventRegistration {
                        return $service->register(
                            event: $this->getOwnerRecord(),
                            data: $data,
                            registeredBy: auth()->user(),
                        );
                    }),
            ])
            ->actions([
                Action::make('markAttended')
                    ->label('Asistió')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (CorporateEventRegistration $record): bool => $record->statusEnum() === CorporateEventRegistrationStatus::Registered)
                    ->authorize('manageRegistrations')
                    ->action(fn (CorporateEventRegistration $record, CorporateEventRegistrationService $service) => $service->markAttended($record)),
                Action::make('cancelRegistration')
                    ->label('Cancelar')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (CorporateEventRegistration $record): bool => $record->statusEnum() === CorporateEventRegistrationStatus::Registered)
                    ->authorize('manageRegistrations')
                    ->action(fn (CorporateEventRegistration $record, CorporateEventRegistrationService $service) => $service->cancel($record)),
                EditAction::make()->authorize('manageRegistrations'),
                DeleteAction::make()->authorize('manageRegistrations'),
            ]);
    }
}
