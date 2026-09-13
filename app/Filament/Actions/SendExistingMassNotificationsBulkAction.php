<?php

namespace App\Filament\Actions;

use App\Filament\Widgets\MarketingDispatchProgressFloaterWidget;
use App\Marketing\BirthdayNotificationAudience;
use App\Models\MassNotification;
use App\Models\User;
use App\Services\Marketing\ExistingMassNotificationOptions;
use App\Services\Marketing\MassEmailDispatchPace;
use App\Services\Marketing\MassNotificationDispatchService;
use App\Services\Marketing\MassNotificationRecipientResolver;
use App\Services\Marketing\QueueWorkerHealthInspector;
use App\Services\Marketing\SelectedAudienceSummary;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Envía notificaciones masivas **ya creadas** a los registros seleccionados en la tabla.
 * El modal lista las campañas existentes para que el analista revise y elija una o varias.
 *
 * El listado nunca se carga completo: `ExistingMassNotificationOptions` acota lo que se
 * renderiza, para que ni la consulta ni el payload de Livewire crezcan con el histórico.
 */
class SendExistingMassNotificationsBulkAction
{
    /**
     * Nombre del campo de búsqueda del modal (no se dehidrata: no llega a la acción).
     */
    private const SEARCH_FIELD = 'mass_notifications_search';

