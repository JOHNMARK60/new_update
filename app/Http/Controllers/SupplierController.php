<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Supplier::create($request->validate(['name' => 'required|max:150|unique:suppliers,name', 'contact_no' => 'nullable|max:50']));

        return back()->with('success', 'Supplier added.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return back()->with('success', 'Supplier deleted.');
    }
}
