<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with('roles');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->latest('id')->paginate(15)->withQueryString();

        return view('admin.employees.index', compact('employees'));
    }

    public function create(): View
    {
        $roles = Role::pluck('name');

        return view('admin.employees.create', compact('roles'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->validated('name'),
                'username' => $request->validated('username'),
                'email' => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
                'need_password_change' => true,
            ]);

            $user->assignRole($request->validated('role'));
        });

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(User $employee): View
    {
        $roles = Role::pluck('name');

        return view('admin.employees.edit', compact('employee', 'roles'));
    }

    public function update(UpdateEmployeeRequest $request, User $employee): RedirectResponse
    {
        if ($employee->id === auth()->id() && $request->validated('role') !== 'Admin') {
            return back()->withErrors(['role' => 'You cannot remove your own Admin role.'])->withInput();
        }

        $data = [
            'name' => $request->validated('name'),
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
            $data['need_password_change'] = true;
        }

        DB::transaction(function () use ($employee, $data, $request) {
            $employee->update($data);
            $employee->syncRoles([$request->validated('role')]);
        });

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        if ($employee->id === auth()->id()) {
            abort(403, 'You cannot delete your own account.');
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
