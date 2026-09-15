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

    public function manageApplication()
    {
        return $this->adminRepository->manageApplication();
    }

    //Helpers functions
    public function getFaculties()
    {
        return $this->adminRepository->getFaculties();
    }

    public function getMajors()
    {
        return $this->adminRepository->getMajors();
    }

    public function getBatches()
    {
        return $this->adminRepository->getBatches();
    }

    public function getMajorsByFaculty(string $facultyCode)
    {
        return $this->adminRepository->majorsByFaculty($facultyCode);
    }

    public function getSavedSettings()
    {
      return $this->adminRepository->getSavedSettings();   
    }

}
