<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function test()
    {

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Welcome to the SDFU application',
            ], 200);
    }
}
