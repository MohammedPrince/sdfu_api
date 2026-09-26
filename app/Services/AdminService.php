<?php

namespace App\Services;

use App\Repositories\AdminRepository;
use Illuminate\Support\Collection;

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
    public function getFaculties(): Collection
    {
        return $this->adminRepository->getFaculties();
    }

    public function getMajors()
    {
        return $this->adminRepository->getMajors();
    }

    public function getBatches(): Collection
    {
        return $this->adminRepository->getBatches();
    }

    public function getMajorsByFaculty(string $facultyCode)
    {
        return $this->adminRepository->majorsByFaculty($facultyCode);
    }

    public function getTimetable($facultyCode, $majorCode, $batch, $semester, $ttid)
    {
        return $this->adminRepository->getTimetable($facultyCode, $majorCode, $batch, $semester, $ttid);
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
    public function getPushedNotifications()
    {
        return $this->adminRepository->getPushedNotifications();
    }

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

    public function getReports(array $filters = []): array
    {
        return $this->adminRepository->getReports($filters);
    }
    //Studnets End

    //Timetable Start

    public function getTimetableCourses(
        int $facultyCode,
        int $majorCode,
        string $batch,
        int $semester
    ) {
        return $this->adminRepository->getTimetableCourses(
            $facultyCode,
            $majorCode,
            $batch,
            $semester
        );
    }

    public function getTimetableTimes()
    {
        return $this->adminRepository->getTimetableTimes();
    }

    public function createTimeTable(array $data): array
    {
        return $this->adminRepository->createTimeTable($data);
    }

    public function getTimetableInstructors()
    {
        return $this->adminRepository->getTimetableInstructors();
    }

    public function getTimetableClassrooms()
    {
        return $this->adminRepository->getTimetableClassrooms();
    }

    public function syncTimetableData(string $facultyCode, string $majorCode, string $batch, int $semester, int $ttid)
    {
        return $this->adminRepository->syncTimetableData($facultyCode, $majorCode, $batch, $semester, $ttid);
    }

    public function getTimetableData(string $facultyCode, string $majorCode, string $batch, int $semester, int $ttid): array
    {
        return $this->adminRepository->getTimetableData($facultyCode, $majorCode, $batch, $semester, $ttid);
    }

    public function getSavedTimetableConfigurations()
    {
        return $this->adminRepository->getSavedTimetableConfigurations();
    }

    public function getSavedTimetableRows(int $facultyCode, int $majorCode, string $batch, int $ttid)
    {
        return $this->adminRepository->getSavedTimetableRows($facultyCode, $majorCode, $batch, $ttid);
    }

    public function getTimetableSemester(int $facultyCode, int $majorCode, string $batch, int $ttid): ?int
    {
        return $this->adminRepository->getTimetableSemester($facultyCode, $majorCode, $batch, $ttid);
    }

    public function replaceTimetable(int $facultyCode, int $majorCode, string $batch, int $ttid, array $rows): int
    {
        return $this->adminRepository->replaceTimetable($facultyCode, $majorCode, $batch, $ttid, $rows);
    }
    public function deleteTimetable(int $facultyCode, int $majorCode, string $batch, int $ttid): int
    {
        return $this->adminRepository->deleteTimetable($facultyCode, $majorCode, $batch, $ttid);
    }

    public function resetStudentPassword(string $studIndex): bool
    {
        // Get the Moodle password for the given studIndex (username)
        $moodlePassword = $this->adminRepository->getMoodlePasswordByUsername($studIndex);

        if (!$moodlePassword) {
            return false;
        }
        // Update the user's password in the main application
        $result = $this->adminRepository->updateUserPasswordByStudIndex($studIndex, $moodlePassword);

        return $result > 0;
    }


    //Timetable End
}