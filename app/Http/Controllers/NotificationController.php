<?php

namespace App\Http\Controllers;

use App\Models\NotificationJob;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function enqueue(Request $request)
    {
        $payload = $request->all();
        $rules = [
            'recipient' => 'required|email',
            'channel' => 'required|in:email,sms',
            'message' => 'required|string',
            'idempotency_key' => 'nullable|string',
        ];
        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($payload['idempotency_key']) {
            $existing = NotificationJob::where('idempotency_key', $payload['idempotency_key'])->first();
            if ($existing) {
                $response = [
                    'job_id' => $existing->id,
                    'status' => $existing->status,
                    'attempts' => $existing->attempts,
                ];

                if ($existing->status === 'RETRY') $response['next_run_at'] = $existing->next_run_at;
                if ($existing->status === 'FAILED') $response['last_error'] = $existing->last_error;

                return response()->json($response, 200);
            }
        }

        $createInput = [
            'recipient' => $request->recipient,
            'channel' => $request->channel,
            'message' => $request->message,
            'idempotency_key' => $request->idempotency_key,
            'status' => 'PENDING',
            'attempts' => 0,
            'max_attempts' => 5,
            'next_run_at' => Carbon::now(),
        ];
        try {
            $job = NotificationJob::create($createInput);
            if (empty($job->id)) {
                throw new Exception("Create Notif Failed", 500);
            }
            cache()->increment('notif_queued');
            return response()->json([
                'job_id' => $job->id,
                'status' => $job->status
            ], 201);
        } catch (\Exception $e) {
            return response()->json($e->getMessage() ?? "Unknown Error Occured", $e->getCode() ?? 500);
        }
    }
}
