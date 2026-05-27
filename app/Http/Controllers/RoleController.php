<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function edit()
    {
        return view('auth.role', ['roles' => array_keys(config('signal.roles'))]);
    }

    public function update(Request $request, AuditLogger $audit)
    {
        $validated = $request->validate([
            'professional_role' => ['required', 'in:'.implode(',', array_keys(config('signal.roles')))],
            'company' => ['nullable', 'string', 'max:120'],
        ]);

        $before = $request->user()->only(['professional_role', 'company']);
        $request->user()->forceFill($validated)->save();
        $audit->log($request->user(), 'user.role_selected', $request->user(), $before, $validated);

        return redirect()->route('discovery.index');
    }
}
