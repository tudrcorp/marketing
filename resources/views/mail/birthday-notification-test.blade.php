<x-mail::message>
# Envío de prueba — {{ $notification->name }}

Este es un **envío de prueba** de la plantilla de cumpleaños configurada en el panel de Marketing de Tu Doctor Group.

@if ($imageUrl)
![Imagen de la notificación]({{ $imageUrl }})
@endif

{{ $notification->copy }}

---

Enviado por **{{ $sentByName }}** desde el panel de Marketing.

<x-mail::subcopy>
Si recibiste este mensaje por error, puedes ignorarlo. No forma parte de un envío masivo.
</x-mail::subcopy>
</x-mail::message>
