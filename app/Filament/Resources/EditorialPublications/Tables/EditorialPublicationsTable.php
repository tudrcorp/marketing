<?php

namespace App\Filament\Resources\EditorialPublications\Tables;

use App\Filament\Resources\EditorialPublications\EditorialPublicationResource;
use App\Marketing\PublicationStatus;
use App\Marketing\SocialPlatform;
use App\Models\EditorialPublication;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EditorialPublicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Publicaciones editoriales')
            ->description('Cronograma operativo de contenidos por red social.')
            ->defaultSort('scheduled_at', 'asc')
            ->columns([
                TextColumn::make('scheduled_at')
                    ->label('Programada')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('socialAccount.name')
                    ->label('Cuenta')
                    ->icon(Heroicon::OutlinedAtSymbol)
                    ->iconColor('primary')
                    ->searchable()
                    ->sortable()
                    ->color('primary'),
                TextColumn::make('socialAccount.platform')
                    ->label('Red')
                    ->formatStateUsing(fn (SocialPlatform $state): string => $state->getSelectOptionHtml())
                    ->html()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->icon(Heroicon::OutlinedNewspaper)
                    ->limit(40)
                    ->tooltip(fn (EditorialPublication $record): string => $record->title),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label('Aprobada')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(PublicationStatus::class)
                    ->multiple(),
                SelectFilter::make('social_account_id')
                    ->label('Cuenta')
                    ->relationship('socialAccount', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('submitForApproval')
                    ->label('Enviar a aprobación')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('warning')
                    ->visible(fn (EditorialPublication $record): bool => $record->status === PublicationStatus::Draft
                        && auth()->user()?->can('update', $record))
                    ->action(function (EditorialPublication $record): void {
                        $record->update(['status' => PublicationStatus::PendingApproval]);
                    }),
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (EditorialPublication $record): bool => $record->status === PublicationStatus::PendingApproval
                        && auth()->user()?->can('approve', $record))
                    ->requiresConfirmation()
                    ->action(function (EditorialPublication $record): void {
                        $record->update([
                            'status' => PublicationStatus::Scheduled,
                            'approved_by_id' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }),
                EditAction::make(),
            ])
            ->striped()
            ->recordClasses('marketing-broker-table__row')
            ->searchable()
            ->searchPlaceholder('Buscar por título o cuenta…')
            ->searchDebounce('400ms')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->recordUrl(fn (EditorialPublication $record): string => EditorialPublicationResource::getUrl('edit', ['record' => $record]))
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin publicaciones editoriales')
            ->emptyStateDescription('Crea la primera publicación para programar contenido en las redes sociales TDG.')
            ->emptyStateIcon(Heroicon::OutlinedNewspaper);
    }
}
