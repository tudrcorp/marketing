<?php

namespace App\Filament\Resources\ExternalCompanies\Support;

use App\Services\Marketing\ExternalCompanyImporter;
use Filament\Support\Colors\Color;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * Presentación del responsable de un externo: color estable por responsable para
 * que el analista distinga de un vistazo cada grupo de la tabla.
 */
class ExternalCompanyResponsiblePresentation
{
    public const UNASSIGNED_LABEL = 'Sin responsable';

    /**
     * Paleta alineada con la marca TDG; el color se elige por hash del responsable,
     * así el mismo responsable conserva siempre el mismo color.
     *
     * @var list<string>
     */
    private const PALETTE = [
        '#e87d2f',
        '#424769',
        '#2f8f6d',
        '#b4432f',
        '#7b5ea7',
        '#2f6f9f',
        '#a8762f',
        '#3f7d7d',
    ];

    private const UNASSIGNED_HEX = '#8f98b8';

    public static function label(?string $responsible): string
    {
        return filled($responsible) ? $responsible : self::UNASSIGNED_LABEL;
    }

    public static function hex(?string $responsible): string
    {
        if (blank($responsible)) {
            return self::UNASSIGNED_HEX;
        }

        return self::PALETTE[abs(crc32($responsible)) % count(self::PALETTE)];
    }

    /**
     * Escala de color lista para `->color()` de Filament.
     *
     * @return array<int, string>
     */
    public static function color(?string $responsible): array
    {
        return Color::hex(self::hex($responsible));
    }

    /**
     * Descripción del encabezado del grupo, con el punto de color del responsable.
     */
    public static function groupDescription(?string $responsible): Htmlable
    {
        return new HtmlString(
            '<span style="display:inline-flex;align-items:center;gap:0.375rem;">'
            .'<span style="width:0.5rem;height:0.5rem;border-radius:9999px;flex:none;background-color:'
            .self::hex($responsible).';"></span>'
            .'<span>'.e(self::origin($responsible)).'</span>'
            .'</span>'
        );
    }

    /**
     * Explica de dónde viene el responsable: una importación o un alta manual.
     */
    public static function origin(?string $responsible): string
    {
        if (blank($responsible)) {
            return 'Externos sin responsable asignado.';
        }

        $reference = self::importReference($responsible);

        if ($reference === null) {
            return 'Responsable registrado desde el panel.';
        }

        return 'Importación del '.$reference['date'].' · correlativo '.$reference['sequence'].'.';
    }

    /**
     * Descompone un responsable generado por la importación (TDG-MAR-R + fecha + correlativo).
     *
     * @return array{date: string, sequence: string}|null
     */
    public static function importReference(?string $responsible): ?array
    {
        if (blank($responsible)) {
            return null;
        }

        $pattern = '/^'.preg_quote(ExternalCompanyImporter::RESPONSIBLE_PREFIX, '/').'(\d{2}-\d{2}-\d{4})(\d{3,})$/';

        if (preg_match($pattern, $responsible, $matches) !== 1) {
            return null;
        }

        return [
            'date' => $matches[1],
            'sequence' => $matches[2],
        ];
    }
}
