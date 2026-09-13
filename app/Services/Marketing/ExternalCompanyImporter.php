<?php

namespace App\Services\Marketing;

use App\Marketing\ExternalCompanyType;
use App\Models\ExternalCompany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

/**
 * Importa externos (empresas o personas naturales) desde un archivo CSV o XLSX.
 *
 * El proceso es síncrono (no usa colas) porque el volumen es bajo y el analista
 * necesita ver el resultado en el acto. Las filas se emparejan por RIF/CI: si el
 * externo ya existe se actualiza, si no se crea.
 */
class ExternalCompanyImporter
{
    /**
     * Tope de filas por archivo para que la importación siga siendo inmediata.
     */
    public const MAX_ROWS = 2000;

    /**
     * Prefijo del responsable que se genera cuando el archivo no trae uno.
     * El identificador completo es PREFIJO + DD-MM-YYYY + correlativo de 3 dígitos,
     * y es el mismo para todas las filas sin responsable de una misma importación.
     */
    public const RESPONSIBLE_PREFIX = 'TDG-MAR-R';

    /**
     * Columnas que el archivo debe traer sí o sí, sea cual sea el tipo de externo.
     *
     * @var list<string>
     */
    public const REQUIRED_FIELDS = [
        'company_name',
        'document_id',
        'phone',
        'email',
    ];

    /**
     * Columnas obligatorias solo cuando la fila es una empresa.
     *
     * @var list<string>
     */
    public const COMPANY_REQUIRED_FIELDS = [
        'legal_name',
    ];

    /**
     * Columnas opcionales: si no vienen, no se pisan las ya guardadas.
     *
     * @var list<string>
     */
    public const OPTIONAL_FIELDS = [
        'type',
        'responsible_name',
        'responsible_document_id',
        'responsible_phone',
        'responsible_email',
    ];

    /**
     * Encabezados aceptados (normalizados) por cada campo.
     *
     * @var array<string, list<string>>
     */
    private const HEADER_ALIASES = [
        'type' => ['tipo', 'tipo de externo', 'tipo externo', 'type'],
        'company_name' => ['compania', 'compania / persona', 'nombre de la compania', 'nombre compania', 'empresa', 'nombre de la empresa', 'nombre y apellido', 'nombre', 'persona', 'company_name'],
        'legal_name' => ['razon social', 'razon_social', 'legal_name'],
        'document_id' => ['rif / ci', 'rif/ci', 'rif ci', 'rif', 'ci', 'cedula', 'documento', 'document_id'],
        'phone' => ['telefono', 'numero de telefono', 'telefono empresa', 'phone'],
        'email' => ['correo', 'correo electronico', 'email', 'correo empresa'],
        'responsible_name' => ['responsable', 'nombre del responsable', 'responsible_name'],
        'responsible_document_id' => ['rif / ci del responsable', 'rif/ci responsable', 'rif responsable', 'ci responsable', 'documento responsable', 'responsible_document_id'],
        'responsible_phone' => ['telefono del responsable', 'telefono responsable', 'tel. responsable', 'tel responsable', 'responsible_phone'],
        'responsible_email' => ['correo del responsable', 'correo responsable', 'email responsable', 'responsible_email'],
    ];

    /**
     * Etiqueta en español de cada columna, usada en la plantilla y en los mensajes.
     *
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'type' => 'Tipo',
        'company_name' => 'Compañía / Nombre y apellido',
        'legal_name' => 'Razón social',
        'document_id' => 'RIF / CI',
        'phone' => 'Teléfono',
        'email' => 'Correo',
        'responsible_name' => 'Responsable',
        'responsible_document_id' => 'RIF / CI del responsable',
        'responsible_phone' => 'Teléfono del responsable',
        'responsible_email' => 'Correo del responsable',
    ];

    /**
     * Etiquetas en español de un grupo de campos, para mensajes al analista.
     *
     * @param  list<string>  $fields
     * @return list<string>
     */
    public static function labels(array $fields): array
    {
        return array_values(array_map(fn (string $field): string => self::FIELD_LABELS[$field], $fields));
    }

