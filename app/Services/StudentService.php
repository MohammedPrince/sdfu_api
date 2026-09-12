<?php

namespace App\Services;

use App\Repositories\StudentRepository;

class StudentService
{
    protected $studentRepository;

    public function __construct(StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    public function studentCheck($data)
    {
        return $this->studentRepository->studentCheck($data);
    }

    public function login($data)
    {
        return $this->studentRepository->login($data);
    }


    public function getProfile()
    {
        return $this->studentRepository->getProfile();
    }

    public function mainData()
    {
        return $this->studentRepository->mainData();
    }

    public function getResult()
    {
        return $this->studentRepository->getResult();
    }

    public function getFees()
    {
        return $this->studentRepository->getFees();
    }

    public function updatePassword($data)
    {
        return $this->studentRepository->updatePassword($data);
    }

    public function logout()
    {
        return $this->studentRepository->logout();
    }

}
