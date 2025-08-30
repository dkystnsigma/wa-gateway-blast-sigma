<x-layout-dashboard title="Google Sheets - File Data">

    @if (session()->has('alert'))
        <x-alert>
            @slot('type', session('alert')['type'])
            @slot('msg', session('alert')['msg'])
        </x-alert>
    @endif

    @if(isset($error))
        <div class="alert alert-danger">
            <h5><i class="bi bi-exclamation-triangle"></i> Error: {{ $error }}</h5>
            @if(isset($instructions))
                <h6 class="mt-3">Instructions:</h6>
                <ul>
                    @foreach($instructions as $instruction)
                        <li>{{ $instruction }}</li>
                    @endforeach
                </ul>
            @endif
            @if(isset($spreadsheet_id))
                <div class="mt-3">
                    <small class="text-muted">Spreadsheet ID: {{ $spreadsheet_id }}</small>
                    <br>
                    <a href="https://docs.google.com/spreadsheets/d/{{ $spreadsheet_id }}/edit" target="_blank" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="bi bi-external-link"></i> Open in Google Sheets
                    </a>
                    <a href="{{ route('google.folder') }}" class="btn btn-secondary btn-sm mt-2">
                        <i class="bi bi-arrow-left"></i> Back to Folders
                    </a>
                </div>
            @endif
        </div>
    @endif

    @if(isset($data) && is_array($data))
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Google Sheets</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('google.folder') }}">Folders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $sheet_name ?? 'Sheet Data' }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- File Info -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">{{ $file_name ?? 'Google Sheet' }}</h5>
                            <p class="text-muted mb-1">Sheet: {{ $sheet_name ?? 'Unknown' }}</p>
                            <p class="text-muted mb-0">Spreadsheet ID: {{ $spreadsheet_id ?? 'N/A' }}</p>
                        </div>
                        <div class="d-flex gap-2">
                            @if(isset($spreadsheet_id))
                                <a href="https://docs.google.com/spreadsheets/d/{{ $spreadsheet_id }}/edit" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> Open in Google Sheets
                                </a>
                            @endif
                            <button onclick="refreshData()" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-clockwise"></i> Refresh Data
                            </button>
                            <button onclick="exportToCSV()" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ count($data) }}</h4>
                            <p class="mb-0">Total Rows</p>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-table display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ isset($header) ? count($header) : 0 }}</h4>
                            <p class="mb-0">Columns</p>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-layout-three-columns display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Sheet Data</h5>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm search-table" placeholder="Search data..." style="width: 200px;">
                    <select class="form-select form-select-sm" id="entriesPerPage" style="width: 100px;">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="dataTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            @if(isset($header) && is_array($header))
                                @foreach($header as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            @else
                                @if(count($data) > 0)
                                    @foreach(array_keys($data[0]) as $column)
                                        <th>{{ $column }}</th>
                                    @endforeach
                                @endif
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($data) > 0)
                            @foreach($data as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    @if(is_array($row))
                                        @foreach($row as $value)
                                            <td>{{ $value }}</td>
                                        @endforeach
                                    @else
                                        <td colspan="{{ isset($header) ? count($header) : 1 }}">{{ $row }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ (isset($header) ? count($header) : 0) + 1 }}" class="text-center text-muted">
                                    No data available
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">
                        Showing {{ min(count($data), 1) }} to {{ count($data) }} of {{ count($data) }} entries
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" id="prevPage" disabled>
                        <i class="bi bi-chevron-left"></i> Previous
                    </button>
                    <span class="align-self-center" id="pageInfo">Page 1</span>
                    <button class="btn btn-sm btn-outline-secondary" id="nextPage" disabled>
                        Next <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="text-center mt-5">
        <i class="bi bi-file-earmark-x display-1 text-muted"></i>
        <h4 class="mt-3">No Data Available</h4>
        <p class="text-muted">Unable to load data from the Google Sheet</p>
        <a href="{{ route('google.folder') }}" class="btn btn-primary">Back to Folders</a>
    </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('dataTable');
            const tbody = table.querySelector('tbody');
            const searchInput = document.querySelector('.search-table');
            const entriesSelect = document.getElementById('entriesPerPage');
            const prevButton = document.getElementById('prevPage');
            const nextButton = document.getElementById('nextPage');
            const pageInfo = document.getElementById('pageInfo');

            let currentPage = 1;
            let rowsPerPage = parseInt(entriesSelect.value);
            let filteredRows = Array.from(tbody.querySelectorAll('tr'));
            let totalPages = Math.ceil(filteredRows.length / rowsPerPage);

            function updateTable() {
                // Hide all rows
                filteredRows.forEach(row => row.style.display = 'none');

                // Show rows for current page
                const startIndex = (currentPage - 1) * rowsPerPage;
                const endIndex = startIndex + rowsPerPage;

                for (let i = startIndex; i < endIndex && i < filteredRows.length; i++) {
                    filteredRows[i].style.display = '';
                }

                // Update pagination info
                pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
                prevButton.disabled = currentPage === 1;
                nextButton.disabled = currentPage === totalPages;

                // Update footer info
                const showingStart = startIndex + 1;
                const showingEnd = Math.min(endIndex, filteredRows.length);
                document.querySelector('.card-footer small').textContent =
                    `Showing ${showingStart} to ${showingEnd} of ${filteredRows.length} entries`;
            }

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();

                filteredRows = Array.from(tbody.querySelectorAll('tr')).filter(row => {
                    if (row.cells.length === 1 && row.cells[0].colSpan > 1) {
                        return true; // "No data available" row
                    }
                    return Array.from(row.cells).some(cell =>
                        cell.textContent.toLowerCase().includes(searchTerm)
                    );
                });

                currentPage = 1;
                totalPages = Math.ceil(filteredRows.length / rowsPerPage);
                updateTable();
            }

            // Event listeners
            searchInput.addEventListener('input', filterTable);

            entriesSelect.addEventListener('change', function() {
                rowsPerPage = parseInt(this.value);
                currentPage = 1;
                totalPages = Math.ceil(filteredRows.length / rowsPerPage);
                updateTable();
            });

            prevButton.addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    updateTable();
                }
            });

            nextButton.addEventListener('click', function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    updateTable();
                }
            });

            // Initialize
            updateTable();
        });

        function refreshData() {
            window.location.reload();
        }

        function exportToCSV() {
            const table = document.getElementById('dataTable');
            const rows = Array.from(table.querySelectorAll('tr'));
            let csv = [];

            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('th, td'));
                const rowData = cells.map(cell => `"${cell.textContent.replace(/"/g, '""')}"`);
                csv.push(rowData.join(','));
            });

            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');

            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', '{{ $file_name ?? "sheet_data" }}.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    </script>
</x-layout-dashboard>
