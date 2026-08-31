@php
    use App\Marketing\MarketingPermission;

    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $groups = MarketingPermission::groups();
    $totalPermissions = count(MarketingPermission::all());
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        class="marketing-role-permissions"
        x-data="{
            search: '',
            statePath: @js($statePath),
            groups: @js($groups),
            totalPermissions: @js($totalPermissions),
            matches(label, description) {
                if (! this.search.trim()) {
                    return true;
                }

                const term = this.search.toLowerCase();

                return label.toLowerCase().includes(term)
                    || description.toLowerCase().includes(term);
            },
            groupIsVisible(group) {
                if (! this.search.trim()) {
                    return true;
                }

                if (this.matches(group.label, group.description)) {
                    return true;
                }

                return group.permissions.some(
                    (permission) => this.matches(permission.label, permission.description),
                );
            },
            selectedCount() {
                return ($wire.get(this.statePath) ?? []).length;
            },
            groupCount(permissions) {
                const selected = $wire.get(this.statePath) ?? [];

                return permissions.filter((permission) => selected.includes(permission)).length;
            },
            isGroupFullySelected(permissions) {
                const selected = $wire.get(this.statePath) ?? [];

                return permissions.every((permission) => selected.includes(permission));
            },
            toggleGroup(permissions, shouldSelect) {
                const selected = $wire.get(this.statePath) ?? [];

                if (shouldSelect) {
                    $wire.set(this.statePath, [...new Set([...selected, ...permissions])]);

                    return;
                }

                $wire.set(
                    this.statePath,
                    selected.filter((permission) => ! permissions.includes(permission)),
                );
            },
            selectAll() {
                $wire.set(this.statePath, @js(MarketingPermission::all()));
            },
            clearAll() {
                $wire.set(this.statePath, []);
            },
            hasVisibleResults() {
                return this.groups.some((group) => this.groupIsVisible(group));
            },
        }"
    >
        <div class="marketing-role-permissions__toolbar">
            <div class="marketing-role-permissions__summary">
                <span class="marketing-role-permissions__summary-count" x-text="`${selectedCount()} / ${totalPermissions}`"></span>
                <span class="marketing-role-permissions__summary-label">permisos activos</span>
            </div>

            <div class="marketing-role-permissions__toolbar-actions">
                @unless ($isDisabled)
                    <button type="button" class="marketing-role-permissions__action" x-on:click="selectAll()">
                        Seleccionar todo
                    </button>
                    <button type="button" class="marketing-role-permissions__action" x-on:click="clearAll()">
                        Limpiar
                    </button>
                @endunless
            </div>
        </div>

        <x-filament::input.wrapper
            inline-prefix
            :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
            class="marketing-role-permissions__search"
        >
            <input
                type="search"
                x-model.debounce.150ms="search"
                placeholder="Buscar permiso por nombre o módulo…"
                class="fi-input fi-input-has-inline-prefix"
                @disabled($isDisabled)
            />
        </x-filament::input.wrapper>

        <div class="marketing-role-permissions__groups">
            @foreach ($groups as $group)
                @php
                    $permissionKeys = collect($group['permissions'])->pluck('key')->all();
                @endphp

                <article
                    wire:key="marketing-role-permission-group-{{ $group['key'] }}"
                    class="marketing-role-permissions__group"
                    x-show="groupIsVisible(@js($group))"
                    x-cloak
                >
                    <header class="marketing-role-permissions__group-header">
                        <div class="marketing-role-permissions__group-heading">
                            <span class="marketing-role-permissions__group-icon-wrap">
                                <x-filament::icon :icon="$group['icon']" class="marketing-role-permissions__group-icon" />
                            </span>

                            <div class="marketing-role-permissions__group-copy">
                                <h4 class="marketing-role-permissions__group-title">{{ $group['label'] }}</h4>
                                <p class="marketing-role-permissions__group-description">{{ $group['description'] }}</p>
                            </div>
                        </div>

                        <div class="marketing-role-permissions__group-actions">
                            <span class="marketing-role-permissions__group-count">
                                <span x-text="groupCount(@js($permissionKeys))"></span>/{{ count($group['permissions']) }}
                            </span>

                            @unless ($isDisabled)
                                <button
                                    type="button"
                                    class="marketing-role-permissions__group-toggle"
                                    x-on:click="toggleGroup(@js($permissionKeys), ! isGroupFullySelected(@js($permissionKeys)))"
                                    x-text="isGroupFullySelected(@js($permissionKeys)) ? 'Quitar módulo' : 'Activar módulo'"
                                ></button>
                            @endunless
                        </div>
                    </header>

                    <ul class="marketing-role-permissions__list">
                        @foreach ($group['permissions'] as $permission)
                            <li
                                wire:key="marketing-role-permission-{{ $permission['key'] }}"
                                class="marketing-role-permissions__item"
                                x-show="matches(@js($permission['label']), @js($permission['description']))"
                                x-cloak
                            >
                                <label class="marketing-role-permissions__option">
                                    <input
                                        type="checkbox"
                                        value="{{ $permission['key'] }}"
                                        @disabled($isDisabled)
                                        wire:loading.attr="disabled"
                                        {{ $wireModelAttribute }}="{{ $statePath }}"
                                        @class([
                                            'fi-checkbox-input marketing-role-permissions__checkbox',
                                            'fi-valid' => ! $errors->has($statePath),
                                            'fi-invalid' => $errors->has($statePath),
                                        ])
                                    />

                                    <span class="marketing-role-permissions__option-body">
                                        <span class="marketing-role-permissions__option-label">
                                            {{ $permission['label'] }}
                                        </span>
                                        <span class="marketing-role-permissions__option-description">
                                            {{ $permission['description'] }}
                                        </span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>

        <p class="marketing-role-permissions__empty" x-cloak x-show="search.trim() && ! hasVisibleResults()">
            No se encontraron permisos para esta búsqueda.
        </p>
    </div>
</x-dynamic-component>
