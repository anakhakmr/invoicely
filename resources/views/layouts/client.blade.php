<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <a href="{{ route('client.dashboard') }}" class="flex items-center gap-2 font-medium" wire:navigate>
                <x-app-logo-icon class="size-6 fill-current text-black dark:text-white" />
                <span>{{ config('app.name', 'Laravel') }}</span>
            </a>

            <flux:navbar class="max-lg:hidden">
                <flux:navbar.item :href="route('client.dashboard')" :current="request()->routeIs('client.dashboard')" wire:navigate>
                    {{ __('My Invoices') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile :name="auth('client')->user()->name" icon-trailing="chevron-down" />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar :name="auth('client')->user()->name" />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth('client')->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth('client')->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('client.logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="client-logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main container>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
