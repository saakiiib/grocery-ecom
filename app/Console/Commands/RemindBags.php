<?php

namespace App\Console\Commands;

use App\Mail\BagReminder;
use App\Models\BagSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RemindBags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remind-bags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email signed-in shoppers whose bag sat untouched 24h (add to cron daily).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sent = 0;
        $stale = BagSnapshot::with('user')
            ->whereNull('reminded_at')
            ->where('updated_at', '<=', now()->subDay())
            ->get()
            ->filter(fn ($snap) => $snap->user && $snap->user->email && $snap->lines !== [] && (float) $snap->subtotal > 0);
        foreach ($stale as $snap) {
            // Ordered since? A fresh order means the bag already converted.
            $ordered = $snap->user->orders()->where('created_at', '>=', $snap->updated_at)->exists();
            if ($ordered) {
                $snap->delete();

                continue;
            }
            try {
                Mail::to($snap->user->email)->send(new BagReminder($snap));
                $snap->update(['reminded_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $this->info("Bag reminders sent: {$sent}.");

        return self::SUCCESS;
    }
}
