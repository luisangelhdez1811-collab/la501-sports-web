<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Carbon\Carbon;

class FinanceController extends Controller
{
    public function index()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $transactions = Transaction::orderBy('transaction_date', 'desc')
                                   ->orderBy('created_at', 'desc')
                                   ->get();

        $income = Transaction::where('type', 'income')
                    ->whereMonth('transaction_date', $currentMonth)
                    ->whereYear('transaction_date', $currentYear)
                    ->sum('amount');

        $expense = Transaction::where('type', 'expense')
                    ->whereMonth('transaction_date', $currentMonth)
                    ->whereYear('transaction_date', $currentYear)
                    ->sum('amount');

        $balance = $income - $expense;

        return view('admin.finances.index', compact('transactions', 'income', 'expense', 'balance'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'transaction_date' => 'required|date',
        ]);

        Transaction::create($request->all());

        return back()->with('success', 'Movimiento registrado correctamente.');
    }

    public function destroy($id)
    {
        Transaction::destroy($id);
        return back()->with('success', 'Movimiento eliminado del registro.');
    }
}
