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

    public function privacyPolicy()
    {
        return view('privacy-policy');
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
        $devices = $student->devices()->latest('last_seen_at')->paginate(5);

        return view('admin.student-details', compact('student', 'devices'));
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

    //Timetable Start

    public function getTimetableCourses(Request $request)
    {
        $validated = $request->validate([
            'faculty_code' => ['required', 'integer'],
            'major_code' => ['required', 'integer'],
            'batch' => ['required', 'string', 'max:50'],
            'semester' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $courses = $this->adminService->getTimetableCourses(
            (int) $validated['faculty_code'],
            (int) $validated['major_code'],
            $validated['batch'],
            (int) $validated['semester']
        );

        return response()->json([
            'courses' => $courses,
        ]);
    }

    public function createTimeTable()
    {
        return view('admin.create_timetable', [
            'faculties' => $this->adminService->getFaculties(),
            'majors' => $this->adminService->getMajors(),
            'batches' => $this->adminService->getBatches(),
            //'courses' => $this->adminService->getTimetableCourses(),
            'instructors' => $this->adminService->getTimetableInstructors(),
            'classrooms' => $this->adminService->getTimetableClassrooms(),
            'times' => $this->adminService->getTimetableTimes(),
        ]);
    }

    public function storeTimeTable(Request $request)
    {
        $validated = $request->validate([
            'faculty_code' => ['required', 'integer'],
            'major_code' => ['required', 'integer'],
            'batch' => ['required', 'string', 'max:4'],
            'semester' => ['required', 'integer', 'min:1', 'max:12'],
            'ttid' => ['required', 'integer'],

            'timetable' => ['required', 'array'],
        ]);

        $result = $this->adminService->createTimeTable($validated);

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.timetable.create')
            ->with('success', $result['message']);
    }

    public function displayTimeTable()
    {
        $timetables = $this->adminService
            ->getSavedTimetableConfigurations();

        return view('admin.display_timetable', [
            'timetables' => $timetables,
        ]);
    }

    public function editTimeTable(int $faculty_code, int $major_code, string $batch, int $ttid)
    {

        $rows = $this->adminService->getSavedTimetableRows($faculty_code, $major_code, $batch, $ttid);

        if ($rows->isEmpty()) {
            return redirect()
                ->route('admin.timetable.manage')
                ->with('error', 'Timetable not found.');
        }

        $semester = $this->adminService->getTimetableSemester($faculty_code, $major_code, $batch, $ttid);

        /*
        |--------------------------------------------------------------------------
        | Convert database rows to editor entries
        |--------------------------------------------------------------------------
        */

        $existingEntries = [];

        foreach ($rows as $row) {

            /*
            |--------------------------------------------------------------------------
            | Period is the global tim.id
            |--------------------------------------------------------------------------
            |
            | 1-4   Saturday
            | 5-8   Sunday
            | 9-12  Monday
            | 13-16 Tuesday
            | 17-20 Wednesday
            | 21-24 Thursday
            |
            */

            $period = (int) $row->Period;

            if ($period < 1 || $period > 24) {
                continue;
            }

            $day = intdiv($period - 1, 4);


            /*
            |--------------------------------------------------------------------------
            | Determine entry type
            |--------------------------------------------------------------------------
            */

            if (
                !empty($row->LabPeriod) ||
                !empty($row->LabID) ||
                !empty($row->Instructor_ID_Lab)
            ) {

                $entryType = 'lab';

            } elseif (
                !empty($row->Period2) ||
                !empty($row->ClassID2) ||
                !empty($row->Instructor_ID_Tut)
            ) {

                $entryType = 'tutorial';

            } else {

                $entryType = 'theory';
            }


            /*
            |--------------------------------------------------------------------------
            | Determine instructor/classroom
            |--------------------------------------------------------------------------
            */

            $instructorId = $row->Instructor_ID;
            $classId = $row->ClassID;

            if ($entryType === 'tutorial') {

                $instructorId =
                    $row->Instructor_ID_Tut ?: $row->Instructor_ID;

                $classId =
                    $row->ClassID2 ?: $row->ClassID;
            }

            if ($entryType === 'lab') {

                $instructorId =
                    $row->Instructor_ID_Lab ?: $row->Instructor_ID;

                $classId =
                    $row->LabID ?: $row->ClassID;
            }


            /*
            |--------------------------------------------------------------------------
            | Hours
            |--------------------------------------------------------------------------
            */

            $hours = 2;

            if ($entryType === 'lab') {
                $hours = (int) ($row->PracticalHrs ?? 0);
            } elseif ($entryType === 'tutorial') {
                $hours = (int) ($row->TutorialHrs ?? 0);
            } else {
                $hours = (int) ($row->TheoryHrs ?? 0);
            }


            $existingEntries[$day][$period][] = [

                'course_code' => $row->Course_Code,

                'stud_group' => (string) $row->Stud_Group,

                'instructor_id' => $instructorId,

                'class_id' => $classId,

                'entry_type' => $entryType,

                'theory_hrs' => $entryType === 'theory'
                    ? $hours
                    : 0,

                'practical_hrs' => $entryType === 'lab'
                    ? $hours
                    : 0,
            ];
        }


        return view('admin.edit_timetable', [

            'faculties' => $this->adminService->getFaculties(),

            'majors' => $this->adminService->getMajors(),

            'batches' => $this->adminService->getBatches(),

            'instructors' =>
                $this->adminService->getTimetableInstructors(),

            'classrooms' =>
                $this->adminService->getTimetableClassrooms(),

            'semester' => $semester,

            'ttid' => $ttid,

            'facultyCode' => $faculty_code,

            'majorCode' => $major_code,

            'batchValue' => $batch,

            'existingEntries' => $existingEntries,
        ]);
    }

    public function updateTimeTable(
        Request $request,
        int $faculty_code,
        int $major_code,
        string $batch,
        int $ttid
    ) {
        $validated = $request->validate([
            'faculty_code' => [
                'required',
                'integer',
            ],

            'major_code' => [
                'required',
                'integer',
            ],

            'batch' => [
                'required',
                'string',
                'max:4',
            ],

            'semester' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],

            'ttid' => [
                'required',
                'integer',
            ],

            'timetable' => [
                'nullable',
                'array',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Security / route consistency
        |--------------------------------------------------------------------------
        */

        if (
            (int) $validated['faculty_code'] !== $faculty_code ||
            (int) $validated['major_code'] !== $major_code ||
            (string) $validated['batch'] !== $batch ||
            (int) $validated['ttid'] !== $ttid
        ) {

            return redirect()
                ->back()
                ->with('error', 'Invalid timetable configuration.');
        }


        /*
        |--------------------------------------------------------------------------
        | Build database rows
        |--------------------------------------------------------------------------
        */

        $rows = $this->buildTimetableRows(
            $validated['timetable'] ?? [],
            $faculty_code,
            $major_code,
            $batch,
            $ttid
        );


        /*
        |--------------------------------------------------------------------------
        | Replace timetable
        |--------------------------------------------------------------------------
        */

        $this->adminService->replaceTimetable(
            $faculty_code,
            $major_code,
            $batch,
            $ttid,
            $rows
        );


        return redirect()
            ->route('admin.timetable.display')
            ->with(
                'success',
                'Timetable updated successfully.'
            );
    }

    public function deleteTimeTable(int $faculty_code, int $major_code, string $batch, int $ttid)
    {

        $deleted = $this->adminService->deleteTimetable($faculty_code, $major_code, $batch, $ttid);

        if ($deleted === 0) {

            return redirect()
                ->route('admin.timetable.display')
                ->with(
                    'error',
                    'Timetable not found.'
                );
        }


        return redirect()
            ->route('admin.timetable.display')
            ->with(
                'success',
                'Timetable deleted successfully.'
            );
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

            $timetableHtml = Helper::formatTimetableForDisplay(
                $result['timetableData'] ?? []
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

    public function getTimeTable()
    {

        return view('admin.show_timetable', [
            'faculties' => $this->adminService->getFaculties(),
            'majors' => $this->adminService->getMajors(),
            'batches' => $this->adminService->getBatches(),

        ]);
    }

    public function showTimeTable(Request $request)
    {
        $validated = $request->validate([
            'faculty_code' => ['required', 'string', 'max:50'],
            'major_code' => ['required', 'string', 'max:50'],
            'batch' => ['required', 'string', 'max:50'],
            'semester' => ['required', 'integer', 'min:1', 'max:12'],
            'ttid' => ['required', 'integer'],
        ]);

        $timetableData = $this->adminService->getTimetableData(
            $validated['faculty_code'],
            $validated['major_code'],
            $validated['batch'],
            $validated['semester'],
            $validated['ttid']
        );

        if (empty($timetableData['timetableDetails'])) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'No timetable found for the selected criteria.'
                );
        }

        $timetableHtml = Helper::formatTimetableForDisplay(
            $timetableData
        );

        return view('admin.show_timetable', [
            'faculties' => $this->adminService->getFaculties(),
            'majors' => $this->adminService->getMajors(),
            'batches' => $this->adminService->getBatches(),
            'timetableData' => $timetableData,
            'timetableHtml' => $timetableHtml,
            'timetableSelection' => $validated,
        ]);
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

    private function buildTimetableRows(array $timetable, int $facultyCode, int $majorCode, string $batch, int $ttid): array
    {

        $rows = [];

        foreach ($timetable as $day => $periods) {

            foreach ($periods as $period => $entries) {

                foreach ($entries as $entry) {

                    $courseCode = trim(
                        $entry['course_code'] ?? ''
                    );

                    if ($courseCode === '') {
                        continue;
                    }


                    $group = (int) (
                        $entry['stud_group'] ?? 1
                    );


                    $entryType = $entry['entry_type'] ?? 'theory';


                    $instructorId = !empty($entry['instructor_id'])
                        ? (int) $entry['instructor_id']
                        : null;

                    $classId = !empty($entry['class_id'])
                        ? (int) $entry['class_id']
                        : null;


                    $theoryHours = (int) (
                        $entry['theory_hrs'] ?? 0
                    );

                    $practicalHours = (int) (
                        $entry['practical_hrs'] ?? 0
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Base row
                    |--------------------------------------------------------------------------
                    */

                    $row = [

                        'TTID' => $ttid,

                        'Dept_Name' => '0',

                        'Batch_Year' => $batch,

                        'Course_Code' => $courseCode,

                        'Stud_Group' => (string) $group,

                        'Instructor_ID' => null,

                        'Instructor_ID_Tut' => null,

                        'Instructor_ID_Lab' => null,

                        'TheoryHrs' => 0,

                        'TutorialHrs' => 0,

                        'Period' => (int) $period,

                        'Period2' => null,

                        'LabPeriod' => null,

                        'ClassID' => null,

                        'ClassID2' => null,

                        'LabID' => null,

                        'PracticalHrs' => null,

                        'Faculty_Code' => $facultyCode,

                        'Major_Code' => $majorCode,

                        'Major_Minor' => null,

                        'User_Name' => auth()->user()->email ?? null,

                        'FZ_Flag' => 0,

                        'Dissolved' => 0,

                        'FZ_Semester' => 0,

                        'c_c' => 0,

                        'new_course_flag' => 1,
                    ];


                    /*
                    |--------------------------------------------------------------------------
                    | Theory
                    |--------------------------------------------------------------------------
                    */

                    if ($entryType === 'theory') {

                        $row['Instructor_ID'] =
                            $instructorId;

                        $row['ClassID'] =
                            $classId;

                        $row['TheoryHrs'] =
                            $theoryHours;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Tutorial
                    |--------------------------------------------------------------------------
                    */ elseif ($entryType === 'tutorial') {

                        $row['Instructor_ID_Tut'] =
                            $instructorId;

                        $row['ClassID2'] =
                            $classId;

                        $row['Period2'] =
                            (int) $period;

                        $row['TutorialHrs'] =
                            $theoryHours;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | LAB
                    |--------------------------------------------------------------------------
                    */ elseif ($entryType === 'lab') {

                        $row['Instructor_ID_Lab'] =
                            $instructorId;

                        $row['LabID'] =
                            $classId;

                        $row['LabPeriod'] =
                            (int) $period;

                        $row['PracticalHrs'] =
                            $practicalHours;
                    }


                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    //Timetable End


}