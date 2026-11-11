<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use App\Notifications\NewReportNotice;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Reports to the platform's team, which the Assistant drafts in someone's words and they send
 * with a tap: sent as it was shown (its words come from the message, never from the browser),
 * each once, a few a day. The owner hears of each by email, and answers by replying.
 */
class ReportService
{
    /**
     * The report one of the Assistant's buttons offers, sent (or found sent already)
     *
     * @return bool false when they sent all they may today
     *
     * @throws ModelNotFoundException when that button offers no report
     */
    public function send(Message $message, int $action, User $by): bool
    {
        return DB::transaction(function () use ($message, $action, $by) {
            // Held: a second tap waits, and finds it sent
            $message = Message::whereKey($message->id)->lockForUpdate()->firstOrFail();
            $offered = $message->extras['actions'][$action] ?? null;
            if (($offered['type'] ?? null) !== 'report') {
                throw (new ModelNotFoundException())->setModel(Message::class, [$message->id]);
            }

            if (! empty($offered['sent'])) {
                return true;
            }
            if (Report::leftToday($by) === 0) {
                return false;
            }

            $report = Report::create(['user_id' => $by->id, 'project_id' => $message->conversation->project_id, 'body' => $offered['message']]);
            DB::afterCommit(fn () => User::where('is_owner', true)->first()?->notify(new NewReportNotice($report)));
            $extras = $message->extras;
            $extras['actions'][$action]['sent'] = true;
            $message->forceFill(['extras' => $extras])->save();

            return true;
        });
    }
}
