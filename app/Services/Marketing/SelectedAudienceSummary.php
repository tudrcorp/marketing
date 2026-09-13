<?php

namespace App\Services\Marketing;

/**
 * Redacta el resumen de la selección que encabeza los modales de envío: cuántos
 * destinatarios se eligieron y, si la tabla está agrupada, cuánto aporta cada grupo.
 */
class SelectedAudienceSummary
{
    /**
     * Grupos que se detallan antes de resumir el resto en «y N más».
     */
    public const GROUPS_DETAILED = 6;

    /**
     * @param  array<string, int>  $groups  Título del grupo => destinatarios seleccionados.
     */
    public static function text(int $total, array $groups = []): string
    {
        $summary = $total.' destinatario'.($total === 1 ? '' : 's')
            .' seleccionado'.($total === 1 ? '' : 's')
            .'. Cada notificación que marques se envía a todos ellos.';

        if (count($groups) < 2) {
            return $summary;
        }

        $detail = [];

        foreach (array_slice($groups, 0, self::GROUPS_DETAILED, preserve_keys: true) as $title => $count) {
            $detail[] = $title.': '.$count;
        }

        $rest = count($groups) - count($detail);

        if ($rest > 0) {
            $detail[] = 'y '.$rest.' grupo'.($rest === 1 ? '' : 's').' más';
        }

        return $summary.' '.implode(' · ', $detail).'.';
    }
}
