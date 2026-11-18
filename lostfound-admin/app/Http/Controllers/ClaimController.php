<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimController extends Controller
{
    public function index(Request $r)
    {
        // ID is a deterministic tie-breaker because MySQL NOW() timestamps can match
        // when multiple app claims are submitted within the same second.
        $q = Claim::with(['item', 'user'])->orderByDesc('created_at')->orderByDesc('id');
        if ($r->filled('status')) {
            $q->where('status', $r->input('status'));
        }
        return view('claims.index', ['claims' => $q->paginate(10)->withQueryString()]);
    }

    public function show(Claim $claim)
    {
        return view('claims.show', ['claim' => $claim->load(['item.category', 'user', 'reviewer'])]);
    }

    public function decide(Request $r, Claim $claim)
    {
        $data = $r->validate(['decision' => 'required|in:approve,reject', 'admin_remarks' => 'nullable|string|max:1000']);
        if ($claim->status !== 'pending') {
            return back()->withErrors(['decision' => 'This claim was already reviewed.']);
        }
        if ($data['decision'] === 'reject' && blank($data['admin_remarks'] ?? null)) {
            return back()->withErrors(['admin_remarks' => 'Give a reason so the user knows what to fix.']);
        }
        if ($data['decision'] === 'approve' && $claim->item->status !== 'listed') {
            return back()->withErrors(['decision' => 'The item is no longer available for claiming.']);
        }

        DB::transaction(function () use ($claim, $data, $r) {
            $approve = $data['decision'] === 'approve';
            $claim->update([
                'status' => $approve ? 'approved' : 'rejected',
                'admin_remarks' => $data['admin_remarks'] ?? null,
                'reviewed_by' => $r->user()->id,
                'reviewed_at' => now(),
            ]);
            if ($approve) {
                $claim->item->update(['status' => 'claimed']);
                Claim::where('item_id', $claim->item_id)->where('status', 'pending')->where('id', '!=', $claim->id)
                    ->update(['status' => 'rejected', 'admin_remarks' => 'Another claim for this item was approved.', 'reviewed_by' => $r->user()->id, 'reviewed_at' => now()]);
            }
            ActivityLog::record('claim_' . $data['decision'], "Claim #{$claim->id} on item #{$claim->item_id}");
        });

        return redirect()->route('claims.show', $claim)->with('ok', 'Decision saved. The user will see it in the app.');
    }
}
