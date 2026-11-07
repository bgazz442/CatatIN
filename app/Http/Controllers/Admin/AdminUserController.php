<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['user', 'admin'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->withCount(['tasks', 'goals'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('is_active', $status === 'active'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'filters'));
    }

    public function show(User $user): View
    {
        $user->loadCount(['tasks', 'goals']);
        $user->loadCount(['catatan']);

        return view('admin.users.show', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'is_active' => ['required', 'boolean'],
        ]);

        $isPrimaryAdmin = strcasecmp($request->user()->email, (string) config('admin.email')) === 0;
        abort_if($request->user()->is($user), 403);
        abort_if(($user->role === 'admin' || $validated['role'] === 'admin') && ! $isPrimaryAdmin, 403);

        $user->forceFill($validated)->save();

        return redirect()->route('admin.users.show', $user)->with('success', 'Informasi pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $isPrimaryAdmin = strcasecmp($request->user()->email, (string) config('admin.email')) === 0;
        abort_if($request->user()->is($user), 403);
        abort_if($user->role === 'admin' && ! $isPrimaryAdmin, 403);

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna dan data terkait berhasil dihapus.');
    }
}
