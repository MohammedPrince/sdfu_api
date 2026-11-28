/*
|--------------------------------------------------------------------------
| Admin DataTables (responsive) - applied to every table.settings-table
|--------------------------------------------------------------------------
|
| data-datatable on the <table> selects the mode:
|
|   (none) / "full"  Complete list on the page: search, sort and paging
|                    are done in the browser.
|   "paginated"      Rows come from a Laravel paginator: keep the server
|                    pagination/filters, DataTables only adds the
|                    responsive layout (no client search/sort/paging).
|   "custom"         The page initialises the table itself (skip here).
|   "off"            Leave the table untouched.
|
| Optional attributes:
|   data-page-length="25"            rows per page (full mode)
|   data-search-placeholder="..."    search box placeholder (full mode)
|   <th data-priority="1">           responsive column priority
|
*/

(function () {

    if (typeof DataTable === 'undefined') {
        console.warn('DataTables library not loaded.');
        return;
    }

    // Report load problems in the console instead of alert() popups
    DataTable.ext.errMode = 'none';

    function isActionCell(cell) {
        return !!cell && !!cell.querySelector('form, button, a.btn, a > button, input, select');
    }

    /**
     * Remove the "@forelse ... @empty" placeholder row (a single colspan cell),
     * which DataTables cannot handle, and return its text for the empty message.
     */
    function takeEmptyRowMessage(table) {
        let message = '';

        table.querySelectorAll('tbody > tr').forEach(function (row) {
            const cells = row.querySelectorAll('td');

            if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
                message = message || cells[0].textContent.trim();
                row.remove();
            }
        });

        return message;
    }

    function initTable(table) {

        const mode = table.dataset.datatable || 'full';

        if (mode === 'off' || mode === 'custom' || DataTable.isDataTable(table)) {
            return;
        }

        const emptyMessage = takeEmptyRowMessage(table) || 'No records found.';
        const rows = table.querySelectorAll('tbody > tr');
        const headers = Array.from(table.querySelectorAll('thead th'));
        const firstRow = rows[0];

        // Columns holding buttons/forms: not sortable or searchable
        const actionColumns = headers
            .map(function (th, index) {
                const label = th.textContent.trim();
                const cell = firstRow ? firstRow.children[index] : null;
                return (label === '' || isActionCell(cell)) ? index : null;
            })
            .filter(function (index) { return index !== null; });

        // Keep the first column and the last (usually actions) visible longest,
        // unless the page set explicit priorities with <th data-priority>.
        const columnDefs = [
            { targets: actionColumns, orderable: false, searchable: false },
        ];

        if (!headers.some(function (th) { return th.hasAttribute('data-priority'); })) {
            columnDefs.push({ targets: 0, responsivePriority: 1 });
            columnDefs.push({ targets: -1, responsivePriority: 2 });
        }

        const isFull = mode === 'full';
        const pageLength = parseInt(table.dataset.pageLength || '10', 10);
        const needsPaging = isFull && rows.length > pageLength;

        table.classList.add('display', 'nowrap', 'admin-datatable');
        table.style.width = '100%';

        new DataTable(table, {
            responsive: true,
            autoWidth: false,
            columnDefs: columnDefs,

            // Keep the server's row order until the user clicks a header
            order: [],
            ordering: isFull,
            searching: isFull && rows.length > 5,
            paging: needsPaging,
            info: needsPaging,
            pageLength: pageLength,
            lengthMenu: [10, 25, 50, 100],

            layout: {
                topStart: isFull && rows.length > 5 ? 'search' : null,
                topEnd: needsPaging ? 'pageLength' : null,
                bottomStart: needsPaging ? 'info' : null,
                bottomEnd: needsPaging ? 'paging' : null,
            },

            language: {
                search: '',
                searchPlaceholder: table.dataset.searchPlaceholder || 'Search...',
                lengthMenu: '_MENU_ per page',
                info: 'Showing _START_ to _END_ of _TOTAL_ results',
                infoEmpty: 'No results',
                infoFiltered: '(filtered from _MAX_)',
                emptyTable: emptyMessage,
                zeroRecords: 'No matching records found.',
            },
        });
    }

    function initAll(root) {
        (root || document).querySelectorAll('table.settings-table').forEach(initTable);
    }

    // Expose for pages that add tables dynamically
    window.initAdminDataTables = initAll;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(); });
    } else {
        initAll();
    }

    // Recalculate hidden columns when a collapsed <details> opens, etc.
    document.addEventListener('toggle', function () {
        DataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
    }, true);

})();
