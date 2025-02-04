<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::button 
            type="submit" 
            class="my-4"
            wire:loading.attr="disabled"
            wire:target="save"
        >
            <span wire:loading.remove wire:target="save">
                Update Application
            </span>
            <span wire:loading wire:target="save">
                Updating...
            </span>
        </x-filament::button>
    </form>
</x-filament-panels::page>
