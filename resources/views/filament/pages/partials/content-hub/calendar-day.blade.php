@props(['day', 'canManage' => false, 'expanded' => false])

<button
    type="button"
    wire:click="selectDay('{{ $day['date'] }}')"
    @if ($canManage)
        x-on:dragover.prevent="$el.classList.add('content-day--dropping')"
        x-on:dragleave="$el.classList.remove('content-day--dropping')"
        x-on:drop.prevent="
            $el.classList.remove('content-day--dropping');
            const postId = $event.dataTransfer.getData('text/plain');
            if (postId) { $wire.reschedulePost(Number(postId), '{{ $day['date'] }}') }
        "
    @endif
    @class([
        'content-day workload-'.$day['workload'],
        'content-day--outside' => ! $day['isCurrentMonth'],
        'content-day--today' => $day['isToday'],
        'content-day--selected' => $day['isSelected'],
        'content-day--expanded' => $expanded,
    ])
    aria-label="{{ $day['count'] }} entregas el {{ $day['date'] }}"
>
    <span class="content-day__header">
        <span class="content-day__number">{{ $day['day'] }}</span>
        @if ($day['count'] > 0)
            <span class="content-day__badge" title="Carga laboral del día">{{ $day['count'] }}</span>
        @endif
    </span>

    <span class="content-day__items">
        @foreach (array_slice($day['posts'], 0, $expanded ? 8 : 3) as $post)
            <span class="content-day__item" style="--item-color: {{ $post['brandColor'] ?? $post['statusColor'] }}">
                @if ($post['blocked'])
                    <span class="content-day__item-alert" title="{{ $post['blockerLabel'] }}">!</span>
                @endif
                <span class="content-day__item-time">{{ $post['time'] }}</span>
                <span class="content-day__item-title">{{ $post['title'] }}</span>
            </span>
        @endforeach

        @if (count($day['posts']) > ($expanded ? 8 : 3))
            <span class="content-day__more">+{{ count($day['posts']) - ($expanded ? 8 : 3) }} más</span>
        @endif
    </span>
</button>
