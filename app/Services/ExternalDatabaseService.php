<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

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
                'e.stud_email',
                'e.stud_tel_mobile',

                // c.* — add/remove to match what callers actually use
                'c.batch',
                'c.semester',
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

                'points' => (float) $row->weightage,

                'remark' => $row->remark ?? '',

                'gpa' => round((float) $row->gpa, 2),

                'cgpa' => round((float) $row->cgpa, 2),

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
}