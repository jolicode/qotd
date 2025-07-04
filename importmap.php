<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    'chart.js/auto' => [
        'version' => '4.4.9',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.13',
    ],
    'stimulus-carousel' => [
        'version' => '5.0.1',
    ],
    'swiper/bundle' => [
        'version' => '11.2.8',
    ],
    'ssr-window' => [
        'version' => '5.0.0',
    ],
    'dom7' => [
        'version' => '4.0.6',
    ],
    'bootstrap' => [
        'version' => '5.3.6',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.6',
        'type' => 'css',
    ],
    'bootstrap-icons/font/bootstrap-icons.css' => [
        'version' => '1.13.1',
        'type' => 'css',
    ],
    'swiper/swiper-bundle.min.css' => [
        'version' => '11.2.8',
        'type' => 'css',
    ],
    'turbo-view-transitions' => [
        'version' => '0.3.0',
    ],
    'chart.js' => [
        'version' => '4.4.9',
    ],
    '@kurkle/color' => [
        'version' => '0.4.0',
    ],
];
