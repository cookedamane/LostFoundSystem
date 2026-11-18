<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\DepartmentSetting;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimController extends Controller
{
    public function index(Request $r)
    {
        // ID is a deterministic tie-breaker because MySQL NOW() timestamps can match
        // when multiple app claims are submitted within the same second.
        $q = Claim::with(['item', 'user'])->whereHas('item', fn ($items) => $items->whereIn('location', DepartmentSetting::locations()))->orderByDesc('created_at')->orderByDesc('id');
        if ($r->filled('status')) {
            $q->where('status', $r->input('status'));
        }
        return view('claims.index', [
            'claims' => $q->paginate(10)->withQueryString(),
            'hasClaimableFoundItems' => Item::where('type', 'found')->where('status', 'listed')
                ->whereIn('location', DepartmentSetting::locations())->exists(),
        ]);
    }

    public function show(Claim $claim)
    {
        abort_unless(in_array($claim->item->location, DepartmentSetting::locations(), true), 404);
        return view('claims.show', ['claim' => $claim->load(['item.category', 'user', 'reviewer'])]);
    }

    public function decide(Request $r, Claim $claim)
    {
        abort_unless(in_array($claim->item->location, DepartmentSetting::locations(), true), 404);
        $data = $r->validate(['decision' => 'required|in:approve,reject', 'admin_remarks' => 'nullable|string|max:1000']);
        if ($data['decision'] === 'reject' && blank($data['admin_remarks'] ?? null)) {
            return back()->withErrors(['admin_remarks' => 'Give a reason so the user knows what to fix.']);
        }

        $error = DB::transaction(function () use ($claim, $data, $r) {
            // Lock item first, then claim, using the same order for every
            // decision on an item. This prevents conflicting approvals and
            // avoids deadlocks between claims for the same item.
            $item = Item::whereKey($claim->item_id)->lockForUpdate()->firstOrFail();
            $lockedClaim = Claim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            if ($lockedClaim->status !== 'pending') {
                return 'This claim was already reviewed.';
            }
            $approve = $data['decision'] === 'approve';
            if ($approve && $item->status !== 'listed') {
                return 'The item is no longer available for claiming.';
            }
            $lockedClaim->update([
                'status' => $approve ? 'approved' : 'rejected',
                'admin_remarks' => $data['admin_remarks'] ?? null,
                'reviewed_by' => $r->user()->id,
                'reviewed_at' => now(),
            ]);
            if ($approve) {
                $item->update(['status' => 'claimed']);
                Claim::where('item_id', $lockedClaim->item_id)->where('status', 'pending')->where('id', '!=', $lockedClaim->id)
                    ->update(['status' => 'rejected', 'admin_remarks' => 'Another claim for this item was approved.', 'reviewed_by' => $r->user()->id, 'reviewed_at' => now()]);
            }
            ActivityLog::record('claim_' . $data['decision'], "Claim #{$lockedClaim->id} on item #{$lockedClaim->item_id}");
            return null;
        });
        if ($error) {
            return back()->withErrors(['decision' => $error]);
        }

        return redirect()->route('claims.show', $claim)->with('ok', 'Decision saved. The user will see it in the app.');
    }
}
