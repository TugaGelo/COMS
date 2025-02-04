<div>
    <section class="mb-6">
        @if($concourse && $concourse->layout)
        <div class="bg-white rounded-lg shadow-md p-4">
            <h2 class="text-xl font-semibold mb-4">{{ $concourse->name }} Layout</h2>
            <div class="relative">
                <img id="concourseLayout" src="{{ Storage::url($concourse->layout) }}" alt="{{ $concourse->name }} Layout" class="w-full max-h-auto rounded-lg">
                @foreach($spaces as $space)
                <div
                    x-data="{ showInfo: false }"
                    @mouseover="showInfo = true"
                    @mouseout="showInfo = false"
                    style="
                                position: absolute; 
                                border: 2px solid {{ $space->status === 'available' ? 'blue' : 'green' }}; 
                                left: {{ $space->space_coordinates_x }}%; 
                                top: {{ $space->space_coordinates_y }}%; 
                                width: {{ $space->space_width }}%; 
                                height: {{ $space->space_length }}%;
                                background-color: {{ $space->status === 'available' ? 'rgba(0, 0, 255, 0.3)' : 'rgba(0, 255, 0, 0.3)' }};
                                transition: background-color 0.3s ease;
                                ;
                            ">
                    <span style="color: {{ $space->status === 'available' ? 'blue' : 'green' }}; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">{{ $space->name }}</span>


                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-gray-100 rounded-lg p-4 text-center">
            <p class="text-gray-600">No layout image available for this concourse.</p>
        </div>
        @endif
    </section>

    <section class="pt-4">
        {{ $this->table }}
    </section>

    <x-filament::modal
        id="application-modal"
        :heading="'Application Form'"
        :slide-over="true"
        wire:model="showApplicationModal">

        <form wire:submit="submitApplication">
            {{ $this->form }}

            <div class="mt-4 flex justify-end">
                <x-filament::button
                    type="submit"
                    color="primary">
                    Submit
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>
</div>