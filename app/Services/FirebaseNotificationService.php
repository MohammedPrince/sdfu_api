<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class FirebaseNotificationService
{
    public function __construct(
        private Messaging $messaging
    ) {
    }


    /**
     * Send notification to one student.
     */
    public function sendToStudentIndex(
        string $studentIndex,
        string $title,
        string $body,
        string $type = 'general',
        array $data = []
    ): bool {

        $user = User::where('stud_index', $studentIndex)->where('role_id', 2)->first();

        if (!$user) {
            return false;
        }

        $devices = UserDevice::where('user_id', $user->id)->where('is_active', true)->whereNotNull('fcm_token')->get();

        if ($devices->isEmpty()) {
            return false;
        }

        $this->storeNotification(
            $user,
            $title,
            $body,
            $type,
            $data
        );

        $this->sendToDevices(
            $devices,
            $title,
            $body,
            $type,
            $data
        );

        return true;
    }


    /**
     * Send notification to all students
     * matching academic group.
     */
    public function sendToAcademicGroup(
        string $facultyCode,
        string $majorCode,
        string $batch,
        int $semester,
        string $title,
        string $body,
        string $type = 'general',
        array $data = []
    ): int {

        $users = User::where('role_id', 2)
            ->where('faculty_code', $facultyCode)
            ->where('major_code', $majorCode)
            ->where('batch', $batch)
            ->where('semester', $semester)
            ->get();


        if ($users->isEmpty()) {
            return 0;
        }


        $userIds = $users->pluck('id')->values();

        $devices = UserDevice::whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->whereNotNull('fcm_token')
            ->get();


        if ($devices->isEmpty()) {
            return 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Store notification for every student
        |--------------------------------------------------------------------------
        */

        foreach ($users as $user) {

            Notification::create([

                'user_id' => $user->id,

                'title' => $title,

                'body' => $body,

                'type' => $type,

                'data' => $data,

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Send Firebase notification
        |--------------------------------------------------------------------------
        */

        $this->sendToDevices(
            $devices,
            $title,
            $body,
            $type,
            $data
        );


        return $users->count();
    }


    /**
     * Send Firebase notification to devices.
     */
    private function sendToDevices(
        Collection $devices,
        string $title,
        string $body,
        string $type,
        array $data = []
    ): array {

        $tokens = $devices
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->values()
            ->all();


        if (empty($tokens)) {

            Log::warning('FCM: No device tokens found');

            return [
                'success' => 0,
                'failed' => 0,
            ];
        }


        Log::info('FCM: Sending notification', [
            'token_count' => count($tokens),
            'title' => $title,
            'body' => $body,
        ]);


        $notification = FirebaseNotification::create(
            $title,
            $body
        );


        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData([
                'type' => $type,
                ...$this->stringifyData($data),
            ]);


        try {

            $report = $this->messaging->sendMulticast(
                $message,
                $tokens
            );


            $successCount = $report->successes()->count();

            $failedCount = $report->failures()->count();


            Log::info('FCM: Send completed', [
                'success' => $successCount,
                'failed' => $failedCount,
            ]);


            foreach ($report->failures()->getItems() as $failure) {

                Log::error('FCM: Delivery failed', [
                    'error' => $failure->error()->getMessage(),
                ]);
            }


            foreach ($report->invalidTokens() as $token) {

                UserDevice::where(
                    'fcm_token',
                    $token
                )->update([
                            'is_active' => false,
                        ]);
            }


            return [
                'success' => $successCount,
                'failed' => $failedCount,
            ];


        } catch (\Throwable $e) {

            Log::error('FCM: Exception', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
            ]);


            return [
                'success' => 0,
                'failed' => count($tokens),
            ];
        }
    }

    /**
     * Store notification.
     */
    private function storeNotification(
        User $user,
        string $title,
        string $body,
        string $type,
        array $data
    ): void {

        Notification::create([

            'user_id' => $user->id,

            'title' => $title,

            'body' => $body,

            'type' => $type,

            'data' => $data,

        ]);
    }


    /**
     * Convert FCM data values to strings.
     */
    private function stringifyData(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {

            $result[$key] = is_scalar($value)
                ? (string) $value
                : json_encode($value);
        }

        return $result;
    }

    public function sendTestToken(
        string $token,
        string $title = 'Test Notification',
        string $body = 'Firebase notification test'
    ): bool {
        try {
            $notification = FirebaseNotification::create(
                $title,
                $body
            );

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData([
                    'type' => 'test',
                ]);

            $this->messaging->send($message);

            Log::info('FCM test sent successfully');

            return true;

        } catch (\Throwable $e) {

            Log::error('FCM test failed', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}