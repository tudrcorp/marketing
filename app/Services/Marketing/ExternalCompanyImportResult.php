<?php

namespace App\Services\Marketing;

/**
 * Resultado de una importación de empresas externas.
 *
 * @phpstan-type ImportRowError array{line: int, company: string, messages: list<string>}
 */
class ExternalCompanyImportResult
{
    /**
     * @param  list<ImportRowError>  $errors
     * @param  string|null  $responsibleReference  Responsable generado para las filas que no traían uno.
     */
    public function __construct(
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly array $errors = [],
        public readonly ?string $responsibleReference = null,
    ) {}

    public function imported(): int
    {
        return $this->created + $this->updated;
    }

    public function failed(): int
    {
        return count($this->errors);
    }

    public function isEmpty(): bool
    {
        return $this->imported() === 0 && $this->failed() === 0;
    }

    /**
     * Resumen en español para la notificación del panel.
     */
    public function summary(): string
    {
        $parts = [];

        if ($this->created > 0) {
            $parts[] = $this->created.' '.($this->created === 1 ? 'empresa creada' : 'empresas creadas');
        }

        if ($this->updated > 0) {
            $parts[] = $this->updated.' '.($this->updated === 1 ? 'empresa actualizada' : 'empresas actualizadas');
        }

        if ($this->failed() > 0) {
            $parts[] = $this->failed().' '.($this->failed() === 1 ? 'fila con error' : 'filas con error');
        }

        return $parts === [] ? 'No se procesó ninguna fila.' : implode(' · ', $parts).'.';
    }

    /**
     * Aviso del responsable generado para las filas que llegaron sin uno.
     */
    public function responsibleLine(): ?string
    {
        if ($this->responsibleReference === null) {
            return null;
        }

        return 'Responsable asignado a las filas sin responsable: '.$this->responsibleReference.'.';
    }

    /**
     * Detalle de las primeras filas rechazadas, para mostrarlo al analista.
     *
     * @return list<string>
     */
    public function errorLines(int $limit = 10): array
    {
        $lines = [];

        foreach (array_slice($this->errors, 0, $limit) as $error) {
            $label = filled($error['company']) ? ' ('.$error['company'].')' : '';
            $lines[] = 'Fila '.$error['line'].$label.': '.implode(' ', $error['messages']);
        }

        if ($this->failed() > $limit) {
            $lines[] = 'Y '.($this->failed() - $limit).' fila(s) más con errores.';
        }

        return $lines;
    }
}
