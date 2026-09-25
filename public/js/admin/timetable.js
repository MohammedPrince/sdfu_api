document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const facultySelect = document.getElementById('faculty_code');
    const majorSelect = document.getElementById('major_code');
    const batchSelect = document.getElementById('batch');
    const semesterSelect = document.getElementById('semester');

    let courses = [];

    const instructors = Array.isArray(window.timetableInstructors)
        ? window.timetableInstructors
        : [];

    const classrooms = Array.isArray(window.timetableClassrooms)
        ? window.timetableClassrooms
        : [];

    /*
    |--------------------------------------------------------------------------
    | Edit mode
    |--------------------------------------------------------------------------
    */

    const isEditMode = Boolean(window.editTimetable);

    /*
    | Expected structure:
    |
    | window.existingTimetableEntries = {
    |     "0": {
    |         "1": [
    |             {
    |                 course_code: "BABA301",
    |                 stud_group: 1,
    |                 instructor_id: 5,
    |                 class_id: 2,
    |                 entry_type: "theory",
    |                 theory_hrs: 2,
    |                 practical_hrs: 0
    |             }
    |         ]
    |     }
    | }
    |
    */

    const existingEntries =
        window.existingTimetableEntries &&
            typeof window.existingTimetableEntries === 'object'
            ? window.existingTimetableEntries
            : {};

    /*
    |--------------------------------------------------------------------------
    | Escape HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {

        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /*
    |--------------------------------------------------------------------------
    | Get configuration
    |--------------------------------------------------------------------------
    */

    function getConfiguration() {

        return {
            faculty: facultySelect ? facultySelect.value : '',
            major: majorSelect ? majorSelect.value : '',
            batch: batchSelect ? batchSelect.value : '',
            semester: semesterSelect ? semesterSelect.value : ''
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration complete
    |--------------------------------------------------------------------------
    */

    function configurationIsComplete() {

        const config = getConfiguration();

        return Boolean(
            config.faculty &&
            config.major &&
            config.batch &&
            config.semester
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Enable / Disable Add buttons
    |--------------------------------------------------------------------------
    */

    function setAddButtonsDisabled(disabled) {

        document
            .querySelectorAll('.timetable-add-btn')
            .forEach(function (button) {

                button.disabled = disabled;

            });
    }

    /*
    |--------------------------------------------------------------------------
    | Clear timetable entries
    |--------------------------------------------------------------------------
    */

    function clearTimetableEntries() {

        document
            .querySelectorAll('.timetable-entry')
            .forEach(function (entry) {

                entry.remove();

            });
    }

    /*
    |--------------------------------------------------------------------------
    | Load courses
    |--------------------------------------------------------------------------
    */

    async function loadCourses() {

        const config = getConfiguration();

        /*
        |--------------------------------------------------------------------------
        | Configuration incomplete
        |--------------------------------------------------------------------------
        */

        if (
            !config.faculty ||
            !config.major ||
            !config.batch ||
            !config.semester
        ) {

            courses = [];

            setAddButtonsDisabled(true);

            return;

        }

        setAddButtonsDisabled(true);

        /*
        |--------------------------------------------------------------------------
        | URL
        |--------------------------------------------------------------------------
        */

        if (!window.adminTimetableCoursesUrl) {

            console.error(
                'adminTimetableCoursesUrl is not defined.'
            );

            courses = [];

            setAddButtonsDisabled(false);

            return;
        }

        try {

            const url = new URL(
                window.adminTimetableCoursesUrl,
                window.location.origin
            );

            url.searchParams.set(
                'faculty_code',
                config.faculty
            );

            url.searchParams.set(
                'major_code',
                config.major
            );

            url.searchParams.set(
                'batch',
                config.batch
            );

            url.searchParams.set(
                'semester',
                config.semester
            );

            const response = await fetch(
                url.toString(),
                {
                    method: 'GET',

                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            if (!response.ok) {

                throw new Error(
                    'Failed to load courses. HTTP ' +
                    response.status
                );

            }

            const data = await response.json();

            /*
            |--------------------------------------------------------------------------
            | Support:
            |
            | { courses: [...] }
            | OR
            | [...]
            |--------------------------------------------------------------------------
            */

            if (Array.isArray(data)) {

                courses = data;

            } else if (
                data &&
                Array.isArray(data.courses)
            ) {

                courses = data.courses;

            } else {

                courses = [];

            }

            /*
            |--------------------------------------------------------------------------
            | Edit mode
            |--------------------------------------------------------------------------
            */

            if (isEditMode) {

                renderExistingEntries();

            }

        } catch (error) {

            console.error(
                'Course loading error:',
                error
            );

            courses = [];

            /*
            |--------------------------------------------------------------------------
            | Do not show alert in edit mode if the page is initially loading
            |--------------------------------------------------------------------------
            */

            alert(
                'Unable to load courses. Please try again.'
            );

        } finally {

            setAddButtonsDisabled(false);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Course options
    |--------------------------------------------------------------------------
    */

    function buildCourseOptions(selectedCourse) {

        let html = `
            <option value="">
                Select Course
            </option>
        `;

        courses.forEach(function (course) {

            const code =
                course.Course_Code ??
                course.course_code ??
                '';

            const name =
                course.Course_Name ??
                course.course_name ??
                '';

            const selected =
                String(code) === String(selectedCourse ?? '')
                    ? 'selected'
                    : '';

            html += `
                <option
                    value="${escapeHtml(code)}"
                    ${selected}
                >
                    ${escapeHtml(code)}
                    ${name ? ' - ' + escapeHtml(name) : ''}
                </option>
            `;

        });

        return html;
    }

    /*
    |--------------------------------------------------------------------------
    | Instructor options
    |--------------------------------------------------------------------------
    */

    function buildInstructorOptions(selectedInstructor) {

        let html = `
            <option value="">
                Select Instructor
            </option>
        `;

        instructors.forEach(function (instructor) {

            const id =
                instructor.Instructor_ID ??
                instructor.instructor_id ??
                '';

            const name =
                instructor.Instructor_Name ??
                instructor.instructor_name ??
                '';

            const selected =
                String(id) === String(selectedInstructor ?? '')
                    ? 'selected'
                    : '';

            html += `
                <option
                    value="${escapeHtml(id)}"
                    ${selected}
                >
                    ${escapeHtml(name)}
                </option>
            `;

        });

        return html;
    }

    /*
    |--------------------------------------------------------------------------
    | Classroom options
    |--------------------------------------------------------------------------
    */

    function buildClassroomOptions(selectedClassroom) {

        let html = `
            <option value="">
                Select Classroom
            </option>
        `;

        classrooms.forEach(function (classroom) {

            const id =
                classroom.Class_ID ??
                classroom.class_id ??
                '';

            const name =
                classroom.Class_Name ??
                classroom.class_name ??
                '';

            const selected =
                String(id) === String(selectedClassroom ?? '')
                    ? 'selected'
                    : '';

            html += `
                <option
                    value="${escapeHtml(id)}"
                    ${selected}
                >
                    ${escapeHtml(name)}
                </option>
            `;

        });

        return html;
    }

    /*
    |--------------------------------------------------------------------------
    | Group options
    |--------------------------------------------------------------------------
    */

    function buildGroupOptions(selectedGroup) {

        const groups = [
            {
                value: '1',
                label: 'A'
            },
            {
                value: '2',
                label: 'B'
            },
            {
                value: '3',
                label: 'C'
            }
        ];

        let html = `
            <option value="">
                Select Group
            </option>
        `;

        groups.forEach(function (group) {

            const selected =
                String(group.value) ===
                    String(selectedGroup ?? '')
                    ? 'selected'
                    : '';

            html += `
                <option
                    value="${group.value}"
                    ${selected}
                >
                    ${group.label}
                </option>
            `;

        });

        return html;
    }

    /*
    |--------------------------------------------------------------------------
    | Type options
    |--------------------------------------------------------------------------
    */

    function buildTypeOptions(selectedType) {

        const type =
            selectedType ||
            'theory';

        return `
            <option
                value="theory"
                ${type === 'theory' ? 'selected' : ''}
            >
                Theory
            </option>

            <option
                value="tutorial"
                ${type === 'tutorial' ? 'selected' : ''}
            >
                Tutorial
            </option>

            <option
                value="lab"
                ${type === 'lab' ? 'selected' : ''}
            >
                LAB
            </option>
        `;
    }

    /*
    |--------------------------------------------------------------------------
    | Create timetable entry
    |--------------------------------------------------------------------------
    */

    function createTimetableEntry(
        day,
        period,
        index,
        values = {}
    ) {

        const courseCode =
            values.course_code ??
            '';

        const studGroup =
            values.stud_group ??
            '';

        const instructorId =
            values.instructor_id ??
            '';

        const classId =
            values.class_id ??
            '';

        const entryType =
            values.entry_type ??
            'theory';

        const theoryHours =
            values.theory_hrs ??
            (entryType === 'theory' ? 2 : 0);

        const practicalHours =
            values.practical_hrs ??
            (entryType === 'lab' ? 2 : 0);

        /*
        |--------------------------------------------------------------------------
        | Find container
        |--------------------------------------------------------------------------
        */

        const container = document.querySelector(
            '.timetable-entries[data-day="' +
            day +
            '"][data-period="' +
            period +
            '"]'
        );

        if (!container) {

            console.warn(
                'Timetable container not found:',
                day,
                period
            );

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Create element
        |--------------------------------------------------------------------------
        */

        const entry = document.createElement('div');

        entry.className = 'timetable-entry';

        entry.innerHTML = `

            <div class="timetable-entry-header">

                <strong>
                    Timetable Entry
                </strong>

                <button
                    type="button"
                    class="timetable-remove-btn"
                    title="Remove"
                >
                    ×
                </button>

            </div>


            <!-- Course -->

            <div class="form-group">

                <label>
                    Course
                </label>

                <select
                    name="timetable[${day}][${period}][${index}][course_code]"
                    class="form-control timetable-course"
                    required
                >

                    ${buildCourseOptions(courseCode)}

                </select>

            </div>


            <!-- Group -->

            <div class="form-group">

                <label>
                    Group
                </label>

                <select
                    name="timetable[${day}][${period}][${index}][stud_group]"
                    class="form-control"
                    required
                >

                    ${buildGroupOptions(studGroup)}

                </select>

            </div>


            <!-- Instructor -->

            <div class="form-group">

                <label>
                    Instructor
                </label>

                <select
                    name="timetable[${day}][${period}][${index}][instructor_id]"
                    class="form-control"
                >

                    ${buildInstructorOptions(instructorId)}

                </select>

            </div>


            <!-- Classroom -->

            <div class="form-group">

                <label>
                    Classroom
                </label>

                <select
                    name="timetable[${day}][${period}][${index}][class_id]"
                    class="form-control"
                >

                    ${buildClassroomOptions(classId)}

                </select>

            </div>


            <!-- Type -->

            <div class="form-group">

                <label>
                    Type
                </label>

                <select
                    name="timetable[${day}][${period}][${index}][entry_type]"
                    class="form-control timetable-entry-type"
                >

                    ${buildTypeOptions(entryType)}

                </select>

            </div>


            <!-- Hours -->

            <div class="form-grid">

                <div class="form-group">

                    <label>
                        Theory Hours
                    </label>

                    <input
                        type="number"
                        name="timetable[${day}][${period}][${index}][theory_hrs]"
                        class="form-control timetable-theory-hours"
                        value="${escapeHtml(theoryHours)}"
                        min="0"
                        max="10"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Practical Hours
                    </label>

                    <input
                        type="number"
                        name="timetable[${day}][${period}][${index}][practical_hrs]"
                        class="form-control timetable-practical-hours"
                        value="${escapeHtml(practicalHours)}"
                        min="0"
                        max="10"
                    >

                </div>

            </div>

        `;

        /*
        |--------------------------------------------------------------------------
        | Append
        |--------------------------------------------------------------------------
        */

        container.appendChild(entry);

        /*
        |--------------------------------------------------------------------------
        | Update fields according to type
        |--------------------------------------------------------------------------
        */

        updateEntryFields(entry);

        return entry;
    }

    /*
    |--------------------------------------------------------------------------
    | Update fields according to entry type
    |--------------------------------------------------------------------------
    */

    function updateEntryFields(entry) {

        const typeSelect =
            entry.querySelector('.timetable-entry-type');

        const theoryHours =
            entry.querySelector('.timetable-theory-hours');

        const practicalHours =
            entry.querySelector('.timetable-practical-hours');

        if (!typeSelect) {
            return;
        }

        const type = typeSelect.value;

        /*
        |--------------------------------------------------------------------------
        | Theory
        |--------------------------------------------------------------------------
        */

        if (type === 'theory') {

            if (
                theoryHours &&
                (
                    theoryHours.value === '' ||
                    Number(theoryHours.value) === 0
                )
            ) {

                theoryHours.value = 2;

            }

            if (practicalHours) {
                practicalHours.value = 0;
            }

        }

        /*
        |--------------------------------------------------------------------------
        | Tutorial
        |--------------------------------------------------------------------------
        */

        else if (type === 'tutorial') {

            if (theoryHours) {
                theoryHours.value = 0;
            }

            if (
                practicalHours &&
                (
                    practicalHours.value === '' ||
                    Number(practicalHours.value) === 0
                )
            ) {

                practicalHours.value = 0;

            }

        }

        /*
        |--------------------------------------------------------------------------
        | LAB
        |--------------------------------------------------------------------------
        */

        else if (type === 'lab') {

            if (theoryHours) {
                theoryHours.value = 0;
            }

            if (
                practicalHours &&
                (
                    practicalHours.value === '' ||
                    Number(practicalHours.value) === 0
                )
            ) {

                practicalHours.value = 2;

            }

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Add timetable entry
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.timetable-add-btn')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    /*
                    |--------------------------------------------------------------------------
                    | Validate configuration
                    |--------------------------------------------------------------------------
                    */

                    if (!configurationIsComplete()) {

                        alert(
                            'Please select Faculty, Major, Batch and Semester first.'
                        );

                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Validate courses
                    |--------------------------------------------------------------------------
                    */

                    if (!courses.length) {

                        alert(
                            'No courses are available for the selected Faculty, Major, Batch and Semester.'
                        );

                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | IMPORTANT:
                    |
                    | period is the GLOBAL tim.id:
                    |
                    | Saturday:  1 - 4
                    | Sunday:    5 - 8
                    | Monday:    9 - 12
                    | Tuesday:  13 - 16
                    | Wednesday:17 - 20
                    | Thursday: 21 - 24
                    |--------------------------------------------------------------------------
                    */

                    const day =
                        this.dataset.day;

                    const period =
                        this.dataset.period;

                    /*
                    |--------------------------------------------------------------------------
                    | Find cell
                    |--------------------------------------------------------------------------
                    */

                    const container =
                        document.querySelector(
                            '.timetable-entries[data-day="' +
                            day +
                            '"][data-period="' +
                            period +
                            '"]'
                        );

                    if (!container) {

                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Generate index
                    |--------------------------------------------------------------------------
                    */

                    const index =
                        container.querySelectorAll(
                            '.timetable-entry'
                        ).length;

                    /*
                    |--------------------------------------------------------------------------
                    | Create
                    |--------------------------------------------------------------------------
                    */

                    createTimetableEntry(
                        day,
                        period,
                        index
                    );

                }
            );

        });

    /*
    |--------------------------------------------------------------------------
    | Remove dynamically-created entry
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        function (event) {

            const button =
                event.target.closest(
                    '.timetable-remove-btn'
                );

            if (!button) {
                return;
            }

            const entry =
                button.closest(
                    '.timetable-entry'
                );

            if (!entry) {
                return;
            }

            entry.remove();

        }
    );

    /*
    |--------------------------------------------------------------------------
    | Entry type changed
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'change',
        function (event) {

            if (
                !event.target.classList.contains(
                    'timetable-entry-type'
                )
            ) {

                return;
            }

            const entry =
                event.target.closest(
                    '.timetable-entry'
                );

            if (!entry) {
                return;
            }

            updateEntryFields(entry);

        }
    );

    /*
    |--------------------------------------------------------------------------
    | Render existing timetable entries
    |--------------------------------------------------------------------------
    */

    function renderExistingEntries() {

        /*
        |--------------------------------------------------------------------------
        | Make sure courses are loaded
        |--------------------------------------------------------------------------
        */

        if (!courses.length) {

            console.warn(
                'Cannot render existing timetable entries because courses are empty.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate rendering
        |--------------------------------------------------------------------------
        */

        clearTimetableEntries();

        /*
        |--------------------------------------------------------------------------
        | Loop:
        |
        | existingEntries[day][period][]
        |--------------------------------------------------------------------------
        */

        Object.keys(existingEntries).forEach(
            function (day) {

                const periods =
                    existingEntries[day];

                if (
                    !periods ||
                    typeof periods !== 'object'
                ) {

                    return;
                }

                Object.keys(periods).forEach(
                    function (period) {

                        const entries =
                            periods[period];

                        if (!Array.isArray(entries)) {

                            return;
                        }

                        entries.forEach(
                            function (values, index) {

                                createTimetableEntry(
                                    day,
                                    period,
                                    index,
                                    values
                                );

                            }
                        );

                    }
                );

            }
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Configuration changed
    |--------------------------------------------------------------------------
    */

    function configurationChanged() {

        /*
        |--------------------------------------------------------------------------
        | If user changes academic configuration, old entries must disappear.
        |--------------------------------------------------------------------------
        */

        clearTimetableEntries();

        courses = [];

        loadCourses();

    }

    /*
    |--------------------------------------------------------------------------
    | Configuration listeners
    |--------------------------------------------------------------------------
    */

    if (facultySelect) {

        facultySelect.addEventListener(
            'change',
            configurationChanged
        );

    }

    if (majorSelect) {

        majorSelect.addEventListener(
            'change',
            configurationChanged
        );

    }

    if (batchSelect) {

        batchSelect.addEventListener(
            'change',
            configurationChanged
        );

    }

    if (semesterSelect) {

        semesterSelect.addEventListener(
            'change',
            configurationChanged
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Initial load
    |--------------------------------------------------------------------------
    |
    | This is important for EDIT mode.
    |
    | When the edit page opens, Faculty/Major/Batch/Semester are already
    | selected. We need to load the courses and then render the existing
    | timetable entries.
    |
    |--------------------------------------------------------------------------
    */

    if (configurationIsComplete()) {

        loadCourses();

    } else {

        setAddButtonsDisabled(true);

    }

});