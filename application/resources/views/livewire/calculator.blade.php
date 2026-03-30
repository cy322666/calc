<div>
    <form wire:submit.prevent="calculateAction">
        <x-filament::button type="submit">
            Рассчитать
        </x-filament::button>

        {{ $this->form }}
    </form>

    <x-filament-actions::modals />
</div>
