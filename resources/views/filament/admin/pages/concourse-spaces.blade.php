<x-filament-panels::page>
    <x-filament::card>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-lg font-medium">Concourse Details</h3>
                <p class="text-sm text-gray-600">Address: {{ $this->concourse->address }}</p>
                <p class="text-sm text-gray-600">Name: {{ $this->concourse->name }}</p>
            </div>

            <div>
                <h3 class="text-lg font-medium">Water Bills</h3>
                <p class="text-sm text-gray-600">Current: {{ number_format($this->concourse->water_bills ?? 0, 2) }}</p>
                <!-- <p class="text-sm text-gray-600">Outstanding: RM {{ number_format($this->concourse->outstanding_water_bill ?? 0, 2) }}</p> -->

                <h3 class="text-lg font-medium">Electric Bills</h3>
                <p class="text-sm text-gray-600">Current: {{ number_format($this->concourse->electricity_bills ?? 0, 2) }}</p>
                <!-- <p class="text-sm text-gray-600">Outstanding: RM {{ number_format($this->concourse->outstanding_electric_bill ?? 0, 2) }}</p> -->
            </div>
        </div>
    </x-filament::card>

    {{ $this->table }}
</x-filament-panels::page>