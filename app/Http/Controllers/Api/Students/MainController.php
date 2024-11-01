<?php

namespace App\Http\Controllers\Api\Students;

use Illuminate\Http\Request;
use App\Services\StudentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudIndexValidation;

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
}
