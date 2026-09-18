<?php

use App\Http\Middleware\ThrottleAdminRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

it('does not execute a failed downstream operation twice', function () {
    $calls = 0;
    try {
        app(ThrottleAdminRequests::class)->handle(Request::create('/admin/test', 'POST'), function () use (&$calls) {
            $calls++;
            throw new RuntimeException('Downstream failure');
        });
        $this->fail('The original error must propagate.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Downstream failure');
    }
    expect($calls)->toBe(1);
});

it('supports Symfony responses without replaying the request', function () {
    $calls = 0;
    $response = app(ThrottleAdminRequests::class)->handle(Request::create('/admin/test', 'POST'), function () use (&$calls) {
        $calls++;
        return new Response('OK');
    });
    expect($calls)->toBe(1);
    expect($response->headers->get('X-RateLimit-Limit'))->toBe('60');
});

it('continues once when only the rate limiter is unavailable', function () {
    RateLimiter::shouldReceive('tooManyAttempts')->once()->andThrow(new RuntimeException('Cache unavailable'));
    $calls = 0;
    app(ThrottleAdminRequests::class)->handle(Request::create('/admin/test', 'POST'), function () use (&$calls) {
        $calls++;
        return new Response('OK');
    });
    expect($calls)->toBe(1);
});
