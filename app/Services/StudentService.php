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

    public function login($data): array
    {
        return $this->studentRepository->login($data);
    }

    public function getProfile(): array
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
    
    public function getTimetable()
    {
        return $this->studentRepository->getTimetable();
    }

    public function updatePassword($data)
    {
        return $this->studentRepository->updatePassword($data);
    }

    //Notifications
    public function getNotifications()
    {
        return $this->studentRepository->getNotifications();
    }

    public function markNotificationAsRead($notificationId)
    {
        return $this->studentRepository->markNotificationAsRead($notificationId);
    }

     public function markAllNotificationsAsRead()
    {
        return $this->studentRepository->markAllNotificationsAsRead();
    }
    
    public function registerToken($data)
    {
        return $this->studentRepository->registerToken($data);
    }

    public function unregisterToken($data)
    {
        return $this->studentRepository->unregisterToken($data);
    }

    public function logout()
    {
        return $this->studentRepository->logout();
    }

}
