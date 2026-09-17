<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
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

        if (!empty($validated['id'])) {

            $setting = SystemSetting::findOrFail($validated['id']);

            $setting->update([
                'faculty_code' => $validated['faculty_code'],
                'major_code' => $validated['major_code'],
                'batch' => $validated['batch'],
                'semester' => $validated['semester'],

                'api_active' => $request->boolean('api_active'),
                'fee_active' => $request->boolean('fee_active'),
                'result_active' => $request->boolean('result_active'),
                'timetable_active' => $request->boolean('timetable_active'),
            ]);

            return redirect()
                ->route('admin.manage')
                ->with('success', 'Application settings updated successfully.');
        }

        SystemSetting::create([
            'faculty_code' => $validated['faculty_code'],
            'major_code' => $validated['major_code'],
            'batch' => $validated['batch'],
            'semester' => $validated['semester'],

            'api_active' => $request->boolean('api_active'),
            'fee_active' => $request->boolean('fee_active'),
            'result_active' => $request->boolean('result_active'),
            'timetable_active' => $request->boolean('timetable_active'),
        ]);

        return redirect()
            ->route('admin.manage')
            ->with('success', 'Application settings saved successfully.');
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

    public function showStudents(User $student)
    {
        abort_unless($student->role_id === 2, 404);

        $student = $this->adminService->getStudentDetails($student);

        return view('admin.students.show', compact('student'));
    }

    public function updateStudentStatus(Request $request, User $student)
    {
        abort_unless($student->role_id === 2, 404);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $student->update([
            'is_active' => (bool) $validated['is_active'],
        ]);

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', 'Student account status updated successfully.');
    }
    //Studnets End


}
