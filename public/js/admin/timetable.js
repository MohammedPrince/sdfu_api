document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Configuration elements
    |--------------------------------------------------------------------------
    */

    const facultySelect = document.getElementById('faculty_code');
    const majorSelect = document.getElementById('major_code');
    const batchSelect = document.getElementById('batch');
    const semesterSelect = document.getElementById('semester');

    let courses = [];


    /*
    |--------------------------------------------------------------------------
    | Modes
    |--------------------------------------------------------------------------
    */

    const isEditMode = Boolean(window.editTimetable);
    const isViewMode = Boolean(window.viewTimetable);


    /*
    |--------------------------------------------------------------------------
    | Instructors
    |--------------------------------------------------------------------------
    */

    const instructors = Array.isArray(window.timetableInstructors)
        ? window.timetableInstructors
        : [];


    /*
    |--------------------------------------------------------------------------
    | Classrooms / Labs
    |--------------------------------------------------------------------------
    |
    | Expected:
    |
    | window.timetableClassrooms = {
    |     classrooms: [...],
    |     labs: [...]
    | }
    |
    | Theory / Tutorial -> classrooms
    | LAB                 -> labs
    |
    |--------------------------------------------------------------------------
    */

    const timetableClassroomData =
        window.timetableClassrooms &&
            typeof window.timetableClassrooms === 'object' &&
            !Array.isArray(window.timetableClassrooms)
            ? window.timetableClassrooms
            : {};

    let timetableClassroomsList =
        Array.isArray(timetableClassroomData.classrooms)
            ? timetableClassroomData.classrooms
            : [];

    let timetableLabsList =
        Array.isArray(timetableClassroomData.labs)
            ? timetableClassroomData.labs
            : [];


    /*
    |--------------------------------------------------------------------------
    | Backward compatibility
    |--------------------------------------------------------------------------
    |
    | If PHP still returns one flat array, detect labs automatically.
    |
    |--------------------------------------------------------------------------
    */

    if (Array.isArray(window.timetableClassrooms)) {

        timetableClassroomsList = [];
        timetableLabsList = [];

        window.timetableClassrooms.forEach(function (item) {

            if (!item) {
                return;
            }

            const isLab =
                item.LabID !== undefined ||
                item.lab_id !== undefined ||
                item.LabName !== undefined ||
                item.lab_name !== undefined;

            if (isLab) {
                timetableLabsList.push(item);
            } else {
                timetableClassroomsList.push(item);
            }
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Existing entries
    |--------------------------------------------------------------------------
    */

    const existingEntries =
        window.existingTimetableEntries &&
            typeof window.existingTimetableEntries === 'object'
            ? window.existingTimetableEntries
            : {};


    /*
    |--------------------------------------------------------------------------
    | Helpers
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


    function getConfiguration() {

        return {
            faculty: facultySelect
                ? facultySelect.value
                : '',

            major: majorSelect
                ? majorSelect.value
                : '',

            batch: batchSelect
                ? batchSelect.value
                : '',

            semester: semesterSelect
                ? semesterSelect.value
                : ''
        };
    }


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
    | Classroom helpers
    |--------------------------------------------------------------------------
    */

    function getClassroomId(item) {

        if (!item) {
            return '';
        }

        return item.Class_ID ??
            item.class_id ??
            item.id ??
            '';
    }


    function getClassroomName(item) {

        if (!item) {
            return '';
        }

        return item.Class_Name ??
            item.class_name ??
            item.name ??
            '';
    }


    /*
    |--------------------------------------------------------------------------
    | Lab helpers
    |--------------------------------------------------------------------------
    */

    function getLabId(item) {

        if (!item) {
            return '';
        }

        return item.LabID ??
            item.lab_id ??
            item.id ??
            '';
    }


    function getLabName(item) {

        if (!item) {
            return '';
        }

        return item.LabName ??
            item.lab_name ??
            item.name ??
            '';
    }


    /*
    |--------------------------------------------------------------------------
    | Find room
    |--------------------------------------------------------------------------
    */

    function findRoomById(id, type) {

        const list = type === 'lab'
            ? timetableLabsList
            : timetableClassroomsList;

        return list.find(function (item) {

            const itemId = type === 'lab'
                ? getLabId(item)
                : getClassroomId(item);

            return String(itemId) === String(id ?? '');

        }) || null;
    }


    function getRoomName(id, type) {

        if (
            id === null ||
            id === undefined ||
            id === ''
        ) {
            return '';
        }

        const item = findRoomById(id, type);

        if (!item) {
            return String(id);
        }

        return type === 'lab'
            ? getLabName(item)
            : getClassroomName(item);
    }


    /*
    |--------------------------------------------------------------------------
    | Course name
    |--------------------------------------------------------------------------
    */

    function getCourseName(courseCode) {

        if (!courseCode) {
            return '';
        }

        const course = courses.find(function (item) {

            const code =
                item.Course_Code ??
                item.course_code ??
                '';

            return String(code) === String(courseCode);

        });

        if (!course) {
            return '';
        }

        return course.Course_Name ??
            course.course_name ??
            '';
    }


    /*
    |--------------------------------------------------------------------------
    | Instructor name
    |--------------------------------------------------------------------------
    */

    function getInstructorName(instructorId) {

        if (
            instructorId === null ||
            instructorId === undefined ||
            instructorId === ''
        ) {
            return '';
        }

        const instructor = instructors.find(function (item) {

            const id =
                item.Instructor_ID ??
                item.instructor_id ??
                '';

            return String(id) === String(instructorId);

        });

        if (!instructor) {
            return String(instructorId);
        }

        return instructor.Instructor_Name ??
            instructor.instructor_name ??
            String(instructorId);
    }


    /*
    |--------------------------------------------------------------------------
    | Group name
    |--------------------------------------------------------------------------
    */

    function getGroupName(group) {

        const groups = {
            '1': 'A',
            '2': 'B',
            '3': 'C'
        };

        return groups[String(group)] ??
            group ??
            '';
    }


    /*
    |--------------------------------------------------------------------------
    | Type name
    |--------------------------------------------------------------------------
    */

    function getTypeName(type) {

        switch (type) {

            case 'theory':
                return 'Lecture';

            case 'tutorial':
                return 'Tutorial';

            case 'lab':
                return 'LAB';

            default:
                return type || 'Lecture';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Add button state
    |--------------------------------------------------------------------------
    */

    function setAddButtonsDisabled(disabled) {

        document
            .querySelectorAll('.timetable-add-btn')
            .forEach(function (button) {

                if (isViewMode) {

                    button.style.display = 'none';

                    return;
                }

                button.disabled = disabled;
            });
    }


    /*
    |--------------------------------------------------------------------------
    | Clear entries
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

        if (
            !config.faculty ||
            !config.major ||
            !config.batch ||
            !config.semester
        ) {

            courses = [];

            setAddButtonsDisabled(true);

            if (isViewMode) {
                renderViewEntries();
            }

            if (isEditMode) {
                renderExistingEntries();
            }

            return;
        }


        setAddButtonsDisabled(true);


        if (!window.adminTimetableCoursesUrl) {

            console.error(
                'adminTimetableCoursesUrl is not defined.'
            );

            courses = [];

            if (isViewMode) {
                renderViewEntries();
            }

            if (isEditMode) {
                renderExistingEntries();
            }

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


            if (isViewMode) {

                renderViewEntries();

            } else if (isEditMode) {

                renderExistingEntries();
            }


        } catch (error) {

            console.error(
                'Course loading error:',
                error
            );

            courses = [];


            if (isViewMode) {

                renderViewEntries();

            } else if (isEditMode) {

                renderExistingEntries();

                alert(
                    'Unable to load courses. Please try again.'
                );
            }

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

        let selectedExists = false;


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
                String(code) ===
                    String(selectedCourse ?? '')
                    ? 'selected'
                    : '';


            if (selected) {
                selectedExists = true;
            }


            html += `
                <option
                    value="${escapeHtml(code)}"
                    ${selected}
                >
                    ${escapeHtml(code)}
                    ${name
                    ? ' - ' + escapeHtml(name)
                    : ''}
                </option>
            `;
        });


        if (
            selectedCourse &&
            !selectedExists
        ) {

            html += `
                <option
                    value="${escapeHtml(selectedCourse)}"
                    selected
                >
                    ${escapeHtml(selectedCourse)}
                </option>
            `;
        }


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

        let selectedExists = false;


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
                String(id) ===
                    String(selectedInstructor ?? '')
                    ? 'selected'
                    : '';


            if (selected) {
                selectedExists = true;
            }


            html += `
                <option
                    value="${escapeHtml(id)}"
                    ${selected}
                >
                    ${escapeHtml(name)}
                </option>
            `;
        });


        if (
            selectedInstructor &&
            !selectedExists
        ) {

            html += `
                <option
                    value="${escapeHtml(selectedInstructor)}"
                    selected
                >
                    ${escapeHtml(selectedInstructor)}
                </option>
            `;
        }


        return html;
    }


    /*
    |--------------------------------------------------------------------------
    | Classroom / Lab options
    |--------------------------------------------------------------------------
    |
    | Theory   -> normal classrooms
    | Tutorial -> normal classrooms
    | LAB      -> labs
    |
    |--------------------------------------------------------------------------
    */

    function buildClassroomOptions(
        selectedRoom,
        selectedType = 'theory'
    ) {

        const isLab = selectedType === 'lab';

        const list = isLab
            ? timetableLabsList
            : timetableClassroomsList;


        let html = `
            <option value="">
                ${isLab
                ? 'Select Lab'
                : 'Select Classroom'}
            </option>
        `;


        let selectedExists = false;


        list.forEach(function (room) {

            const id = isLab
                ? getLabId(room)
                : getClassroomId(room);

            const name = isLab
                ? getLabName(room)
                : getClassroomName(room);


            const selected =
                String(id) ===
                    String(selectedRoom ?? '')
                    ? 'selected'
                    : '';


            if (selected) {
                selectedExists = true;
            }


            html += `
                <option
                    value="${escapeHtml(id)}"
                    ${selected}
                >
                    ${escapeHtml(name)}
                </option>
            `;
        });


        /*
        |--------------------------------------------------------------------------
        | Preserve existing room if it no longer exists in lookup
        |--------------------------------------------------------------------------
        */

        if (
            selectedRoom &&
            !selectedExists
        ) {

            html += `
                <option
                    value="${escapeHtml(selectedRoom)}"
                    selected
                >
                    ${escapeHtml(
                getRoomName(
                    selectedRoom,
                    selectedType
                ) || selectedRoom
            )}
                </option>
            `;
        }


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


        let selectedExists = false;


        groups.forEach(function (group) {

            const selected =
                String(group.value) ===
                    String(selectedGroup ?? '')
                    ? 'selected'
                    : '';


            if (selected) {
                selectedExists = true;
            }


            html += `
                <option
                    value="${group.value}"
                    ${selected}
                >
                    ${group.label}
                </option>
            `;
        });


        if (
            selectedGroup &&
            !selectedExists
        ) {

            html += `
                <option
                    value="${escapeHtml(selectedGroup)}"
                    selected
                >
                    ${escapeHtml(selectedGroup)}
                </option>
            `;
        }


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
                ${type === 'theory'
                ? 'selected'
                : ''}
            >
                Theory
            </option>

            <option
                value="tutorial"
                ${type === 'tutorial'
                ? 'selected'
                : ''}
            >
                Tutorial
            </option>

            <option
                value="lab"
                ${type === 'lab'
                ? 'selected'
                : ''}
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
            0;

        const practicalHours =
            values.practical_hrs ??
            0;


        const container = document.querySelector(
            '.timetable-entries[data-day="' +
            day +
            '"][data-period="' +
            period +
            '"]'
        );


        if (!container) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | View mode
        |--------------------------------------------------------------------------
        */

        if (isViewMode) {

            return createViewTimetableEntry(
                day,
                period,
                values
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Edit mode
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


            <div class="form-group">

                <label>
                    Classroom / Lab
                </label>

                <select
                    name="timetable[${day}][${period}][${index}][class_id]"
                    class="form-control timetable-room"
                >
                    ${buildClassroomOptions(
            classId,
            entryType
        )}
                </select>

            </div>


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


        container.appendChild(entry);


        updateEntryFields(entry);


        return entry;
    }


    /*
    |--------------------------------------------------------------------------
    | Update fields according to entry type
    |--------------------------------------------------------------------------
    */

    function updateEntryFields(entry) {

        if (!entry) {
            return;
        }


        const typeSelect =
            entry.querySelector(
                '.timetable-entry-type'
            );


        const roomSelect =
            entry.querySelector(
                '.timetable-room'
            );


        const theoryHours =
            entry.querySelector(
                '.timetable-theory-hours'
            );


        const practicalHours =
            entry.querySelector(
                '.timetable-practical-hours'
            );


        if (!typeSelect) {
            return;
        }


        const type =
            typeSelect.value || 'theory';


        /*
        |--------------------------------------------------------------------------
        | Rebuild classroom / lab list
        |--------------------------------------------------------------------------
        */

        if (roomSelect) {

            const currentRoom =
                roomSelect.value;


            roomSelect.innerHTML =
                buildClassroomOptions(
                    currentRoom,
                    type
                );
        }


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

                    if (isViewMode) {
                        return;
                    }


                    if (!configurationIsComplete()) {

                        alert(
                            'Please select Faculty, Major, Batch and Semester first.'
                        );

                        return;
                    }


                    if (!courses.length) {

                        alert(
                            'No courses are available for the selected Faculty, Major, Batch and Semester.'
                        );

                        return;
                    }


                    const day =
                        this.dataset.day;


                    const period =
                        this.dataset.period;


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


                    const index =
                        container.querySelectorAll(
                            '.timetable-entry'
                        ).length;


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
    | Remove timetable entry
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


            if (isViewMode) {
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


            if (isViewMode) {
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
    | Render existing timetable
    |--------------------------------------------------------------------------
    */

    function renderExistingEntries() {

        clearTimetableEntries();


        if (
            !existingEntries ||
            typeof existingEntries !== 'object'
        ) {

            console.warn(
                'No existing timetable entries found.'
            );

            return;
        }


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
                            function (
                                values,
                                index
                            ) {

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
    | View entry
    |--------------------------------------------------------------------------
    */

    function createViewTimetableEntry(
        day,
        period,
        values = {}
    ) {

        const container = document.querySelector(
            '.timetable-entries[data-day="' +
            day +
            '"][data-period="' +
            period +
            '"]'
        );


        if (!container) {
            return null;
        }


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


        const courseName =
            getCourseName(courseCode);


        const instructorName =
            getInstructorName(instructorId);


        const roomName =
            getRoomName(
                classId,
                entryType
            );


        const groupName =
            getGroupName(studGroup);


        const typeName =
            getTypeName(entryType);


        const entry =
            document.createElement('div');


        entry.className =
            'timetable-entry timetable-view-entry';


        entry.innerHTML = `

            <div class="timetable-view-entry-course">

                ${escapeHtml(courseCode)}

                ${courseName
                ? ' - ' +
                escapeHtml(courseName)
                : ''
            }

            </div>


            ${instructorName
                ? `
                        <div class="timetable-view-entry-line">

                            <strong>
                                Instructor:
                            </strong>

                            ${escapeHtml(
                    instructorName
                )}

                        </div>
                      `
                : ''
            }


            ${roomName || groupName
                ? `
                        <div class="timetable-view-entry-line">

                            <strong>
                                Room:
                            </strong>

                            ${escapeHtml(
                    roomName || '—'
                )}

                            ${groupName
                    ? ', Group: ' +
                    escapeHtml(
                        groupName
                    )
                    : ''
                }

                        </div>
                      `
                : ''
            }


            <div class="timetable-view-entry-type">

                ${escapeHtml(typeName)}

            </div>

        `;


        container.appendChild(entry);


        return entry;
    }


    /*
    |--------------------------------------------------------------------------
    | Render view entries
    |--------------------------------------------------------------------------
    */

    function renderViewEntries() {

        clearTimetableEntries();


        if (
            !existingEntries ||
            typeof existingEntries !== 'object'
        ) {
            return;
        }


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
                            function (values) {

                                createViewTimetableEntry(
                                    day,
                                    period,
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

        if (isViewMode) {
            return;
        }


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
    */

    if (configurationIsComplete()) {

        loadCourses();

    } else {

        if (isViewMode) {

            /*
            |--------------------------------------------------------------------------
            | View mode can have hidden configuration inputs.
            |--------------------------------------------------------------------------
            */

            renderViewEntries();

        } else {

            setAddButtonsDisabled(true);
        }
    }

});