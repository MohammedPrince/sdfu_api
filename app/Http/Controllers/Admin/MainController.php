<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;


class MainController extends Controller
{
    protected $adminService;
    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function testConnection()
    {
        $url = 'http://41.41.217.230/ott/api/index.php?faculty_code=2&major_code=2&batch=2022&semester=1&ttid=40';

        try {

            $response = Http::acceptJson()
                ->connectTimeout(15)
                ->timeout(30)
                ->get($url, [
                    'faculty_code' => 3,
                    'major_code' => 3,
                    'batch' => 2023,
                    'semester' => 2,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Connection successful',
                'http_code' => $response->status(),
                'data' => $response->json(),
            ], $response->status());

        } catch (\Illuminate\Http\Client\ConnectionException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Connection failed',
                'error' => $e->getMessage(),
            ], 500);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Request failed',
                'error' => $e->getMessage(),
            ], 500);
        }
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

            $timetableData = $result['timetableData'] ?? [];

            $timetableHtml = $this->formatTimetableForDisplay(
                $timetableData
            );

            return redirect()
                ->back()
                ->with('success', $result['message'])
                ->with('timetable_data', $timetableData)
                ->with('timetable_html', $timetableHtml)
                ->with('timetable_records', $result['records'] ?? []);
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
        // Handle empty data
        if (empty($timetableData)) {
            return '<div class="alert alert-info">No timetable data available for the selected criteria.</div>';
        }

        // Define time slots (standard university timetable slots)
        $timeSlots = [
            '08:00 - 09:00',
            '09:00 - 10:00',
            '10:00 - 11:00',
            '11:00 - 12:00',
            '12:00 - 13:00',
            '13:00 - 14:00',
            '14:00 - 15:00',
            '15:00 - 16:00',
            '16:00 - 17:00'
        ];

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        // Initialize timetable grid
        $timetableGrid = [];
        foreach ($timeSlots as $slot) {
            $timetableGrid[$slot] = array_fill(0, count($days), '');
        }

        // Process timetable data - handle different possible data structures
        if (is_array($timetableData)) {
            // Check if it's a list of classes
            if (!empty($timetableData) && is_array($timetableData[0])) {
                foreach ($timetableData as $class) {
                    // Handle different possible data structures
                    $day = null;
                    $startTime = null;
                    $endTime = null;
                    $subject = 'Unknown Subject';
                    $room = '';
                    $instructor = '';

                    // Try to extract data from various possible formats
                    if (isset($class['day'])) {
                        $day = $class['day'];
                    } elseif (isset($class['Day'])) {
                        $day = $class['Day'];
                    }

                    if (isset($class['start_time'])) {
                        $startTime = $class['start_time'];
                    } elseif (isset($class['StartTime'])) {
                        $startTime = $class['StartTime'];
                    }

                    if (isset($class['end_time'])) {
                        $endTime = $class['end_time'];
                    } elseif (isset($class['EndTime'])) {
                        $endTime = $class['EndTime'];
                    }

                    if (isset($class['subject'])) {
                        $subject = $class['subject'];
                    } elseif (isset($class['Subject'])) {
                        $subject = $class['Subject'];
                    } elseif (isset($class['course_name'])) {
                        $subject = $class['course_name'];
                    }

                    if (isset($class['room'])) {
                        $room = $class['room'];
                    } elseif (isset($class['Room'])) {
                        $room = $class['Room'];
                    }

                    if (isset($class['instructor'])) {
                        $instructor = $class['instructor'];
                    } elseif (isset($class['Instructor'])) {
                        $instructor = $class['Instructor'];
                    } elseif (isset($class['teacher'])) {
                        $instructor = $class['teacher'];
                    }

                    // Only process if we have essential data
                    if ($day && $startTime && $endTime) {
                        // Find matching time slot
                        foreach ($timeSlots as $slot) {
                            list($slotStart) = explode(' - ', $slot);
                            if ($slotStart == $startTime) {
                                // Find day index
                                $dayIndex = array_search($day, $days);
                                if ($dayIndex !== false) {
                                    // Build class info string
                                    $classInfo = "<strong>{$subject}</strong>";
                                    if (!empty($room)) {
                                        $classInfo .= "<br/><small>Room: {$room}</small>";
                                    }
                                    if (!empty($instructor)) {
                                        $classInfo .= "<br/><small>Instructor: {$instructor}</small>";
                                    }

                                    $timetableGrid[$slot][$dayIndex] = $classInfo;
                                }
                                break;
                            }
                        }
                    }
                }
            }
            // Handle case where data is already formatted or in different structure
            else {
                // Fallback to showing structured data
                ob_start();
                echo '<pre class="timetable-data">';
                print_r($timetableData);
                echo '</pre>';
                $html = ob_get_clean();
                return '<div class="timetable-table-responsive"><div class="alert alert-info">Timetable data received (raw format):</div>' . $html . '</div>';
            }
        }

        // Build HTML table
        $html = '<div class="timetable-table-responsive">';
        $html .= '<table class="table table-bordered timetable-grid">';
        $html .= '<thead><tr>';
        $html .= '<th class="time-slot-header">Time/Day</th>';

        // Add day headers
        foreach ($days as $day) {
            $html .= '<th class="day-header">' . $day . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        // Add time slots and data
        foreach ($timeSlots as $timeSlot) {
            $html .= '<tr>';
            $html .= '<td class="time-slot fw-bold">' . $timeSlot . '</td>';

            foreach ($days as $dayIndex => $dayName) {
                $cellContent = $timetableGrid[$timeSlot][$dayIndex] ?? '';
                if (!empty($cellContent)) {
                    $html .= '<td class="timetable-cell">' . $cellContent . '</td>';
                } else {
                    $html .= '<td class="timetable-cell empty-cell"></td>';
                }
            }

            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }
    //Studnets End

}