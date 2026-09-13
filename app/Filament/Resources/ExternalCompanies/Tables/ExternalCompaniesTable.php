<?php

namespace App\Filament\Resources\ExternalCompanies\Tables;

use App\Filament\Actions\SendExistingMassNotificationsBulkAction;
use App\Filament\Actions\SendMassNotificationBulkAction;
use App\Filament\Resources\ExternalCompanies\Support\ExternalCompanyResponsiblePresentation;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\ExternalCompanyType;
use App\Models\ExternalCompany;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ExternalCompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Externos')
            ->description('Empresas y personas naturales que Marketing administra localmente para campañas. No forman parte del directorio de Integracorp.')
            ->defaultSort('company_name')
            ->groups([
                Group::make('responsible_name')
                    ->label('Responsable')
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn (ExternalCompany $record): string => ExternalCompanyResponsiblePresentation::label($record->responsible_name))
                    ->getDescriptionFromRecordUsing(fn (ExternalCompany $record): Htmlable => ExternalCompanyResponsiblePresentation::groupDescription($record->responsible_name)),
                Group::make('type')
                    ->label('Tipo')
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn (ExternalCompany $record): string => $record->type->getLabel()),
            ])
            ->defaultGroup('responsible_name')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Compañía / Persona')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->icon(fn (ExternalCompany $record): Heroicon => $record->type->getIcon())
                    ->description(fn (ExternalCompany $record): ?string => $record->legal_name),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('document_id')
                    ->label('RIF / CI')
                    ->searchable()
                    ->fontFamily(FontFamily::Mono)
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('responsible_name')
                    ->label('Responsable')
                    ->searchable()
                    ->badge()
                    ->color(fn (ExternalCompany $record): array => ExternalCompanyResponsiblePresentation::color($record->responsible_name))
                    ->formatStateUsing(fn (?string $state): string => ExternalCompanyResponsiblePresentation::label($state))
                    ->description(fn (ExternalCompany $record): ?string => $record->responsible_email),
                TextColumn::make('responsible_phone')
                    ->label('Tel. responsable')
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(ExternalCompanyType::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    SendExistingMassNotificationsBulkAction::make(BirthdayNotificationAudience::Externals),
                    // SendMassNotificationBulkAction::make(BirthdayNotificationAudience::Externals),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin externos registrados')
            ->emptyStateDescription('Registra la primera empresa o persona natural para usarla en campañas de marketing.')
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice2)
            ->searchPlaceholder('Buscar por compañía, persona, RIF/CI, correo o responsable…');
    }
}
