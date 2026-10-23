<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="flex flex-col gap-5">
        @csrf

        <div class="flex flex-col gap-2">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="flex flex-col gap-2">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="flex flex-col gap-2">
            <x-input-label for="bio" :value="__('Short bio (optional)')" />
            <textarea id="bio" name="bio" rows="3" maxlength="1000" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">{{ old('bio') }}</textarea>
            <x-input-error :messages="$errors->get('bio')" />
        </div>

        <div class="flex flex-col gap-2">
            <x-input-label for="avatar" :value="__('Profile photo (optional)')" />
            <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-stone-600 file:mr-4 file:rounded-full file:border-0 file:bg-stone-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-stone-700 hover:file:bg-stone-200">
            <p class="text-xs text-stone-500">JPEG, PNG, or WebP. Maximum size 5 MB.</p>
            <x-input-error :messages="$errors->get('avatar')" />
        </div>

        <div class="flex flex-col gap-2">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex flex-col gap-2">
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-stone-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('login') }}" class="text-sm font-medium text-stone-600 hover:text-stone-950">
                {{ __('Already registered? Log in') }}
            </a>
            <x-primary-button>{{ __('Create account') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
