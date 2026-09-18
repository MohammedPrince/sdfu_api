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
        //return $this->adminRepository->manageApplication();
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

    public function countCourses()
    {
        return $this->adminRepository->countCourses();
    }

    public function countNotifications()
    {
        return $this->adminRepository->countNotifications();
    }

    public function getVisitorCounts()
    {
        return $this->adminRepository->getVisitorCounts();
    }

    public function getRecentNotifications()
    {
        return $this->adminRepository->getRecentNotifications();
    }

    public function getApplicationOverview()
    {
        return $this->adminRepository->getApplicationOverview();
    }

    //Studnets Start
    public function getStudents($data)
    {
        return $this->adminRepository->getStudents($data);
    }

    public function getStudentBatches()
    {
        return $this->adminRepository->getStudentBatches();
    }

    public function getStudentDetails($data)
    {
        return $this->adminRepository->getStudentDetails($data);
    }

    public function updateStudentStatus($student, $isActive): void
    {
        $this->adminRepository->updateStudentStatus($student, $isActive);
    }
    //Studnets End

}