    public static function make(BirthdayNotificationAudience $audience): BulkAction
    {
        // Una instancia por acción (es decir, por request): el listado, las descripciones
        // y el texto de ayuda de un mismo render comparten una sola consulta.
        $options = new ExistingMassNotificationOptions;

        return BulkAction::make('sendExistingMassNotifications')
            ->label('Enviar notificación masiva creada')
            ->icon(Heroicon::OutlinedInboxArrowDown)
            ->color('primary')
            ->modalHeading('Enviar una notificación masiva ya creada')
            ->modalDescription(fn (BulkAction $action): string => self::selectionSummary($action))
            ->modalIcon(Heroicon::OutlinedBellAlert)
            ->modalIconColor('gray')
            ->modalSubmitActionLabel('Enviar seleccionadas')
            ->modalCancelActionLabel('Cancelar')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                TextInput::make(self::SEARCH_FIELD)
                    ->label('Buscar notificación')
                    ->placeholder('Buscar por título…')
                    ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)
                    ->autocomplete(false)
                    ->live(debounce: '500ms')
                    ->dehydrated(false),
                CheckboxList::make('mass_notifications')
                    ->label('Notificaciones masivas creadas')
                    ->options(fn (Get $get): array => $options->labels(self::searchTerm($get), self::selectedIds($get)))
                    ->descriptions(fn (Get $get): array => $options->descriptions(self::searchTerm($get), self::selectedIds($get)))
                    ->required()
                    ->bulkToggleable()
                    ->columns(1)
                    ->helperText(fn (Get $get): string => $options->helperText(self::searchTerm($get), self::selectedIds($get))),
            ])
            ->authorize(fn (): bool => Gate::check('sendAny', MassNotification::class))
            ->action(function (
                BulkAction $action,
                array $data,
                Collection $records,
                MassNotificationDispatchService $dispatchService,
                MassNotificationRecipientResolver $recipientResolver,
            ) use ($audience): void {
                /** @var list<int> $selectedIds */
                $selectedIds = array_map('intval', $data['mass_notifications'] ?? []);

                $notifications = MassNotification::query()
                    ->whereIn('id', $selectedIds)
                    ->get()
                    ->filter(fn (MassNotification $notification): bool => Gate::check('send', $notification));

                if ($notifications->isEmpty()) {
                    Notification::make()
                        ->title('No se envió nada')
                        ->body('Ninguna de las notificaciones seleccionadas está disponible para envío.')
                        ->warning()
                        ->send();

                    return;
                }

                $recipients = $recipientResolver->resolveMany($audience, $records->values()->all());

                if ($recipients === []) {
                    Notification::make()
                        ->title('Sin destinatarios válidos')
                        ->body('Los registros seleccionados no tienen correo ni teléfono utilizables.')
                        ->warning()
                        ->send();

                    return;
                }

                $queueWarning = app(QueueWorkerHealthInspector::class)->unavailableMessage();

                if ($queueWarning !== null) {
                    Notification::make()
                        ->title('No se encoló: no hay worker de colas activo')
                        ->body($queueWarning)
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                /** @var User $sentBy */
                $sentBy = auth()->user();

                $queued = 0;
                $sent = 0;
                $failed = [];

                foreach ($notifications as $notification) {
                    $result = $dispatchService->dispatchExistingTo(
                        notification: $notification,
                        recipients: $recipients,
                        sentBy: $sentBy,
                    );

                    if ($result->queued) {
                        $queued++;

                        continue;
                    }

                    if ($result->allSuccessful()) {
                        $sent++;

                        continue;
                    }

                    $failed[] = $notification->title.': '.($result->failureMessage() ?? $result->summary());
                }

                self::notifyProgressStarted($action);
                self::notifyResult($queued, $sent, $failed, count($recipients));
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function searchTerm(Get $get): string
    {
        return trim((string) ($get(self::SEARCH_FIELD) ?? ''));
    }

    /**
     * @return list<int>
     */
    private static function selectedIds(Get $get): array
    {
        $ids = array_values(array_unique(array_map(
            'intval',
            array_filter((array) ($get('mass_notifications') ?? []), 'is_numeric'),
        )));

        sort($ids);

        return $ids;
    }

    /**
     * Resumen de la selección: cuántos destinatarios se eligieron y, si la tabla está
     * agrupada, cuántos aporta cada grupo. Es lo único que necesita ver el analista
     * para saber a cuánta gente le llegará cada campaña que marque.
     */
    private static function selectionSummary(BulkAction $action): string
    {
        $groups = self::selectedCountsByGroup($action);

        return SelectedAudienceSummary::text(
            total: $groups === [] ? self::selectedRecordsCount($action) : array_sum($groups),
            groups: $groups,
        );
    }

    /**
     * Cuenta los seleccionados por grupo con un solo agregado en SQL, sin hidratarlos.
     *
     * @return array<string, int>
     */
    private static function selectedCountsByGroup(BulkAction $action): array
    {
        $livewire = $action->getLivewire();

        if (! $livewire instanceof HasTable) {
            return [];
        }

        $grouping = $livewire->getTable()->getGrouping();

        if ($grouping === null) {
            return [];
        }

        $column = $grouping->getColumn();

        // Agrupar por una relación necesitaría un join propio: mejor solo el total.
        if (str_contains($column, '.')) {
            return [];
        }

        $query = $action->getSelectedRecordsQuery();
        $model = $query->getModel();

        $counts = $query->toBase()
            ->reorder()
            ->select($column)
            ->selectRaw('count(*) as aggregate')
            ->groupBy($column)
            ->orderByDesc('aggregate')
            ->pluck('aggregate', $column)
            ->all();

        $summary = [];

        foreach ($counts as $value => $count) {
            $title = self::groupTitle($grouping, $model, $column, $value);
            $summary[$title] = ($summary[$title] ?? 0) + (int) $count;
        }

        return $summary;
    }

    /**
     * Título del grupo tal como lo muestra la tabla. Se hidrata un modelo suelto con la
     * columna agrupada porque el título lo define un closure del propio `Group`.
     */
    private static function groupTitle(Group $grouping, Model $model, string $column, mixed $value): string
    {
        if (blank($value)) {
            return 'Sin '.mb_strtolower((string) $grouping->getLabel());
        }

        try {
            $title = $grouping->getTitle($model->newInstance()->forceFill([$column => $value]));
        } catch (Throwable) {
            $title = null;
        }

        return (string) ($title ?? $value);
    }

    /**
     * Cuenta en SQL en lugar de hidratar la selección completa: el modal se vuelve a
     * renderizar con cada búsqueda y la descripción se recalcula en cada render.
     */
    private static function selectedRecordsCount(BulkAction $action): int
    {
        return $action->getSelectedRecordsQuery()->count();
    }

    private static function notifyProgressStarted(BulkAction $action): void
    {
        $livewire = $action->getLivewire();

        if ($livewire === null) {
            return;
        }

        $livewire->dispatch('dispatch-progress-started')
            ->to(MarketingDispatchProgressFloaterWidget::class);
    }

    /**
     * @param  list<string>  $failed
     */
    private static function notifyResult(int $queued, int $sent, array $failed, int $recipientCount): void
    {
        $lines = [];

        if ($queued > 0) {
            $lines[] = $queued.' notificación(es) en cola hacia '.$recipientCount.' destinatario(s). El panel de progreso mostrará el avance.';
            // El detalle del ritmo de envío sale aquí y no en el modal, que solo resume la selección.
            $lines[] = MassEmailDispatchPace::analystWarning($recipientCount);
        }

        if ($sent > 0) {
            $lines[] = $sent.' notificación(es) enviada(s) a '.$recipientCount.' destinatario(s).';
        }

        $lines = [...$lines, ...$failed];

        $notification = Notification::make()
            ->title($failed === [] ? 'Envío iniciado' : 'Envío con errores')
            ->body(implode("\n", $lines));

        $failed === []
            ? $notification->success()->send()
            : $notification->warning()->persistent()->send();
    }
}
