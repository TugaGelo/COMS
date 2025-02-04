@if($this->selectedConcourseLayout)
    <section class="mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <h2 class="text-xl font-semibold mb-4">{{ $this->selectedConcourseLayout->name }} Layout</h2>
            <div class="relative">   
                <img src="{{ Storage::url($this->selectedConcourseLayout->layout) }}" alt="{{ $this->selectedConcourseLayout->name }} Layout" class="w-full max-h-auto rounded-lg">
                @foreach($this->filteredTenantSpaces as $space)
                    <div
                        style="
                            position: absolute; 
                            border: 2px solid green; 
                            left: {{ $space->space_coordinates_x }}%; 
                            top: {{ $space->space_coordinates_y }}%; 
                            width: {{ $space->space_width }}%; 
                            height: {{ $space->space_length }}%;
                            background-color: rgba(0, 255, 0, 0.3);
                            transition: background-color 0.3s ease;
                            cursor: pointer;
                        "
                        onmouseover="this.style.backgroundColor='rgba(0, 255, 0, 0.5)'"
                        onmouseout="this.style.backgroundColor='rgba(0, 255, 0, 0.3)'"
                    >
                        <span style="color: green; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">{{ $space->name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif 