<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationChannel;
use App\Models\MassNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Arma la lista de campañas masivas ya creadas que se ofrece en el modal de envío.
 *
 * El histórico nunca se carga completo: se traen como mucho `LIMIT` campañas —las más
 * recientes, o las que coinciden con la búsqueda— más las que el analista ya marcó, de
 * modo que la consulta, el DOM y el payload de Livewire no crezcan con los años de uso.
 *
 * La instancia memoriza sus resultados, así que el listado, las descripciones y el texto
 * de ayuda de un mismo render comparten una sola consulta.
 */
class ExistingMassNotificationOptions
{
    /**
     * Campañas que se muestran a la vez (más las ya seleccionadas).
     */
    public const LIMIT = 5;

    /**
     * @var array<string, Collection<int, MassNotification>>
     */
    private array $cache = [];

    private ?int $total = null;

    /**
     * @param  list<int>  $selected
     * @return Collection<int, MassNotification>
     */
    public function visible(string $search, array $selected): Collection
    {
        $search = trim($search);
        sort($selected);

        return $this->cache[$search.'|'.implode(',', $selected)] ??= $this->query($search, $selected);
    }

    /**
     * @param  list<int>  $selected
     * @return array<int, string>
     */
    public function labels(string $search, array $selected): array
    {
        return $this->visible($search, $selected)
            ->mapWithKeys(fn (MassNotification $notification): array => [
                $notification->getKey() => $notification->title,
            ])
            ->all();
    }

    /**
     * @param  list<int>  $selected
     * @return array<int, string>
     */
    public function descriptions(string $search, array $selected): array
    {
        return $this->visible($search, $selected)
            ->mapWithKeys(function (MassNotification $notification) use ($selected): array {
                $channels = collect($notification->channelEnums())
                    ->map(fn (BirthdayNotificationChannel $channel): string => $channel->getLabel())
                    ->join(', ');

                $description = $channels.' · creada el '.$notification->created_at?->format('d/m/Y');

                if (in_array((int) $notification->getKey(), $selected, true)) {
                    $description .= ' · ya marcada';
                }

                return [$notification->getKey() => $description];
            })
            ->all();
    }

    /**
     * Explica que la lista está acotada y que el buscador alcanza el resto del histórico.
     *
     * @param  list<int>  $selected
     */
    public function helperText(string $search, array $selected): string
    {
        $search = trim($search);
        $shown = $this->visible($search, $selected)->count();

        if ($search !== '') {
            return $shown === 0
                ? 'Ninguna campaña coincide con «'.$search.'». Prueba con otra parte del título.'
                : 'Coincidencias con «'.$search.'», junto a las que ya marcaste. Puedes elegir una o varias.';
        }

        $hidden = max($this->total() - $shown, 0);

        if ($hidden === 0) {
            return 'Puedes marcar una o varias. Solo se listan las campañas con mensaje y canales configurados.';
        }

        return 'Se muestran las '.$shown.' campañas más recientes de '.$this->total()
            .'. Usa el buscador para llegar a las otras '.$hidden.'.';
    }

    /**
     * Campañas con mensaje redactado que existen en total (para situar al analista).
     */
    public function total(): int
    {
        return $this->total ??= $this->base()->count();
    }

    /**
     * @param  list<int>  $selected
     * @return Collection<int, MassNotification>
     */
    private function query(string $search, array $selected): Collection
    {
        $listed = $this->base()
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', '%'.self::sanitizeSearch($search).'%'))
            ->when($selected !== [], fn (Builder $query) => $query->whereNotIn('id', $selected))
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();

        $marked = $selected === []
            ? new Collection
            : $this->base()->whereIn('id', $selected)->orderByDesc('id')->get();

        return $marked
            ->concat($listed)
            ->filter(fn (MassNotification $notification): bool => $notification->channelEnums() !== []
                && Gate::check('send', $notification))
            ->values();
    }

    /**
     * Consulta base: sin la columna `copy` (el HTML del correo, que pesa) y descartando
     * en SQL las campañas que no tienen mensaje redactado.
     *
     * @return Builder<MassNotification>
     */
    private function base(): Builder
    {
        return MassNotification::query()
            ->select(['id', 'title', 'channels', 'created_at'])
            ->whereNotNull('copy')
            ->where('copy', '!=', '');
    }

    /**
     * Quita los comodines de LIKE en vez de escaparlos: así el término es seguro sin
     * depender de la cláusula ESCAPE, que difiere entre SQLite y MySQL.
     */
    private static function sanitizeSearch(string $search): string
    {
        return str_replace(['%', '_'], '', $search);
    }
}
