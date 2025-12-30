<?php

namespace App\Console\Commands;

use App\Domain\GiftCode;
use App\Enums\GiftCodeStatus;
use App\Repositories\GiftCodeRepositoryInterface;
use App\Repositories\RedemptionRepositoryInterface;
use App\Repositories\ReceivedWebhookRepositoryInterface;
use Illuminate\Console\Command;

class GiftFlowSeedCommand extends Command
{
    protected $signature = 'giftflow:seed {--fresh : Clear existing data before seeding}';

    protected $description = 'Seed the gift codes for GiftFlow platform';

    public function __construct(
        private GiftCodeRepositoryInterface $giftCodeRepository,
        private RedemptionRepositoryInterface $redemptionRepository,
        private ReceivedWebhookRepositoryInterface $receivedWebhookRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->info('Clearing existing data...');
            $this->giftCodeRepository->clear();
            $this->redemptionRepository->clear();
            $this->receivedWebhookRepository->clear();
        }

        $this->info('Seeding gift codes...');

        $giftCodes = [
            new GiftCode(
                code: 'GFLOW-TEST-0001',
                status: GiftCodeStatus::Available,
                productId: 'product_abc',
                creatorId: 'creator_123',
            ),
            new GiftCode(
                code: 'GFLOW-TEST-0002',
                status: GiftCodeStatus::Available,
                productId: 'product_def',
                creatorId: 'creator_456',
            ),
            new GiftCode(
                code: 'GFLOW-USED-0003',
                status: GiftCodeStatus::Redeemed,
                productId: 'product_ghi',
                creatorId: 'creator_789',
            ),
        ];

        foreach ($giftCodes as $giftCode) {
            $this->giftCodeRepository->save($giftCode);
            $status = $giftCode->status === GiftCodeStatus::Available ? 'available' : 'redeemed';
            $this->line("  ✓ {$giftCode->code} ({$status})");
        }

        $this->newLine();
        $this->info('Gift codes seeded successfully!');
        $this->newLine();
        $this->table(
            ['Code', 'Status', 'Product ID', 'Creator ID'],
            array_map(fn($gc) => [
                $gc->code,
                $gc->status->value,
                $gc->productId,
                $gc->creatorId,
            ], $giftCodes)
        );

        return Command::SUCCESS;
    }
}
