<?php

it('returns a successful response for the healthcheck endpoint', function () {
    $this->get('/up')->assertOk();
});
