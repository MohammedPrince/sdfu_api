<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudIndexValidation;
use App\Services\StudentService;
use Illuminate\Http\Request;


class MainController extends Controller
{
    protected $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    public function test()
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Welcome to the SDFU application',
        ], 200);
    }

    public function login(Request $request)
    {

        $studIndex = trim($request->input('stud_index', ''));
        $studPassword = $request->input('stud_password', '');

        $data = ['stud_index' => $studIndex, 'stud_password' => $studPassword];

        $result = $this->studentService->login($data);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'studentDetails' => $result['studentDetails'],
                ]
            ], $result['code']);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'],
            ], $result['code']);
        }
    }

    public function getProfile()
    {
        $result = $this->studentService->getProfile();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'studentDetails' => $result['studentDetails'],
                ]
            ], $result['code']);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'],
            ], $result['code']);
        }
    }

    public function checkIndex(StudIndexValidation $request)
    {

        $data = $request->validated();

        $result = $this->studentService->studentCheck($data);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'studIndex' => $result['studIndex'],
                ]
            ], $result['code']);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message']
            ], $result['code']);
        }

    }

    public function mainData()
    {
        $result = $this->studentService->mainData();

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Main Data Retrieved Successfully',
            'data' => [
                'studentDetails' => $result['studentDetails'],
                'semesterResult' => $result['semesterResult'],
                'feeDetails' => $result['feeDetails'],
                'timetable' => $result['timetable'],
                'appStatus' => $result['appStatus'],
            ],
        ]);
    }

    public function getResult()
    {
        $result = $this->studentService->getResult();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    // 'studentDetails' => $result['studentDetails'],
                    'semesterResult' => $result['semesterResult'],
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

    public function getFees()
    {
        $result = $this->studentService->getFees();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    // 'studentDetails' => $result['studentDetails'],
                    'feeDetails' => $result['feeDetails'],
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

    public function logout()
    {

        $result = $this->studentService->logout();

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
                'error' => $result['message']
            ], $result['code']);
        }
    }

}
