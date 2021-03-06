<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CanRequestFriendshipTest extends TestCase
{

    use RefreshDatabase;
    
    /**
    *   @test
    *   @throws \Throwable
    */
    public function guests_cannot_create_friendship_request() {

        $recipient = factory(User::class)->create();
    
        $response = $this->postJson(route('friendships.store', $recipient));

        $response->assertStatus(401);

    }

    /** 
     * @test
     * @throws \Throwable
     */
    public function can_create_friendship_request() {

        // Variables
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        // Given
        $response = $this->actingAs($sender)->postJson(route('friendships.store', $recipient));

        $response->assertJson([

           'friendship_status' => 'pending'

        ]);
        
        // When
        $this->assertDatabaseHas('friendships', [

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => 'pending'

        ]);

        $this->actingAs($sender)->postJson(route('friendships.store', $recipient));
        $this->assertCount(1, Friendship::all());

    }
    
    /**
    *   @test
    *   @throws \Throwable
    */
    public function a_user_cannot_send_friend_request_to_itself() {

        $sender = factory(User::class)->create();

        // Given
        $this->actingAs($sender)->postJson(route('friendships.store', $sender));

        // When
        $this->assertDatabaseMissing('friendships', [

            'sender_id' => $sender->id,
            'recipient_id' => $sender->id,
            'status' => 'pending'

        ]);
        
    }

    /**
     *   @test
     *   @throws \Throwable
     */
    public function guests_cannot_delete_friendship_request() {

        $recipient = factory(User::class)->create();

        $response = $this->deleteJson(route('friendships.destroy', $recipient));

        $response->assertStatus(401);

    }

    /**
     * @test
     * @throws \Throwable
     */
    public function senders_can_delete_sent_friendship_request() {

        $this->withoutExceptionHandling();

        // Variables
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        Friendship::create([

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,

        ]);

        // Given
        $response = $this->actingAs($sender)->deleteJson(route('friendships.destroy', $recipient));

        $response->assertJson([

            'friendship_status' => 'deleted'

        ]);

        // When
        $this->assertDatabaseMissing('friendships', [

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,

        ]);

    }

    /**
     * @test
     * @throws \Throwable
     */
    public function senders_cannot_delete_denied_friendship_request() {

        // Variables
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        Friendship::create([

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => 'denied'

        ]);

        // Given
        $response = $this->actingAs($sender)->deleteJson(route('friendships.destroy', $recipient));

        $response->assertJson([

            'friendship_status' => 'denied'

        ]);

        // When
        $this->assertDatabaseHas('friendships', [

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => 'denied'

        ]);

    }

    /**
     * @test
     * @throws \Throwable
     */
    public function recipients_can_delete_denied_friendship_request() {

        // Variables
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        Friendship::create([

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => 'denied'

        ]);

        // Given
        $response = $this->actingAs($recipient)->deleteJson(route('friendships.destroy', $sender));

        $response->assertJson([

            'friendship_status' => 'deleted'

        ]);

        // When
        $this->assertDatabaseMissing('friendships', [

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => 'denied'

        ]);

    }

    /**
     * @test
     * @throws \Throwable
     */
    public function recipients_can_delete_received_friendship_request() {

        $this->withoutExceptionHandling();

        // Variables
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        Friendship::create([

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,

        ]);

        // Given
        $response = $this->actingAs($recipient)->deleteJson(route('friendships.destroy', $sender));

        $response->assertJson([

            'friendship_status' => 'deleted'

        ]);

        // When
        $this->assertDatabaseMissing('friendships', [

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,

        ]);

    }

    /**
     * @test
     * @throws \Throwable
     */
    public function can_accept_friendship_request() {

        $this->withoutExceptionHandling();

        // Variables
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        Friendship::create([

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => 'pending'

        ]);

        // Given
        $response = $this->actingAs($recipient)->postJson(route('accept-friendships.store', $sender));

        $response->assertJson([

            'friendship_status' => 'accepted'

        ]);

        // When
        $this->assertDatabaseHas('friendships',
            [

                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'status' => 'accepted'

            ]);

    }

    /**
     *   @test
     *   @throws \Throwable
     */
    public function guests_cannot_accept_friendship_request() {

        $user = factory(User::class)->create();

        $this->postJson(route('accept-friendships.store', $user))->assertStatus(401);

        $this->get(route('accept-friendships.index'))->assertRedirect('login');

    }

    /**
     * @test
     * @throws \Throwable
     */
    public function can_deny_friendship_request() {

        $this->withoutExceptionHandling();

        // Variables
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        Friendship::create([

            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => 'pending'

        ]);

        // Given
        $response = $this->actingAs($recipient)->deleteJson(route('accept-friendships.destroy', $sender));

        $response->assertJson([

            'friendship_status' => 'denied'

        ]);

        // When
        $this->assertDatabaseHas('friendships',
            [

                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'status' => 'denied'

            ]);

    }

    /**
     *   @test
     *   @throws \Throwable
     */
    public function guests_cannot_deny_friendship_request() {

        $user = factory(User::class)->create();

        $response = $this->deleteJson(route('accept-friendships.destroy', $user));

        $response->assertStatus(401);

    }
}
