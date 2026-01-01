<?php

use App\Services\EventIdGenerator;

describe('EventIdGenerator', function () {
    beforeEach(function () {
        $this->generator = new EventIdGenerator();
    });

    it('generates deterministic event id for same input', function () {
        $code = 'GFLOW-TEST-0001';
        $email = 'user@example.com';

        $firstCall = $this->generator->generate($code, $email);
        $secondCall = $this->generator->generate($code, $email);

        expect($firstCall)->toBe($secondCall);
        expect($firstCall)->toStartWith('evt_');
        expect(strlen($firstCall))->toBe(36);
    });

    it('generates different ids for different codes', function () {
        $email = 'user@example.com';

        $firstId = $this->generator->generate('CODE-A', $email);
        $secondId = $this->generator->generate('CODE-B', $email);

        expect($firstId)->not->toBe($secondId);
    });

    it('generates different ids for different emails', function () {
        $code = 'GFLOW-TEST-0001';

        $firstId = $this->generator->generate($code, 'user1@example.com');
        $secondId = $this->generator->generate($code, 'user2@example.com');

        expect($firstId)->not->toBe($secondId);
    });

    it('produces consistent hash format', function () {
        $eventId = $this->generator->generate('TEST', 'test@test.com');

        expect($eventId)->toMatch('/^evt_[a-f0-9]{32}$/');
    });
});
