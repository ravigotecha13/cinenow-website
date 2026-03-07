<?php

namespace Modules\Frontend\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PpvWatchController
{
    /**
     * AUTHORITATIVE access check:
     * - if no active ticket => purchase_required
     * - if active ticket exists => ok + resume time + show_repurchase after 25%
     */
    public function checkAccess(Request $request)
    {
        $userId = auth()->id() ?? auth('sanctum')->id();
        if (!$userId) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        $entertainmentId = (int) $request->entertainment_id;

        $ticket = DB::table('ppv_tickets')
            ->where('user_id', $userId)
            ->where('entertainment_id', $entertainmentId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        if (!$ticket) {
            $hasHistory = DB::table('ppv_tickets')
                ->where('user_id', $userId)
                ->where('entertainment_id', $entertainmentId)
                ->exists();

            return response()->json([
                'status' => 'purchase_required',
                'is_repurchase' => $hasHistory
            ]);
        }

        $progress = DB::table('watch_progress')
            ->where('ticket_id', $ticket->id)
            ->first();

        $watchedPercent = (int)($progress->watched_percentage ?? 0);
        $resumeSeconds  = (int)($progress->last_time_seconds ?? 0);

        // If completed on this ticket, it should NOT be watchable
        if ($watchedPercent >= 98 || !empty($progress->completed_at)) {
            return response()->json([
                'status' => 'consumed_or_completed'
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'ticket_id' => $ticket->id,
            'resume_time' => $resumeSeconds,
            'watched_percentage' => $watchedPercent,
            'show_repurchase' => $watchedPercent >= 25,
        ]);
    }

    /**
     * Save progress tied to ACTIVE ticket only.
     * If no active ticket => expired (frontend should show purchase)
     */
    public function saveProgress(Request $request)
    {
        $userId = auth()->id() ?? auth('sanctum')->id();
        if (!$userId) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        $entertainmentId = (int) $request->entertainment_id;

        $ticket = DB::table('ppv_tickets')
            ->where('user_id', $userId)
            ->where('entertainment_id', $entertainmentId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        if (!$ticket) {
            return response()->json(['status' => 'expired']);
        }

        $lastSeconds = max(0, (int) $request->last_time_seconds);
        $percent     = min(100, max(0, (int) $request->watched_percentage));

        // If already completed, do not allow updating (prevents “un-completing”)
        $existing = DB::table('watch_progress')
            ->where('ticket_id', $ticket->id)
            ->first();

        if ($existing && ((int)$existing->watched_percentage >= 98 || !empty($existing->completed_at))) {
            return response()->json(['status' => 'completed']);
        }

        // Upsert on ticket_id (1 progress row per ticket)
        DB::table('watch_progress')->updateOrInsert(
            ['ticket_id' => $ticket->id],
            [
                'user_id' => $userId,
                'entertainment_id' => $entertainmentId,
                'entertainment_type' => $request->entertainment_type ?? 'movie',
                'last_time_seconds' => $lastSeconds,
                // keep max to prevent moving backwards cheating
                'watched_percentage' => $existing ? max((int)$existing->watched_percentage, $percent) : $percent,
                'updated_at' => now(),
            ]
        );

        // Auto-consume if crosses completion threshold (98%)
        if ($percent >= 98) {
            $this->consumeTicketInternal($userId, $entertainmentId);
            return response()->json(['status' => 'completed', 'show_repurchase' => true]);
        }

        return response()->json([
            'status' => 'ok',
            'show_repurchase' => $percent >= 25
        ]);
    }

    /**
     * Consume ticket - called on ended and also auto-consumed from saveProgress >=98%
     */
    public function consumeTicket(Request $request)
    {
        $userId = auth()->id() ?? auth('sanctum')->id();
        if (!$userId) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        $entertainmentId = (int) $request->entertainment_id;

        $this->consumeTicketInternal($userId, $entertainmentId);

        return response()->json(['status' => 'ok']);
    }

    private function consumeTicketInternal(int $userId, int $entertainmentId): void
    {
        DB::transaction(function () use ($userId, $entertainmentId) {
            // lock active ticket
            $ticket = DB::table('ppv_tickets')
                ->where('user_id', $userId)
                ->where('entertainment_id', $entertainmentId)
                ->where('status', 'active')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (!$ticket) return;

            DB::table('ppv_tickets')
                ->where('id', $ticket->id)
                ->update([
                    'status' => 'consumed',
                    'consumed_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('watch_progress')
                ->where('ticket_id', $ticket->id)
                ->update([
                    'watched_percentage' => 100,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
        });
    }
}
