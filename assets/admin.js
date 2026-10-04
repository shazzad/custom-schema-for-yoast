( function () {
	'use strict';

	var data = window.csfyData || {};
	var textarea = document.getElementById( 'csfy_json' );
	var button = document.getElementById( 'csfy-insert-template' );
	var main = document.querySelector( 'input[name="csfy_main_entity"]' );
	var modes = document.querySelectorAll( 'input[name="csfy_mode"]' );

	if ( ! textarea ) {
		return;
	}

	function template() {
		var url = data.permalink || 'https://example.com/plugins/example/';
		return {
			'@type': 'SoftwareApplication',
			'@id': url + '#software',
			name: data.title || 'Plugin name',
			description: 'One sentence describing what the plugin does.',
			url: url,
			applicationCategory: 'WordPress plugin',
			operatingSystem: 'WordPress',
			softwareVersion: '1.0.0',
			downloadUrl: 'https://wordpress.org/plugins/example/',
			offers: { '@type': 'Offer', price: '0', priceCurrency: 'USD' },
			aggregateRating: { '@type': 'AggregateRating', ratingValue: '4.7', ratingCount: '94', bestRating: '5' }
		};
	}

	if ( button ) {
		button.addEventListener( 'click', function () {
			if ( textarea.value.trim() !== '' && ! window.confirm( data.confirm || 'Replace the current JSON?' ) ) {
				return;
			}
			textarea.value = JSON.stringify( template(), null, 2 );
			textarea.focus();
		} );
	}

	function syncMainEntity() {
		var replace = document.querySelector( 'input[name="csfy_mode"][value="replace"]' );
		if ( main && replace ) {
			main.disabled = replace.checked;
		}
	}
	Array.prototype.forEach.call( modes, function ( radio ) {
		radio.addEventListener( 'change', syncMainEntity );
	} );
	syncMainEntity();

	// A disabled checkbox is not submitted; re-enable at submit so the stored choice survives a Replace detour.
	var form = textarea.form;
	if ( form && main ) {
		form.addEventListener( 'submit', function () {
			main.disabled = false;
		} );
	}
}() );
