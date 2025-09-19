<!DOCTYPE html>
<html class="semi-dark " lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('assets/images/logo-sigma.png') }}" type="image/png" />
    <!--plugins-->
    <link href="{{ asset('assets/plugins/vectormap/jquery-jvectormap-2.0.2.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" />

    <!-- loader-->
    <link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet" />

    <!--Theme Styles-->
    <link href="{{ asset('assets/css/dark-theme.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/light-theme.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/semi-dark.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/header-colors.css') }}" rel="stylesheet" />
    {{-- csrf --}}
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://unpkg.com/emoji-button@latest/dist/index.css" rel="stylesheet" />
    <style>
        .modern-form .form-group {
            margin-bottom: 1.5rem;
        }

        .modern-form .form-control {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 0.75rem 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .modern-form .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .modern-form label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .modern-form label i {
            margin-right: 0.5rem;
            color: #007bff;
        }

        .modern-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .modern-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border: none;
        }

        .modern-card .card-body {
            padding: 2rem;
        }

        .modern-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .modern-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #555;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #007bff;
        }

        /* Google-style switch */
        .google-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .google-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .google-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .google-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .google-switch input:checked+.google-slider {
            background-color: #2196F3;
        }

        .google-switch input:focus+.google-slider {
            box-shadow: 0 0 1px #2196F3;
        }

        .google-switch input:checked+.google-slider:before {
            transform: translateX(26px);
        }

        /* Custom CSS for Select2 */
        .select2-container--default .select2-selection--single {
            background-color: #fff;
            border: 1px solid #aaa;
            border-radius: 4px;
            padding: 10px;
            height: 40px;
        }

        #device_idd_create {
            width: 100% !important;
        }

        .search-result {
            padding: 5px;
            cursor: pointer;
        }

        .search-result:hover {
            background: #f0f0f0;
        }
    </style>
    <title>WAB</title>
</head>

