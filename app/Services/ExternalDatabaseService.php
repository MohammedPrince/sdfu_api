<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExternalDatabaseService
{
    /*
    |--------------------------------------------------------------------------
    | Moodle
    |--------------------------------------------------------------------------
    */

    public function getMoodleStudent(string $studIndex): ?object
    {
        return DB::connection('mysql_moodle')
            ->table('user')
            ->select([
                'id',
                'username',
                'password',
                'firstname',
                'lastname',
                'email',
            ])
            ->where('username', $studIndex)
            ->where('deleted', 0)
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | Student Details
    |--------------------------------------------------------------------------
    */

    public function getStudentDetails(string $studIndex): ?object
    {
        return DB::connection('mysql_sis')
            ->table('student_profile_e as e')
            ->join(
                'student_profile_common as c',
                'e.stud_id',
                '=',
                'c.stud_id'
            )
            ->where('e.stud_id', $studIndex)
            ->select([
                // e.* — explicit, so a shared column name never gets
                // silently overwritten by c.*
                'e.stud_id',
                'e.stud_name',
                'e.stud_surname',
                'e.familyname',
                'e.lastName',

                // c.* — add/remove to match what callers actually use
                'c.stud_email',
                'c.stud_tel_mobile',
                'c.batch',
                'c.curr_sem',
                'c.faculty_code',
                'c.major_code',
            ])
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | Faculty
    |--------------------------------------------------------------------------
    */

    public function getFacultyName($facultyCode): ?string
    {
        return DB::connection('mysql_sis')
            ->table('faculty')
            ->where('faculty_code', $facultyCode)
            ->value('faculty_desc_e');
    }


    /*
    |--------------------------------------------------------------------------
    | Major
    |--------------------------------------------------------------------------
    */

    public function getMajorName($majorCode): ?string
    {
        return DB::connection('mysql_sis')
            ->table('major')
            ->where('major_code', $majorCode)
            ->value('major_desc_e');
    }


    /*
    |--------------------------------------------------------------------------
    | Ministry Number
    |--------------------------------------------------------------------------
    | Pulled separately instead of leftJoin'd into getStudentResult() —
    | if a student has more than one stud_highschool row, a join would
    | silently duplicate every course row in the result set.
    */

    public function getMinistryNo($stud_id): ?string
    {
        return DB::connection('mysql_sis')
            ->table('stud_highschool')
            ->where('stud_id', $stud_id)
            ->value('ministry_no');
    }


    /*
    |--------------------------------------------------------------------------
    | Student Result
    |--------------------------------------------------------------------------
    */

    public function getStudentResult(
        $stud_id,
        $facultyCode,
        $majorCode,
        $batch,
        $semester
    ): array {
        $db = DB::connection('mysql_sis');

        $ministryNo = $this->getMinistryNo($stud_id);

        $results = $db->table('stud_course_mark as scm')

            /*
            |--------------------------------------------------------------------------
            | Course
            |--------------------------------------------------------------------------
            */

            ->join('course_details as crs', function ($join) {
                $join->on('scm.course_code', '=', 'crs.course_code')
                    ->on('scm.semester', '=', 'crs.course_semester')
                    ->on('scm.major_code', '=', 'crs.major_code')
                    ->on('scm.batch', '=', 'crs.batch');
            })

            /*
            |--------------------------------------------------------------------------
            | Transcript
            |--------------------------------------------------------------------------
            */

            ->join('stud_transcript_table as res', function ($join) {
                $join->on('scm.stud_id', '=', 'res.stud_id')
                    ->on('scm.semester', '=', 'res.semester')
                    ->on('scm.batch', '=', 'res.batch')
                    ->on('scm.major_code', '=', 'res.major_code');
            })

            /*
            |--------------------------------------------------------------------------
            | CGPA Status
            |--------------------------------------------------------------------------
            */

            ->join(
                'cgpa_status as status',
                'res.cgpa_status_code',
                '=',
                'status.cgpa_status_code'
            )

            /*
            |--------------------------------------------------------------------------
            | Student Profile
            |--------------------------------------------------------------------------
            */

            ->join(
                'student_profile_e as sp',
                'scm.stud_id',
                '=',
                'sp.stud_id'
            )

            /*
            |--------------------------------------------------------------------------
            | Student Common Profile
            |--------------------------------------------------------------------------
            */

            ->join('student_profile_common as spc', function ($join) {
                $join->on('res.stud_id', '=', 'spc.stud_id')
                    ->on('res.major_code', '=', 'spc.major_code')
                    ->on('res.batch', '=', 'spc.batch');
            })

            /*
            |--------------------------------------------------------------------------
            | Faculty
            |--------------------------------------------------------------------------
            */

            ->join(
                'faculty as f',
                'f.faculty_code',
                '=',
                'scm.faculty_code'
            )

            /*
            |--------------------------------------------------------------------------
            | Major
            |--------------------------------------------------------------------------
            */

            ->join(
                'major as m',
                'm.major_code',
                '=',
                'scm.major_code'
            )

            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            ->where('scm.stud_id', $stud_id)
            ->where('scm.faculty_code', $facultyCode)
            ->where('scm.major_code', $majorCode)
            ->where('scm.batch', $batch)
            ->where('scm.semester', $semester)
            ->where('crs.course_units', '>', 0)

            /*
            |--------------------------------------------------------------------------
            | Select
            |--------------------------------------------------------------------------
            */

            ->select([
                'scm.result_status',
                'scm.stud_id',
                'scm.semester',

                'crs.course_code',
                'crs.course_name',
                'crs.course_units',

                'sp.stud_name',
                'sp.stud_surname',
                'sp.familyname',

                'scm.grade',
                'scm.sub_grade1',
                'scm.sub_grade2',
                'scm.weightage',
                'scm.remark',

                'res.gpa',
                'res.cgpa',
                'res.cgpa_status_code',

                'status.status_desc_e',

                'f.faculty_desc_e',
                'm.major_desc_e',
                'm.abbreviation',
            ])

            ->orderBy('scm.course_code')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Build Result
        |--------------------------------------------------------------------------
        */

        $list = [];

        $i = 0;

        foreach ($results as $row) {

            $i++;

            /*
            |--------------------------------------------------------------------------
            | Student Name
            |--------------------------------------------------------------------------
            */

            $studentName = trim(
                $row->stud_name . ' ' .
                $row->stud_surname . ' ' .
                $row->familyname
            );


            /*
            |--------------------------------------------------------------------------
            | Grade
            |--------------------------------------------------------------------------
            */

            $grade = $row->grade;

            if (!empty($row->sub_grade1)) {
                $grade .= '/' . $row->sub_grade1;
            }

            if (!empty($row->sub_grade2)) {
                $grade .= '/' . $row->sub_grade2;
            }


            /*
            |--------------------------------------------------------------------------
            | Result
            |--------------------------------------------------------------------------
            */

            $list[] = [

                'result_status' => (int) $row->result_status,

                'stud_id' => $row->stud_id,

                'student_name' => $studentName,

                'semester' => (int) $row->semester,

                'course_code' => $row->course_code,

                'course_name' => $row->course_name,

                'course_units' => (int) $row->course_units,

                'grade' => $grade,

                'points' => number_format((float) $row->weightage, 2, '.', ''),

                'remark' => $row->remark ?? '',

                'gpa' => number_format((float) $row->gpa, 2, '.', ''),
                'cgpa' => number_format((float) $row->cgpa, 2, '.', ''),

                'status' => $row->status_desc_e,

                'ministry_no' => $ministryNo,

                'faculty' => $row->faculty_desc_e,

                'major' => $row->major_desc_e,

                'abbreviation' => $row->abbreviation,

                'i' => $i,
            ];
        }

        return $list;
    }


    /*
    |--------------------------------------------------------------------------
    | Student Fees
    |--------------------------------------------------------------------------
    */

    public function getStudentFees(
        $stud_id,
        $facultyCode,
        $majorCode,
        $batch,
        $semester
    ): ?object {
        return DB::connection('mysql_fib')
            ->table('fu_student_fee_fib_flag_local')
            ->where('student_index_no', $stud_id)
            ->select([
                'start_date',
                'end_date',
                'viewData',
                'total_fee_bank',
            ])
            ->first();
    }

    public function getStudentTimetable($stud_id, $faculty_code, $major_code, $batch, $semester)
    {
        // NOTE: swap 'mysql_ott' for whatever this connection is actually
        // named in config/database.php — the original code's "mysql_fib"
        // comment was just a placeholder, not a confirmed name.
        $connection = DB::connection('mysql_ott');

        $group = 1;
        $newCourseFlag = 1;
        $ttid = 40;

        $faculty_code = 2;
        $major_code = 2;
        $batch = '2022';

        // Lecture/tutorial bindings — only keys that appear in $lectureQuery /
        // $fallbackQuery below. PDO throws "Invalid parameter number:
        // parameter was not defined" if a bindings array carries ANY key
        // that isn't referenced in that specific query's SQL, so lecture
        // and lab bindings must stay separate rather than sharing one array.
        $bindings = [
            'ttid' => $ttid,
            'new_course_flag' => $newCourseFlag,
            'faculty_code' => $faculty_code,
            'major_code' => $major_code,
            'batch' => $batch,
            'group' => $group,
        ];

        $lectureQuery = "
            select Period, Period2, Course_Code, Stud_Group, ClassID, ClassID2,
                   Instructor_ID, Instructor_ID_Tut
            from tbl_setting_timetable
            where TTID = :ttid
              and new_course_flag = :new_course_flag
              and Dissolved = 0
              and Faculty_Code = :faculty_code
              and Major_Code = :major_code
              and Batch_Year = :batch
              and Stud_Group = :group
        ";

        $timetableRows = $connection->select($lectureQuery, $bindings);

        // Fall back to a prior season if the current one has nothing yet.
        if (empty($timetableRows)) {
            $fallbackQuery = "
                select Period, Period2, Course_Code, Stud_Group, ClassID, ClassID2,
                       Instructor_ID, Instructor_ID_Tut
                from tbl_setting_timetable
                where TTID = :ttid
                  and new_course_flag = :new_course_flag
                  and Faculty_Code = :faculty_code
                  and Major_Code = :major_code
                  and Batch_Year = :batch
                  and Stud_Group = :group
            ";
            $timetableRows = $connection->select($fallbackQuery, $bindings);
        }


        $days = [];

        foreach ($timetableRows as $row) {
            $lecTime = $connection->selectOne(
                'select day, time, day_name from tim where id = ?',
                [$row->Period]
            );
            $tutTime = $row->Period2
                ? $connection->selectOne('select day, time, day_name from tim where id = ?', [$row->Period2])
                : null;

            $lecRoom = $row->ClassID
                ? $connection->selectOne('select Class_Name from tbl_classrooms where Class_ID = ?', [$row->ClassID])
                : null;
            $tutRoom = $row->ClassID2
                ? $connection->selectOne('select Class_Name from tbl_classrooms where Class_ID = ?', [$row->ClassID2])
                : null;

            $course = $connection->selectOne(
                'select Course_Name from tbl_courses
                 where Course_Code = ? and Batch_Year = ? and Faculty_Code = ? and Major_Code = ? and new_course_flag != 0',
                [$row->Course_Code, $batch, $faculty_code, $major_code]
            );

            $lecInstructor = $row->Instructor_ID
                ? $connection->selectOne('select Instructor_Name from tbl_instructors where Instructor_ID = ?', [$row->Instructor_ID])
                : null;
            $tutInstructor = $row->Instructor_ID_Tut
                ? $connection->selectOne('select Instructor_Name from tbl_instructors where Instructor_ID = ?', [$row->Instructor_ID_Tut])
                : null;

            $dayKey = $lecTime->day_name ?? 'Unscheduled';

            $days[$dayKey][] = [
                'type' => 'lecture',
                'course_code' => trim($row->Course_Code ?? null),
                'course_name' => trim($course->Course_Name ?? null),
                'stud_group' => $row->Stud_Group,
                'time' => $this->formatTime($lecTime->time ?? null),
                'day' => $lecTime->day ?? null,
                'room' => trim($lecRoom->Class_Name ?? null),
                'instructor_name' => trim($lecInstructor->Instructor_Name ?? null),
                'time_tut' => $this->formatTime($tutTime->time ?? null),
                'day_tut' => $tutTime->day ?? null,
                'room_tut' => trim($tutRoom->Class_Name ?? null),
                'instructor_name_tut' => trim($tutInstructor->Instructor_Name ?? null),
                'period' => $row->Period,
                'period2' => $row->Period2,
            ];
        }

        // lab_timetable
        $labBindings = [
            'ttid' => $ttid,
            'faculty_code' => $faculty_code,
            'major_code' => $major_code,
            'batch' => $batch,
            'lab_groups' => $group,
        ];

        $labQuery = "
            select * from lab_timetable
            where Period != '' and Deleted = 0 and TTID = :ttid
              and Faculty_Code = :faculty_code and Major_Code = :major_code
              and Batch_Year = :batch and Lab_Groups = :lab_groups
        ";
        $labRows = $connection->select($labQuery, $labBindings);

        foreach ($labRows as $row) {
            $labTime = $connection->selectOne(
                'select day, time, day_name from tim where id = ?',
                [$row->Period]
            );
            $instructor = $row->Instructor_Ids
                ? $connection->selectOne('select Instructor_Name from tbl_instructors where Instructor_ID = ?', [$row->Instructor_Ids])
                : null;
            $lab = $row->Lab_Id
                ? $connection->selectOne('select LabName from tbl_labs where LabID = ?', [$row->Lab_Id])
                : null;
            $course = $connection->selectOne(
                'select Course_Name from tbl_courses where Course_Code = ? and new_course_flag = 1',
                [$row->Course_Code]
            );

            $dayKey = $labTime->day_name ?? 'Unscheduled';

            $days[$dayKey][] = [
                'type' => 'lab',
                'course_code' => $row->Course_Code,
                'course_name' => $course->Course_Name ?? null,
                'batch_year' => $row->Batch_Year,
                'stud_group' => $row->Stud_Group,
                'lab_groups' => $row->Lab_Groups,
                'instructor_name' => $instructor->Instructor_Name ?? null,
                'time' => $this->formatTime($labTime->time ?? null),
                'day' => $labTime->day ?? null,
                'room' => $lab->LabName ?? null,
                'faculty_code' => $row->Faculty_Code,
                'major_code' => $row->Major_Code,
                'period' => $row->Period,
            ];
        }

        // Sort each day's entries chronologically so the front end can render
        // them top-to-bottom exactly as in the screenshot, no client sorting needed.
        foreach ($days as $dayKey => $entries) {
            usort($entries, fn($a, $b) => strcmp((string) $a['time'], (string) $b['time']));
            $days[$dayKey] = $entries;
        }

        return ['days' => $days];
    }

    public function updateMoodlePassword($username, $newPassword): bool
    {

        $user = DB::connection('mysql_moodle')->table('user')->where('username', $username)->first();

        if (!$user) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Moodle Password Hash
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Moodle does NOT use Laravel's bcrypt password hash directly.
        |
        | For modern Moodle versions, passwords are normally stored using
        | Moodle's $2y$ bcrypt-compatible format.
        |
        */

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        return DB::connection('mysql_moodle')->table('user')->where('id', $user->id)
            ->update([
                'password' => $hash,
                'timemodified' => now()->timestamp,
            ]) > 0;
    }

    //Helpers Functions
    private function formatTime($time): ?string
    {
        if (empty($time)) {
            return null;
        }

        $time = trim($time);

        // Time range: 12:30 - 2:30
        if (str_contains($time, '-')) {

            [$start, $end] = array_map(
                'trim',
                explode('-', $time, 2)
            );

            try {
                $startFormatted = Carbon::parse($start)->format('g:i A');
                $endFormatted = Carbon::parse($end)->format('g:i A');

                return $startFormatted . ' - ' . $endFormatted;
            } catch (\Throwable $e) {
                // Return original value if it cannot be parsed
                return $time;
            }
        }

        // Single time
        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Throwable $e) {
            return $time;
        }
    }
}