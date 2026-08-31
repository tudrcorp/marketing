<?php

namespace App\Filament\Resources\ClientGroups\RelationManagers;

use App\Filament\Actions\SendMassNotificationBulkAction;
use App\Marketing\BirthdayNotificationAudience;
use App\Models\Client;
use App\Models\ClientGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsRelationManager extends RelationManager
{
    protected static string $relationship = 'clients';

    protected static ?string $title = 'Clientes';

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->label('Nombre y apellido o razón social')
                    ->required()
                    ->maxLength(180)
                    ->columnSpanFull(),
                TextInput::make('document_id')
                    ->label('Cédula o RIF')
                    ->maxLength(20),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->maxLength(180),
                TextInput::make('phone')
                    ->label('Teléfono de contacto')
                    ->tel()
                    ->required()
                    ->maxLength(30)
                    ->helperText('WhatsApp se envía a este número (formato 04XX…). El mensaje incluirá el contacto del responsable del grupo.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Clientes del grupo')
            ->description('Registra los contactos que pertenecen a este grupo.')
            ->emptyStateHeading('Sin clientes registrados')
            ->emptyStateDescription('Agrega el primer cliente de este grupo con el botón de abajo.')
            ->emptyStateIcon(Heroicon::OutlinedUserPlus)
            ->emptyStateActions([
                $this->makeCreateClientAction(),
            ])
            ->defaultSort('full_name')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Cliente')
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
            ])
            ->headerActions([
                $this->makeCreateClientAction(),
            ])
            ->recordActions([
                EditAction::make()->authorize($this->canManageClients(...)),
                DeleteAction::make()->authorize($this->canManageClients(...)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    SendMassNotificationBulkAction::make(BirthdayNotificationAudience::ClientGroups),
                    DeleteBulkAction::make()->authorize($this->canManageClients(...)),
                ]),
            ]);
    }

    protected function makeCreateClientAction(): CreateAction
    {
        return CreateAction::make()
            ->label('Nuevo cliente')
            ->icon(Heroicon::OutlinedPlus)
            ->authorize($this->canManageClients(...));
    }

    protected function canManageClients(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        $ownerRecord = $this->getOwnerRecord();

        if (! $ownerRecord instanceof ClientGroup) {
            return false;
        }

        return $user->can('manageClients', $ownerRecord);
    }
}
