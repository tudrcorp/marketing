<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchStatus;

/**
 * @phpstan-type FailureGuidance array{
 *     failure_code: string,
 *     analyst_message: string,
 *     resolution_steps: string,
 * }
 */
class NotificationDispatchFailureResolver
{
    /**
     * @return FailureGuidance
     */
    public function resolve(
        NotificationDispatchStatus $status,
        ?string $technicalMessage,
        ?BirthdayNotificationChannel $channel = null,
    ): array {
        if ($status === NotificationDispatchStatus::Queued) {
            if (str_contains(mb_strtolower($technicalMessage ?? ''), 'reencolado')) {
                return $this->guidance(
                    'email_batch_rescheduled',
                    'El proveedor de correo pidió una pausa y el lote quedó reprogramado con los destinatarios que faltaban. Se reanuda solo.',
                    "1. No relances la campaña: los destinatarios que ya recibieron el correo no se repiten, pero un envío nuevo sí los duplicaría.\n2. Espera a la hora indicada en el resumen y revisa que el lote termine en Enviado.\n3. Si agota los reintentos, este historial mostrará el motivo final y a cuántos destinatarios faltó escribir.",
                    $channel,
                );
            }

            return $this->guidance(
                'queued_success',
                'El envío fue aceptado y está procesándose en segundo plano.',
                "1. Espera unos minutos mientras se procesan los lotes.\n2. Revisa este historial para confirmar que cada lote termine en Enviado.\n3. Si algún lote queda en Fallido, sigue las indicaciones de ese registro.",
                $channel,
            );
        }

        if ($status === NotificationDispatchStatus::Sent) {
            if ($channel === BirthdayNotificationChannel::Email) {
                return [
                    'failure_code' => 'smtp_accepted',
                    'analyst_message' => 'El servidor de correo aceptó el mensaje. Eso no garantiza que llegó al buzón final.',
                    'resolution_steps' => "1. Si el destinatario no lo recibió, revisa la bandeja de {$this->senderMailbox()} por un rebote (Mail Delivery Subsystem).\n2. Un 550 5.1.1 significa que el correo no existe: corrige el destinatario y vuelve a enviar.\n3. No reintentes el mismo correo inválido; Gmail lo seguirá rechazando.",
                ];
            }

            return [
                'failure_code' => 'none',
                'analyst_message' => 'El envío se completó sin incidencias reportadas.',
                'resolution_steps' => 'No necesitas hacer nada. Puedes revisar el detalle del envío para confirmar destinatarios y canal.',
            ];
        }

        $message = mb_strtolower(trim($technicalMessage ?? ''));

        return match (true) {
            str_contains($message, 'curl error 28'),
            str_contains($message, 'operation timed out'),
            str_contains($message, 'no confirmó el resultado a tiempo') => $this->guidance(
                'api_email_timeout_unconfirmed',
                'El API de correos tardó más de lo esperado en responder, pero es probable que los correos sí se hayan enviado (el proveedor procesa el envío antes de confirmar). No pudimos confirmar el resultado exacto por esta vía.',
                "1. Antes de reintentar, verifica manualmente si los destinatarios recibieron el correo (o consulta los logs de integracorp-api).\n2. Si confirmas que sí se enviaron, no reintentes: duplicarías el envío.\n3. Si confirmas que no se enviaron, o si el volumen de destinatarios es muy grande, considera subir MARKETING_API_EMAIL_TIMEOUT o dividir el envío en audiencias más pequeñas.",
            ),
            str_contains($message, 'no se pudo conectar con el api de correos'),
            str_contains($message, 'could not resolve host'),
            str_contains($message, 'connection refused') => $this->guidance(
                'api_email_unreachable',
                'No pudimos contactar al servicio de correos de TDG.',
                "1. Verifica que el API de marketing esté activo (widget de salud en el dashboard).\n2. Confirma que MARKETING_API_URL en el servidor apunte al entorno correcto.\n3. Si el problema persiste, contacta a soporte técnico con la hora exacta del fallo.",
            ),
            str_contains($message, '5.4.5'),
            str_contains($message, 'daily user sending limit'),
            str_contains($message, 'sending limit exceeded'),
            str_contains($message, 'cuota diaria de correo agotada') => $this->guidance(
                'email_quota_exceeded',
                'Se agotó la cuota diaria de envío del proveedor de correo. No es un problema de los destinatarios ni del contenido: la cuenta remitente llegó a su tope de correos por día.',
                "1. NO relances la campaña: los lotes pendientes se reintentan solos cuando entra cuota nueva, y un envío manual duplicaría los correos ya entregados.\n"
                    ."2. Revisa en este historial cuántos destinatarios quedaron pendientes y a qué hora está previsto el próximo intento.\n"
                    ."3. Gmail y Workspace miden una ventana móvil de 24 h: la cuota se libera de a poco, no a medianoche.\n"
                    ."4. Si esto se repite con cada campaña, la cuenta remitente se quedó corta para el volumen y hay que subir el plan o mover el envío a un proveedor transaccional.",
            ),
            str_contains($message, '454'),
            str_contains($message, 'too many login attempts'),
            str_contains($message, 'invalid login'),
            str_contains($message, 'autenticación de correo bloqueada') => $this->guidance(
                'email_auth_throttled',
                'El proveedor bloqueó temporalmente la autenticación del remitente por demasiados intentos de conexión seguidos. Suele aparecer justo después de agotar la cuota diaria.',
                "1. NO relances la campaña: cada reenvío añade más intentos de login y alarga el bloqueo.\n"
                    ."2. Los lotes pendientes ya quedaron reprogramados y se reanudan solos.\n"
                    ."3. Si al reanudarse vuelve a fallar, confirma que la contraseña de aplicación del remitente siga vigente en el API.\n"
                    ."4. Revisa antes si hubo un error de cuota (550 5.4.5) en los lotes previos: esa suele ser la causa de fondo.",
            ),
            str_contains($message, '550 5.1.1'),
            str_contains($message, '5.1.1'),
            str_contains($message, 'nosuchuser'),
            str_contains($message, 'user unknown'),
            str_contains($message, 'address not found'),
            str_contains($message, 'dirección no se encuentra'),
            str_contains($message, 'the email account that you tried to reach does not exist'),
            str_contains($message, 'recipient address rejected'),
            str_contains($message, 'mailbox unavailable') && str_contains($message, '550') => $this->guidance(
                'email_address_not_found',
                'La dirección de correo no existe o no puede recibir mensajes.',
                "1. Revisa el email del destinatario: busca errores de tipeo o espacios extra.\n2. Confirma en Audiencias TDG que el contacto tenga el correo correcto.\n3. Corrige el dato y reenvía solo a ese destinatario (o usa Reintentar en el historial).",
            ),
            str_contains($message, '550 5.2.1'),
            str_contains($message, '5.2.1'),
            str_contains($message, 'mailbox disabled'),
            str_contains($message, 'account disabled') => $this->guidance(
                'email_mailbox_disabled',
                'El buzón del destinatario está deshabilitado o no acepta correo.',
                "1. Verifica con el destinatario que su cuenta de correo siga activa.\n2. Actualiza el email en Audiencias TDG si tiene otra dirección vigente.\n3. Reenvía solo a los contactos corregidos.",
            ),
            str_contains($message, '550 5.7.'),
            str_contains($message, '5.7.1'),
            str_contains($message, 'relay denied'),
            str_contains($message, 'spam') && (str_contains($message, '550') || str_contains($message, '553')) => $this->guidance(
                'email_rejected_by_provider',
                'Gmail u otro proveedor rechazó el mensaje (política o reputación del remitente).',
                "1. Revisa que el contenido no dispare filtros anti-spam.\n2. Confirma que la cuenta remitente y la contraseña de aplicación sigan válidas.\n3. Espera unos minutos y reintenta; si se repite, escala a soporte técnico.",
            ),
            str_contains($message, '421'),
            str_contains($message, '450'),
            str_contains($message, '451'),
            str_contains($message, '452'),
            (str_contains($message, '500') && (str_contains($message, 'smtp') || str_contains($message, 'gmail') || str_contains($message, 'gsmtp'))) => $this->guidance(
                'smtp_temporary_failure',
                'Gmail/SMTP reportó un error temporal durante la entrega.',
                "1. Reintenta el envío en unos minutos.\n2. Si solo fallaron algunos destinatarios, usa Reintentar en el historial para no duplicar éxitos.\n3. Si el error persiste, comparte el detalle técnico con soporte.",
            ),
            str_contains($message, 'econnreset'),
            str_contains($message, 'etimedout'),
            str_contains($message, 'connection closed'),
            str_contains($message, 'no se pudo enviar ningún correo') => $this->guidance(
                'smtp_connection_reset',
                'Gmail/SMTP cortó la conexión durante el envío. Reintenta en unos segundos.',
                "1. Vuelve a enviar la campaña (el API ahora reintenta cortes temporales).\n2. Si falla otra vez, confirma que la contraseña de aplicación de Gmail siga vigente.\n3. Evita disparar varios envíos masivos al mismo tiempo; Gmail limita conexiones simultáneas.",
            ),
            str_contains($message, 'no se pudo conectar con el api de mensajería'),
            str_contains($message, 'no se pudo conectar con el api') => $this->guidance(
                'api_messaging_unreachable',
                'No pudimos contactar al servicio de WhatsApp/SMS de TDG.',
                "1. Revisa el estado del API en el dashboard.\n2. Confirma que integracorp-api esté en ejecución y accesible desde este servidor.\n3. Reintenta el envío en unos minutos; si continúa, escala a soporte técnico.",
            ),
            str_contains($message, 'ningún destinatario seleccionado tiene correo'),
            str_contains($message, 'no hay destinatarios válidos') => $this->guidance(
                'no_valid_emails',
                'Los contactos seleccionados no tienen un correo electrónico usable.',
                "1. Abre el perfil del destinatario en Audiencias TDG y valida que tenga email.\n2. Si envías por audiencia completa, confirma que el grupo tenga contactos con correo actualizado.\n3. Vuelve a intentar el envío después de corregir los datos.",
            ),
            str_contains($message, 'ningún destinatario seleccionado tiene teléfono'),
            str_contains($message, 'teléfono válido') => $this->guidance(
                'no_valid_phones',
                'Los contactos seleccionados no tienen un número de teléfono válido para WhatsApp.',
                "1. Verifica que el contacto tenga móvil con código de país (ej. 58412…).\n2. Evita números fijos o incompletos.\n3. Actualiza el teléfono en Audiencias TDG y reintenta.",
            ),
            str_contains($message, 'debe tener copy o imagen'),
            str_contains($message, 'debe tener copy o adjunto') => $this->guidance(
                'missing_content',
                'La campaña no tiene mensaje ni imagen para enviar.',
                "1. Edita la notificación y agrega texto en el copy o sube una imagen/adjunto.\n2. Guarda los cambios y vuelve a enviar.\n3. Para WhatsApp, al menos uno de los dos es obligatorio.",
            ),
            str_contains($message, 'sms aún no está configurado') => $this->guidance(
                'sms_not_configured',
                'El canal SMS todavía no está habilitado en este entorno.',
                "1. Usa WhatsApp o correo para esta campaña.\n2. Si necesitas SMS, solicita su activación al equipo técnico.\n3. Mientras tanto, informa al destinatario por otro canal disponible.",
            ),
            str_contains($message, 'debes indicar un correo'),
            str_contains($message, 'debes indicar un número') => $this->guidance(
                'missing_recipient',
                'Falta el dato de contacto del destinatario en el formulario de envío.',
                "1. Completa el correo o teléfono según el canal elegido.\n2. Verifica que no haya espacios extra o caracteres inválidos.\n3. Vuelve a enviar la invitación.",
            ),
            str_contains($message, 'mensaje no puede estar vacío') => $this->guidance(
                'empty_message',
                'El mensaje personalizado está vacío.',
                "1. Escribe el texto de la invitación en el campo de mensaje.\n2. Puedes partir de la plantilla sugerida y ajustarla.\n3. Guarda y reenvía.",
            ),
            str_contains($message, 'número de teléfono inválido'),
            str_contains($message, 'invalid phone'),
            str_contains($message, 'teléfono inválido') => $this->guidance(
                'invalid_phone',
                'El número ingresado no cumple el formato internacional esperado.',
                "1. Usa formato con código de país: 58412XXXXXXX (sin + ni espacios).\n2. Si el número es venezolano, empieza con 58.\n3. Corrige el número y reintenta el envío de prueba.",
            ),
            str_contains($message, 'file not exist'),
            str_contains($message, 'imagen no'),
            str_contains($message, 'image') && str_contains($message, 'not') => $this->guidance(
                'image_unreachable',
                'WhatsApp no pudo descargar la imagen adjunta.',
                "1. Confirma que la imagen esté subida y visible en el panel.\n2. En entornos locales, el API debe poder acceder a la URL de la imagen.\n3. Sube de nuevo la imagen o usa una URL pública accesible.",
            ),
            str_contains($message, 'cola de whatsapp reportó fallos'),
            str_contains($message, 'reportó fallos al procesar') => $this->guidance(
                'whatsapp_queue_failed',
                'El API aceptó el lote, pero Ultramsg no pudo entregar uno o más mensajes.',
                "1. Revisa la consola de integracorp-api (busca [whatsapp-queue] Error al enviar mensaje).\n2. Si enviaste imagen, confirma que el adjunto exista y que el caption no supere 1024 caracteres.\n3. Verifica que el número tenga WhatsApp activo y esté en formato 58412…\n4. Consulta GET /api/notifications/whatsapp/status en el API.",
            ),
            str_contains($message, 'el api rechazó') => $this->guidance(
                'api_rejected',
                'El servicio de envío rechazó la solicitud.',
                "1. Lee el detalle técnico de este registro para identificar la causa exacta.\n2. Corrige la configuración señalada (contenido, destinatario o adjunto).\n3. Si el error es desconocido, comparte este ID de log con soporte técnico.",
            ),
            $status === NotificationDispatchStatus::Partial => $this->guidance(
                'partial_delivery',
                'Solo una parte de los destinatarios recibió el mensaje.',
                "1. Revisa cuántos envíos se completaron versus el total.\n2. Valida los contactos que pudieron fallar (email/teléfono inválido).\n3. Reenvía solo a los contactos corregidos para no duplicar mensajes.",
            ),
            default => $this->guidance(
                'unknown',
                filled($technicalMessage)
                    ? 'Ocurrió un problema durante el envío que requiere tu revisión.'
                    : 'El envío no se completó y necesitamos que revises la configuración.',
                filled($technicalMessage)
                    ? "1. Revisa el detalle técnico abajo: «{$technicalMessage}».\n2. Corrige la causa indicada en la campaña o el destinatario.\n3. Si no identificas la solución, comparte este registro con soporte técnico."
                    : "1. Verifica canal, destinatario y contenido de la campaña.\n2. Reintenta el envío.\n3. Si el problema continúa, contacta a soporte con el ID de este log.",
                $channel,
            ),
        };
    }

    /**
     * Buzón desde el que sale el correo, para no dejar la cuenta escrita a mano en el
     * mensaje (cambió al migrar de Gmail a Workspace).
     */
    private function senderMailbox(): string
    {
        $address = config('mail.from.address');

        return is_string($address) && filled($address) ? $address : 'la cuenta remitente';
    }

    /**
     * @return FailureGuidance
     */
    private function guidance(
        string $failureCode,
        string $analystMessage,
        string $resolutionSteps,
        ?BirthdayNotificationChannel $channel = null,
    ): array {
        if ($channel !== null) {
            $resolutionSteps = "Canal: {$channel->getLabel()}\n\n{$resolutionSteps}";
        }

        return [
            'failure_code' => $failureCode,
            'analyst_message' => $analystMessage,
            'resolution_steps' => $resolutionSteps,
        ];
    }
}