<body>

    <div class="wrapper">
        @if (session()->has('alert'))
            <x-alert>
                @slot('type', session('alert')['type'])
                @slot('msg', session('alert')['msg'])
            </x-alert>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        {{-- wizard --}}
        <div class="mt-4 mb-5">
            <div class="container">
                <h6 class="mb-0 text-uppercase">Create Your Order</h6>
                <hr />
                {{--  --}}
                <form class="modern-form" action="{{ route('order.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modern-card card">
                        <div class="card-header">
                            <i class="bi bi-plus-circle me-2"></i><strong> Order Blasting</strong>
                        </div>
                        <div class="card-body">
                            <h5 class="section-title"><i class="bi bi-info-circle"></i> Informasi Dasar</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="name"><i class="bi bi-person"></i> Nama CS</label>
                                        <select name="name" id="name" class="form-control" required>
                                            <option value="">Pilih Nama CS</option>
                                            <option value="DIKY">DIKY</option>
                                            <option value="ALFA">ALFA</option>
                                            <option value="AMMA">AMMA</option>
                                            <option value="BETA">BETA</option>
                                            <option value="GAMMA">GAMMA</option>
                                            <option value="DELTA">DELTA</option>
                                            <option value="EPSILON">EPSILON</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="product"><i class="bi bi-box"></i> Produk</label>
                                        <select name="product" id="product" class="form-control" required>
                                            <option value="">Pilih Keterangan</option>
                                            <option value="bio insuleaf">BIO INSULEAF</option>
                                            <option value="etawalin">ETAWALIN</option>
                                            <option value="nutriflakes">NUTRIFLAKES</option>
                                            <option value="zymuno">ZYMUNO</option>
                                            <option value="etawak">ETAWAKU</option>
                                            <option value="etawaherb">ETAWAHERB</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="date_now"><i class="bi bi-calendar"></i> Tanggal Hari
                                            Ini</label>
                                        <input name="date_now" id="date_now" type="date" class="form-control"
                                            value="{{ now()->format('Y-m-d') }}" readonly required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="sendTime"><i class="bi bi-calendar-event"></i> Waktu
                                            Blasting</label>
                                        <input type="datetime-local" id="sendTime" name="sendTime"
                                            class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <h5 class="section-title"><i class="bi bi-pencil"></i> Konten & Keterangan</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="type_blast"><i class="bi bi-chat-text"></i> Keterangan
                                            Blasting</label>
                                        <select name="type_blast" id="type_blast" class="form-control" required>
                                            <option value="">Pilih Keterangan</option>
                                            <option value="resi">RESI</option>
                                            <option value="doa">DOA</option>
                                            <option value="reminder">REMINDER</option>
                                            <option value="konsul_1">KONSUL 1</option>
                                            <option value="konsul_2">KONSUL 2</option>
                                            <option value="konv_1">KONV 1</option>
                                            <option value="konv_2">KONV 2</option>
                                            <option value="konv_3">KONV 3</option>
                                            <option value="konv_4">KONV 4</option>
                                            <option value="perkenalan">PERKENALAN</option>
                                            <option value="tips_1">TIPS 1</option>
                                            <option value="tips_2">TIPS 2</option>
                                            <option value="konsultasi">KONSULTASI</option>
                                            <option value="konversi_1">KONVERSI 1</option>
                                            <option value="konversi_2">KONVERSI 2</option>
                                            <option value="soft_selling">SOFT SELLING</option>
                                            <option value="data_pasif">DATA PASIF</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="copy_writing"><i class="bi bi-file-text"></i> Copy
                                            Writing</label>

                                        <select name="copy_writing" id="copy_writing" class="form-control" required>
                                            <option value="">Pilih Keterangan</option>
                                            <option value="database">Database</option>
                                            <option value="mandiri">Mandiri</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="teks_writing"><i class="bi bi-type"></i> Teks Writing</label>
                                        <textarea name="teks_writing" id="teks_writing" placeholder="Tulis teks writing" rows="4"
                                            class="form-control" required></textarea>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="note"><i class="bi bi-plus-square"></i> Keterangan
                                            Tambahan</label>
                                        <textarea name="note" id="note" placeholder="Keterangan tambahan jika ada" rows="4"
                                            class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">

                                        <label><i class="bi bi-eye"></i> Preview</label>
                                        <button class="btn btn-sm btn-info text-light" type="button"
                                            id="preview-message"><i class="bi bi-arrow-clockwise"></i>
                                            Preview</button>
                                    </div>
                                    <div class="table-responsive">
                                        <table id="preview-table" class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th><i class="bi bi-telephone"></i> Nomor</th>
                                                    <th><i class="bi bi-chat-dots"></i> Pesan</th>
                                                    <th><i class="bi bi-file-text"></i> Template</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="3">Isi lengkap dat untuk melihat
                                                        preview</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="hidden-inputs"></div>
                                </div>
                            </div>
                            
                            <div class="row mt-2">
                                <div class="col-auto">
                                    <div class="form-group">
                                        <label for="img"><i class="bi bi-images"></i> Konten</label>
                                        <div class="d-flex align-items-center">
                                            <label class="google-switch">
                                                <input type="checkbox" id="img" name="img">
                                                <span class="google-slider"></span>
                                            </label>
                                            <span class="mx-2 text-img-blast">Tidak Aktif</span>
                                        </div>
                                    </div>

                                </div>
                                <div class="col-auto">
                                    <div class="priview-img border">

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-end card-footer bg-transparent border-0">
                            <button type="submit" class="modern-btn btn text-light">
                                <i class="bi bi-send me-2"></i> Buat Order
                            </button>
                        </div>
                    </div>
                </form>
                {{--  --}}
            </div>
        </div>
    </div>

    {{--  --}}

    <!-- Loading Overlay -->
    <div id="loading-overlay"
        style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:2000;background:rgba(20,20,20,0.85);align-items:center;justify-content:center;display:none;">
        <dotlottie-wc src="https://lottie.host/0f18aa7e-1847-4274-bbd7-938cc93292c3/TEaUDP4RRA.lottie" speed="1"
            autoplay loop></dotlottie-wc>
    </div>
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.6.2/dist/dotlottie-wc.js" type="module"></script>
    <script src="{{ asset('js/autoreply.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/js/form-select2.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        let currentTemplate = '';
        let currentCustomerData = [];
        const previewMessage = document.getElementById('preview-message');
        const loadingOverlay = document.getElementById('loading-overlay');

        $(document).ready(function() {
            // Set today's date
            let today = new Date().toISOString().split('T')[0];
            $('#date_now').val(today);

            // Initialize Select2 for all selects
            $('#name').select2({
                placeholder: "Pilih Nama CS",
                allowClear: true,
                width: '100%'
            });
            $('#product').select2({
                placeholder: "Pilih Produk",
                allowClear: true,
                width: '100%'
            });
            $('#type_blast').select2({
                placeholder: "Pilih Tipe Blast",
                allowClear: true,
                width: '100%'
            });
            $('#copy_writing').select2({
                placeholder: "Pilih Copy Writing",
                allowClear: true,
                width: '100%'
            });

            // Listen for copy_writing change
            $('#copy_writing').on('change', function() {
                if ($(this).val() === 'database') {
                    previewMessage.disabled = true;
                    validateAndFetchData();
                } else {
                    previewMessage.disabled = false;
                    $('#preview-table tbody').html(
                        '<tr><td colspan="3">Klik "Priview" untuk melihat preview</td></tr>');
                    $('#hidden-inputs').html('');
                }
            });

            // Listen for changes in name, product, type_blast when copy_writing is database
            $('#name, #product, #type_blast').on('change', function() {
                if ($('#copy_writing').val() === 'database') {
                    loadingOverlay.style.display = 'flex';
                    validateAndFetchData();
                } else if($('#copy_writing').val() === 'mandiri') {
                    loadingOverlay.style.display = 'flex';
                    validateAndFetchDataNonDB();
                }
            });

            // Toggle button for Konten
            $('#img-toggle').click(function() {
                if ($(this).text() === 'Tidak') {
                    $(this).text('Ya').removeClass('btn-secondary').addClass('btn-success');
                    $('#img-value').val('1');
                } else {
                    $(this).text('Tidak').removeClass('btn-success').addClass('btn-secondary');
                    $('#img-value').val('0');
                }
            });

            previewMessage.addEventListener('click', function() {
                loadingOverlay.style.display = 'flex';
                validateAndFetchDataNonDB();
            });
        });

        // Non DB
        function validateAndFetchDataNonDB() {
            const name = $('#name').val();
            const product = $('#product').val();
            const typeBlast = $('#type_blast').val();
            const copy_writing = $('#copy_writing').val();
            const teks_writing = $('#teks_writing').val();

            if (!name || !product || !typeBlast || !copy_writing) {
                toastr.warning('Harap lengkapi semua field: Nama CS, Produk, Keterangan Blasting, dan Copy Writing');
                loadingOverlay.style.display = 'none';
                return;
            }

            if (!teks_writing || teks_writing.trim() === '') {
                toastr.warning('Harap isi Teks Writing dengan template yang akan digunakan');
                loadingOverlay.style.display = 'none';
                return;
            }

            fetchContent(name, product, typeBlast, teks_writing);
        }
        async function fetchContent(name, product, typeBlast, templateText) {
            try {
                // Show loading
                $('#preview-table tbody').html('<tr><td colspan="3"><i class="bi bi-clock"></i> Loading...</td></tr>');
                $('#hidden-inputs').html('');

                // Step 1: Use template from textarea (skip get template for non-database)
                currentTemplate = templateText;

                // Step 2: Get customer data from folder and excel
                await getCustomerData(name, typeBlast);

                // Step 3: Generate preview
                generatePreview();

            } catch (error) {
                console.error('Error fetching data:', error);
                toastr.error('Terjadi kesalahan saat mengambil data: ' + error.message);
                $('#preview-table tbody').html('<tr><td colspan="3">Error loading data</td></tr>');
                $('#hidden-inputs').html('');
                loadingOverlay.style.display = 'none';
            }
        }

        // DB
        function validateAndFetchData() {
            const name = $('#name').val();
            const product = $('#product').val();
            const typeBlast = $('#type_blast').val();

            if (!name || !product || !typeBlast) {
                toastr.warning('Harap lengkapi semua field: Nama CS, Produk, dan Keterangan Blasting');
                loadingOverlay.style.display = 'none';
                return;
            }

            fetchDatabaseContent(name, product, typeBlast);
        }

        async function fetchDatabaseContent(name, product, typeBlast) {
            try {

                loadingOverlay.style.display = 'flex';
                // Show loading
                $('#preview-table tbody').html('<tr><td colspan="3"><i class="bi bi-clock"></i> Loading...</td></tr>');
                $('#hidden-inputs').html('');

                // Step 1: Get template from json-setting
                await getTemplate(product, typeBlast);

                // Step 2: Get customer data from folder and excel
                await getCustomerData(name, typeBlast);

                // Step 3: Generate preview
                generatePreview();

            } catch (error) {
                loadingOverlay.style.display = 'none';
                console.error('Error fetching data:', error);
                toastr.error('Terjadi kesalahan saat mengambil data: ' + error.message);
                $('#preview-table tbody').html('<tr><td colspan="3">Error loading data</td></tr>');
                $('#hidden-inputs').html('');
            }
        }

        async function getTemplate(product, typeBlast) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '{{ route('order.json.setting') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.data && response.data.length > 0) {
                            // Find matching product - try exact match first, then partial match
                            let matchingProduct = response.data.find(item => {
                                return item.PRODUK && item.PRODUK.toLowerCase() === product
                                    .toLowerCase();
                            });

                            // If no exact match, try partial match
                            if (!matchingProduct) {
                                matchingProduct = response.data.find(item => {
                                    return item.PRODUK && item.PRODUK.toLowerCase()
                                        .includes(product.toLowerCase());
                                });
                            }

                            // For special cases like ETAWALIN matching with NUTALIN
                            if (!matchingProduct) {
                                const productMappings = {
                                    'etawalin': ['nutalin', 'etawalin'],
                                    'nutriflakes': ['nutalin', 'nutriflakes'],
                                    'bio insuleaf': ['bio insuleaf', 'insuleaf'],
                                    'zymuno': ['zymuno'],
                                    'etawaku': ['etawaku'],
                                    'etawaherb': ['etawaherb']
                                };

                                const possibleMatches = productMappings[product.toLowerCase()] || [
                                    product.toLowerCase()
                                ];
                                matchingProduct = response.data.find(item => {
                                    if (!item.PRODUK) return false;
                                    return possibleMatches.some(match =>
                                        item.PRODUK.toLowerCase().includes(match)
                                    );
                                });
                            }

                            if (matchingProduct) {
                                // Map type_blast to the correct field
                                const fieldMapping = {
                                    'perkenalan': 'PERKENALAN',
                                    'reminder': 'REMINDER',
                                    'tips_1': 'TIPS 1',
                                    'doa': 'DOA',
                                    'konsul_1': 'KONSUL 1',
                                    'konv_1': 'KONV 1',
                                    'konv_2': 'KONV 2',
                                    'tips_2': 'TIPS 2',
                                    'konv_3': 'KONV 3',
                                    'konsul_2': 'KONSUL 2',
                                    'konv_4': 'KONV 4',
                                    'konsultasi': 'KONSULTASI',
                                    'konversi_1': 'KONVERSI 1',
                                    'soft_selling': 'SOFT SELLING',
                                    'konversi_2': 'KONVERSI 2',
                                    'data_pasif': 'DATA PASIF'
                                };

                                const templateField = fieldMapping[typeBlast];
                                if (templateField && matchingProduct[templateField]) {
                                    currentTemplate = matchingProduct[templateField];
                                    resolve();
                                } else {
                                    reject(new Error(
                                        'Template tidak ditemukan untuk kombinasi produk dan tipe blast'
                                    ));

                                    loadingOverlay.style.display = 'none';
                                }
                            } else {
                                reject(new Error('Produk tidak ditemukan dalam database template'));

                                loadingOverlay.style.display = 'none';
                            }
                        } else {
                            reject(new Error('Data template kosong'));

                            loadingOverlay.style.display = 'none';
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error('Gagal mengambil template: ' + error));

                        loadingOverlay.style.display = 'none';
                    }
                });
            });
        }

        async function getCustomerData(name, typeBlast) {
            return new Promise(async (resolve, reject) => {
                try {
                    // Step 1: Get folder list
                    const folderResponse = await $.ajax({
                        url: '{{ route('order.json.folder.cs') }}',
                        method: 'GET'
                    });

                    if (!folderResponse.files || folderResponse.files.length === 0) {
                        reject(new Error('Tidak ada folder CS ditemukan'));

                        loadingOverlay.style.display = 'none';
                        return;
                    }

                    // Step 2: Find matching folder by name
                    const matchingFolder = folderResponse.files.find(folder =>
                        folder.isFolder && folder.name.toLowerCase() === name.toLowerCase()
                    );

                    if (!matchingFolder) {
                        reject(new Error('Folder CS dengan nama "' + name + '" tidak ditemukan'));

                        loadingOverlay.style.display = 'none';
                        return;
                    }

                    // Step 3: Get files in the matching folder
                    const filesResponse = await $.ajax({
                        url: `/order/json-folder-cs/${matchingFolder.id}`,
                        method: 'GET'
                    });

                    if (!filesResponse.files || filesResponse.files.length === 0) {
                        reject(new Error('Tidak ada file dalam folder "' + name + '"'));
                        loadingOverlay.style.display = 'none';
                        return;
                    }

                    // Step 4: Find matching excel file by type_blast
                    const typeBlastMapping = {
                        'konsultasi': ['konsultasi', 'konsul'],
                        'doa': ['doa'],
                        'reminder': ['reminder'],
                        'konversi_1': ['konversi'],
                        'konversi_2': ['konversi'],
                        'soft_selling': ['soft'],
                        'data_pasif': ['pasif'],
                        'perkenalan': ['perkenalan'],
                        'tips_1': ['tips'],
                        'tips_2': ['tips'],
                        'konv_1': ['konv', 'konversi'],
                        'konv_2': ['konv', 'konversi'],
                        'konv_3': ['konv', 'konversi'],
                        'konv_4': ['konv', 'konversi'],
                        'konsul_1': ['konsul', 'konsultasi'],
                        'konsul_2': ['konsul', 'konsultasi']
                    };

                    const searchTerms = typeBlastMapping[typeBlast] || [typeBlast];
                    const matchingFile = filesResponse.files.find(file => {
                        if (!file.isExcelFile) return false;
                        return searchTerms.some(term =>
                            file.name.toLowerCase().includes(term.toLowerCase())
                        );
                    });

                    if (!matchingFile) {
                        reject(new Error('File Excel untuk "' + typeBlast +
                            '" tidak ditemukan dalam folder "' + name + '"'));
                        loadingOverlay.style.display = 'none';
                        return;
                    }

                    // Step 5: Read excel file data
                    const excelResponse = await $.ajax({
                        url: `/order/json-read-excel/${matchingFile.id}`,
                        method: 'GET'
                    });

                    if (!excelResponse.data || excelResponse.data.length === 0) {
                        reject(new Error('Data customer kosong dalam file Excel'));
                        loadingOverlay.style.display = 'none';
                        return;
                    }

                    currentCustomerData = excelResponse.data;
                    resolve();

                } catch (error) {
                    loadingOverlay.style.display = 'none';
                    reject(error);
                }
            });
        }

        function generatePreview() {
            if (!currentTemplate || currentCustomerData.length === 0) {
                $('#preview-table tbody').html('<tr><td colspan="3">Data tidak lengkap</td></tr>');
                $('#hidden-inputs').html('');
                loadingOverlay.style.display = 'none';
                return;
            }

            let tableRows = '';
            let hiddenInputs = '';

            currentCustomerData.forEach(customer => {
                // Get customer data values - support unlimited columns (A, B, C, ... Z, AA, AB, etc.)
                const customerValues = Object.values(customer);
                const phoneNumber = customerValues[0] || ''; // Column A (always phone number)

                // Replace template variables dynamically - support A to Z and beyond
                let processedMessage = currentTemplate;

                // Replace column A (phone number) first
                processedMessage = processedMessage.replace(/\{\{A\}\}/g, phoneNumber);

                // Replace other columns (B, C, D, ... Z, AA, AB, etc.)
                for (let i = 1; i < customerValues.length; i++) {
                    const columnLetter = getColumnLetter(i); // 1 = B, 2 = C, 3 = D, etc.
                    const value = customerValues[i] || '';
                    const regex = new RegExp(`\\{\\{${columnLetter}\\}\\}`, 'g');
                    processedMessage = processedMessage.replace(regex, value);
                }

                // Convert \n to actual line breaks for display
                const displayMessage = processedMessage.replace(/\\n/g, '<br>');

                tableRows += `
                    <tr>
                        <td>${phoneNumber}</td>
                        <td>${displayMessage}</td>
                        <td>${currentTemplate}</td>
                    </tr>
                `;

                // Add hidden inputs for form submission
                hiddenInputs += `<input type="hidden" name="phones[]" value="${phoneNumber}">`;
                hiddenInputs += `<input type="hidden" name="messages[]" value="${processedMessage.replace(/"/g, '&quot;')}">`;
            });

            $('#preview-table tbody').html(tableRows);
            $('#hidden-inputs').html(hiddenInputs);

            // Update the textarea with the first processed message as sample
            if (currentCustomerData.length > 0) {
                const firstCustomer = Object.values(currentCustomerData[0]);
                const phoneNumber = firstCustomer[0] || '';

                let sampleMessage = currentTemplate;

                $('#teks_writing').val(sampleMessage);
                loadingOverlay.style.display = 'none';
            }
        }

        // Helper function to convert column index to letter (0 = A, 1 = B, 2 = C, ..., 25 = Z, 26 = AA, etc.)
        function getColumnLetter(index) {
            let result = '';
            index++; // Convert to 1-based (1 = A, 2 = B, etc.)

            while (index > 0) {
                index--; // Adjust for 0-based array
                result = String.fromCharCode(65 + (index % 26)) + result;
                index = Math.floor(index / 26);
            }

            return result;
        }
    </script>
    <script>
        toastr.options = {
            closeButton: false,
            debug: false,
            newestOnTop: false,
            progressBar: false,
            positionClass: "toast-top-right",
            preventDuplicates: false,
            onclick: null,
            showDuration: "300",
            hideDuration: "1000",
            timeOut: "5000",
            extendedTimeOut: "1000",
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut",
        };
    </script>
    <script>
        // Set initial value for datetime-local to current local time
        let now = new Date();
        let year = now.getFullYear();
        let month = String(now.getMonth() + 1).padStart(2, '0');
        let day = String(now.getDate()).padStart(2, '0');
        let hours = String(now.getHours()).padStart(2, '0');
        let minutes = String(now.getMinutes()).padStart(2, '0');
        let localDatetime = `${year}-${month}-${day}T${hours}:${minutes}`;
        $('#sendTime').val(localDatetime);

        // Make datetime picker open on click anywhere
        $('#sendTime').on('click', function() {
            if (this.showPicker) {
                this.showPicker();
            }
        });

        // Toggle schedule time input
        $('#sendNow').change(function() {
            if ($(this).is(':checked')) {
                $('#scheduleTime').hide();
            } else {
                $('#scheduleTime').show();
            }
        });
    </script>
    <script>
        // Konten toggle switch
        $('#img').change(function() {
            if ($(this).is(':checked')) {
                const name = $('#name').val();
                if (!name) {
                    $(this).prop('checked', false);
                    toastr.warning('Harap lengkapi field: Nama CS');
                    return;
                }

                // Check if image file exists for this CS name - try multiple patterns
                checkImageExists(name.toLowerCase());
            } else {
                $('.priview-img').html('');
                $('.text-img-blast').text('Tidak Aktif');
            }
        });

        function checkImageExists(name) {
            const extensions = ['jpg', 'jpeg', 'png', 'gif'];
            let attempts = 0;

            function tryNextExtension() {
                if (attempts >= extensions.length) {
                    // No image found with any extension
                    $('#img').prop('checked', false);
                    $('.priview-img').html('');
                    $('.text-img-blast').text('Tidak Aktif');
                    toastr.warning(`File konten untuk ${name} tidak ditemukan`);
                    return;
                }

                const fileName = `${name}.${extensions[attempts]}`;
                const imagePath = `/storage/files/1/Konten/${fileName}`;
                const img = new Image();

                img.onload = function() {
                    // Image exists, show preview
                    $('.priview-img').html(
                        `<img src="${imagePath}" alt="Konten ${name}" class="img-fluid mt-2" style="max-width: 200px;">`
                    );
                    $('.text-img-blast').text('Aktif');
                    toastr.success('Konten berhasil dimuat');
                };

                img.onerror = function() {
                    attempts++;
                    tryNextExtension();
                };

                img.src = imagePath;
            }

            tryNextExtension();
        }
    </script>
</body>

</html>
