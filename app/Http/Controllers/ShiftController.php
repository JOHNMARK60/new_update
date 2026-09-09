<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function open(Request $request): RedirectResponse
    {
        $data = $request->validate(['opening_cash' => 'required|numeric|min:0']);
        if (! CashierShift::where('user_id', $request->user()->id)->where('status', 'open')->exists()) {
            CashierShift::create(['user_id' => $request->user()->id, 'opened_at' => now(), 'opening_cash' => $data['opening_cash'], 'status' => 'open']);
        }

        return back()->with('success', 'Shift opened.');
    }

    public function close(Request $request): RedirectResponse
    {
        $data = $request->validate(['closing_cash' => 'nullable|numeric|min:0', 'notes' => 'nullable|max:255']);
        $shift = CashierShift::where('user_id', $request->user()->id)->where('status', 'open')->latest('opened_at')->firstOrFail();
        $shift->update(['closed_at' => now(), 'closing_cash' => $data['closing_cash'] ?? null, 'notes' => $data['notes'] ?? null, 'status' => 'closed']);

        return back()->with('success', 'Shift closed. Complete daily closing when ready.');
    }
}
