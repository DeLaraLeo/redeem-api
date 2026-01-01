<?php

use App\DataTransferObjects\WebhookResult;

describe('WebhookResult', function () {
    describe('success factory', function () {
        it('creates successful result with default status 200', function () {
            $result = WebhookResult::success('Operation completed');

            expect($result->success)->toBeTrue();
            expect($result->status)->toBe(200);
            expect($result->message)->toBe('Operation completed');
            expect($result->error)->toBeNull();
        });

        it('creates successful result with custom status', function () {
            $result = WebhookResult::success('Created', 201);

            expect($result->success)->toBeTrue();
            expect($result->status)->toBe(201);
        });
    });

    describe('failure factory', function () {
        it('creates failure result with error details', function () {
            $result = WebhookResult::failure(
                error: 'invalid_signature',
                message: 'Signature validation failed',
                status: 401
            );

            expect($result->success)->toBeFalse();
            expect($result->status)->toBe(401);
            expect($result->error)->toBe('invalid_signature');
            expect($result->message)->toBe('Signature validation failed');
        });

        it('creates failure result for different error types', function () {
            $notFound = WebhookResult::failure('not_found', 'Resource not found', 404);
            $badRequest = WebhookResult::failure('bad_request', 'Invalid input', 400);

            expect($notFound->status)->toBe(404);
            expect($badRequest->status)->toBe(400);
        });
    });
});
