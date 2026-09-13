<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Distingue si un externo es una empresa (persona jurídica) o una persona natural.
 */
enum ExternalCompanyType: string implements HasColor, HasIcon, HasLabel
{
    case Company = 'company';
    case NaturalPerson = 'natural_person';

    public function getLabel(): string
    {
        return match ($this) {
            self::Company => 'Empresa',
            self::NaturalPerson => 'Persona natural',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Company => 'info',
            self::NaturalPerson => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Company => Heroicon::OutlinedBuildingOffice2,
            self::NaturalPerson => Heroicon::OutlinedUser,
        };
    }

    public function isCompany(): bool
    {
        return $this === self::Company;
    }

    /**
     * Etiqueta del campo que guarda el nombre principal del externo.
     */
    public function nameLabel(): string
    {
        return $this->isCompany() ? 'Nombre de la compañía' : 'Nombre y apellido';
    }

    /**
     * Etiqueta del documento de identidad del externo.
     */
    public function documentLabel(): string
    {
        return $this->isCompany() ? 'RIF' : 'CI';
    }

    /**
     * Resuelve el tipo a partir del texto de una importación («Empresa», «Natural», …).
     */
    public static function fromImportValue(?string $value): ?self
    {
        $normalized = mb_strtolower(trim((string) $value));

        if ($normalized === '') {
            return null;
        }

        return match (true) {
            in_array($normalized, ['empresa', 'empresas', 'juridica', 'jurídica', 'persona juridica', 'persona jurídica', 'company', 'j'], true) => self::Company,
            in_array($normalized, ['persona natural', 'natural', 'personas naturales', 'persona', 'natural person', 'natural_person', 'n', 'v'], true) => self::NaturalPerson,
            default => null,
        };
    }
}
