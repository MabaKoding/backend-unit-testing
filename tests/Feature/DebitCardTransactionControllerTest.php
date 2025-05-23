<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        DebitCardTransaction::factory()->count(3)->create(['debit_card_id' => $this->debitCard->id]);
        $response = $this->getJson("/api/debit-card-transactions?debit_card_id={$this->debitCard->id}");
        $response->assertOk()->assertJsonCount(3);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        DebitCardTransaction::factory()->count(2)->create(['debit_card_id' => $otherCard->id]);

        $response = $this->getJson("/api/debit-card-transactions?debit_card_id={$otherCard->id}");
        $response->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $response = $this->postJson("/api/debit-card-transactions",
        [
            'debit_card_id' => $this->debitCard->id, 
            'amount' => 15000000, 
            'currency_code' => "IDR"
        ]);
        
        $response->assertCreated()->assertJsonFragment(['amount' => 15000000, 'currency_code' => "IDR"]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson("/api/debit-card-transactions",
        [
            'debit_card_id' => $otherCard->id, 
            'amount' => 10000, 
            'currency_code' => "SGD"
        ]);

        $response->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $this->debitCard->id]);
        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");
        
        $this->assertDatabaseHas('debit_card_transactions', [
            'id' => $transaction->id
        ]);
        
        $response->assertOk();
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $otherCard->id]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
