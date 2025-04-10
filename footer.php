		</div>
	</div>

	<div id="footer">
		<div class="jumbotron push-spaces">
			<strong><?php echo $this->lang->line('common_copyrights', date('Y')); ?> · 
			<a href="https://opensourcepos.org" target="_blank"><?php echo $this->lang->line('common_website'); ?></a>  · 
			<?php echo $this->config->item('application_version'); ?> - 
			<a target="_blank" href="https://github.com/opensourcepos/opensourcepos/commit/<?php echo $this->config->item('commit_sha1'); ?>">
				<?php echo substr($this->config->item('commit_sha1'), 0, 6); ?>
			</a></strong>.
		</div>
	</div>

	<script
		defer
		src="https://maps.googleapis.com/maps/api/js?key=<?php echo $this->config->item('google_maps_api_key'); ?>&libraries=places&callback=initializeAddressAutocomplete">
	</script>

	<script>
	function initializeAddressAutocomplete() {
		$(document).on('shown.bs.modal', function () {
			const input = $('#address_1');
			if (!input.length || input.data('autocomplete-bound')) return;
			input.attr('placeholder', 'Enter an Address');

			const container = $('<ul>', {
				id: 'google-autocomplete',
				class: 'dropdown-menu',
				css: {
					position: 'absolute',
					zIndex: 10000,
					backgroundColor: '#fff',
					border: '1px solid #ccc',
					maxHeight: '200px',
					overflowY: 'auto',
					padding: '0',
					margin: '0',
					listStyleType: 'none',
					display: 'none'
				}
			});

			$('body').append(container);

			input.on('input', function () {
				const query = input.val();
				if (!query) return container.hide();

				const service = new google.maps.places.AutocompleteService();
				service.getPlacePredictions({ input: query, types: ['address'] }, function (predictions, status) {
					if (status !== google.maps.places.PlacesServiceStatus.OK || !predictions.length) {
						return container.hide();
					}

					container.empty();

					predictions.forEach(function (prediction) {
						const item = $('<li>').text(prediction.description).css({
							padding: '8px',
							cursor: 'pointer'
						});

						item.on('mousedown', function () {
							container.hide();

							const detailService = new google.maps.places.PlacesService(document.createElement('div'));
							detailService.getDetails({ placeId: prediction.place_id }, function (place, status) {
								if (status === google.maps.places.PlacesServiceStatus.OK) {
									console.log('Google returned:', place);
									console.log('Address components:', place.address_components);

									let street_number = '';
									let route = '';
									const components = {};
									let fallback_city = '';

									for (let i = 0; i < place.address_components.length; i++) {
										const comp = place.address_components[i];
										const types = comp.types;

										if (types.includes('street_number')) {
											street_number = comp.long_name;
										} else if (types.includes('route')) {
											route = comp.long_name;
										}

										if (types.includes('locality')) {
											components.city = comp.long_name;
										} else if (types.includes('postal_town')) {
											components.city = components.city || comp.long_name;
										} else if (types.includes('sublocality') || types.includes('sublocality_level_1')) {
											components.city = components.city || comp.long_name;
										} else if (types.includes('neighborhood')) {
											components.city = components.city || comp.long_name;
										}

										if (!components.city && types.includes('political') && comp.long_name.toLowerCase().includes('township')) {
											fallback_city = comp.long_name;
										}

										if (types.includes('administrative_area_level_1')) {
											components.state = comp.long_name;
										} else if (types.includes('postal_code')) {
											components.postcode = comp.long_name;
										} else if (types.includes('country')) {
											components.country = comp.long_name;
										}
									}

									components.city = components.city || fallback_city;

									const fullStreet = `${street_number} ${route}`.trim();
									$('#address_1').val(fullStreet);
									$('#city').val(components.city || '');
									$('#state').val(components.state || '');
									$('#postcode').val(components.postcode || '');
									$('#country').val(components.country || '');
								}
							});
						});

						container.append(item);
					});

					const offset = input.offset();
					container.css({
						top: offset.top + input.outerHeight(),
						left: offset.left,
						width: input.outerWidth()
					}).show();
				});
			});

			input.on('blur', function () {
				setTimeout(function () {
					container.hide();
				}, 200);
			});

			input.data('autocomplete-bound', true);
		});
	}
	</script>

<script>
$(document).ready(function () {
	const formatPhoneNumber = (input) => {
		let cleaned = input.replace(/\D/g, '').substring(0, 10); // Remove non-digits & limit to 10
		let formatted = '';

		if (cleaned.length > 0) {
			formatted = cleaned.substring(0, 3);
		}
		if (cleaned.length >= 4) {
			formatted += '-' + cleaned.substring(3, 6);
		}
		if (cleaned.length >= 7) {
			formatted += '-' + cleaned.substring(6, 10);
		}

		return formatted;
	};

	// Listen for input on any field with id or class 'phone_number'
	$(document).on('input', '#phone_number', function () {
		const formatted = formatPhoneNumber($(this).val());
		$(this).val(formatted);
	});
});
</script>

</body>
</html>
