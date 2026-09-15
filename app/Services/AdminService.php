<?php

namespace App\Services;

use App\Repositories\AdminRepository;

class AdminService
{

    protected $adminRepository;

    public function __construct(AdminRepository $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    public function getSystemSettings(
        $facultyCode,
        $majorCode,
        $batch,
        $semester
    ) {
        return $this->adminRepository->getSystemSettings(
            $facultyCode,
            $majorCode,
            $batch,
            $semester
        );
    }
    public function countStudents(): int
    {
        return $this->adminRepository->countStudents();
    }

}
