<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;



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

    public function manageTimeTable()
    {

        $faculties = $this->adminService->getFaculties();
        $majors = $this->adminService->getMajors();
        $batches = $this->adminService->getBatches();

        // For TTID, we'll need to get this from somewhere - let's use a default or get from settings
        // For now, we'll pass empty arrays and let the view handle it or we can get from database
        $savedSettings = $this->adminService->getSavedSettings();

        // Load server configuration from file
        $serverConfig = null;
        $configFile = storage_path('app/config/server_config.json');
        if (File::exists($configFile)) {
            $configData = json_decode(File::get($configFile), true);
            if (is_array($configData)) {
                $serverConfig = $configData;
            }
        }

        return view('admin.manage_timetable', compact(
            'faculties',
            'majors',
            'batches',
            'savedSettings',
            'serverConfig'
        ));
    }

    public function fetchTimetable(Request $request)
    {
        $validated = $request->validate([
            'faculty_code' => ['required', 'string', 'max:50'],
            'major_code' => ['required', 'string', 'max:50'],
            'batch' => ['required', 'string', 'max:50'],
            'semester' => ['required', 'integer', 'min:1', 'max:12'],
            'ttid' => ['required', 'integer'],
        ]);

        $result = $this->adminService->syncTimetableData(
            $validated['faculty_code'],
            $validated['major_code'],
            $validated['batch'],
            $validated['semester'],
            $validated['ttid']
        );

        if ($result['success']) {
            return redirect()
                ->back()
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with(
                'error',
                'Failed to synchronize timetable: ' . $result['message']
            );
    }

    public function saveServerConfig(Request $request)
    {
        $validated = $request->validate([
            'server_ip' => [
                'required',
                'regex:/^(?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(?::(?:\d{1,5}))?$/',
            ],
        ]);
        $configData = [
            'server_ip' => $validated['server_ip'],
        ];

        $configDir = storage_path('app/config');
        if (!File::exists($configDir)) {
            File::makeDirectory($configDir, 0755, true);
        }

        File::put($configDir . '/server_config.json', json_encode($configData, JSON_PRETTY_PRINT));

        return redirect()->back()->with('success', 'Server configuration saved successfully!');
    }

    // Helper method to format timetable data for display
    private function formatTimetableForDisplay($timetableData)
    {
        $html = '<div class="timetable-table-responsive">';
        $html .= '<table class="timetable-table">';
        $html .= '<thead><tr>';
        $html .= '<th>Time/Day</th>';

        // Add day headers
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $day) {
            $html .= '<th>' . $day . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        // We'll simplify this for now - in a real implementation, you'd format the data properly
        // For now, we'll just show a success message and raw data
        $html .= '<tr><td colspan="8">';
        $html .= '<pre class="timetable-raw-data">' . print_r($timetableData, true) . '</pre>';
        $html .= '</td></tr>';

        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }
    //Studnets End

}