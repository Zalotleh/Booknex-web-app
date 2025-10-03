(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		booknetic.initMultilangInput( $( "#input_location_name" ), 'locations', 'name' );
		booknetic.initMultilangInput( $( "#input_address" ), 'locations', 'address' );
		booknetic.initMultilangInput( $( "#input_note" ), 'locations', 'notes' );

		$('.fs-modal').on('click', '#addLocationSave', function ()
		{
			let location_name	= $("#input_location_name").val(),
				phone			= $("#input_phone").val(),
				address			= $("#input_address").val(),
				note			= $("#input_note").val(),
				image			= $("#input_image")[0].files[0];

			if( location_name === '' )
			{
				booknetic.toast(booknetic.__('fill_all_required'), 'unsuccess');
				return;
			}

			const id = $("#add_new_JS").data('location-id');
			const data = new FormData();

			data.append('location_name', location_name);
			data.append('address', address);
			data.append('phone', phone);
			data.append('note', note);
			data.append('image', image);
			data.append('latitude', marker.getPosition() ? marker.getPosition().lat() : '');
			data.append('longitude', marker.getPosition() ? marker.getPosition().lng() : '');
			data.append('address_components', addressComponenets || '');
			data.append('translations', booknetic.getTranslationData( $( '.fs-modal' ).first() ));

			const onSave = () => {
				const dataTable = $("#fs_data_table_div");

				booknetic.modalHide($(".fs-modal"));

				if( dataTable.length > 0 ) {
					booknetic.dataTable.reload( dataTable );
				}
			}

			if ( !id ) {
				booknetic.ajax( 'locations.create', data, onSave);
				return;
			}

			data.append('id', id);
			booknetic.ajax( 'locations.update', data, onSave);
		}).on('click', '#hideLocationBtn', function ()
		{
			const id = $("#add_new_JS").data('location-id');
			booknetic.ajax('toggleVisibility', { id }, function ()
			{
				booknetic.modalHide($(".fs-modal"));
				booknetic.dataTable.reload( $("#fs_data_table_div") );
			});
		});

		let latitude = $('#add_new_JS').data('latitude') || 0;
		let longitude = $('#add_new_JS').data('longitude') || 0;
		let zoom = latitude > 0 ? 15 : 2;

		let map, marker, autocomplete, addressComponenets;

		function initMap() {
			const defaultLocation = { lat: latitude, lng: longitude };
			map = new google.maps.Map(document.getElementById("divmap"), {
				center: defaultLocation,
				zoom: zoom
			});

			marker = new google.maps.Marker({
				position: defaultLocation,
				map: map,
				draggable: true
			});

			if( ! ( latitude > 0 ) ) {
				// Try to get the user's geolocation
				if (navigator.geolocation) {
					navigator.geolocation.getCurrentPosition(
						(position) => {
							const userLocation = {
								lat: position.coords.latitude,
								lng: position.coords.longitude,
							};

							// Update map and marker
							map.setCenter(userLocation);
							map.setZoom(15);
							marker.setPosition(userLocation);
							marker.setVisible(true);

							// Get address from geolocation
							reverseGeocode(userLocation);
						},
						(error) => {
							console.warn("Geolocation failed or was denied. Showing global view.");
						}
					);
				} else {
					console.warn("Geolocation is not supported by this browser. Showing global view.");
				}
			} else {
				reverseGeocode(defaultLocation);
			}

			// Initialize autocomplete
			const input = document.getElementById("input_address");
			autocomplete = new google.maps.places.Autocomplete(input);
			autocomplete.bindTo("bounds", map);

			autocomplete.addListener("place_changed", () => {
				const place = autocomplete.getPlace();

				if (!place.geometry || !place.geometry.location) {
					alert("No details available for the input: '" + place.name + "'");
					return;
				}

				map.setCenter(place.geometry.location);
				map.setZoom(15);
				marker.setPosition(place.geometry.location);
				marker.setVisible(true);
				updateDetails(place);
			});

			google.maps.event.addListener(marker, "dragend", function () {
				const position = marker.getPosition();
				reverseGeocode(position);
			});
		}

		// Reverse geocode marker position to get address details
		function reverseGeocode(location) {
			const geocoder = new google.maps.Geocoder();
			geocoder.geocode({ location }, (results, status) => {
				if (status === "OK" && results[0]) {
					updateDetails(results[0]);
				} else {
					addressComponenets = '';
				}
			});
		}

		// Function to update address details
		function updateDetails(place) {
			addressComponenets = '';

			if (place.address_components) {
				place.address_components.forEach((component) => {
					addressComponenets += ( addressComponenets === '' ? '' : '>') + component.long_name;
				});
			}
		}

		initMap();
	});

})(jQuery);