    /**
     * Contenido CSV de la plantilla de ejemplo (con BOM para que Excel respete los acentos).
     */
    public static function template(): string
    {
        $rows = [
            array_values(self::FIELD_LABELS),
            [
                ExternalCompanyType::Company->getLabel(),
                'Seguros Ejemplo',
                'Seguros Ejemplo, C.A.',
                'J-123456789',
                '+58 412 1234567',
                'contacto@ejemplo.com',
                'María Pérez',
                'V-12345678',
                '+58 414 7654321',
                'maria.perez@ejemplo.com',
            ],
            [
                ExternalCompanyType::NaturalPerson->getLabel(),
                'Luis Díaz',
                '',
                'V-87654321',
                '+58 424 9876543',
                'luis.diaz@ejemplo.com',
                '',
                '',
                '',
                '',
            ],
        ];

        $csv = "\u{FEFF}";

        foreach ($rows as $row) {
            $csv .= implode(';', array_map(
                fn (string $value): string => '"'.str_replace('"', '""', $value).'"',
                $row,
            ))."\n";
        }

        return $csv;
    }

    /**
     * @param  string  $path  Ruta absoluta del archivo subido.
     * @param  string  $extension  Extensión original (csv, txt, xlsx).
     * @param  int|null  $authorId  Usuario que ejecuta la importación.
     */
    public function import(string $path, string $extension, ?int $authorId = null): ExternalCompanyImportResult
    {
        $rows = $this->readRows($path, $extension);

        if ($rows === []) {
            throw new RuntimeException('El archivo está vacío.');
        }

        $map = $this->resolveHeaderMap(array_shift($rows));
        $this->assertRequiredHeaders($map);

        if (count($rows) > self::MAX_ROWS) {
            throw new RuntimeException(
                'El archivo tiene '.count($rows).' filas y el máximo por importación es '.self::MAX_ROWS.'. Divídelo en varios archivos.'
            );
        }

        return $this->processRows($rows, $map, $authorId);
    }

    /**
     * Sin columna «Tipo» todas las filas se toman como empresa, así que en ese caso
     * la razón social también tiene que venir en el archivo.
     *
     * @param  array<int, string>  $map
     */
    private function assertRequiredHeaders(array $map): void
    {
        $present = array_values($map);
        $required = self::REQUIRED_FIELDS;

        if (! in_array('type', $present, true)) {
            $required = [...$required, ...self::COMPANY_REQUIRED_FIELDS];
        }

        $missing = array_values(array_diff($required, $present));

        if ($missing === []) {
            return;
        }

        throw new RuntimeException(
            'Faltan columnas obligatorias en el archivo: '
            .implode(', ', self::labels($missing))
            .'. Descarga la plantilla para ver el formato esperado.'
        );
    }

