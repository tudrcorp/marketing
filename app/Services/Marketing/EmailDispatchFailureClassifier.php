<?php

namespace App\Services\Marketing;

use App\Marketing\EmailDispatchFailureKind;

/**
 * Traduce la respuesta del API de correos a una causa accionable.
 *
 * El API responde `502` con `reason` cuando no salió ningún correo y `207` con
 * `failures[]` cuando el envío fue parcial; en el parcial el código SMTP real solo
 * aparece dentro de cada failure, así que el llamador debe pasar ambos textos.
 */
class EmailDispatchFailureClassifier
{
    public function classify(?int $httpStatus, string $message): EmailDispatchFailureKind
    {
        $haystack = mb_strtolower($message);

        // La cuota va primero: `550-5.4.5` también contiene «550», que más abajo
        // significaría buzón inexistente.
        if ($this->matchesAny($haystack, [
            '5.4.5',
            'daily user sending limit',
            'daily sending quota',
            'sending limit exceeded',
            'quota exceeded',
            'límite diario',
            'limite diario',
            'bandwidth',
            'ancho de banda',
            'unusual usage',
            'uso inusual',
            'web upload',
            'cargas por medio de la web',
        ])) {
            return EmailDispatchFailureKind::QuotaExceeded;
        }

        if ($this->matchesAny($haystack, [
            '454',
            'too many login attempts',
            'invalid login',
            'eauth',
            'authentication failed',
            'username and password not accepted',
        ])) {
            return EmailDispatchFailureKind::AuthThrottled;
        }

        if ($httpStatus === 429 || $this->matchesAny($haystack, [
            '421',
            '450',
            '451',
            '452',
            '4.7.28',
            'econnreset',
            'etimedout',
            'esocket',
            'connection closed',
            'socket closed',
            'unexpected socket close',
            'try again later',
            'no confirmó el resultado a tiempo',
            'no se pudo conectar con el api',
        ])) {
            return EmailDispatchFailureKind::Transient;
        }

        if ($this->matchesAny($haystack, [
            '5.1.1',
            '5.2.1',
            '5.7.1',
            'user unknown',
            'no such user',
            'address not found',
            'mailbox unavailable',
            'does not exist',
        ])) {
            return EmailDispatchFailureKind::Permanent;
        }

        return EmailDispatchFailureKind::Unknown;
    }

    /**
     * Compone el texto a clasificar juntando el motivo general con el de cada
     * destinatario fallido, porque el código SMTP puede venir solo en uno de los dos.
     *
     * @param  array<string, mixed>  $body
     */
    public function messageFromResponseBody(array $body, string $fallback): string
    {
        $parts = [$fallback];

        foreach (['reason', 'message', 'error', 'detail'] as $key) {
            if (filled($body[$key] ?? null) && is_string($body[$key])) {
                $parts[] = $body[$key];
            }
        }

        if (is_array($body['failures'] ?? null)) {
            foreach ($body['failures'] as $failure) {
                if (is_array($failure) && filled($failure['reason'] ?? null) && is_string($failure['reason'])) {
                    $parts[] = $failure['reason'];
                }
            }
        }

        return implode(' | ', array_unique(array_filter($parts)));
    }

    /**
     * Direcciones que el API reportó como fallidas, para reintentar solo esas y no
     * duplicar los correos que sí salieron en el intento anterior.
     *
     * @param  array<string, mixed>  $body
     * @return list<string>
     */
    public function failedAddresses(array $body): array
    {
        if (! is_array($body['failures'] ?? null)) {
            return [];
        }

        return collect($body['failures'])
            ->map(function (mixed $failure): ?string {
                if (! is_array($failure)) {
                    return null;
                }

                $address = $failure['email'] ?? $failure['to'] ?? $failure['address'] ?? null;

                return is_string($address) && filled($address) ? $address : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $needles
     */
    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
