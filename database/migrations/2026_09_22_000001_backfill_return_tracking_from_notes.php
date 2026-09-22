<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('certificate_requests')
            ->whereNull('last_returned_to')
            ->whereIn('status', ['DRAFT', 'WAIT_DVKH', 'PTN_PROCESSING'])
            ->orderBy('id')
            ->chunkById(100, function ($requests) {
                foreach ($requests as $request) {
                    $note = (string) ($request->note ?? '');
                    $tracking = $this->trackingFromNote($request->status, $note);

                    if (!$tracking) {
                        continue;
                    }

                    DB::table('certificate_requests')
                        ->where('id', $request->id)
                        ->update([
                            'last_returned_from' => $tracking['from'],
                            'last_returned_to' => $tracking['to'],
                            'last_return_reason' => $tracking['reason'],
                            'last_returned_at' => $request->updated_at,
                            'updated_at' => $request->updated_at,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Data backfill only. Do not clear return tracking on rollback because
        // the same columns are used by live workflow actions.
    }

    private function trackingFromNote(string $status, string $note): ?array
    {
        if ($status === 'DRAFT') {
            $reason = $this->lastReason($note, '/\[DVKH trả lại\]:\s*(.+?)(?:\R|$)/u');

            return $reason === null ? null : [
                'from' => 'DVKH',
                'to' => 'TRUNG_TAM',
                'reason' => $reason,
            ];
        }

        if ($status === 'PTN_PROCESSING') {
            $reason = $this->lastReason($note, '/\[Trưởng PTN trả lại.+?PTN xử lý lại.+?\]:\s*(.+?)(?:\R|$)/u');

            return $reason === null ? null : [
                'from' => 'TRUONG_PTN',
                'to' => 'PTN',
                'reason' => $reason,
            ];
        }

        if ($status === 'WAIT_DVKH') {
            $ptnReason = $this->lastReason($note, '/\[PTN trả lại DVKH\]:\s*(.+?)(?:\R|$)/u');

            if ($ptnReason !== null) {
                return [
                    'from' => 'PTN',
                    'to' => 'DVKH',
                    'reason' => $ptnReason,
                ];
            }

            $managerReason = $this->lastReason($note, '/\[Trưởng PTN trả lại.+?DVKH.+?\]:\s*(.+?)(?:\R|$)/u');

            if ($managerReason !== null) {
                return [
                    'from' => 'TRUONG_PTN',
                    'to' => 'DVKH',
                    'reason' => $managerReason,
                ];
            }
        }

        return null;
    }

    private function lastReason(string $text, string $pattern): ?string
    {
        if (!preg_match_all($pattern, $text, $matches) || empty($matches[1])) {
            return null;
        }

        $reason = trim((string) end($matches[1]));

        return $reason === '' ? null : $reason;
    }
};
