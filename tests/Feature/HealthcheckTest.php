<?php

it('returns a successful response for the healthcheck endpoint', function () {
    $this->get('/up')
        ->assertOk()
        ->assertExactJson([
            'success' => true,
        ]);
});

it('returns a successful response for the database diagnostic endpoint', function () {
    $this->get('/up/database')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'database' => 'ok',
            'connection' => 'sqlite',
        ]);
});
