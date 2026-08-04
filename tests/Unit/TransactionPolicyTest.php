<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Transaction;
use App\Modules\Transactions\Policies\TransactionPolicy;
use PHPUnit\Framework\TestCase;

final class TransactionPolicyTest extends TestCase
{
    private function makeTransaction(int $createdBy): Transaction
    {
        $t = new Transaction();
        $t->id = 1;
        $t->createdBy = $createdBy;
        return $t;
    }

    public function testAdminCanAlwaysEditRegardlessOfOwnership(): void
    {
        $transaction = $this->makeTransaction(createdBy: 99);
        $this->assertTrue(TransactionPolicy::canEdit($transaction, userId: 1, roleName: 'admin'));
    }

    public function testAdminCanAlwaysDeleteRegardlessOfOwnership(): void
    {
        $transaction = $this->makeTransaction(createdBy: 99);
        $this->assertTrue(TransactionPolicy::canDelete($transaction, userId: 1, roleName: 'admin'));
    }

    public function testNonOwnerMemberCannotEdit(): void
    {
        $transaction = $this->makeTransaction(createdBy: 99);
        $this->assertFalse(TransactionPolicy::canEdit($transaction, userId: 1, roleName: 'member'));
    }

    public function testNonOwnerMemberCannotDelete(): void
    {
        $transaction = $this->makeTransaction(createdBy: 99);
        $this->assertFalse(TransactionPolicy::canDelete($transaction, userId: 1, roleName: 'member'));
    }

    public function testNonOwnerCannotView(): void
    {
        $transaction = $this->makeTransaction(createdBy: 99);
        $this->assertFalse(TransactionPolicy::canView($transaction, userId: 1, roleName: 'member'));
    }

    public function testOwnerCanView(): void
    {
        $transaction = $this->makeTransaction(createdBy: 1);
        $this->assertTrue(TransactionPolicy::canView($transaction, userId: 1, roleName: 'member'));
    }

    public function testAdminCanViewAnyTransaction(): void
    {
        $transaction = $this->makeTransaction(createdBy: 99);
        $this->assertTrue(TransactionPolicy::canView($transaction, userId: 1, roleName: 'admin'));
    }

    // Note: the "owner AND allow_edit_own is on" case depends on App\Core\SettingsCache,
    // which reads from the database. That path is covered in tests/Feature (requires a
    // configured test database — see tests/Feature/README.md).
}
