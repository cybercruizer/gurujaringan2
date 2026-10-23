<section>
    <header class="flex flex-col gap-2">
        <h2 class="text-lg font-semibold text-stone-950">{{ __('Profile information') }}</h2>
        <p class="text-sm leading-6 text-stone-600">{{ __("Update your account's name, profile photo, bio, and email address.") }}</p>
    </header>

    @if ($user->avatar)
        <div class="mt-6 flex items-center gap-4">
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar) }}" alt="" class="size-16 rounded-full object-cover">
            <p class="text-sm text-stone-600">Current profile photo</p>
        </div>
    @endif

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 flex flex-col gap-5">
        @csrf
        @method('patch')

        <div class="flex flex-col gap-2">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="flex flex-col gap-2">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="text-sm text-amber-800">
                    {{ __('Your email address is unverified.') }}
                    <button form="send-verification" class="font-medium underline hover:text-amber-950">
                        {{ __('Resend the verification email.') }}
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p role="status" class="text-sm font-medium text-emerald-700">
                        {{ __('A new verification link has been sent.') }}
                    </p>
                @endif
            @endif
        </div>

        <div class="flex flex-col gap-2">
            <x-input-label for="bio" :value="__('Short bio')" />
            <textarea id="bio" name="bio" rows="4" maxlength="1000" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">{{ old('bio', $user->bio) }}</textarea>
            <x-input-error :messages="$errors->get('bio')" />
        </div>

        <div class="flex flex-col gap-2">
            <x-input-label for="avatar" :value="__('Replace profile photo')" />
            <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-stone-600 file:mr-4 file:rounded-full file:border-0 file:bg-stone-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-stone-700 hover:file:bg-stone-200">
            <p class="text-xs text-stone-500">JPEG, PNG, or WebP. Maximum size 5 MB.</p>
            <x-input-error :messages="$errors->get('avatar')" />
        </div>

        <div class="flex items-center gap-4 border-t border-stone-200 pt-5">
            <x-primary-button>{{ __('Save profile') }}</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p class="text-sm text-emerald-700">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
