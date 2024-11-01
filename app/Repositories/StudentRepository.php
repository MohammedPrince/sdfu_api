<?php

namespace App\Repositories;

class StudentRepository
{

    public function __construct()
    {

    }


    public function studentCheck($data)
    {
        $studIndex = $data['stud_index'];

        if (!empty($studIndex)) {
            return ['success' => true, 'code' => 200, 'message' => 'Student Index Exists', 'studIndex' => $studIndex];
        } else {
            return ['success' => false, 'code' => 404, 'message' => 'Student Index Not Exists'];
        }

    }

}
