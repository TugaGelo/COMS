<div>
    {{ $this->table }}
    <div id="map" style="height: 400px; width: 100%;" class="mb-4"></div>
</div>

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA64WIgsJoT70A83moLEvuhFwwV6R-15Wg&callback=initMap" async defer></script>
<script>
    function initMap() {
        const map = new google.maps.Map(document.getElementById("map"), {
            zoom: 10,
            center: {
                lat: 0,
                lng: 0
            },
            mapTypeId: 'roadmap',
            mapTypeControl: false,
            zoomControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            clickableIcons: false,
            styles: [{
                featureType: 'all',
                elementType: 'all',
                stylers: [{
                    visibility: 'on'
                }]
            }],
            disableDefaultUI: true,
            zoomControl: true,
            zoomControlOptions: {
                position: google.maps.ControlPosition.TOP_RIGHT,
            },
            mapTypeControl: true,
            mapTypeControlOptions: {
                position: google.maps.ControlPosition.TOP_LEFT,
            },
            zoom: 10,
            zoomControl: true,
            zoomControlOptions: {
                position: google.maps.ControlPosition.BOTTOM_RIGHT,
            },
            scaleControl: true,
            scaleControlOptions: {
                position: google.maps.ControlPosition.TOP_RIGHT,
            },
            fullscreenControl: true,
            fullscreenControlOptions: {
                position: google.maps.ControlPosition.TOP_RIGHT,
            },
            streetViewControl: true,
            streetViewControlOptions: {
                position: google.maps.ControlPosition.RIGHT_BOTTOM,
            },
            rotateControl: true,
            rotateControlOptions: {
                position: google.maps.ControlPosition.RIGHT_BOTTOM,
            },
            panControl: true,
            panControlOptions: {
                position: google.maps.ControlPosition.RIGHT_BOTTOM,
            },
            zoomControl: true,
            zoomControlOptions: {
                position: google.maps.ControlPosition.RIGHT_BOTTOM,
            },
            zoomControl: true,
            zoomControlOptions: {
                position: google.maps.ControlPosition.RIGHT_BOTTOM,
            },
        });

        const concourses = @json($concourses);
        const bounds = new google.maps.LatLngBounds();

        concourses.forEach((concourse) => {
            if (concourse.lat && concourse.lng) {
                const marker = new google.maps.Marker({
                    position: {
                        lat: parseFloat(concourse.lat),
                        lng: parseFloat(concourse.lng)
                    },
                    map: map,
                    title: concourse.name,

                });

                bounds.extend(marker.getPosition());

                const infoWindow = new google.maps.InfoWindow({
                    content: `
                                <h1 class="text-gray-950 text-lg font-bold"> ${concourse.name} </h1> 
                                <h2 class="text-gray-950 my-2 font-semibold text-md"> Spaces: ${concourse.spaces}</h2>                            
                                <x-filament::badge>
                                    <h2 class="text-md">
                                    Address: ${concourse.address}
                                    </h2>
                                </x-filament::badge>
                            
                             `,
                });

                marker.addListener("click", () => {
                    infoWindow.open({
                        anchor: marker,
                        map,
                        shouldFocus: false,
                        pixelOffset: new google.maps.Size(0, -30),
                        content: infoWindow.getContent(),
                        maxWidth: 100,
                        maxHeight: 100,
                    });
                });
            }
        });

        map.fitBounds(bounds);
    }
</script>
@endpush