<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterFcmTokenRequest;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    public function getNotifications()
    {
        $result = $this->studentService->getNotifications();

        if ($result['success']) {

            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'notificationsDetails' => $result['notificationsDetails'],
                ],
            ], $result['code']);

        } else {

            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'],
            ], $result['code']);
        }
    }

    //Read One
    public function markNotificationAsRead($notificationId)
    {
        $result = $this->studentService->markNotificationAsRead($notificationId);

        if ($result['success']) {

            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'notificationDetails' => $result['notificationDetails'],
                    'unread_count' => $result['unread_count'],
                ],
            ], $result['code']);

        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
        ], $result['code']);
    }

    //Read All
    public function markAllNotificationsAsRead()
    {
        $result = $this->studentService->markAllNotificationsAsRead();

        if ($result['success']) {

            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'notificationDetails' => $result['notificationDetails'],
                ],
            ], $result['code']);

        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
        ], $result['code']);
    }

    public function registerToken(RegisterFcmTokenRequest $request)
    {

        $result = $this->studentService->registerToken($request);

        if ($result['success']) {

            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
            ], $result['code']);

        } else {

            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'],
            ], $result['code']);
        }
    }

    public function unregisterToken(Request $request): JsonResponse
    {

        $request->validate([
            'token' => [
                'required',
                'string',
                'max:4096',
            ],
        ]);

        $result = $this->studentService->unregisterToken($request);

        if ($result['success']) {

            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
            ], $result['code']);

        } else {

            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'],
            ], $result['code']);
        }
    }
}
