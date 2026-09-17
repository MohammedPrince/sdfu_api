
document.addEventListener('DOMContentLoaded', function () {

    const facultySelect = document.getElementById('faculty_code');
    const majorSelect = document.getElementById('major_code');

    facultySelect.addEventListener('change', function () {

        const facultyCode = this.value;

        // Reset major
        majorSelect.innerHTML = `
            <option value="">Select Major</option>
        `;

        majorSelect.disabled = true;

        if (!facultyCode) {
            return;
        }

        fetch(`{{ url('/admin/manage/majors') }}/${encodeURIComponent(facultyCode)}`)
            .then(response => {

                if (!response.ok) {
                    throw new Error('Failed to load majors.');
                }

                return response.json();
            })
            .then(majors => {

                majors.forEach(major => {

                    const option = document.createElement('option');

                    option.value = major.major_code;
                    option.textContent = major.major_desc_e;

                    majorSelect.appendChild(option);
                });

                majorSelect.disabled = false;
            })
            .catch(error => {

                console.error(error);

                majorSelect.innerHTML = `
                    <option value="">Unable to load majors</option>
                `;

                majorSelect.disabled = true;
            });
    });

});
