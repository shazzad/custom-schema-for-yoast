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

	// Live status: the block editor saves the box via fetch and never re-renders the PHP panel.
	var statusLine = document.createElement( 'p' );
	statusLine.className = 'csfy-live-status';
	var anchor = textarea.parentNode;
	anchor.parentNode.insertBefore( statusLine, anchor.nextSibling );

	function setStatus( text, ok ) {
		statusLine.textContent = text;
		statusLine.className = 'csfy-live-status' + ( text ? ( ok ? ' is-ok' : ' is-error' ) : '' );
	}

	function checkJson() {
		var raw = textarea.value.replace( /^\uFEFF/, '' ).trim();
		var parsed, nodes, i;
		if ( raw === '' ) {
			setStatus( '', true );
			return;
		}
		try {
			parsed = JSON.parse( raw );
		} catch ( e ) {
			setStatus( 'Invalid JSON: ' + e.message, false );
			return;
		}
		if ( parsed === null || typeof parsed !== 'object' ) {
			setStatus( 'Expected an object or an array of objects.', false );
			return;
		}
		if ( Array.isArray( parsed ) ) {
			nodes = parsed;
		} else if ( Array.isArray( parsed[ '@graph' ] ) ) {
			nodes = parsed[ '@graph' ];
		} else {
			nodes = [ parsed ];
		}
		for ( i = 0; i < nodes.length; i++ ) {
			if ( nodes[ i ] === null || typeof nodes[ i ] !== 'object' || Array.isArray( nodes[ i ] ) ) {
				setStatus( 'Node ' + ( i + 1 ) + ' is not an object.', false );
				return;
			}
			if ( ! nodes[ i ][ '@type' ] ) {
				setStatus( 'Node ' + ( i + 1 ) + ' has no "@type".', false );
				return;
			}
		}
		setStatus( 'Valid JSON \u2014 ' + nodes.length + ' node(s)', true );
	}

	var timer = null;
	textarea.addEventListener( 'input', function () {
		window.clearTimeout( timer );
		timer = window.setTimeout( checkJson, 300 );
	} );
	checkJson();

	function template() {
		var url = data.permalink ? data.permalink : 'https://example.com/plugins/example/';
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
			checkJson();
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
