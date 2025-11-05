<?php

namespace App\Console\Commands;

use App\Models\NotificationJob;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:notifications {--limit=10} {--sleep=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Worker processing the notification jobs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $workerID = uniqid('worker-');
        $this->info("Worker started: $workerID");

        while (true) {
            $jobs = $this->claimJobs((int)$this->option('limit'));
            if (count($jobs) === 0) {
                sleep($this->option('sleep'));
                continue;
            }

            foreach ($jobs as $job) {
                $this->processJob($job);
            }
        }
    }

    private function claimJobs($limit)
    {
        try {
            return DB::transaction(function () use ($limit) {
                $rows = DB::select(
                    "SELECT id
                        FROM notification_jobs
                        WHERE status IN ('PENDING','RETRY')
                        AND next_run_at <= ?
                        ORDER BY next_run_at ASC
                        LIMIT ?
                        FOR UPDATE SKIP LOCKED
                    ",
                    [
                        Carbon::now(),
                        $limit
                    ]
                );

                if (!$rows) {
                    DB::commit();
                    return [];
                }

                $ids = array_column($rows, 'id');

                NotificationJob::whereIn('id', $ids)
                    ->update([
                        'status'     => 'PROCESSING',
                        'updated_at' => now()
                    ]);

                DB::commit();

                return NotificationJob::whereIn('id', $ids)->get();
            });
        } catch (\Exception $e) {
            $this->error("Claim error {$e->getMessage()}");
            return [];
        }
    }

    private function processJob(NotificationJob $job)
    {
        $this->info("Processing job {$job->id} (attempt {$job->attempts})");

        try {
            if (rand(1, 100) <= 70) {
                $job->status = 'SUCCESS';
                $job->processed_at = Carbon::now();
                $job->save();

                cache()->increment('notif_success');
                $this->info("Job {$job->id}: SUCCESS");
                return;
            }
            throw new Exception("Upstream timeout");
        } catch (\Exception $e) {
            $this->retryOrFail($job, $e->getMessage());
        }
    }

    private function retryOrFail(NotificationJob $job, $error)
    {
        $job->attempts++;

        if ($job->attempts >= $job->max_attempts) {
            $job->status = 'FAILED';
            $job->last_error = $error;
            $job->save();

            cache()->increment('notif_failed');
            $this->warn("Job {$job->id}: FAILED after max attempts");
            return;
        }

        $base = pow(2, $job->attempts - 1);
        $jitter = rand(0, (int)($base * 0.3 * 1000)) / 1000;
        $next = $base + $jitter;

        $job->status = 'RETRY';
        $job->next_run_at = Carbon::now()->addSeconds($next);
        $job->last_error = $error;
        $job->save();

        $this->info("Job {$job->id}: RETRY at {$job->next_run_at} (delay {$next}s)");
    }
}
