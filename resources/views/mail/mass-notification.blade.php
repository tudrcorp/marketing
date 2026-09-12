<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef0f6;font-family:'IBM Plex Sans',Arial,Helvetica,sans-serif;color:#2d3250;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef0f6;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(45,50,80,0.08);">
                    <tr>
                        <td style="background-color:#2d3250;padding:32px;text-align:center;">
                            <img
                                src="{{ $logoUrl }}"
                                alt="Tu Doctor Group"
                                width="200"
                                style="display:block;margin:0 auto;max-width:200px;height:auto;border:0;"
                            >
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 32px 12px;">
                            <h1 style="margin:0;font-size:24px;line-height:1.3;color:#2d3250;">{{ $notification->title }}</h1>
                        </td>
                    </tr>

                    @if (filled($notification->copy))
                        <tr>
                            <td style="padding:12px 32px 24px;font-size:16px;line-height:1.7;color:#4b516f;white-space:pre-wrap;">{{ $notification->copy }}</td>
                        </tr>
                    @endif

                    @if ($useHostedImage && filled($imageUrl))
                        <tr>
                            <td style="padding:0 32px 32px;">
                                <img
                                    src="{{ $imageUrl }}"
                                    alt="Adjunto de la campaña"
                                    style="display:block;width:100%;max-width:536px;height:auto;border-radius:12px;border:0;"
                                >
                            </td>
                        </tr>
                    @endif

                    @if (filled($attachmentUrl) && ! $useHostedImage)
                        <tr>
                            <td style="padding:0 32px 32px;font-size:16px;line-height:1.7;color:#4b516f;text-align:center;">
                                <a href="{{ $attachmentUrl }}" style="color:#e87722;font-weight:600;">Descargar adjunto de la campaña</a>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:24px 32px;background-color:#f7f8fc;border-top:1px solid #e4e7f1;font-size:13px;line-height:1.6;color:#6b7190;text-align:center;">
                            Mensaje enviado por <strong>Tu Doctor Group</strong>.
                            Campaña preparada por <strong>el Departamento Comercial</strong>.
                            <br><br>
                            @if ($forTransactional ?? false)
                                Este es un envío de prueba. El enlace de baja y la dirección fiscal los inserta Mailchimp en la campaña real.
                                <br>
                                Tu Doctor Group LLC, Centro Lido 12, Caracas, Venezuela.
                            @else
                                Si no deseas recibir estos correos, puedes <a href="*|UNSUB|*" style="color:#6b7190;">darte de baja</a>.
                                <br>
                                *|LIST:ADDRESS|*
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
