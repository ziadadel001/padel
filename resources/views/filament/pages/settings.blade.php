<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Court Settings</x-slot>
        <x-slot name="description">Manage the pricing for court bookings.</x-slot>

        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6 flex justify-end gap-x-3">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Save Settings
                </x-filament::button>
            </div>
        </form>

        <x-filament-actions::modals />
    </x-filament::section>
</x-filament-panels::page>
