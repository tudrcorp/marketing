<?php

namespace App\Filament\Resources\ExternalCompanies\Actions;

use App\Marketing\ExternalCompanyType;
use App\Models\ExternalCompany;
use App\Services\Marketing\ExternalCompanyImporter;
use App\Services\Marketing\ExternalCompanyImportResult;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ImportExternalCompaniesAction
{
    public static function make(): Action
    {
        return Action::make('importExternalCompanies')
            ->label('Importar externos')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('Importar externos')
            ->modalDescription('Carga un archivo CSV o XLSX con los externos. Las filas cuyo RIF / CI ya exista actualizarán el externo registrado.')
            ->modalSubmitActionLabel('Importar')
            ->modalWidth('2xl')
            ->authorize(fn (): bool => Gate::check('create', ExternalCompany::class))
            ->schema([
                SchemaActions::make([
                    self::templateAction(),
                ]),
                Text::make('Columnas obligatorias: '.self::columnList(ExternalCompanyImporter::REQUIRED_FIELDS).'.')
                    ->size(TextSize::Small)
                    ->color('gray'),
                Text::make('La columna Tipo acepta «'.ExternalCompanyType::Company->getLabel().'» o «'.ExternalCompanyType::NaturalPerson->getLabel().'»; si no viene, la fila se registra como '.mb_strtolower(ExternalCompanyType::Company->getLabel()).'. La Razón social solo es obligatoria para empresas.')
                    ->size(TextSize::Small)
                    ->color('gray'),
                Text::make('Columnas opcionales: '.self::columnList(ExternalCompanyImporter::OPTIONAL_FIELDS).'. Si no vienen en el archivo, se conservan los datos ya registrados.')
                    ->size(TextSize::Small)
                    ->color('gray'),
                Text::make('Las filas sin responsable reciben uno generado: '.ExternalCompanyImporter::RESPONSIBLE_PREFIX.'DD-MM-YYYY + correlativo de 3 dígitos, igual para toda la importación. Ese mismo correlativo queda como RIF / CI del responsable.')
                    ->size(TextSize::Small)
                    ->color('gray'),
                FileUpload::make('archivo')
                    ->label('Archivo')
                    ->required()
                    ->storeFiles(false)
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10240)
                    ->helperText('Formatos admitidos: .csv y .xlsx. Máximo '.ExternalCompanyImporter::MAX_ROWS.' filas por archivo.'),
            ])
            ->action(function (array $data, ExternalCompanyImporter $importer): void {
                $file = $data['archivo'] ?? null;

                if (is_array($file)) {
                    $file = Arr::first($file);
                }

                if (! $file instanceof UploadedFile) {
                    self::failure('No se recibió ningún archivo. Vuelve a intentarlo.');

                    return;
                }

                try {
                    $result = $importer->import(
                        $file->getRealPath(),
                        $file->getClientOriginalExtension(),
                        auth()->id(),
                    );
                } catch (Throwable $exception) {
                    self::failure($exception->getMessage());

                    return;
                }

                self::notifyResult($result);
            });
    }

    /**
     * @param  list<string>  $fields
     */
    private static function columnList(array $fields): string
    {
        $labels = ExternalCompanyImporter::labels($fields);
        $last = array_pop($labels);

        return $labels === [] ? $last : implode(', ', $labels).' y '.$last;
    }

    private static function templateAction(): Action
    {
        return Action::make('descargarPlantillaExternos')
            ->label('Descargar plantilla (CSV)')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->link()
            ->action(fn (): StreamedResponse => response()->streamDownload(
                fn () => print ExternalCompanyImporter::template(),
                'plantilla-externos.csv',
                ['Content-Type' => 'text/csv; charset=UTF-8'],
            ));
    }

    private static function notifyResult(ExternalCompanyImportResult $result): void
    {
        if ($result->isEmpty()) {
            self::failure('El archivo no contenía filas para importar.');

            return;
        }

        $lines = array_filter([
            $result->summary(),
            $result->responsibleLine(),
            ...$result->errorLines(),
        ]);

        $notification = Notification::make()
            ->title($result->imported() > 0 ? 'Importación completada' : 'No se importó ninguna fila')
            ->body(implode("\n", $lines));

        if ($result->failed() > 0) {
            $notification
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        $notification->success()->send();
    }

    private static function failure(string $message): void
    {
        Notification::make()
            ->title('No se pudo importar el archivo')
            ->body($message)
            ->danger()
            ->persistent()
            ->send();
    }
}
