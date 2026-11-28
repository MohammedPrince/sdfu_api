<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignTemplate;
use App\Models\Notification;
use App\Models\ScheduledCampaign;
use App\Services\AdminService;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        // Rows are loaded by the DataTable from data(); only the count is needed here
        $notificationsTotal = Notification::count();

        $campaigns = ScheduledCampaign::query()->latest()->get();
        $templateGroups = CampaignTemplate::query()
            ->orderBy('template_group')
            ->orderBy('sequence')
            ->get()
            ->groupBy('template_group');

        return view('admin.notifications', compact(
            'faculties',
            'batches',
            'notificationsTotal',
            'campaigns',
            'templateGroups'
        ));
    }

    /**
     * Server-side data for the Pushed Notifications DataTable.
     */
    public function data(Request $request): JsonResponse
    {
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));
        $search = trim((string) $request->input('search.value', ''));
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        $result = $this->adminService->getPushedNotificationsTable($search, $start, $length, $direction);

        $data = $result['rows']->map(function (Notification $notification) {

            $user = $notification->user;
            $body = (string) $notification->body;

            return [
                'student' => $user
                    ? '<strong>' . e($user->name ?? 'Student') . '</strong>'
                    : '<span class="notification-deleted-user">Student account removed</span>',

                'student_index' => $user
                    ? '<span class="notification-student-index">' . e($user->stud_index) . '</span>'
                    : '<span class="notification-muted">—</span>',

                'title' => '<strong class="notification-title">' . e($notification->title) . '</strong>',

                'message' => '<span class="notification-body" title="' . e($body) . '">'
                    . e(Str::limit($body, 60)) . '</span>',

                'type' => '<span class="notification-type">' . e(ucfirst($notification->type ?? 'general')) . '</span>',

                'status' => $notification->read_at
                    ? '<span class="notification-status read">Read</span>'
                    : '<span class="notification-status unread">Unread</span>',

                'sent_at' => '<div class="notification-date"><strong>'
                    . $notification->created_at->format('d M Y') . '</strong><span>'
                    . $notification->created_at->format('h:i A') . '</span></div>',
            ];
        })->values();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['filtered'],
            'data' => $data,
        ]);
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
