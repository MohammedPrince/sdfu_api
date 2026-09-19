<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\Request;



class MainController extends Controller
{
    protected $adminService;
    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function index()
    {
        Helper::recordVisitor();

        $visitorCount = Helper::visitorCount();
        $applicationStatus = Helper::checkApplicationStatus();

        return view('home', compact('visitorCount', 'applicationStatus'));
    }

    public function dashboard()
    {

        $studentCount = $this->adminService->countStudents();

        $courseCount = $this->adminService->countCourses();

        $notificationCount = $this->adminService->countNotifications();

        $visitorCount = $this->adminService->getVisitorCounts();

        $applicationOverview = $this->adminService->getApplicationOverview();

        $recentNotifications = $this->adminService->getRecentNotifications();

        return view('admin.dashboard', compact(
            'studentCount',
            'courseCount',
            'notificationCount',
            'visitorCount',
            'applicationOverview',
            'recentNotifications'
        ));
    }

    public function manageApplication(Request $request)
    {

        $settings = null;
        $faculties = $this->adminService->getFaculties();
        $majors = $this->adminService->getMajors();
        $batches = $this->adminService->getBatches();
        $savedSettings = $this->adminService->getSavedSettings();

        $editSetting = null;

        if ($request->filled('edit')) {

            $editSetting = SystemSetting::find(
                $request->integer('edit')
            );
        }

        return view('admin.manage', compact(
            'faculties',
            'majors',
            'batches',
            'settings',
            'savedSettings',
            'editSetting'
        ));
    }

    public function updateApplication(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:system_settings,id'],

            'faculty_code' => ['required', 'string', 'max:50'],
            'major_code' => ['required', 'string', 'max:50'],
            'batch' => ['required', 'string', 'max:50'],
            'semester' => ['required', 'integer', 'min:1', 'max:12'],

            'api_active' => ['required', 'boolean'],
            'fee_active' => ['required', 'boolean'],
            'result_active' => ['required', 'boolean'],
            'timetable_active' => ['required', 'boolean'],
        ]);

        $data = [
            'faculty_code' => $validated['faculty_code'],
            'major_code' => $validated['major_code'],
            'batch' => $validated['batch'],
            'semester' => $validated['semester'],

            'api_active' => $request->boolean('api_active'),
            'fee_active' => $request->boolean('fee_active'),
            'result_active' => $request->boolean('result_active'),
            'timetable_active' => $request->boolean('timetable_active'),
        ];

        /*
        |--------------------------------------------------------------------------
        | Edit existing setting
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['id'])) {

            $setting = SystemSetting::findOrFail($validated['id']);

            $setting->update($data);

            return redirect()
                ->route('admin.manage')
                ->with(
                    'success',
                    'Application settings updated successfully.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Create or update academic setting
        |--------------------------------------------------------------------------
        */

        SystemSetting::updateOrCreate(
            [
                'faculty_code' => $validated['faculty_code'],
                'major_code' => $validated['major_code'],
                'batch' => $validated['batch'],
                'semester' => $validated['semester'],
            ],
            [
                'api_active' => $data['api_active'],
                'fee_active' => $data['fee_active'],
                'result_active' => $data['result_active'],
                'timetable_active' => $data['timetable_active'],
            ]
        );

        return redirect()
            ->route('admin.manage')
            ->with(
                'success',
                'Application settings saved successfully.'
            );
    }

    public function getMajors(string $faculty_code)
    {
        $majors = $this->adminService->getMajorsByFaculty($faculty_code);

        return response()->json($majors);
    }

    //Studnets Start
    public function students(Request $request)
    {

        $filters = [
            'search' => trim($request->input('search', '')),
            'faculty_code' => $request->input('faculty_code'),
            'major_code' => $request->input('major_code'),
            'batch' => $request->input('batch'),
            'semester' => $request->input('semester'),
            'status' => $request->input('status'),
        ];

        $students = $this->adminService->getStudents($filters);
        $faculties = $this->adminService->getFaculties();
        $majors = $this->adminService->getMajors();
        $batches = $this->adminService->getStudentBatches();

        return view('admin.students', compact(
            'students',
            'faculties',
            'majors',
            'batches',
            'filters'
        ));
    }

    public function showStudents(string $studentId)
    {
        $id = base64_decode($studentId, true);

        if ($id === false || !ctype_digit($id)) {
            abort(404);
        }

        $student = User::where('id', (int) $id)->where('role_id', 2)->firstOrFail();

        $student = $this->adminService->getStudentDetails($student);
        return view('admin.student-details', compact('student'));
    }

    public function updateStudentStatus(Request $request, string $studentId)
    {
        $id = base64_decode($studentId, true);

        if ($id === false || !ctype_digit($id)) {
            abort(404);
        }

        $student = User::where('id', (int) $id)->where('role_id', 2)->firstOrFail();
        $isActive = $request->boolean('is_active');

        $this->adminService->updateStudentStatus($student, $isActive);

        return redirect()
            ->route('admin.students.show', [
                'studentId' => $studentId,
            ])
            ->with(
                'success',
                $isActive
                ? 'Student account activated successfully.'
                : 'Student account disabled successfully.'
            );
    }

    public function reports(Request $request)
    {
        $validated = $request->validate([
            'faculty_code' => ['nullable', 'string', 'max:50'],
            'major_code' => ['nullable', 'string', 'max:50'],
            'batch' => ['nullable', 'string', 'max:50'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:12'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $report = $this->adminService->getReports($validated);

        $faculties = $this->adminService->getFaculties();
        $batches = $this->adminService->getBatches();

        $majors = collect();

        if (!empty($validated['faculty_code'])) {
            $majors = $this->adminService->getMajorsByFaculty(
                $validated['faculty_code']
            );
        }

        return view('admin.reports', [
            'report' => $report,
            'faculties' => $faculties,
            'majors' => $majors,
            'batches' => $batches,
            'filters' => $validated,
        ]);
    }
    //Studnets End


}
