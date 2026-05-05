<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo user
        $user = User::create([
            'name'     => 'Alex Johnson',
            'email'    => 'demo@finance.test',
            'password' => Hash::make('password'),
            'currency' => 'USD',
        ]);

        // Categories
        $cats = [
            ['name' => 'Salary',        'type' => 'income',  'icon' => '💼', 'color' => '#10b981'],
            ['name' => 'Freelance',     'type' => 'income',  'icon' => '💻', 'color' => '#6366f1'],
            ['name' => 'Investments',   'type' => 'income',  'icon' => '📈', 'color' => '#f59e0b'],
            ['name' => 'Food',          'type' => 'expense', 'icon' => '🍔', 'color' => '#ef4444'],
            ['name' => 'Transport',     'type' => 'expense', 'icon' => '🚗', 'color' => '#f97316'],
            ['name' => 'Utilities',     'type' => 'expense', 'icon' => '💡', 'color' => '#eab308'],
            ['name' => 'Rent',          'type' => 'expense', 'icon' => '🏠', 'color' => '#8b5cf6'],
            ['name' => 'Entertainment', 'type' => 'expense', 'icon' => '🎬', 'color' => '#ec4899'],
            ['name' => 'Healthcare',    'type' => 'expense', 'icon' => '🏥', 'color' => '#14b8a6'],
            ['name' => 'Shopping',      'type' => 'expense', 'icon' => '🛍️', 'color' => '#06b6d4'],
        ];

        $created = [];
        foreach ($cats as $cat) {
            $created[$cat['name']] = Category::create(array_merge($cat, ['user_id' => $user->id]));
        }

        // Generate 6 months of sample transactions
        for ($m = 5; $m >= 0; $m--) {
            $month = Carbon::now()->subMonths($m);

            // Income
            Transaction::create([
                'user_id'     => $user->id,
                'category_id' => $created['Salary']->id,
                'type'        => 'income',
                'amount'      => 5500,
                'date'        => $month->copy()->day(1)->format('Y-m-d'),
                'description' => 'Monthly salary',
            ]);

            if (rand(0, 1)) {
                Transaction::create([
                    'user_id'     => $user->id,
                    'category_id' => $created['Freelance']->id,
                    'type'        => 'income',
                    'amount'      => rand(300, 1200),
                    'date'        => $month->copy()->day(15)->format('Y-m-d'),
                    'description' => 'Freelance project',
                ]);
            }

            // Expenses
            Transaction::create([
                'user_id'     => $user->id,
                'category_id' => $created['Rent']->id,
                'type'        => 'expense',
                'amount'      => 1400,
                'date'        => $month->copy()->day(2)->format('Y-m-d'),
                'description' => 'Monthly rent',
            ]);

            // Random expenses
            $expenseCats = ['Food', 'Transport', 'Utilities', 'Entertainment', 'Shopping', 'Healthcare'];
            $txCount = rand(6, 14);
            for ($i = 0; $i < $txCount; $i++) {
                $catName = $expenseCats[array_rand($expenseCats)];
                Transaction::create([
                    'user_id'     => $user->id,
                    'category_id' => $created[$catName]->id,
                    'type'        => 'expense',
                    'amount'      => match ($catName) {
                        'Food'          => rand(10, 80),
                        'Transport'     => rand(20, 100),
                        'Utilities'     => rand(50, 200),
                        'Entertainment' => rand(15, 120),
                        'Shopping'      => rand(30, 250),
                        'Healthcare'    => rand(20, 150),
                        default         => rand(20, 100),
                    },
                    'date'        => $month->copy()->day(rand(1, 28))->format('Y-m-d'),
                    'description' => match ($catName) {
                        'Food'          => ['Groceries', 'Restaurant', 'Coffee shop', 'Takeout'][rand(0, 3)],
                        'Transport'     => ['Gas', 'Uber', 'Bus pass', 'Parking'][rand(0, 3)],
                        'Utilities'     => ['Electricity', 'Internet', 'Water bill', 'Phone plan'][rand(0, 3)],
                        'Entertainment' => ['Netflix', 'Movie tickets', 'Concert', 'Games'][rand(0, 3)],
                        'Shopping'      => ['Clothes', 'Amazon order', 'Electronics', 'Home goods'][rand(0, 3)],
                        'Healthcare'    => ['Doctor visit', 'Pharmacy', 'Gym membership', 'Vitamins'][rand(0, 3)],
                        default         => 'Misc expense',
                    },
                ]);
            }
        }

        $this->command->info('✅ Demo user seeded: demo@finance.test / password');
    }
}
