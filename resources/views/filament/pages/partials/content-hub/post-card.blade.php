@props([
    'post',
    'canManage' => false,
    'draggable' => false,
    'compact' => false,
])

<article
    data-post-id="{{ $post->id }}"
    @class([
        'content-card group relative flex flex-col gap-2 rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition dark:border-white/10 dark:bg-gray-900',
        'hover:shadow-md hover:-translate-y-px' => true,
        'content-card--blocked' => $post->isBlocked(),
    ])
    style="--brand-color: {{ $post->brand?->color_hex ?? '#f9b17a' }}"
    @if ($draggable)
        draggable="true"
        x-on:dragstart="$event.dataTransfer.setData('text/plain', '{{ $post->id }}'); $event.dataTransfer.effectAllowed = 'move'"
    @endif
>
    <span class="absolute inset-y-0 left-0 w-1 rounded-l-xl" style="background-color: var(--brand-color)" aria-hidden="true"></span>

    <div class="flex items-start justify-between gap-2 pl-2">
        <div class="min-w-0 flex-1">
            @if ($post->brand)
                <p class="truncate text-[11px] font-semibold uppercase tracking-wide" style="color: var(--brand-color)">
                    {{ $post->brand->name }}
                </p>
            @endif
            <button
                type="button"
                wire:click="mountAction('editPost', { post: {{ $post->id }} })"
                class="mt-0.5 block w-full truncate text-left text-sm font-semibold text-gray-950 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
            >
                {{ $post->title }}
            </button>
        </div>

        @if ($canManage && ! $compact)
            <span
                data-drag-handle
                class="content-card__handle"
                title="Arrastra la tarjeta para moverla de columna"
                aria-hidden="true"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M7 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm6 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM7 8.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm6 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM7 13a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm6 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z" />
                </svg>
            </span>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-1.5 pl-2">
        <span
            class="content-chip"
            style="--chip-color: {{ $post->priority->getHexColor() }}"
            title="Prioridad {{ $post->priority->getLabel() }}"
        >
            {{ $post->priority->getLabel() }}
        </span>

        <span class="content-chip content-chip--muted">{{ $post->format->getLabel() }}</span>

        @if ($post->pillar)
            <span class="content-chip" style="--chip-color: {{ $post->pillar->getHexColor() }}">
                {{ $post->pillar->getLabel() }}
            </span>
        @endif
    </div>

    @if ($post->isBlocked())
        <p class="ml-2 flex items-center gap-1.5 rounded-lg bg-danger-50 px-2 py-1 text-[11px] font-semibold text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
            </svg>
            {{ $post->blocker->getLabel() }}
        </p>
    @endif

    <div class="flex items-center justify-between gap-2 pl-2 text-[11px] text-gray-500 dark:text-gray-400">
        <span class="flex items-center gap-1">
            @if ($post->scheduled_at)
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2ZM3.5 8v7.25c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25V8h-13Z" clip-rule="evenodd" />
                </svg>
                <span @class(['font-semibold text-danger-600 dark:text-danger-400' => $post->isOverdue()])>
                    {{ $post->scheduled_at->timezone(config('app.timezone'))->locale('es')->isoFormat('D MMM · HH:mm') }}
                </span>
            @else
                <span class="italic">Sin fecha</span>
            @endif
        </span>

        @if ($canManage)
            <button
                type="button"
                wire:click="toggleTimer({{ $post->id }})"
                @class([
                    'content-timer',
                    'content-timer--running' => $post->isTimerRunning(),
                ])
                title="{{ $post->isTimerRunning() ? 'Detener temporizador' : 'Iniciar temporizador' }}"
            >
                @if ($post->isTimerRunning())
                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6 5.75A.75.75 0 0 1 6.75 5h1.5a.75.75 0 0 1 .75.75v8.5a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1-.75-.75v-8.5Zm5 0a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 .75.75v8.5a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1-.75-.75v-8.5Z" />
                    </svg>
                @else
                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.3 2.84A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.27l9.344-5.891a1.5 1.5 0 0 0 0-2.538L6.3 2.841Z" />
                    </svg>
                @endif
                <span class="tabular-nums">{{ $post->formattedElapsedTime() }}</span>
            </button>
        @endif
    </div>
</article>
