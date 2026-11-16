<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create(['name' => 'Lost and Found Admin', 'email' => 'admin@lostfound.test', 'password' => 'Admin@12345', 'role' => 'admin']);
        $maria = User::create(['name' => 'Maria Santos', 'email' => 'student@lostfound.test', 'password' => 'Student@12345', 'role' => 'user', 'student_id' => '2026-0001', 'phone' => '09171234567']);

        $cat = [];
        foreach (['IDs and Cards', 'Electronics', 'Bags and Wallets', 'Keys', 'Clothing', 'Books and Supplies', 'Others'] as $n) {
            $cat[$n] = Category::create(['name' => $n])->id;
        }

        $found = [
            ['Casio fx-991 calculator', 'Electronics', 'Library, second floor', 'Black scientific calculator with a scratched cover.', 3],
            ['Blue water bottle', 'Others', 'Gymnasium bleachers', 'Blue insulated bottle with stickers.', 5],
            ['Brown leather wallet', 'Bags and Wallets', 'Cafeteria', 'Brown wallet found under a table. Contents are kept by the office.', 2],
            ['Set of keys', 'Keys', 'Room 204', 'Three keys on a red lanyard.', 1],
        ];
        $calc = null;
        foreach ($found as [$name, $c, $loc, $desc, $days]) {
            $i = Item::create(['type' => 'found', 'name' => $name, 'description' => $desc, 'category_id' => $cat[$c], 'location' => $loc,
                'date_event' => now()->subDays($days)->toDateString(), 'status' => 'listed', 'reported_by' => $admin->id]);
            $calc ??= $i;
        }

        Item::create(['type' => 'lost', 'name' => 'Silver USB flash drive', 'description' => '32 GB drive with a blue cap, contains my thesis files.', 'category_id' => $cat['Electronics'],
            'location' => 'Computer laboratory', 'date_event' => now()->subDays(2)->toDateString(), 'status' => 'pending', 'reported_by' => $maria->id]);

        Claim::create(['item_id' => $calc->id, 'user_id' => $maria->id, 'proof_of_ownership' => 'My name is written inside the battery cover.', 'identifying_details' => 'Scratch on the lower left corner.']);
    }
}
