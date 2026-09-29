@extends('admin.layouts.app')

@section('title', 'Create/View Timetables')
@section('page-title', 'Create/View Timetables')
@section('page-description', 'Create, Update or Delete existing timetables')

@section('content')

    <div class="manage-page">

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="admin-card saved-settings-card">

            <div class="admin-card-header">

                <div class="settings-section">

                    <h4>Create/View Timetable</h4>

                    <p>
                        Create/View current timetables for each faculty, major and batch.
                    </p>
                    <br>

                    <p>
                        <a href="{{ route('admin.timetable.create') }}">
                            <button class="btn-primary">Create Timetable</button>
                        </a>
                    </p>

                </div>

            </div>

            <div class="settings-section">
                <div class="table-responsive">

                    <table class="settings-table reports-table">

                        <thead>

                            <tr>

                                <th>
                                    TTID
                                </th>

                                <th>
                                    Faculty
                                </th>

                                <th>
                                    Major
                                </th>

                                <th>
                                    Batch
                                </th>

                                <th>
                                    Entries
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse ($timetables as $timetable)
                                <tr>

                                    <td>
                                        {{ $timetable->TTID }}
                                    </td>

                                    <td>
                                        {{ $timetable->faculty_desc_e ?? $timetable->Faculty_Code }}
                                    </td>

                                    <td>
                                        {{ $timetable->major_desc_e ?? $timetable->Major_Code }}
                                    </td>

                                    <td>
                                        {{ $timetable->Batch_Year }}
                                    </td>

                                    <td>
                                        {{ $timetable->entry_count }}
                                    </td>

                                    <td>

                                        <a
                                            href="{{ route('admin.timetable.edit', [
                                                'faculty_code' => $timetable->Faculty_Code,
                                                'major_code' => $timetable->Major_Code,
                                                'batch' => $timetable->Batch_Year,
                                                'ttid' => $timetable->TTID,
                                                'type' => 'view',
                                            ]) }}">
                                            <button class="btn-primary">View</button>
                                        </a>

                                        <a
                                            href="{{ route('admin.timetable.edit', [
                                                'faculty_code' => $timetable->Faculty_Code,
                                                'major_code' => $timetable->Major_Code,
                                                'batch' => $timetable->Batch_Year,
                                                'ttid' => $timetable->TTID,
                                                'type' => 'edit',
                                            ]) }}">
                                            <button class="btn-secondary">Edit</button>
                                        </a>

                                        <form method="POST"
                                            action="{{ route('admin.timetable.delete', [
                                                'faculty_code' => $timetable->Faculty_Code,
                                                'major_code' => $timetable->Major_Code,
                                                'batch' => $timetable->Batch_Year,
                                                'ttid' => $timetable->TTID,
                                                'type' => 'edit',
                                            ]) }}"
                                            style="display:inline;"
                                            onsubmit="return confirm('Delete this complete timetable? This action cannot be undone.');">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn-danger">
                                                Delete
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="7" style="text-align:center;">
                                        No timetables have been created yet.
                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                    @if ($timetables->hasPages())
                        @php
                            $timetablePaginator = $timetables;
                            $startPage = max(1, $timetablePaginator->currentPage() - 1);
                            $endPage = min($timetablePaginator->lastPage(), $timetablePaginator->currentPage() + 1);
                        @endphp

                        <div class="pagination-wrapper report-pagination">
                            <p class="report-pagination-summary">
                                Showing {{ $timetablePaginator->firstItem() }} to {{ $timetablePaginator->lastItem() }} of
                                {{ $timetablePaginator->total() }} results
                            </p>

                            <nav aria-label="Timetable pagination">
                                <ul class="report-pagination-list">
                                    <li>
                                        @if ($timetablePaginator->onFirstPage())
                                            <span class="is-disabled" aria-disabled="true">Previous</span>
                                        @else
                                            <a href="{{ $timetablePaginator->previousPageUrl() }}"
                                                rel="prev">Previous</a>
                                        @endif
                                    </li>

                                    @foreach ($timetablePaginator->getUrlRange($startPage, $endPage) as $page => $url)
                                        <li>
                                            @if ($page === $timetablePaginator->currentPage())
                                                <span class="is-active" aria-current="page">{{ $page }}</span>
                                            @else
                                                <a href="{{ $url }}">{{ $page }}</a>
                                            @endif
                                        </li>
                                    @endforeach

                                    <li>
                                        @if ($timetablePaginator->hasMorePages())
                                            <a href="{{ $timetablePaginator->nextPageUrl() }}" rel="next">Next</a>
                                        @else
                                            <span class="is-disabled" aria-disabled="true">Next</span>
                                        @endif
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    @endif

                </div>
            </div>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.btn-view-timetable');

            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const url = this.getAttribute('data-url');
                    const timetableView = document.getElementById('timetable-view');

                    // Show loading state
                    timetableView.innerHTML =
                        '<div class="text-center py-4"><span class="loading loading-spinner loading-lg"></span></div>';

                    fetch(url)
                        .then(response => {
                            // Log the response for debugging
                            console.log('Response status:', response.status);
                            console.log('Response ok:', response.ok);

                            if (!response.ok) {
                                // Try to get error message from response
                                return response.text().then(errorText => {
                                    throw new Error(
                                        `HTTP ${response.status}: ${errorText || 'Network response was not ok'}`
                                    );
                                });
                            }
                            return response.text();
                        })
                        .then(html => {
                            timetableView.innerHTML = html;
                        })
                        .catch(error => {
                            console.error('Error loading timetable:', error);
                            // Show more detailed error message for debugging
                            timetableView.innerHTML =
                                `<div class="alert alert-danger">Error loading timetable: ${error.message}</div>`;
                        });
                });
            });
        });
    </script>
@endsection
