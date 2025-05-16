<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        DebitCard::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/debit-cards');

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id
        ]);

        $response->assertOk();
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $otherUser = User::factory()->create();
        $otherUserCards = DebitCard::factory()->count(3)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/debit-cards');
        $response->assertOk();

        $returnedCardIds = collect($response->json())->pluck('id')->all();

        foreach ($otherUserCards as $card) {
            $this->assertNotContains($card->id, $returnedCardIds);
        }
    }

    public function testCustomerCanCreateADebitCard()
    {   
        // post /debit-cards
        $response = $this->postJson('/api/debit-cards', ['type' => 'Visa']);
        
        $this->assertDatabaseHas('debit_cards', [
            'type' => 'Visa'
        ]);

        $response->assertStatus(201);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id
        ]);

        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $otherUserCard = DebitCard::factory()->create();
        $response = $this->getJson("/api/debit-cards/{$otherUserCard->id}");
        $response->assertForbidden();
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $response  = $this->putJson("/api/debit-cards/{$debitCard->id}",['is_active' => true]);

        $response->assertOk();
        $this->assertTrue($debitCard->fresh()->is_active);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $response  = $this->putJson("/api/debit-cards/{$debitCard->id}",['is_active' => false]);

        $response->assertOk();
        $this->assertFalse($debitCard->fresh()->is_active);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $response = $this->putJson("/api/debit-cards/{$debitCard->id}",['is_active' => 'Yes This Is Must Be Activated']);

        $response->assertStatus(422)->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $user = User::factory()->create();
        $p = Passport::actingAs($user);
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);
        $response  = $this->actingAs($p)->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertNoContent();
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $user = User::factory()->create();
        $p = Passport::actingAs($user);
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id,
        ]);

        $response = $this->actingAs($p)->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id]);
    }

    // Extra bonus for extra tests :)
}