    /**
     * @param  list<list<string>>  $rows
     * @param  array<int, string>  $map
     */
    private function processRows(array $rows, array $map, ?int $authorId): ExternalCompanyImportResult
    {
        $created = 0;
        $updated = 0;
        $errors = [];
        $existing = $this->existingByDocument();

        /** @var array{name: string, document: string}|null $reference */
        $reference = null;
        $resolveReference = function () use (&$reference): array {
            return $reference ??= $this->generateResponsibleReference();
        };

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $attributes = $this->extractAttributes($row, $map);
            $rawType = $this->extractValue($row, $map, 'type');

            if ($this->isBlankRow($attributes) && blank($rawType)) {
                continue;
            }

            $type = ExternalCompanyType::fromImportValue($rawType);

            if ($type === null && filled($rawType)) {
                $errors[] = [
                    'line' => $line,
                    'company' => (string) $attributes['company_name'],
                    'messages' => ['El Tipo debe ser «'.ExternalCompanyType::Company->getLabel().'» o «'.ExternalCompanyType::NaturalPerson->getLabel().'».'],
                ];

                continue;
            }

            $type ??= ExternalCompanyType::Company;

            $validator = Validator::make($attributes, $this->rules($type), [], self::FIELD_LABELS);

            if ($validator->fails()) {
                $errors[] = [
                    'line' => $line,
                    'company' => (string) $attributes['company_name'],
                    'messages' => array_values($validator->errors()->all()),
                ];

                continue;
            }

            $key = $this->documentKey((string) $attributes['document_id']);
            $company = $existing[$key] ?? null;

            if ($company instanceof ExternalCompany) {
                // Solo se pisan las columnas que el archivo trae con valor, para no
                // borrar los datos del responsable ya registrados en el panel.
                $company
                    ->fill(array_filter($attributes, fn (?string $value): bool => filled($value)))
                    ->fill(['type' => $type]);

                $this->assignDefaultResponsible($company, $resolveReference);

                $company->save();
                $updated++;

                continue;
            }

            $company = new ExternalCompany;
            $company->fill([
                ...$attributes,
                'type' => $type,
                'created_by_id' => $authorId,
            ]);

            $this->assignDefaultResponsible($company, $resolveReference);

            $company->save();

            $existing[$key] = $company;
            $created++;
        }

