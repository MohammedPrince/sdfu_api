<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{


    public function __construct(

        public AdminService $adminService,
        public FirebaseNotificationService $firebaseNotificationService
    ) {

    }

    public function index()
    {
        $faculties = $this->adminService->getFaculties();
        $batches = $this->adminService->getBatches();
        $notifications = $this->adminService->getPushedNotifications();

        return view('admin.notifications', compact(
            'faculties',
            'batches',
            'notifications'
        ));
    }

    /**
     * Push notification to all students
     * matching academic group.
     */
    public function pushToGroup(Request $request)
    {
        $validated = $request->validate([

            'faculty_code' => [
                'required',
                'string',
                'max:50',
            ],

            'major_code' => [
                'required',
                'string',
                'max:50',
            ],

            'batch' => [
                'required',
                'string',
                'max:50',
            ],

            'semester' => [
                'required',
                'integer',
                'min:1',
                'max:12',
            ],

            'notification_type' => [
                'required',
                'string',
                'max:100',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'body' => [
                'required',
                'string',
                'max:2000',
            ],

        ]);


        $count = $this->firebaseNotificationService->sendToAcademicGroup(

            facultyCode: $validated['faculty_code'],

            majorCode: $validated['major_code'],

            batch: $validated['batch'],

            semester: (int) $validated['semester'],

            notification_type: $validated['notification_type'],

            title: $validated['title'],

            body: $validated['body'],

        );

        return redirect()
            ->route('admin.notifications')
            ->with(
                'success',
                "Notification sent successfully to {$count} student(s)."
            );
    }


    /**
     * Push notification to one student.
     */
    public function pushToOne(Request $request)
    {
        $validated = $request->validate([
            'student_index' => [
                'required',
                'string',
                'max:2000',
            ],

            'notification_type' => [
                'required',
                'string',
                'max:100',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'body' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        // Convert comma-separated indexes into a clean unique array
        $studentIndexes = collect(
            preg_split('/[\s,]+/', $validated['student_index'])
        )
            ->map(fn($index) => trim($index))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($studentIndexes)) {
            return redirect()
                ->route('admin.notifications')
                ->withErrors([
                    'student_index' => 'Please enter at least one student index.',
                ])
                ->withInput();
        }

        $result = $this->firebaseNotificationService->sendToStudentIndexes(
            studentIndexes: $studentIndexes,
            notification_type: $validated['notification_type'],
            title: $validated['title'],
            body: $validated['body'],
        );

        if ($result['sent_students'] === 0) {
            return redirect()
                ->route('admin.notifications')
                ->withErrors([
                    'student_index' =>
                        'No matching students with active registered devices were found.',
                ])
                ->withInput();
        }

        $message = sprintf(
            'Notification sent successfully to %d student(s).',
            $result['sent_students']
        );

        if ($result['not_found'] > 0) {
            $message .= sprintf(
                ' %d student index(es) were not found or have no active device.',
                $result['not_found']
            );
        }

        return redirect()
            ->route('admin.notifications')
            ->with('success', $message);
    }

    public function testFirebase()
    {
        $token = '';

        $sent = $this->firebaseNotificationService->sendTestToken(
            $token,
            'Test from Laravel',
            'Firebase test notification'
        );

        return response()->json([
            'success' => $sent,
        ]);
    }
}
