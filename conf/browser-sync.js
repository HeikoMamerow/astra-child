#!/usr/bin/env node

const bs = require( 'browser-sync' ).create();

bs.init( {
	proxy: {
		target: 'https://meditieren-lernen.local',
	},
	url: 'https://localhost:3000',
	https: true,
	files: [ 'astra-child/**/*' ],
	notify: false,
	open: false,
	reloadOnRestart: true,
} );