        return new ExternalCompanyImportResult($created, $updated, $errors, $reference['name'] ?? null);
    }

    /**
     * Todo externo registrado debe tener responsable: si el archivo no lo trae y
     * tampoco lo tiene el registro, se completa con el correlativo de la importación.
     *
     * @param  callable(): array{name: string, document: string}  $resolveReference
     */
    private function assignDefaultResponsible(ExternalCompany $company, callable $resolveReference): void
    {
        if (filled($company->responsible_name) && filled($company->responsible_document_id)) {
            return;
        }

        $reference = $resolveReference();

        if (blank($company->responsible_name)) {
            $company->responsible_name = $reference['name'];
        }

        if (blank($company->responsible_document_id)) {
            $company->responsible_document_id = $reference['document'];
        }
    }

    /**
     * Reserva el siguiente correlativo del día, mirando los responsables ya generados.
     *
     * @return array{name: string, document: string}
     */
    private function generateResponsibleReference(): array
    {
        $prefix = self::RESPONSIBLE_PREFIX.now()->format('d-m-Y');

        $lastSequence = ExternalCompany::withTrashed()
            ->where('responsible_name', 'like', $prefix.'%')
            ->pluck('responsible_name')
            ->map(fn (string $name): int => (int) mb_substr($name, mb_strlen($prefix)))
            ->max() ?? 0;

        $sequence = str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);

        return [
            'name' => $prefix.$sequence,
            'document' => $sequence,
        ];
    }

    /**
     * @return array<string, ExternalCompany>
     */
    private function existingByDocument(): array
    {
        $companies = [];

        foreach (ExternalCompany::query()->get() as $company) {
            $companies[$this->documentKey((string) $company->document_id)] = $company;
        }

        return $companies;
    }

    private function documentKey(string $document): string
    {
        return mb_strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $document));
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(ExternalCompanyType $type): array
    {
        return [
            'company_name' => ['required', 'string', 'max:180'],
            'legal_name' => [$type->isCompany() ? 'required' : 'nullable', 'string', 'max:180'],
            'document_id' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:180'],
            'responsible_name' => ['nullable', 'string', 'max:180'],
            'responsible_document_id' => ['nullable', 'string', 'max:20'],
            'responsible_phone' => ['nullable', 'string', 'max:30'],
            'responsible_email' => ['nullable', 'email', 'max:180'],
        ];
    }

    /**
     * Las columnas opcionales ausentes quedan en `null` para que la validación
     * `nullable` las ignore en lugar de exigirlas.
     *
     * @param  list<string>  $row
     * @param  array<int, string>  $map
     * @return array<string, string|null>
     */
    private function extractAttributes(array $row, array $map): array
    {
        $attributes = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            $attributes[$field] = (string) $this->extractValue($row, $map, $field);
        }

        foreach ([...self::COMPANY_REQUIRED_FIELDS, ...self::OPTIONAL_FIELDS] as $field) {
            if ($field === 'type') {
                continue;
            }

            $value = $this->extractValue($row, $map, $field);
            $attributes[$field] = blank($value) ? null : $value;
        }

        $attributes['email'] = mb_strtolower($attributes['email']);
        $attributes['responsible_email'] = filled($attributes['responsible_email'])
            ? mb_strtolower((string) $attributes['responsible_email'])
            : null;

        return $attributes;
    }

    /**
     * @param  list<string>  $row
     * @param  array<int, string>  $map
     */
    private function extractValue(array $row, array $map, string $field): ?string
    {
        $position = array_search($field, $map, true);

        if ($position === false) {
            return null;
        }

        return trim($row[$position] ?? '');
    }

    /**
     * @param  array<string, string|null>  $attributes
     */
    private function isBlankRow(array $attributes): bool
    {
        return collect($attributes)->every(fn (?string $value): bool => blank($value));
    }

    /**
     * Relaciona la posición de cada columna del archivo con un campo del modelo.
     *
     * @param  list<string>  $headers
     * @return array<int, string>
     */
    private function resolveHeaderMap(array $headers): array
    {
        $aliasesByField = $this->headerAliases();
        $map = [];

        foreach ($headers as $position => $header) {
            $normalized = $this->normalizeHeader($header);

            foreach ($aliasesByField as $field => $aliases) {
                if (in_array($field, $map, true)) {
                    continue;
                }

                if (in_array($normalized, $aliases, true)) {
                    $map[$position] = $field;

                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Alias aceptados por campo. La etiqueta con la que se escribe la plantilla siempre
     * cuenta como alias, para que un archivo descargado del panel se pueda reimportar
     * aunque las etiquetas cambien.
     *
     * @return array<string, list<string>>
     */
    private function headerAliases(): array
    {
        $aliases = [];

        foreach (self::HEADER_ALIASES as $field => $fieldAliases) {
            $aliases[$field] = array_values(array_unique([
                $this->normalizeHeader(self::FIELD_LABELS[$field]),
                ...$fieldAliases,
            ]));
        }

        return $aliases;
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = Str::ascii(trim($header));
        $normalized = mb_strtolower(str_replace("\u{FEFF}", '', $normalized));

        return (string) preg_replace('/\s+/', ' ', $normalized);
    }

    /**
     * @return list<list<string>>
     */
    private function readRows(string $path, string $extension): array
    {
        return match (mb_strtolower($extension)) {
            'xlsx' => $this->readXlsxRows($path),
            'csv', 'txt' => $this->readCsvRows($path),
            default => throw new RuntimeException('Formato no soportado. Usa un archivo CSV o XLSX.'),
        };
    }

    /**
     * @return list<list<string>>
     */
    private function readCsvRows(string $path): array
    {
        $reader = CsvReader::createFromPath($path, 'r');
        $reader->setDelimiter($this->detectDelimiter($path));
        $reader->skipInputBOM();

        $rows = [];

        foreach ($reader->getRecords() as $record) {
            $rows[] = array_map(fn ($value): string => (string) $value, array_values($record));
        }

        return $rows;
    }

    private function detectDelimiter(string $path): string
    {
        $firstLine = (string) fgets(fopen($path, 'r') ?: throw new RuntimeException('No se pudo leer el archivo.'));

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    /**
     * @return list<list<string>>
     */
    private function readXlsxRows(string $path): array
    {
        $reader = new XlsxReader;
        $reader->open($path);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(
                    fn ($value): string => $value instanceof \DateTimeInterface
                        ? $value->format('d/m/Y')
                        : trim((string) $value),
                    $row->toArray(),
                );
            }

            break;
        }

        $reader->close();

        return $rows;
    }
}
