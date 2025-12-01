<?php

namespace Tests\Feature;

use App\Models\Consumable;
use App\Models\ConsumableUsage;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumablesRecycleBinTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_delete_moves_to_deleted_consumables_and_logs(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $c = Consumable::create([
            'consumable_name' => 'Ink',
            'consumable_description' => 'Black ink',
            'consumable_brand' => 'HP',
            'current_quantity' => 10,
            'threshold_limit' => 2,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('api.consumables.destroy', ['consumable' => $c->id]))
            ->assertOk();

        $this->assertSoftDeleted('consumables', ['id' => $c->id]);
        $this->assertDatabaseHas('deleted_consumables', [
            'consumable_id' => $c->id,
            'consumable_name' => 'Ink',
            'deleted_by' => $user->id,
            'restore_status' => false,
        ]);
    }

    public function test_restore_from_deleted_consumables(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $c = Consumable::create([
            'consumable_name' => 'Paper',
            'current_quantity' => 50,
            'threshold_limit' => 10,
            'unit' => 'ream',
        ]);
        $this->actingAs($user)->deleteJson(route('api.consumables.destroy', ['consumable' => $c->id]))->assertOk();

        $this->actingAs($user)
            ->postJson(route('api.consumables.restore', ['id' => $c->id]))
            ->assertOk();

        $this->assertDatabaseMissing('deleted_consumables', [
            'consumable_id' => $c->id,
        ]);
        $this->assertDatabaseHas('consumables', ['id' => $c->id, 'deleted_at' => null]);
    }

    public function test_force_delete_removes_consumable_but_keeps_usages_history(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $c = Consumable::create([
            'consumable_name' => 'Cable',
            'current_quantity' => 5,
            'threshold_limit' => 1,
            'unit' => 'pcs',
        ]);
        ConsumableUsage::create([
            'consumable_id' => $c->id,
            'quantity_used' => 1,
            'purpose' => 'Setup',
            'used_by' => 'IT',
            'date_used' => now()->toDateString(),
        ]);

        $this->actingAs($user)->deleteJson(route('api.consumables.destroy', ['consumable' => $c->id]))->assertOk();

        $this->actingAs($user)
            ->deleteJson(route('api.consumables.force-destroy', ['id' => $c->id]))
            ->assertOk();

        $this->assertDatabaseMissing('consumables', ['id' => $c->id]);
        $this->assertDatabaseMissing('deleted_consumables', ['consumable_id' => $c->id]);
        $this->assertDatabaseHas('consumable_usages', ['quantity_used' => 1]);
        $this->assertTrue(\DB::table('consumable_usages')->whereNull('consumable_id')->exists());
    }
}
