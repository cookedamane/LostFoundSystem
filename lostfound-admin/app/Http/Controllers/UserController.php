<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class UserController extends Controller
{
    private function isAnonymized(User $user): bool
    {
        return str_starts_with($user->email, 'deleted-') && str_ends_with($user->email, '@example.invalid');
    }

    public function index(Request $r)
    {
        $q = User::withCount('claims')->where('email', 'not like', 'deleted-%@example.invalid')->orderBy('role')->orderBy('name');
        if ($r->filled('q')) {
            $t = '%' . $r->input('q') . '%';
            $q->where(fn ($w) => $w->where('name', 'like', $t)->orWhere('email', 'like', $t)->orWhere('student_id', 'like', $t));
        }
        return view('users.index', ['users' => $q->paginate(10)->withQueryString()]);
    }

    // Users register themselves in the mobile app. The web only creates admin/staff accounts.
    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email|max:150|unique:users,email',
            'role' => 'required|in:staff',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $u = User::create($data);
        ActivityLog::record('staff_created', $u->email . ' (' . $u->role . ')');
        return back()->with('ok', 'Staff account created.');
    }

    public function toggle(Request $r, User $user)
    {
        if ($this->isAnonymized($user)) {
            return back()->withErrors(['user' => 'This account has already been deleted.']);
        }
        if ($user->id === $r->user()->id) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }
        $user->update(['is_active' => ! $user->is_active]);
        ActivityLog::record($user->is_active ? 'user_activated' : 'user_deactivated', $user->email);
        return back()->with('ok', $user->is_active ? 'Account activated.' : 'Account deactivated. They can no longer sign in.');
    }

    public function password(Request $r, User $user)
    {
        if ($this->isAnonymized($user)) {
            return back()->withErrors(['user' => 'This account has already been deleted.']);
        }
        $data = $r->validate(['password' => ['required', Password::min(8)->letters()->numbers()]]);
        $user->update($data);
        ActivityLog::record('password_reset', $user->email);
        return back()->with('ok', "Password reset for {$user->name}.");
    }

    public function destroy(Request $r, User $user)
    {
        if ($this->isAnonymized($user)) {
            return back()->withErrors(['user' => 'This account has already been deleted.']);
        }
        if ($user->id === $r->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        if ($user->role === 'admin' && $user->is_active
            && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['user' => 'You cannot delete the last active Administrator account.']);
        }

        DB::transaction(function () use ($user) {
            $user->refresh();
            $formerEmail = $user->email;
            ActivityLog::record('account_deleted', "Account #{$user->id} ({$user->role}) anonymized by Administrator");

            // Revoke app tokens and remove claimant-entered personal details while retaining
            // the item, claim status, decision, and audit trail for office records.
            DB::table('api_tokens')->where('user_id', $user->id)->delete();
            DB::table('activity_logs')->where('details', 'like', '%' . $formerEmail . '%')
                ->update(['details' => '[Personal details erased with account deletion]']);
            $user->claims()->update([
                'proof_of_ownership' => '[Erased with account deletion]',
                'identifying_details' => null,
            ]);
            $user->forceFill([
                'name' => 'Deleted account',
                'email' => "deleted-{$user->id}-" . Str::lower(Str::random(8)) . '@example.invalid',
                'password' => Str::random(64),
                'student_id' => null,
                'phone' => null,
                'is_active' => false,
                'remember_token' => null,
            ])->save();
        });

        return back()->with('ok', 'Account access and personal profile data removed. Related item and claim records remain anonymized for the system history.');
    }
}
