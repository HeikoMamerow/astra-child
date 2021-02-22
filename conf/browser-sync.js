#!/usr/bin/env node

const bs = require( 'browser-sync' ).create();

bs.init( {
	proxy: {
		target: 'https://demo2.meditieren-lernen.test',
	},
	url: 'https://localhost:3000',
	https: true,
	files: [ 'astra-child/**/*' ],
	notify: false,
	open: false,
	reloadOnRestart: true,
} );
