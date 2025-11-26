<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Consumable;
use App\Models\ConsumableLog;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumablesUsageLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_creates_log_and_updates_quantity(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $c = Consumable::create([
            'consumable_name' => 'Toner',
            'current_quantity' => 6,
            'threshold_limit' => 2,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->postJson(route('api.consumables.usage', ['consumable' => $c->id]), [
                'quantity_used' => 2,
                'purpose' => 'Printing',
                'used_by' => 'Admin',
                'date_used' => now()->toDateString(),
                'notes' => 'Monthly report',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('consumables', ['id' => $c->id, 'current_quantity' => 4]);
        $this->assertDatabaseHas('consumable_logs', ['consumable_id' => $c->id, 'action' => 'usage']);
    }
}

