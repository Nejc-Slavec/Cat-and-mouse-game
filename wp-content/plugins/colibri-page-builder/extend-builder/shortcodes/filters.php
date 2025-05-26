<?php


namespace ExtendBuilder;


add_action( 'plugins_loaded', function () {
	require_once __DIR__ . '/wpforms-colibri-template.php';
} );


require_once __DIR__ . '/wpforms-filters.php';
require_once __DIR__ . '/forminator-filters.php';
