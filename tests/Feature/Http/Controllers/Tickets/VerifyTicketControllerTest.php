<?php

use App\Models\Ticket;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function () {
  $this->admin = User::factory()->create();
  $this->ticket = Ticket::factory()->create();
  $reference = 'unique_reference-' . rand(10000, 99999);
  $this->requestData = [
    'ticket_id' => $this->ticket->id,
    'reference' => $reference,
    'device_no' => 'device123'
  ];
});

it('returns validation error if required fields are missing', function () {
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), [])
    ->assertJsonValidationErrors(['reference', 'device_no', 'ticket_id']);
});

it('can verify a ticket', function () {
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), $this->requestData)
    ->assertOk()
    ->assertJsonStructure(['success', 'slug', 'data' => ['id']])
    ->assertJson(['success' => true, 'slug' => 'verified']);

  // Assert that the ticket verification record exists in the database
  $this->assertDatabaseHas('ticket_verifications', $this->requestData);
});

it('returns already verified if the ticket is already verified', function () {
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), $this->requestData)
    ->assertOk();

  travelTo(now()->addSeconds(10));

  // Verification still valid if called from same device and within time allowance
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), [
      ...$this->requestData,
      'reference' => 'refA'
    ])
    ->assertOk()
    ->assertJson(['success' => true]);

  // Verification NOT valid if called from different device
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), [
      ...$this->requestData,
      'reference' => 'refB',
      'device_no' => 'device123B'
    ])
    ->assertOk()
    ->assertJson(['success' => false]);

  travelTo(now()->addSeconds(70));

  // Verification NOT valid if time allowance has elapsed
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), [
      ...$this->requestData,
      'reference' => 'refC'
    ])
    ->assertOk()
    ->assertJson(['success' => false]);
});