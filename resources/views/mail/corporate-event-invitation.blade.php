<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación: {{ $event->title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef0f6;font-family:'IBM Plex Sans',Arial,Helvetica,sans-serif;color:#2d3250;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef0f6;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(45,50,80,0.08);">
                    <tr>
                        <td style="background-color:#2d3250;padding:32px;text-align:center;">
                            <img
                                src="cid:company-logo"
                                alt="Tu Doctor Group"
                                width="200"
                                style="display:block;margin:0 auto;max-width:200px;height:auto;border:0;"
                            >
                        </td>
                    </tr>

                    @if ($useEmbeddedCoverImage)
                        <tr>
                            <td style="padding:0;background-color:#f4f5f9;">
                                <img
                                    src="cid:campaign-image"
                                    alt="{{ $event->title }}"
                                    width="600"
                                    style="display:block;width:100%;max-width:100%;height:auto;border:0;"
                                >
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:32px 32px 0;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#9a4f1e;">
                                Invitación corporativa
                            </p>
                            <h1 style="margin:0 0 20px;font-size:28px;line-height:1.25;color:#2d3250;">
                                {{ $event->title }}
                            </h1>

                            @if (filled($message))
                                <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#424769;white-space:pre-line;">
                                    {{ $message }}
                                </p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 32px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fafbfc;border:1px solid #eef0f6;border-radius:14px;">
                                <tr>
                                    <td style="padding:20px 22px;">
                                        <p style="margin:0 0 12px;font-size:13px;font-weight:600;color:#676f9d;text-transform:uppercase;letter-spacing:0.06em;">
                                            Detalles del evento
                                        </p>
                                        <p style="margin:0 0 10px;font-size:15px;line-height:1.6;color:#424769;">
                                            <strong style="color:#2d3250;">Tipo:</strong> {{ $typeLabel }}
                                        </p>
                                        <p style="margin:0 0 10px;font-size:15px;line-height:1.6;color:#424769;">
                                            <strong style="color:#2d3250;">Modalidad:</strong> {{ $modalityLabel }}
                                        </p>
                                        <p style="margin:0 0 10px;font-size:15px;line-height:1.6;color:#424769;">
                                            <strong style="color:#2d3250;">Fecha:</strong> {{ $dateLabel }}
                                        </p>
                                        <p style="margin:0;font-size:15px;line-height:1.6;color:#424769;">
                                            <strong style="color:#2d3250;">Lugar:</strong> {{ $locationLabel }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if (filled($registrationUrl))
                        <tr>
                            <td style="padding:0 32px 32px;text-align:center;">
                                <a
                                    href="{{ $registrationUrl }}"
                                    style="display:inline-block;padding:14px 28px;border-radius:999px;background-color:#f9b17a;color:#2d3250;font-size:16px;font-weight:700;text-decoration:none;box-shadow:0 8px 22px rgba(249,177,122,0.35);"
                                >
                                    Inscribirme ahora
                                </a>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:24px 32px 28px;border-top:1px solid #eef0f6;background-color:#fafbfc;">
                            <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#676f9d;text-align:center;">
                                Tu Doctor Group · Marketing
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#8f98b8;text-align:center;">
                                @if ($sentByName)
                                    Invitación enviada por {{ $sentByName }}.
                                @else
                                    Este mensaje fue enviado como parte de una comunicación de Tu Doctor Group.
                                @endif
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
