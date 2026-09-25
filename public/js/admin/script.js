console.log('admin script.js loaded successfully');
//Lod Faculty, Major and batch
document.addEventListener('DOMContentLoaded', function () {

    const facultySelect = document.getElementById('faculty_code');
    const majorSelect = document.getElementById('major_code');

    if (!facultySelect || !majorSelect) {
        return;
    }

    console.log('Faculty Select:', facultySelect);
    console.log('Major Select:', majorSelect);

    console.log('Faculty Code:', facultySelect?.value);
    console.log('Major Code:', majorSelect?.value);

    facultySelect.addEventListener('change', function () {

        const facultyCode = this.value.trim();

        console.log('Selected Faculty Code:', facultyCode);

        // Reset major
        majorSelect.innerHTML = '<option value="">Select Major</option>';
        majorSelect.disabled = true;

        // No faculty selected
        if (!facultyCode) {
            return;
        }

        // Make sure URL exists
        if (!window.adminMajorsUrl) {
            console.error('adminMajorsUrl is not defined.');
            return;
        }

        const url = `${window.adminMajorsUrl}/${encodeURIComponent(facultyCode)}`;

        console.log('Loading majors from:', url);

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(response => {

                if (!response.ok) {
                    throw new Error(
                        `Failed to load majors. HTTP ${response.status}`
                    );
                }

                return response.json();
            })
            .then(majors => {

                // Make sure response is an array
                if (!Array.isArray(majors)) {
                    throw new Error('Invalid majors response.');
                }

                majorSelect.innerHTML =
                    '<option value="">Select Major</option>';

                majors.forEach(major => {

                    const option = document.createElement('option');

                    option.value = major.major_code;
                    option.textContent = major.major_desc_e;

                    majorSelect.appendChild(option);
                });

                majorSelect.disabled = false;

                console.log('Majors loaded:', majors);
            })
            .catch(error => {

                console.error('Major loading error:', error);

                majorSelect.innerHTML =
                    '<option value="">Unable to load majors</option>';

                majorSelect.disabled = true;
            });

    });

});