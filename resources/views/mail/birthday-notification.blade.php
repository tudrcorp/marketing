<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->name }}</title>
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

                    @if ($isTest)
                        <tr>
                            <td style="padding:16px 32px 0;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fff8f2;border:1px solid #fbc99f;border-radius:12px;">
                                    <tr>
                                        <td style="padding:14px 18px;font-size:14px;line-height:1.5;color:#5c2f12;">
                                            <strong style="color:#9a4f1e;">Envío de prueba.</strong>
                                            Este mensaje valida la plantilla configurada en el panel de Marketing.
                                            @if ($sentByName)
                                                Enviado por <strong>{{ $sentByName }}</strong>.
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:32px;">
                            @if ($useEmbeddedImage)
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 24px;">
                                    <tr>
                                        <td style="border-radius:12px;overflow:hidden;background-color:#f4f5f9;">
                                            <img
                                                src="cid:campaign-image"
                                                alt="Imagen de felicitación"
                                                width="536"
                                                style="display:block;width:100%;max-width:100%;height:auto;border:0;"
                                            >
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if (filled($notification->copy))
                                <p style="margin:0;font-size:16px;line-height:1.7;color:#424769;white-space:pre-line;text-align:center;">
                                    {{ $notification->copy }}
                                </p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 28px;border-top:1px solid #eef0f6;background-color:#fafbfc;">
                            <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#676f9d;text-align:center;">
                                Tu Doctor Group · Marketing
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#8f98b8;text-align:center;">
                                @if ($isTest)
                                    Si recibiste este mensaje por error, puedes ignorarlo. No forma parte de un envío masivo.
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
