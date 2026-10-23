<x-app-layout>
    <section class="mx-auto flex w-full max-w-4xl flex-col gap-6 px-4 py-10 sm:px-6 sm:py-14">
        <div class="flex flex-col gap-2">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Your account</p>
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">{{ __('Profile settings') }}</h1>
            <p class="text-stone-600">Manage your public profile, email, and account security.</p>
        </div>

        @if (session('status') === 'profile-updated')
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                Profile saved.
            </p>
        @endif

        <div class="flex flex-col gap-6">
            <section class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-8">
                <div class="max-w-2xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-8">
                <div class="max-w-2xl">
                    @include('profile.partials.update-password-form')
                </div>
            </section>

            <section class="rounded-2xl border border-red-200 bg-white p-5 sm:p-8">
                <div class="max-w-2xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </section>
        </div>
    </section>
</x-app-layout>
