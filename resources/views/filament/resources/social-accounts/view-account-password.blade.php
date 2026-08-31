<div class="marketing-credential">
    <div class="marketing-credential__notice">
        <x-filament::icon icon="heroicon-o-shield-check" class="marketing-credential__notice-icon size-5" />
        <div>
            <p class="marketing-credential__notice-title">Acceso restringido a administradores</p>
            <p class="marketing-credential__notice-copy">
                Esta clave está cifrada en la base de datos. Compártela solo con personal autorizado de TDG.
            </p>
        </div>
    </div>

    <dl class="marketing-credential__meta">
        <div>
            <dt>Cuenta</dt>
            <dd>{{ $account->name }}</dd>
        </div>
        <div>
            <dt>Usuario / handle</dt>
            <dd>{{ $account->handle ?: '—' }}</dd>
        </div>
        <div>
            <dt>Red social</dt>
            <dd>{{ $account->platform->getLabel() }}</dd>
        </div>
    </dl>

    <div class="marketing-credential__password-card">
        <div class="marketing-credential__password-header">
            <span>Clave en texto claro</span>
            <button
                type="button"
                class="marketing-credential__copy-btn"
                x-data="{ copied: false }"
                x-on:click="
                    navigator.clipboard.writeText(@js($password));
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                "
            >
                <span x-show="! copied">Copiar</span>
                <span x-show="copied" x-cloak>Copiada</span>
            </button>
        </div>
        <code class="marketing-credential__password-value">{{ $password }}</code>
    </div>
</div>
