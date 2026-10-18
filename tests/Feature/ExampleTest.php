<?php

test('site root returns 200 ok directly with zero redirect hops', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertOk();
    $this->assertFalse($response->isRedirect(), 'GET / must return 200 directly without 301/302 redirects');
});

test('static image diet assets exist and meet size thresholds', function () {
    $publicPath = public_path();

    // 1. StarfieldCanvas.webp
    $starfield = $publicPath . DIRECTORY_SEPARATOR . 'StarfieldCanvas.webp';
    expect(file_exists($starfield))->toBeTrue('StarfieldCanvas.webp must exist');
    $starfieldSize = filesize($starfield);
    expect($starfieldSize)->toBeGreaterThan(50 * 1024)
        ->toBeLessThan(150 * 1024); // Target ~90-120 KB

    // 2. logo-pemira.webp
    $logo = $publicPath . DIRECTORY_SEPARATOR . 'logo-pemira.webp';
    expect(file_exists($logo))->toBeTrue('logo-pemira.webp must exist');
    $logoSize = filesize($logo);
    expect($logoSize)->toBeGreaterThan(25 * 1024)
        ->toBeLessThan(55 * 1024); // Target ~35-45 KB

    // 3. Result page assets
    $corner = $publicPath . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'corner-image.png';
    $frameBem = $publicPath . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'frame-bem.webp';
    $frameHima = $publicPath . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'frame-hima.webp';

    expect(file_exists($corner))->toBeTrue('corner-image.png must exist');
    expect(file_exists($frameBem))->toBeTrue('frame-bem.webp must exist');
    expect(file_exists($frameHima))->toBeTrue('frame-hima.webp must exist');
});