@props(['columns', 'canManage' => false])

<div class="content-kanban" role="list">
    @foreach ($columns as $column)
        <section class="content-kanban__column" role="listitem" aria-label="Columna {{ $column['label'] }}">
            <header class="content-kanban__header" style="--column-color: {{ $column['color'] }}">
                <span class="content-kanban__dot" aria-hidden="true"></span>
                <h3 class="content-kanban__title">{{ $column['label'] }}</h3>
                <span class="content-kanban__count">{{ $column['count'] }}</span>
            </header>

            <div
                class="content-kanban__list"
                data-status="{{ $column['status']->value }}"
                data-kanban-column="{{ $canManage ? 'true' : 'false' }}"
                wire:key="column-{{ $column['status']->value }}"
            >
                @forelse ($column['posts'] as $post)
                    <div wire:key="post-{{ $post->id }}">
                        @include('filament.pages.partials.content-hub.post-card', [
                            'post' => $post,
                            'canManage' => $canManage,
                        ])
                    </div>
                @empty
                    <p class="content-kanban__empty">Sin piezas en esta etapa.</p>
                @endforelse
            </div>

            @if ($canManage)
                <button
                    type="button"
                    wire:click="mountAction('createPost', { status: '{{ $column['status']->value }}' })"
                    class="content-kanban__add"
                >
                    + Añadir pieza
                </button>
            @endif
        </section>
    @endforeach
</div>
