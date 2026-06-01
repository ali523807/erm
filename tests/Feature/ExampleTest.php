<?php

it('renders the public landing page', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Equipment Rental Management');
});
