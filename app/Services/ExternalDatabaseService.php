<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;


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
                'c.sex_code',
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

    public function resultMaintenanceMode(): bool
    {
        $status = DB::connection('mysql_sis')
            ->table('maintenance_mode')
            ->value('maintenance_status');

        return (int) $status === 1;
    }

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

    public function getStudentTimetable($stud_id, $faculty_code, $major_code, $batch, $semester, $ttid = 40)
    {
        // NOTE: swap 'mysql_ott' for whatever this connection is actually
        // named in config/database.php — the original code's "mysql_fib"
        // comment was just a placeholder, not a confirmed name.
        $connection = DB::connection('mysql_ott');

        $group = 1;
        $newCourseFlag = 1;

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
                  and Stud_Group = :group";

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
                'stud_group' => match ((int) $row->Stud_Group) {
                    1 => 'A',
                    2 => 'B',
                    3 => 'C',
                    default => null,
                },
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
                'course_code' => trim($row->Course_Code),
                'course_name' => trim($course->Course_Name ?? null),
                'batch_year' => trim($row->Batch_Year),
                'stud_group' => match ((int) $row->Stud_Group) {
                    1 => 'A',
                    2 => 'B',
                    3 => 'C',
                    default => null,
                },
                'lab_groups' => $row->Lab_Groups,
                'instructor_name' => trim($instructor->Instructor_Name ?? null),
                'time' => $this->formatTime($labTime->time ?? null),
                'day' => $labTime->day ?? null,
                'room' => trim($lab->LabName ?? null),
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

    /*
    |--------------------------------------------------------------------------
    | Timetable Tables - Raw Data Fetch Methods
    |--------------------------------------------------------------------------
    */

    public function getLabTimetable($faculty_code, $major_code, $batch, $semester, $ttid)
    {
        return DB::connection('mysql_ott')
            ->table('lab_timetable')
            ->where('TTID', $ttid)
            ->where('Faculty_Code', $faculty_code)
            ->where('Major_Code', $major_code)
            ->where('Batch_Year', $batch)
            ->where('Deleted', 0)
            ->get();
    }

    public function getClassrooms(string $facultyCode, string $majorCode, string $batch, int $semester, int $ttid): Collection
    {

        return DB::connection('mysql_ott')
            ->table('tbl_classrooms')
            ->where('Faculty_Code', $facultyCode)
            ->orderByDesc('Class_ID')
            ->get();
    }

    public function getCourses($faculty_code, $major_code, $batch, $semester, $ttid)
    {
        return DB::connection('mysql_ott')
            ->table('tbl_courses')
            ->where('Faculty_Code', $faculty_code)
            ->where('Major_Code', $major_code)
            ->where('Batch_Year', $batch)
            ->where('new_course_flag', '<>', 0)
            ->get();
    }

    public function getInstructors($faculty_code, $major_code, $batch, $semester, $ttid)
    {
        return DB::connection('mysql_ott')
            ->table('tbl_instructors')
            ->where('Faculty_Code', $faculty_code)
            ->where('Major_Code', $major_code)
            ->where('Batch_Year', $batch)
            ->get();
    }

    public function getSettingTimetable($faculty_code, $major_code, $batch, $semester, $ttid)
    {
        return DB::connection('mysql_ott')
            ->table('tbl_setting_timetable')
            ->where('TTID', $ttid)
            ->where('Faculty_Code', $faculty_code)
            ->where('Major_Code', $major_code)
            ->where('Batch_Year', $batch)
            ->where('Dissolved', 0)
            ->get();
    }

    public function getTim($faculty_code, $major_code, $batch, $semester, $ttid)
    {
        return DB::connection('mysql_ott')
            ->table('tim')
            ->get(); // Assuming tim is not filtered by timetable specifics
    }

    public function getTimetables($faculty_code, $major_code, $batch, $semester, $ttid)
    {
        // Assuming there is a timetables table? If not, we might need to adjust.
        return DB::connection('mysql_ott')
            ->table('timetables')
            ->where('TTID', $ttid)
            ->where('Faculty_Code', $faculty_code)
            ->where('Major_Code', $major_code)
            ->where('Batch_Year', $batch)
            ->get();
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

        IMPORTANT:
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

    //Timetable Start

    public function getTimetableCoursesOLD(
        int $facultyCode,
        int $majorCode,
        string $batch,
        int $semester
    ) {
        return DB::connection('mysql_ott')
            ->table('tbl_courses')
            ->where('Faculty_Code', $facultyCode)
            ->where('Major_Code', $majorCode)
            ->where('Batch_Year', $batch)
            ->where('semester', $semester)
            ->where('del', 0)
            ->where('new_course_flag', 2)
            ->orderBy('Course_Code')
            ->get();
    }

    public function getTimetableCourses(
        int $facultyCode,
        int $majorCode,
        string $batch,
        int $semester
    ) {
        return DB::connection('mysql_ott')
            ->table('tbl_courses')
            ->select([
                'Course_Code',
                'Course_Name',
            ])
            ->where('Faculty_Code', $facultyCode)
            ->where('Major_Code', $majorCode)
            ->where('Batch_Year', $batch)
            ->where('semester', $semester)

            ->where('del', 0)
            ->groupBy(
                'Course_Code',
                'Course_Name'
            )
            ->orderBy('Course_Code')
            ->get();
    }

    public function getTimetableInstructors()
    {
        return DB::connection('mysql_ott')
            ->table('tbl_instructors')
            ->where(function ($query) {
                $query->whereNull('Deleted')
                    ->orWhere('Deleted', 0);
            })
            ->orderBy('Instructor_Name')
            ->get();
    }

    public function getTimetableClassrooms()
    {
        return DB::connection('mysql_ott')
            ->table('tbl_classrooms')
            ->orderBy('Class_Name')
            ->get();
    }

    public function getTimetableTimes()
    {
        return DB::connection('mysql_ott')
            ->table('tim')
            ->whereIn('id', [1, 2, 3, 4])
            ->orderBy('id')
            ->get();
    }

    public function getTimetableData(
        string $facultyCode,
        string $majorCode,
        string $batch,
        int $semester,
        int $ttid
    ): array {
        $connection = DB::connection('mysql_ott');

        /*
        |--------------------------------------------------------------------------
        | Timetable Settings
        |--------------------------------------------------------------------------
        */

        $timetableDetails = $connection
            ->table('tbl_setting_timetable')
            ->where('Faculty_Code', $facultyCode)
            ->where('Major_Code', $majorCode)
            ->where('Batch_Year', $batch)
            ->where('TTID', $ttid)
            ->where('new_course_flag', 1)
            ->orderByDesc('Id')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Classrooms
        |--------------------------------------------------------------------------
        */

        $classRoomDetails = $connection
            ->table('tbl_classrooms')
            ->where('Faculty_Code', $facultyCode)
            ->orderByDesc('Class_ID')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Courses
        |--------------------------------------------------------------------------
        */

        $courseDetails = $connection
            ->table('tbl_courses')
            ->where('Faculty_Code', $facultyCode)
            ->where('Major_Code', $majorCode)
            ->where('Batch_Year', $batch)
            ->where('semester', $semester)
            ->where('new_course_flag', 1)
            ->orderByDesc('Id')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Instructors
        |--------------------------------------------------------------------------
        */

        $instructorDetails = $connection
            ->table('tbl_instructors')
            ->where('Faculty_Code', $facultyCode)
            ->where('Deleted', 0)
            ->orderByDesc('Instructor_ID')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Time
        |--------------------------------------------------------------------------
        */

        $timeDetails = $connection
            ->table('tim')
            ->orderByDesc('id')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Seasons / TTID
        |--------------------------------------------------------------------------
        */

        $seasonDetails = $connection
            ->table('timetables')
            ->where('TTID', $ttid)
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Lab Timetable
        |--------------------------------------------------------------------------
        */

        $labTimetableDetails = $connection
            ->table('lab_timetable')
            ->where('Faculty_Code', $facultyCode)
            ->where('Major_Code', $majorCode)
            ->where('Batch_Year', $batch)
            ->where('TTID', $ttid)
            ->where('Deleted', 0)
            ->orderByDesc('Id')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Return Complete Timetable Data
        |--------------------------------------------------------------------------
        */

        return [
            'timetableDetails' => $timetableDetails,
            'classRoomDetails' => $classRoomDetails,
            'courseDetails' => $courseDetails,
            'instructorDetails' => $instructorDetails,
            'labTimetableDetails' => $labTimetableDetails,
            'timeDetails' => $timeDetails,
            'seasonDetails' => $seasonDetails,
        ];
    }


    public function getSavedTimetableConfigurations($perPage = 10)
    {
        return DB::connection('mysql_ott')
            ->table('tbl_setting_timetable as st')

            ->leftJoin('faculty as f', 'f.faculty_code', '=', 'st.Faculty_Code')
            ->leftJoin('major as m', 'm.major_code', '=', 'st.Major_Code')

            ->select([
                'st.TTID',
                'st.Faculty_Code',
                'st.Major_Code',
                'st.Batch_Year',

                'f.faculty_desc_e',
                'm.major_desc_e',

                DB::raw('COUNT(st.Id) as entry_count'),
                DB::raw('MAX(st.Id) as last_id'),
            ])

            ->whereNotNull('st.Batch_Year')
            ->where('st.Batch_Year', '!=', '')

            ->groupBy(
                'st.TTID',
                'st.Faculty_Code',
                'st.Major_Code',
                'st.Batch_Year',
                'f.faculty_desc_e',
                'm.major_desc_e'
            )

            ->orderByDesc('last_id')

            ->paginate($perPage)

            ->appends(Request::query());
    }

    public function getSavedTimetableRows(
        int $facultyCode,
        int $majorCode,
        string $batch,
        int $ttid
    ) {
        return DB::connection('mysql_ott')
            ->table('tbl_setting_timetable')
            ->where('Faculty_Code', $facultyCode)
            ->where('Major_Code', $majorCode)
            ->where('Batch_Year', $batch)
            ->where('TTID', $ttid)
            ->orderBy('Period')
            ->orderBy('Course_Code')
            ->get();
    }


    public function getTimetableSemester(
        int $facultyCode,
        int $majorCode,
        string $batch,
        int $ttid
    ): ?int {
        $courseCodes = DB::connection('mysql_ott')
            ->table('tbl_setting_timetable')
            ->where('Faculty_Code', $facultyCode)
            ->where('Major_Code', $majorCode)
            ->where('Batch_Year', $batch)
            ->where('TTID', $ttid)
            ->whereNotNull('Course_Code')
            ->pluck('Course_Code')
            ->unique()
            ->values();

        if ($courseCodes->isEmpty()) {
            return null;
        }

        $semesters = DB::connection('mysql_ott')
            ->table('tbl_courses')
            ->where('Faculty_Code', $facultyCode)
            ->where('Major_Code', $majorCode)
            ->where('Batch_Year', $batch)
            ->whereIn('Course_Code', $courseCodes)
            ->where('del', 0)
            ->whereNotNull('semester')
            ->pluck('semester')
            ->unique()
            ->values();

        return $semesters->count() === 1
            ? (int) $semesters->first()
            : null;
    }

    public function deleteTimetable(
        int $facultyCode,
        int $majorCode,
        string $batch,
        int $ttid
    ): int {
        return DB::connection('mysql_ott')
            ->table('tbl_setting_timetable')
            ->where('Faculty_Code', $facultyCode)
            ->where('Major_Code', $majorCode)
            ->where('Batch_Year', $batch)
            ->where('TTID', $ttid)
            ->delete();
    }

    public function replaceTimetable(
        int $facultyCode,
        int $majorCode,
        string $batch,
        int $ttid,
        array $rows
    ): int {

        $connection = DB::connection('mysql_ott');

        return $connection->transaction(function () use ($connection, $facultyCode, $majorCode, $batch, $ttid, $rows) {

            /*
            |--------------------------------------------------------------------------
            | Delete existing timetable
            |--------------------------------------------------------------------------
            */

            $connection
                ->table('tbl_setting_timetable')
                ->where('Faculty_Code', $facultyCode)
                ->where('Major_Code', $majorCode)
                ->where('Batch_Year', $batch)
                ->where('TTID', $ttid)
                ->delete();


            /*
            |--------------------------------------------------------------------------
            | Insert updated timetable
            |--------------------------------------------------------------------------
            */

            if (empty($rows)) {
                return 0;
            }


            foreach (array_chunk($rows, 500) as $chunk) {

                $connection
                    ->table('tbl_setting_timetable')
                    ->insert($chunk);
            }


            return count($rows);
        });
    }

    //Timetable End

    //DB Opreations
    public function truncateTable(string $table): void
    {
        DB::connection('mysql_ott')
            ->table($table)
            ->truncate();
    }

    public function insertTable(string $table, Collection|array $data): void
    {
        $rows = $data instanceof Collection
            ? $data->toArray()
            : $data;

        if (empty($rows)) {
            return;
        }

        DB::connection('mysql_ott')
            ->table($table)
            ->insert($rows);
    }

    public function upsertTable(
        string $table,
        Collection|array $data,
        array $uniqueBy,
        int $chunkSize = 1000
    ): int {
        $rows = $data instanceof Collection
            ? $data->toArray()
            : $data;

        if (empty($rows)) {
            return 0;
        }

        $connection = DB::connection('mysql_ott');

        $rows = array_values($rows);

        $count = 0;

        foreach (array_chunk($rows, $chunkSize) as $chunk) {

            $connection
                ->table($table)
                ->upsert(
                    $chunk,
                    $uniqueBy,
                    array_keys($chunk[0])
                );

            $count += count($chunk);
        }

        return $count;
    }

    public function beginTransaction(): void
    {
        DB::connection('mysql_ott')->beginTransaction();
    }

    public function commit(): void
    {
        DB::connection('mysql_ott')->commit();
    }

    public function rollBack(): void
    {
        DB::connection('mysql_ott')->rollBack();
    }

    public function transactionLevel(): int
    {
        return DB::connection('mysql_ott')->transactionLevel();
    }

    public function countCourses(): int
    {
        // Count distinct courses from tbl_setting_timetable by faculty_code, major_code, batch, and course_code
        return \DB::connection('mysql_ott')
            ->table('tbl_setting_timetable')
            ->selectRaw('COUNT(DISTINCT CONCAT(faculty_code, "-", major_code, "-", Batch_Year, "-", course_code)) as count')
            ->whereNotNull('course_code')
            ->where('TTID', '>=', 40)
            ->where('course_code', '!=', '')
            ->where('new_course_flag', 1)
            ->value('count') ?? 0;
    }

    public function createTimeTable(array $data): array
    {
        $connection = DB::connection('mysql_ott');

        try {

            return $connection->transaction(function () use ($connection, $data) {

                $facultyCode = (int) $data['faculty_code'];
                $majorCode = (int) $data['major_code'];
                $batch = (string) $data['batch'];
                $semester = (int) $data['semester'];
                $ttid = (int) $data['ttid'];

                /*
                |--------------------------------------------------------------------------
                | Check existing timetable
                |--------------------------------------------------------------------------
                */

                $exists = $connection
                    ->table('tbl_setting_timetable')
                    ->where('TTID', $ttid)
                    ->where('Faculty_Code', $facultyCode)
                    ->where('Major_Code', $majorCode)
                    ->where('Batch_Year', $batch)
                    ->exists();

                if ($exists) {

                    return [
                        'success' => false,
                        'message' => 'A timetable already exists for the selected Faculty, Major, Batch, TTID and configuration.',
                    ];
                }


                /*
                |--------------------------------------------------------------------------
                | Insert timetable entries
                |--------------------------------------------------------------------------
                */

                $rows = [];

                foreach ($data['timetable'] as $day => $periods) {

                    foreach ($periods as $period => $entries) {

                        if (empty($entries)) {
                            continue;
                        }

                        foreach ($entries as $entry) {

                            /*
                            |--------------------------------------------------------------------------
                            | Ignore completely empty entries
                            |--------------------------------------------------------------------------
                            */

                            if (
                                empty($entry['course_code']) &&
                                empty($entry['instructor_id']) &&
                                empty($entry['class_id'])
                            ) {
                                continue;
                            }


                            $rows[] = [

                                'TTID' => $ttid,

                                'Dept_Name' => '0',

                                'Batch_Year' => $batch,

                                'Course_Code' => $entry['course_code'] ?? '',

                                'Stud_Group' => $entry['stud_group'] ?? '1',

                                'Instructor_ID' =>
                                    !empty($entry['instructor_id'])
                                    ? (int) $entry['instructor_id']
                                    : null,

                                'Instructor_ID_Tut' =>
                                    !empty($entry['instructor_id_tut'])
                                    ? (int) $entry['instructor_id_tut']
                                    : null,

                                'Instructor_ID_Lab' =>
                                    !empty($entry['instructor_id_lab'])
                                    ? (int) $entry['instructor_id_lab']
                                    : null,

                                'TheoryHrs' =>
                                    isset($entry['theory_hrs'])
                                    ? (int) $entry['theory_hrs']
                                    : 2,

                                'TutorialHrs' =>
                                    isset($entry['tutorial_hrs'])
                                    ? (int) $entry['tutorial_hrs']
                                    : 0,

                                'Period' =>
                                    !empty($entry['period'])
                                    ? (int) $entry['period']
                                    : (int) $period,

                                'Period2' =>
                                    !empty($entry['period2'])
                                    ? (int) $entry['period2']
                                    : null,

                                'LabPeriod' =>
                                    !empty($entry['lab_period'])
                                    ? (int) $entry['lab_period']
                                    : null,

                                'ClassID' =>
                                    !empty($entry['class_id'])
                                    ? (int) $entry['class_id']
                                    : null,

                                'ClassID2' =>
                                    !empty($entry['class_id2'])
                                    ? (int) $entry['class_id2']
                                    : null,

                                'LabID' =>
                                    !empty($entry['lab_id'])
                                    ? (int) $entry['lab_id']
                                    : null,

                                'PracticalHrs' =>
                                    isset($entry['practical_hrs'])
                                    ? (int) $entry['practical_hrs']
                                    : 0,

                                'Faculty_Code' => $facultyCode,

                                'Major_Code' => $majorCode,

                                'Major_Minor' =>
                                    isset($entry['major_minor'])
                                    ? (int) $entry['major_minor']
                                    : null,

                                'User_Name' =>
                                    auth()->user()->name ?? null,

                                'FZ_Flag' => 0,

                                'Dissolved' => 0,

                                'FZ_Semester' => 0,

                                'c_c' => 0,

                                'new_course_flag' => 1,
                            ];
                        }
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Nothing to insert
                |--------------------------------------------------------------------------
                */

                if (empty($rows)) {

                    return [
                        'success' => false,
                        'message' => 'No timetable entries were provided.',
                    ];
                }


                /*
                |--------------------------------------------------------------------------
                | Insert
                |--------------------------------------------------------------------------
                */

                $connection
                    ->table('tbl_setting_timetable')
                    ->insert($rows);


                return [
                    'success' => true,
                    'message' => count($rows) . ' timetable entries created successfully.',
                ];
            });

        } catch (\Throwable $e) {

            report($e);

            return [
                'success' => false,
                'message' => 'Failed to create timetable: ' . $e->getMessage(),
            ];
        }
    }

    //Helpers Functions
    private function formatTime($time): ?string
    {
        if (empty($time)) {
            return null;
        }

        $time = trim($time);

        // Time range: 10:00 - 12:00
        if (str_contains($time, '-')) {

            [$start, $end] = array_map(
                'trim',
                explode('-', $time, 2)
            );

            return $this->formatTimetableTime($start)
                . ' - ' .
                $this->formatTimetableTime($end);
        }

        return $this->formatTimetableTime($time);
    }
    private function formatTimetableTime($time): string
    {
        $time = trim($time);

        return match ($time) {
            '7:00', '07:00' => '07:00 AM',
            '9:00', '09:00' => '09:00 AM',
            '10:00' => '10:00 AM',
            '12:00' => '12:00 PM',
            '12:30' => '12:30 PM',
            '2:30', '02:30' => '02:30 PM',
            '3:00', '03:00' => '03:00 PM',
            '5:00', '05:00' => '05:00 PM',
            default => $time,
        };
    }

    public function faculties(): Collection
    {
        return DB::connection('mysql_sis')
            ->table('faculty')
            ->where('deleted', 0)
            ->get();
    }

    public function majors(): Collection
    {
        return DB::connection('mysql_sis')
            ->table('major')
            ->where('deleted', 0)
            ->get();
    }

    public function batches(): Collection
    {
        return DB::connection('mysql_sis')
            ->table('batch_control')
            ->select('batch')
            ->distinct()
            ->orderBy('batch')
            ->get();
    }
    public function majorsByFaculty(string $facultyCode): Collection
    {
        return DB::connection('mysql_sis')
            ->table('major')
            ->where('faculty_code', $facultyCode)
            ->get();
    }
}