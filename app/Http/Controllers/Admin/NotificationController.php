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
                'max:100',
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


        $sent = $this->firebaseNotificationService->sendToStudentIndex(

            studentIndex: $validated['student_index'],

            notification_type: $validated['notification_type'],

            title: $validated['title'],

            body: $validated['body'],

        );


        if (!$sent) {

            return redirect()
                ->route('admin.notifications')
                ->withErrors([
                    'student_index' =>
                        'Student not found or no active device is registered for this student.',
                ])
                ->withInput();
        }


        return redirect()
            ->route('admin.notifications')
            ->with(
                'success',
                'Notification sent successfully to the student.'
            );
    }

    public function testFirebase()
    {
        $token = 'ctlbPtcVSIuiZ1s_DkHUlj:APA91bGX7mYfc7ZsSe5KJ_OHiDq6inTEQF_MK7ok6IuzAarFj20nsn50Wb1KgPyU5WcwbE2a6SiAK2_WtskUYS3K41DaCt_bFZL7FY9xct9EDLx6WDKmu68';

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
