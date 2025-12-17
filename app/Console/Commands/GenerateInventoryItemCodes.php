<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateInventoryItemCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:generate-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate item codes for existing inventory items that do not have one.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inventoriesToUpdate = Inventory::whereNull('item_code')->orderBy('inventory_accountable')->orderBy('inventory_name')->get();

        if ($inventoriesToUpdate->isEmpty()) {
            $this->info('All inventory items already have an item code.');
            return 0;
        }

        $this->info("Found {$inventoriesToUpdate->count()} inventory items without an item code. Generating now...");

        foreach ($inventoriesToUpdate as $inventory) {
            $this->info("Processing: {$inventory->inventory_accountable} -> {$inventory->inventory_name}");
            $inventory->item_code = Inventory::generateUniqueItemCode($inventory->inventory_name, $inventory->inventory_accountable);
            $inventory->save();
        }

        $this->info('Successfully generated item codes for all applicable inventory items.');
        return 0;
    }
}
