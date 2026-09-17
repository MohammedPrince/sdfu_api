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

        return view('home', compact('visitorCount','applicationStatus'));
    }

    public function dashboard()
    {
        $studentCount = $this->adminService->countStudents();

        return view('admin.dashboard', compact('studentCount'));
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

}
