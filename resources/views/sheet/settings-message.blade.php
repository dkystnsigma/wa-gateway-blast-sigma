<x-layout-dashboard title="Settings Message - Google Sheets">
    @if (session()->has('alert'))
        <x-alert>
            @slot('type', session('alert')['type'])
            @slot('msg', session('alert')['msg'])
        </x-alert>
    @endif
    @if (isset($error))
        <div class="alert alert-danger">
            <h5><i class="bi bi-exclamation-triangle"></i> Error: {{ $error }}</h5>
            @if (isset($possible_causes))
                <h6 class="mt-3">Possible Causes:</h6>
                <ul>
                    @foreach ($possible_causes as $cause)
                        <li>{{ $cause }}</li>
                    @endforeach
                </ul>
            @endif
            @if (isset($solutions))
                <h6 class="mt-3">Solutions:</h6>
                <ul>
                    @foreach ($solutions as $solution)
                        <li>{{ $solution }}</li>
                    @endforeach
                </ul>
            @endif
            @if (isset($spreadsheet_id))
                <div class="mt-3">
                    <small class="text-muted">Spreadsheet ID: {{ $spreadsheet_id }}</small>
                    <br>
                    <a href="https://docs.google.com/spreadsheets/d/{{ $spreadsheet_id }}/edit" target="_blank"
                        class="btn btn-outline-primary btn-sm mt-2">
                        <i class="bi bi-external-link"></i> Open in Google Sheets
                    </a>
                </div>
            @endif
        </div>
        @if (isset($setup_instructions))
            <div class="alert alert-info">
                <h6><i class="bi bi-lightbulb"></i> Setup Instructions:</h6>
                <ul class="mb-3">
                    @foreach ($setup_instructions as $instruction)
                        <li>{{ $instruction }}</li>
                    @endforeach
                </ul>

                @if (isset($template_headers))
                    <div class="mt-3">
                        <h6>Template Headers (Copy & Paste to Row 1):</h6>
                        <div class="bg-light p-3 rounded">
                            <code>{{ implode(' | ', $template_headers) }}</code>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary mt-2" onclick="copyTemplateHeaders()">
                            <i class="bi bi-clipboard"></i> Copy Headers
                        </button>
                    </div>
                @endif

                @if (isset($spreadsheet_url))
                    <div class="mt-3">
                        <a href="{{ $spreadsheet_url }}" target="_blank" class="btn btn-primary">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Open Spreadsheet to Add Headers
                        </a>
                    </div>
                @endif
            </div>
        @endif
        @if (isset($sample_data))
            <div class="alert alert-warning">
                <h6><i class="bi bi-info-circle"></i> Sample Data Found:</h6>
                <p>Here are the first few rows of data found in your sheet:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        @foreach ($sample_data as $index => $row)
                            <tr>
                                <td class="text-muted small">{{ $index + 1 }}</td>
                                @foreach ($row as $cell)
                                    <td>{{ $cell ?: '-' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif
    @endif
    @if (isset($info))
        <div class="alert alert-info">
            <h6><i class="bi bi-info-circle"></i> Information: {{ $info }}</h6>
            @if (isset($raw_data_mode) && $raw_data_mode)
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="bi bi-exclamation-triangle"></i>
                        This sheet is displayed in raw data mode because no structured column headers were found.
                        Data is shown with generic column names for easier viewing.
                    </small>
                </div>
            @endif
            @if (isset($setup_instructions))
                <ul class="mb-0 mt-2">
                    @foreach ($setup_instructions as $instruction)
                        <li>{{ $instruction }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif


    @if (isset($data) && is_array($data))
        <!-- File Info -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-1">
                                    <i class="bi bi-gear text-primary me-2"></i>
                                    {{ $file_name ?? 'Settings Message' }}
                                </h5>
                                <p class="text-muted mb-1">
                                    Sheet: <strong>{{ $sheet_name ?? 'Unknown' }}</strong>
                                    @if (isset($current_sheet) && isset($current_sheet['index']))
                                        (Sheet {{ $current_sheet['index'] + 1 }} of {{ count($sheets ?? []) }})
                                    @endif
                                </p>
                                <p class="text-muted mb-0">Spreadsheet ID: {{ $spreadsheet_id ?? 'N/A' }}</p>
                            </div>
                            <div class="d-flex gap-2">
                                @if (isset($spreadsheet_id))
                                    <a href="https://docs.google.com/spreadsheets/d/{{ $spreadsheet_id }}/edit"
                                        target="_blank" class="btn btn-outline-primary btn-sm">
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
        <div class="row">
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">{{ $total_rows ?? count($data) }}</h4>
                                <p class="mb-0">Total Messages</p>
                            </div>
                            <div class="ms-3">
                                <i class="bi bi-chat-dots display-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">{{ $total_columns ?? (isset($header) ? count($header) : 0) }}</h4>
                                <p class="mb-0">Message Fields</p>
                            </div>
                            <div class="ms-3">
                                <i class="bi bi-layout-three-columns display-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">
                                    @if (isset($last_updated))
                                        @php
                                            $date = is_string($last_updated)
                                                ? \Carbon\Carbon::parse($last_updated)
                                                : $last_updated;
                                            echo $date->format('M d, H:i');
                                        @endphp
                                    @else
                                        {{ now()->format('M d, H:i') }}
                                    @endif
                                </h6>
                                <p class="mb-0">Last Updated</p>
                            </div>
                            <div class="ms-3">
                                <i class="bi bi-clock display-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sheet Navigation Tabs - Always show if multiple sheets exist -->
        @if (isset($sheets) && is_array($sheets) && count($sheets) > 1)
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>
                                        Sheet Navigation
                                    </h5>
                                    <p class="text-muted mb-0 small">
                                        Current: <strong>{{ $sheet_name ?? 'Unknown' }}</strong>
                                        @if (isset($current_sheet) && isset($current_sheet['index']))
                                            ({{ $current_sheet['index'] + 1 }} of {{ count($sheets) }} sheets)
                                        @endif
                                    </p>
                                </div>
                                @if (isset($spreadsheet_id))
                                    <a href="https://docs.google.com/spreadsheets/d/{{ $spreadsheet_id }}/edit"
                                        target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-external-link"></i> Open Spreadsheet
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="nav nav-tabs border-bottom-0" id="sheetTabs" role="tablist">
                                @foreach ($sheets as $index => $sheet)
                                    <button
                                        class="nav-link {{ ($current_sheet['name'] ?? '') === $sheet['name'] ? 'active' : '' }}"
                                        id="sheet-{{ $index }}-tab" data-bs-toggle="tab"
                                        data-bs-target="#sheet-{{ $index }}" type="button" role="tab"
                                        aria-controls="sheet-{{ $index }}"
                                        aria-selected="{{ ($current_sheet['name'] ?? '') === $sheet['name'] ? 'true' : 'false' }}"
                                        data-sheet-name="{{ $sheet['name'] }}">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                                        {{ $sheet['name'] }}
                                        @if (isset($sheet['url']))
                                            <a href="{{ $sheet['url'] }}" class="text-decoration-none ms-1"
                                                title="Open this sheet">
                                                <i class="bi bi-box-arrow-up-right small"></i>
                                            </a>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <!-- Data Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-check text-primary me-2"></i>
                        Message Settings Data
                    </h5>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm search-table"
                            placeholder="Search messages..." style="width: 200px;">
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
                                @if (isset($header) && is_array($header))
                                    @foreach ($header as $column)
                                        <th>{{ $column }}</th>
                                    @endforeach
                                @else
                                    @if (count($data) > 0)
                                        @foreach (array_keys($data[0]) as $column)
                                            <th>{{ $column }}</th>
                                        @endforeach
                                    @endif
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @if (count($data) > 0)
                                @foreach ($data as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        @if (is_array($row))
                                            @foreach ($row as $value)
                                                <td>
                                                    @if ($value && strlen($value) > 100)
                                                        <div class="d-flex align-items-center">
                                                            <span class="text-truncate d-inline-block"
                                                                style="max-width: 300px;"
                                                                data-bs-toggle="tooltip"
                                                                data-bs-placement="top"
                                                                title="{{ htmlspecialchars($value) }}">
                                                                {{ Str::limit($value, 100) }}
                                                            </span>
                                                            <button class="btn btn-sm btn-outline-secondary ms-1 p-0"
                                                                onclick="showFullContent('{{ addslashes($value) }}')"
                                                                style="width: 24px; height: 24px; font-size: 12px;"
                                                                title="View full content">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                        </div>
                                                    @else
                                                        {{ $value ?: '-' }}
                                                    @endif
                                                </td>
                                            @endforeach
                                        @else
                                            <td colspan="{{ (isset($header) ? count($header) : 1) + 1 }}">
                                                {{ $row }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="{{ (isset($header) ? count($header) : 0) + 1 }}"
                                        class="text-center text-muted">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No message data available yet. Add your first message to get started!
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
            <i class="bi bi-gear display-1 text-muted"></i>
            <h4 class="mt-3">Settings Message</h4>
            <p class="text-muted">Configure your message settings here</p>
            <a href="{{ route('home') }}" class="btn btn-primary">Back to Home</a>
        </div>
        @if (isset($setup_instructions) || isset($template_headers))
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-question-circle text-info me-2"></i>
                                How to Get Started
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="bi bi-1-circle text-primary me-2"></i> Step 1: Add Headers</h6>
                                    <p class="text-muted small">Add column headers in the first row of your Google
                                        Sheet</p>

                                    @if (isset($template_headers))
                                        <div class="bg-light p-2 rounded mb-3">
                                            <small class="text-muted">Suggested headers:</small>
                                            <br>
                                            <code class="small">{{ implode(' | ', $template_headers) }}</code>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-2-circle text-primary me-2"></i> Step 2: Add Data</h6>
                                    <p class="text-muted small">Add your message settings data starting from row 2</p>

                                    <div class="bg-light p-2 rounded">
                                        <small class="text-muted">Example data:</small>
                                        <br>
                                        <code class="small">Welcome Message | Welcome to our service! | Active |
                                            2024-01-01</code>
                                    </div>
                                </div>
                            </div>

                            @if (isset($spreadsheet_url))
                                <div class="text-center mt-4">
                                    <a href="{{ $spreadsheet_url }}" target="_blank" class="btn btn-success">
                                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                                        Open Google Sheet to Add Data
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sheet tabs navigation - Always available if tabs exist
            const sheetTabs = document.querySelectorAll('#sheetTabs .nav-link');
            sheetTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sheetName = this.getAttribute('data-sheet-name');
                    if (sheetName) {
                        // Get current URL and construct new URL
                        const currentUrl = new URL(window.location.href);
                        const basePath = currentUrl.pathname.split('/').slice(0, -1).join('/');
                        const targetUrl = basePath + '/' + encodeURIComponent(sheetName);

                        console.log('Navigating to sheet:', sheetName);
                        console.log('Target URL:', targetUrl);

                        // Navigate to the new sheet
                        window.location.href = currentUrl.origin + targetUrl;
                    }
                });
            });

            // Only initialize table functionality if data table exists
            const table = document.getElementById('dataTable');
            if (table) {
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
            }
        });

        function refreshData() {
            window.location.reload();
        }

        function exportToCSV() {
            const table = document.getElementById('dataTable');
            if (!table) {
                alert('No data table available to export');
                return;
            }

            const rows = Array.from(table.querySelectorAll('tr'));
            let csv = [];

            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('th, td'));
                const rowData = cells.map(cell => `"${cell.textContent.replace(/"/g, '""')}"`);
                csv.push(rowData.join(','));
            });

            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');

            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'settings_message.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        function showFullContent(content) {
            // Create modal for showing full content
            const modalHtml = `
                <div class="modal fade" id="contentModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Full Content</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <pre style="white-space: pre-wrap; word-wrap: break-word;">${content}</pre>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="copyToClipboard('${content.replace(/'/g, "\\'")}')">Copy to Clipboard</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if any
            const existingModal = document.getElementById('contentModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('contentModal'));
            modal.show();
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const toastHtml = `
                    <div class="toast align-items-center text-white bg-success border-0" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">Content copied to clipboard!</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                `;

                const toastContainer = document.getElementById('toastContainer') || createToastContainer();
                toastContainer.insertAdjacentHTML('beforeend', toastHtml);

                const toast = new bootstrap.Toast(toastContainer.lastElementChild);
                toast.show();
            });
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</x-layout-dashboard>
