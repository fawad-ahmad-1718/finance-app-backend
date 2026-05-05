<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()
            ->transactions()
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        // Filters
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $perPage = min((int) ($request->per_page ?? 20), 100);
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
                function ($attr, $value, $fail) use ($request) {
                    $cat = \App\Models\Category::find($value);
                    if ($cat && $cat->user_id !== $request->user()->id) {
                        $fail('Invalid category.');
                    }
                },
            ],
            'type'        => 'required|in:income,expense',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        $transaction = $request->user()->transactions()->create($validated);
        $transaction->load('category');

        return response()->json($transaction, 201);
    }

    public function show(Request $request, Transaction $transaction)
    {
        $this->authorize($request, $transaction);
        return response()->json($transaction->load('category'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorize($request, $transaction);

        $validated = $request->validate([
            'category_id' => [
                'sometimes',
                'exists:categories,id',
                function ($attr, $value, $fail) use ($request) {
                    $cat = \App\Models\Category::find($value);
                    if ($cat && $cat->user_id !== $request->user()->id) {
                        $fail('Invalid category.');
                    }
                },
            ],
            'type'        => 'sometimes|in:income,expense',
            'amount'      => 'sometimes|numeric|min:0.01',
            'date'        => 'sometimes|date',
            'description' => 'nullable|string|max:255',
        ]);

        $transaction->update($validated);
        $transaction->load('category');

        return response()->json($transaction);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        $this->authorize($request, $transaction);
        $transaction->delete();

        return response()->json(null, 204);
    }

    // Dashboard summary
    public function summary(Request $request)
    {
        $query = $request->user()->transactions();

        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $transactions = $query->with('category')->get();

        $income  = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        // Monthly breakdown (last 6 months)
        $monthly = $request->user()
            ->transactions()
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, type, SUM(amount) as total")
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get()
            ->groupBy('month');

        $monthlyData = [];
        foreach ($monthly as $month => $rows) {
            $monthlyData[] = [
                'month'   => $month,
                'income'  => (float) $rows->where('type', 'income')->first()?->total ?? 0,
                'expense' => (float) $rows->where('type', 'expense')->first()?->total ?? 0,
            ];
        }

        // Category breakdown for expenses
        $expenseByCategory = $transactions
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->map(fn ($grp) => [
                'category' => $grp->first()->category,
                'total'    => $grp->sum('amount'),
            ])
            ->values();

        return response()->json([
            'income'              => (float) $income,
            'expense'             => (float) $expense,
            'balance'             => (float) ($income - $expense),
            'monthly'             => $monthlyData,
            'expense_by_category' => $expenseByCategory,
            'transaction_count'   => $transactions->count(),
        ]);
    }

    private function authorize(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403, 'Forbidden');
        }
    }
}
