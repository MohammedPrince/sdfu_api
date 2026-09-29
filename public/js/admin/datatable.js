
document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('studentSearch');
    const clearButton = document.getElementById('clearStudentSearch');
    const searchCount = document.getElementById('studentSearchCount');
    const noStudentsFound = document.getElementById('noStudentsFound');

    if (!searchInput) {
        return;
    }

    const table = document.querySelector('.students-table');

    if (!table) {
        return;
    }

    const rows = Array.from(
        table.querySelectorAll('tbody tr[data-student-index]')
    );

    function filterStudents() {

        const searchValue = searchInput.value
            .trim()
            .toLowerCase();

        let visibleCount = 0;

        rows.forEach(function (row) {

            const studentIndex =
                row.dataset.studentIndex || '';

            const matched =
                searchValue === '' ||
                studentIndex.includes(searchValue);

            row.style.display = matched ? '' : 'none';

            if (matched) {
                visibleCount++;
            }

        });

        /*
        |--------------------------------------------------------------------------
        | Clear button
        |--------------------------------------------------------------------------
        */

        clearButton.style.display =
            searchValue !== '' ? 'flex' : 'none';


        /*
        |--------------------------------------------------------------------------
        | Search result count
        |--------------------------------------------------------------------------
        */

        if (searchValue !== '') {

            searchCount.textContent =
                visibleCount === 0
                    ? 'No students found'
                    : `${visibleCount} student${visibleCount === 1 ? '' : 's'} found`;

        } else {

            searchCount.textContent = '';

        }


        /*
        |--------------------------------------------------------------------------
        | No results row
        |--------------------------------------------------------------------------
        */

        if (noStudentsFound) {

            noStudentsFound.style.display =
                searchValue !== '' && visibleCount === 0
                    ? 'table-cell'
                    : 'none';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    searchInput.addEventListener('input', filterStudents);


    /*
    |--------------------------------------------------------------------------
    | Clear
    |--------------------------------------------------------------------------
    */

    clearButton.addEventListener('click', function () {

        searchInput.value = '';

        filterStudents();

        searchInput.focus();

    });


    /*
    |--------------------------------------------------------------------------
    | Escape = clear search
    |--------------------------------------------------------------------------
    */

    searchInput.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {

            searchInput.value = '';

            filterStudents();

        }

    });

});
