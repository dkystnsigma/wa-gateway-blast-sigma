<x-layout-dashboard title="Google Sheets - Folder">

    <style>
        /* Modern table styling */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: #495057;
            padding: 12px 16px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .table tbody td {
            padding: 10px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }

        /* Card styling */
        .card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }

        /* Button styling */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Search input styling */
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        /* Loading animation */
        .spinner-border {
            animation: spinner-border 0.75s linear infinite;
        }

        /* Progress bar styling */
        .progress {
            border-radius: 4px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
        }

        /* Custom scrollbar */
        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Smooth transitions */
        .file-preview-area > div {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Loading animation improvements */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .loading-pulse {
            animation: pulse 2s infinite;
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        /* Hover effects for table rows */
        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
            transform: translateX(4px);
        }

        /* Modern card styling */
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        /* Button hover effects */
        .btn {
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        /* Search input styling */
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
            transform: scale(1.02);
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }

            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>

    @if (session()->has('alert'))
        <x-alert>
            @slot('type', session('alert')['type'])
            @slot('msg', session('alert')['msg'])
        </x-alert>
    @endif

    @if(isset($error))
        <div class="alert alert-danger">
            <h5>Error: {{ $error }}</h5>
            @if(isset($instructions))
                <h6>Instructions:</h6>
                <ul>
                    @foreach($instructions as $instruction)
                        <li>{{ $instruction }}</li>
                    @endforeach
                </ul>
            @endif
            @if(isset($possible_causes))
                <h6>Possible Causes:</h6>
                <ul>
                    @foreach($possible_causes as $cause)
                        <li>{{ $cause }}</li>
                    @endforeach
                </ul>
            @endif
            @if(isset($solutions))
                <h6>Solutions:</h6>
                <ul>
                    @foreach($solutions as $solution)
                        <li>{{ $solution }}</li>
                    @endforeach
                </ul>
            @endif
            @if(isset($sharing_link))
                <p><a href="{{ $sharing_link }}" target="_blank" class="btn btn-primary btn-sm">Open Folder in Google Drive</a></p>
            @endif
        </div>
    @endif

    @if(isset($folder))
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Google Sheets</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('google.folder') }}">Folders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $folder['name'] }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Folder Info -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1">{{ $folder['name'] }}</h5>
                            <p class="text-muted mb-0">Folder ID: {{ $folder['id'] }}</p>
                        </div>
                        <div>
                            <a href="https://drive.google.com/drive/folders/{{ $folder['id'] }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-folder"></i> Open in Google Drive
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    @if(isset($total_files))
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $total_files }}</h4>
                            <p class="mb-0">Total Files</p>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-files display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $google_sheets_count ?? 0 }}</h4>
                            <p class="mb-0">Google Sheets</p>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-file-earmark-spreadsheet display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $excel_files_count ?? 0 }}</h4>
                            <p class="mb-0">Excel Files</p>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-file-earmark-excel display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $subfolders_count ?? 0 }}</h4>
                            <p class="mb-0">Subfolders</p>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-folder display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="email-wrapper mb-5">
        <!-- Sidebar with Folders -->
        <div class="email-sidebar">
            <div class="email-sidebar-header d-grid">
                <button class="btn btn-primary compose-mail-btn" onclick="refreshFolder()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Refresh Folder
                </button>
                <input type="text" class="form-control mt-2 search-files" placeholder="Search files...">
            </div>
            <div class="email-sidebar-content">
                <div class="email-navigation">
                    <div class="list-group list-group-flush file-list" style="overflow-y: scroll !important; height: 140%;">
                        @if(isset($files) && is_array($files))
                            @foreach($files as $file)
                                <div class="list-group-item file-item" data-name="{{ strtolower($file['name']) }}">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            @if($file['isFolder'])
                                                <i class="bi bi-folder text-warning display-6"></i>
                                            @elseif($file['isGoogleSheet'])
                                                <i class="bi bi-file-earmark-spreadsheet text-success display-6"></i>
                                            @elseif(isset($file['isExcelFile']) && $file['isExcelFile'])
                                                <i class="bi bi-file-earmark-excel text-primary display-6"></i>
                                            @else
                                                <i class="bi bi-file-earmark text-muted display-6"></i>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 file-name">
                                                @if($file['isFolder'])
                                                    <a href="{{ route('google.folder.specific', $file['id']) }}" class="text-decoration-none">
                                                        {{ $file['name'] }}
                                                    </a>
                                                @elseif($file['isGoogleSheet'])
                                                    <a href="#" onclick="loadSheetData('{{ $file['id'] }}', '{{ $file['name'] }}', 'sheet')" class="text-decoration-none">
                                                        {{ $file['name'] }}
                                                    </a>
                                                @elseif(isset($file['isExcelFile']) && $file['isExcelFile'])
                                                    <a href="#" onclick="loadSheetData('{{ $file['id'] }}', '{{ $file['name'] }}', 'excel')" class="text-decoration-none">
                                                        {{ $file['name'] }}
                                                    </a>
                                                @else
                                                    {{ $file['name'] }}
                                                @endif
                                            </h6>
                                            <small class="text-muted">
                                                @if($file['isFolder'])
                                                    Folder
                                                @elseif($file['isGoogleSheet'])
                                                    Google Sheet
                                                @elseif(isset($file['isExcelFile']) && $file['isExcelFile'])
                                                    Excel File
                                                @else
                                                    {{ $file['mimeType'] }}
                                                @endif
                                            </small>
                                            @if(isset($file['size']) && $file['size'])
                                                <br><small class="text-muted">{{ number_format($file['size'] / 1024, 2) }} KB</small>
                                            @endif
                                        </div>
                                        <div class="ms-2">
                                            @if(isset($file['webViewLink']))
                                                <a href="{{ $file['webViewLink'] }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Open in Google Drive">
                                                    <i class="bi bi-external-link"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted mt-4">
                                <i class="bi bi-folder-x display-4"></i>
                                <p>No files found in this folder</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="email-content">
            <div class="file-details" id="file-preview-area">
                <!-- Default view when no file selected -->
                <div id="default-view" class="text-center mt-5">
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body py-5">
                            <i class="bi bi-folder display-1 text-primary mb-4"></i>
                            <h4 class="card-title text-primary mb-3">{{ $folder['name'] }}</h4>
                            <p class="card-text text-muted mb-4">Click on a file from the sidebar to preview its data here</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 bg-white">
                                        <div class="card-body text-center">
                                            <i class="bi bi-file-earmark-spreadsheet text-success display-4 mb-2"></i>
                                            <h6 class="card-title text-success">Google Sheets</h6>
                                            <p class="card-text small text-muted">Preview data instantly in this panel</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 bg-white">
                                        <div class="card-body text-center">
                                            <i class="bi bi-file-earmark-excel text-primary display-4 mb-2"></i>
                                            <h6 class="card-title text-primary">Excel Files</h6>
                                            <p class="card-text small text-muted">Preview data instantly in this panel</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <small class="text-muted">
                                    <i class="bi bi-lightbulb me-1"></i>
                                    Tip: Click on file names in the sidebar to preview data
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading view -->
                <div id="loading-view" class="text-center mt-5" style="display: none;">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-5">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <h5 class="card-title text-primary">Loading data...</h5>
                            <p class="card-text text-muted">Please wait while we fetch the file data</p>
                            <div class="progress mt-3" style="height: 4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error view -->
                <div id="error-view" style="display: none;">
                    <div class="card border-danger border">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-exclamation-triangle text-danger display-6 me-3"></i>
                                <div>
                                    <h5 class="card-title text-danger mb-1" id="error-title">Error loading file</h5>
                                    <p class="card-text text-muted mb-0" id="error-message">Something went wrong</p>
                                </div>
                            </div>
                            <div id="error-instructions" class="mb-3"></div>
                            <button class="btn btn-outline-secondary btn-sm" onclick="showDefaultView()">
                                <i class="bi bi-arrow-left me-1"></i> Back to folder view
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Data preview -->
                <div id="data-view" class="px-2" style="display: none;">
                    <!-- File Header -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 id="file-title" class="mb-1">File Name</h4>
                            <small class="text-muted">Preview • <span id="data-info"></span></small>
                        </div>
                        <div>
                            <button class="btn btn-secondary btn-sm" onclick="showDefaultView()">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button class="btn btn-primary btn-sm" id="open-full-view">
                                <i class="bi bi-arrows-fullscreen"></i> Full View
                            </button>
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="flex-grow-1 me-3">
                            <input type="text" class="form-control form-control-sm preview-search" placeholder="Search in preview data..." style="max-width: 300px;">
                        </div>
                        <div class="text-muted small" id="preview-stats">
                            <!-- Stats will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive p-2">
                                <table class="table table-hover align-middle mb-0" id="preview-table">
                                    <thead class="table-light">
                                        <tr id="preview-header">
                                            <!-- Headers will be populated dynamically -->
                                        </tr>
                                    </thead>
                                    <tbody id="preview-data" class="table-group-divider">
                                        <!-- Data will be populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light border-0">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Showing first 100 rows in preview mode
                                    </small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small class="text-muted">
                                        <i class="bi bi-lightning-charge me-1"></i>
                                        Fast preview • Click "Full View" for complete data
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        function refreshFolder() {
            window.location.reload();
        }

        function showDefaultView() {
            document.getElementById('default-view').style.display = 'block';
            document.getElementById('loading-view').style.display = 'none';
            document.getElementById('error-view').style.display = 'none';
            document.getElementById('data-view').style.display = 'none';
        }

        function showLoadingView() {
            document.getElementById('default-view').style.display = 'none';
            document.getElementById('loading-view').style.display = 'block';
            document.getElementById('error-view').style.display = 'none';
            document.getElementById('data-view').style.display = 'none';
        }

        function showErrorView(title, message, instructions = []) {
            document.getElementById('default-view').style.display = 'none';
            document.getElementById('loading-view').style.display = 'none';
            document.getElementById('error-view').style.display = 'block';
            document.getElementById('data-view').style.display = 'none';

            document.getElementById('error-title').textContent = title;
            document.getElementById('error-message').textContent = message;
            
            const instructionsDiv = document.getElementById('error-instructions');
            if (instructions.length > 0) {
                const ul = document.createElement('ul');
                instructions.forEach(instruction => {
                    const li = document.createElement('li');
                    li.textContent = instruction;
                    ul.appendChild(li);
                });
                instructionsDiv.innerHTML = '<h6 class="mt-3">Instructions:</h6>';
                instructionsDiv.appendChild(ul);
            } else {
                instructionsDiv.innerHTML = '';
            }
        }

        function showDataView(fileName, data) {
            document.getElementById('default-view').style.display = 'none';
            document.getElementById('loading-view').style.display = 'none';
            document.getElementById('error-view').style.display = 'none';
            document.getElementById('data-view').style.display = 'block';

            // Update file title
            document.getElementById('file-title').textContent = fileName;

            // Update stats
            const totalRows = data.data ? data.data.length : 0;
            const totalCols = data.header ? data.header.length : 0;
            document.getElementById('data-info').innerHTML = `<i class="bi bi-table me-1"></i>${totalRows} rows, ${totalCols} columns`;
            document.getElementById('preview-stats').innerHTML = `<i class="bi bi-table me-1"></i>Total: ${totalRows} rows × ${totalCols} columns`;

            // Build table header
            const headerRow = document.getElementById('preview-header');
            headerRow.innerHTML = '<th class="text-center" style="width: 60px;">#</th>';
            if (data.header && data.header.length > 0) {
                data.header.forEach((col, index) => {
                    const colName = col || `Column ${index + 1}`;
                    headerRow.innerHTML += `<th class="fw-semibold">${colName}</th>`;
                });
            }
            headerRow.innerHTML += '</tr>';

            // Build table data (limit to first 100 rows for performance)
            const tbody = document.getElementById('preview-data');
            tbody.innerHTML = '';

            if (data.data && data.data.length > 0) {
                const displayData = data.data.slice(0, 100); // Only show first 100 rows in preview

                displayData.forEach((row, index) => {
                    let rowHtml = `<tr><td class="text-center text-muted small">${index + 1}</td>`;

                    if (data.header && data.header.length > 0) {
                        data.header.forEach(col => {
                            const value = row[col] !== undefined ? row[col] : '';
                            const displayValue = value === null || value === '' ? '<span class="text-muted">-</span>' : value;
                            rowHtml += `<td class="text-truncate" style="max-width: 200px;" title="${value}">${displayValue}</td>`;
                        });
                    } else {
                        // If no header, assume row is array
                        Object.values(row).forEach(value => {
                            const displayValue = value === null || value === '' ? '<span class="text-muted">-</span>' : value;
                            rowHtml += `<td class="text-truncate" style="max-width: 200px;" title="${value}">${displayValue}</td>`;
                        });
                    }

                    rowHtml += '</tr>';
                    tbody.innerHTML += rowHtml;
                });
            } else {
                const colSpan = (data.header ? data.header.length : 1) + 1;
                tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center py-4">
                    <i class="bi bi-info-circle text-muted display-6 mb-2"></i>
                    <p class="text-muted mb-0">No data available in this file</p>
                </td></tr>`;
            }

            // Set up full view link
            const fullViewBtn = document.getElementById('open-full-view');
            if (data.spreadsheet_id) {
                fullViewBtn.onclick = () => {
                    window.open(`{{ url('') }}/en/google/read-sheet/${data.spreadsheet_id}`, '_blank');
                };
            } else if (data.file_id) {
                fullViewBtn.onclick = () => {
                    window.open(`{{ url('') }}/en/google/read-excel/${data.file_id}`, '_blank');
                };
            }
        }

        function loadSheetData(fileId, fileName, type) {
            showLoadingView();

            // Add loading class to body for global loading state
            document.body.classList.add('loading');

            const url = type === 'sheet' 
                ? `{{ url('') }}/en/google/read-sheet/${fileId}?preview=1`
                : `{{ url('') }}/en/google/read-excel/${fileId}?preview=1`;

            // Add timeout for fetch request
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            })
            .then(response => {
                clearTimeout(timeoutId);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                document.body.classList.remove('loading');
                if (data.error) {
                    showErrorView(
                        'Error loading file',
                        data.error,
                        data.instructions || []
                    );
                    // Show error toast if available
                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.error, 'Preview Error');
                    }
                } else {
                    showDataView(fileName, data);
                    // Add success animation
                    const dataView = document.getElementById('data-view');
                    dataView.classList.add('fade-in-up');
                    
                    // Show success toast if available
                    if (typeof toastr !== 'undefined') {
                        toastr.success(`Successfully loaded ${fileName}`, 'Data Preview');
                    }
                }
            })
            .catch(error => {
                clearTimeout(timeoutId);
                document.body.classList.remove('loading');
                console.error('Error loading data:', error);

                let errorTitle = 'Failed to load file';
                let errorMessage = 'Could not load file data. Please try again.';
                let instructions = [
                    'Check your internet connection',
                    'Make sure the file is accessible',
                    'Try refreshing the page if the problem persists'
                ];

                if (error.name === 'AbortError') {
                    errorTitle = 'Request Timeout';
                    errorMessage = 'The request took too long to complete.';
                    instructions = [
                        'Check your internet connection',
                        'The file might be too large to preview',
                        'Try opening the file directly instead'
                    ];
                }

                showErrorView(errorTitle, errorMessage, instructions);
                
                // Show error toast if available
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMessage, errorTitle);
                }
            });
        }

        // Search functionality for folder
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-files');
            const fileItems = document.querySelectorAll('.file-item');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();

                    fileItems.forEach(item => {
                        const fileName = item.dataset.name;
                        if (fileName.includes(searchTerm)) {
                            item.style.display = 'block';
                            item.style.opacity = '1';
                        } else {
                            item.style.opacity = '0.3';
                            setTimeout(() => {
                                if (!fileName.includes(searchInput.value.toLowerCase())) {
                                    item.style.display = 'none';
                                }
                            }, 150);
                        }
                    });
                });
            }

            // Enhanced search functionality for preview data with debouncing
            let searchTimeout;
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('preview-search')) {
                    clearTimeout(searchTimeout);
                    const searchInput = e.target;

                    searchTimeout = setTimeout(() => {
                        const searchTerm = searchInput.value.toLowerCase().trim();
                        const rows = document.querySelectorAll('#preview-data tr');
                        let visibleCount = 0;

                        rows.forEach(row => {
                            if (row.cells && row.cells.length > 1) { // Skip empty state rows
                                const text = row.textContent.toLowerCase();
                                const isVisible = searchTerm === '' || text.includes(searchTerm);
                                row.style.display = isVisible ? '' : 'none';
                                if (isVisible) visibleCount++;
                            }
                        });

                        // Update search results info
                        const searchContainer = searchInput.closest('.d-flex');
                        let resultsInfo = searchContainer.querySelector('.search-results-info');

                        if (!resultsInfo) {
                            resultsInfo = document.createElement('small');
                            resultsInfo.className = 'text-muted search-results-info ms-2';
                            searchContainer.appendChild(resultsInfo);
                        }

                        if (searchTerm !== '') {
                            resultsInfo.innerHTML = `<i class="bi bi-search me-1"></i>Found ${visibleCount} matching rows`;
                            resultsInfo.style.display = 'inline';
                        } else {
                            resultsInfo.style.display = 'none';
                        }
                    }, 300); // 300ms debounce
                }
            });

            // Add smooth transitions for view changes
            const views = ['default-view', 'loading-view', 'error-view', 'data-view'];
            views.forEach(viewId => {
                const view = document.getElementById(viewId);
                if (view) {
                    view.style.transition = 'all 0.3s ease-in-out';
                }
            });
        });
    </script>
</x-layout-dashboard>
