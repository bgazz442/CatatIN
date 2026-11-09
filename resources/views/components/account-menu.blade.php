@php($accountMenuContext = $accountMenuContext ?? 'desktop')
<div class="account-menu account-menu--{{ $accountMenuContext }}" data-account-menu>
    <button
        type="button"
        class="account-menu-trigger"
        data-account-menu-trigger
        aria-label="Info akun dan ubah nama"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="account-menu-panel-{{ $accountMenuContext }}"
    >
        <span class="account-menu-avatar">
            @if(auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" alt="">
            @else
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            @endif
        </span>
        @if($accountMenuContext === 'desktop')
            <span class="account-menu-trigger-name">{{ auth()->user()->name }}</span>
        @elseif($accountMenuContext === 'chat')
            <span class="account-menu-chat-identity">
                <span class="account-menu-trigger-name">{{ auth()->user()->name }}</span>
                <span class="account-menu-chat-email">{{ auth()->user()->email }}</span>
            </span>
        @endif
        <i data-lucide="chevron-down" class="account-menu-chevron h-4 w-4"></i>
    </button>
    <div id="account-menu-panel-{{ $accountMenuContext }}" class="account-menu-panel" data-account-menu-panel hidden>
        <div class="account-menu-identity">
            <span class="account-menu-identity-avatar" aria-hidden="true">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </span>
            <div class="account-menu-identity-copy">
                <p class="account-menu-name">{{ auth()->user()->name }}</p>
                <p class="account-menu-email">{{ auth()->user()->email }}</p>
                <span class="account-menu-role">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Akun pribadi' }}</span>
            </div>
        </div>
        <form method="POST" action="{{ route('settings.profile.update') }}" class="account-menu-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
            <label for="account-name-{{ $accountMenuContext }}">Ubah nama pengguna</label>
            <div class="account-menu-form-row">
                <input id="account-name-{{ $accountMenuContext }}" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" maxlength="255" required autocomplete="name">
                <button type="submit" aria-label="Simpan nama">
                    <i data-lucide="check" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Simpan nama</span>
                </button>
            </div>
            @error('name')
                <p class="account-menu-error">{{ $message }}</p>
            @enderror
        </form>
    </div>
</div>
