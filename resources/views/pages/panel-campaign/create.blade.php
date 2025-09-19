<x-layout-dashboard title="Create Campaign">

    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://unpkg.com/emoji-button@latest/dist/index.css" rel="stylesheet" />
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch-input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .switch-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .switch-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        .switch-input:checked+.switch-slider {
            background-color: #28a745;
        }

        .switch-input:checked+.switch-slider:before {
            transform: translateX(26px);
        }

        .switch-success .switch-slider {
            background-color: #ffffff;
        }

        .switch-3d .switch-slider {
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .switch-3d .switch-slider:before {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dropzone {
            border: 2px dashed #ccc;
            border-radius: 5px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .dropzone:hover,
        .dropzone.dragover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .dropzone p {
            margin: 0;
            font-size: 18px;
        }

        .dropzone em {
            color: #6c757d;
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
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Campaign</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>

    </div>
    <!--end breadcrumb-->

    {{-- wizard --}}
    <div class="row">
        <div class="col-xl-12 mx-auto">
            <h6 class="mb-0 text-uppercase">Create Your Campaign </h6>
            <hr />
            {{--  --}}
            <div class="shadow card">
                <div class="border-0 card-header bg-light">
                    <i class="bi bi-send me-2"></i><strong> Campaign Baru</strong>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Nama Campaign</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3"><label for="name" class="mb-2"><strong>Whatsapp
                                Server</strong></label>
                        <select class="form-control" id="device_idd_create" name="device_id">
                            <option value="" selected>Semua Whatsapp Server</option>
                            @foreach ($numbers as $device)
                                <option value="{{ $device->id }}">{{ $device->name }} - 
                                    {{ $device->body }}
                                    ({{ $device->status }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3"><label for="name" class="mb-2"><strong>Data Nomor
                                Whatsapp</strong></label>
                        <div class="controls mt-2">
                            <label class="switch switch-3d switch-success mx-2 mb-2">
                                <input type="checkbox" class="switch-input" id="manualInput" checked>
                                <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                            </label>
                            <label class="form-check-label ml-2 mb-2">Masukan Manual</label>
                            <label class="switch switch-3d switch-success mx-2 mb-2">
                                <input type="checkbox" class="switch-input" id="groupWhatsapp">
                                <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                            </label>
                            <label class="form-check-label ml-2 mb-2">Grup Whatsapp</label>
                            <label class="switch switch-3d switch-success mx-2 mb-2">
                                <input type="checkbox" class="switch-input" id="groupContact">
                                <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                            </label>
                            <label class="form-check-label ml-2 mb-2">Grup Kontak</label>
                            <label class="switch switch-3d switch-success mx-2 mb-2">
                                <input type="checkbox" class="switch-input" id="importExcel">
                                <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                            </label>
                            <label class="form-check-label ml-2 mb-2">Impor File Excel</label>
                            <label class="switch switch-3d switch-success mx-2 mb-2">
                                <input type="checkbox" class="switch-input" id="randomNumber">
                                <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                            </label>
                            <label class="form-check-label ml-2 mb-2">Nomor Random</label>
                        </div>
                    </div>
                    <div id="manualInputGroup" class="form-group mb-3 mx-4">
                        <label class="mb-2"><i>tulis nomor tujuan dan pisahkan dengan
                                koma(,) atau enter jika lebih dari satu</i> <i>atau</i>
                        </label>
                        <button type="button" disabled class="btn-sm btn btn-secondary disabled mb-2">Ambil Dari
                            Kontak</button>
                        <div class="controls">
                            <textarea id="noManualText" name="noManualText" placeholder="628134354353,08143243543,6281244534453" rows="5"
                                class="form-control" aria-invalid="false"></textarea>
                            <p class="help-bloc text-end"><strong><span>0</span> Nomor</strong></p>
                        </div>
                    </div>
                    <div id="whatsappGroup" class="d-none mx-4" style="margin-top: 10px;">
                        <div class="form-group mb-3"><label for="name" class="mb-2"><strong>Whatsapp
                                    Group</strong></label>
                            <select class="form-control" id="channelSelect" name="channelSelect">
                                <option value="">Grub Whatsapp </option>
                            </select>
                        </div>
                    </div>
                    <div id="contactGroup" class="d-none mx-4" style="margin-top: 10px;">
                        <div class="form-group mb-3"><label for="name" class="mb-2"><strong>
                                    Group Kontak</strong></label>
                            <select class="form-control" id="channelSelect" name="channelSelect">
                                <option value="">Grub Kontak </option>
                            </select>
                        </div>
                    </div>
                    <div id="excelUpload" class="d-none mx-4" style="margin-top: 10px;">
                        <div class="form-group mb-3">
                            <section class="container">
                                <div tabindex="0" class="dropzone"><input accept=".xlsx" multiple=""
                                        type="file" autocomplete="off" tabindex="-1" style="display: none;">
                                    <p>Tarik dan jatuhkan file excel disini, atau klik disini untuk memilih file</p>
                                    <em>(Hanya file *.xlsx yang diijinkan)</em>
                                </div>
                                <div class="file-list mt-3"></div>
                            </section>
                        </div>
                        <div style="background-color: rgb(245, 247, 249);">
                            <blockquote class="blockquote" style="padding: 10px;">
                                <h4>Catatan:</h4>
                                <ol style="font-size: 14px;">
                                    <li>Format excel 2007 dan ke atas (ekstensinya .xlsx)</li>
                                    <li>Kolom A untuk nomor whatsapp</li>
                                    <li>Dimulai dari baris kedua</li>
                                    <li>Download contoh file disini <a
                                            href="{{ asset('assets/template/excel/example-message.xlsx') }}" download
                                            class="btn-sm mt-2 btn btn-info">Download</a></li>
                                </ol>
                            </blockquote>
                        </div>
                    </div>
                    <div id="randomNumberGroup" class="d-none" style="margin-top: 10px;">
                        <div class="form-group mb-3">
                            <label for="name" class="mb-2"><strong>Nomor Random</strong></label>
                            <input name="random_number" id="random_number" placeholder="random number"
                                type="text" class="form-control" aria-invalid="false" value="">
                        </div>
                    </div>

                    <div class="form-group mb-3"><label class="mb-2"><strong>Pesan Teks</strong><span
                                class="text-danger">*</span></label>
                        <textarea id="message" name="message" placeholder="tulis pesan" rows="5" class="form-control"
                            aria-invalid="false"></textarea>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <div class="d-flex gap-3">
                            <button type="button" id="show-emoji" class="btn-sm mt-2 btn btn-info">Tampilkan
                                Emoji</button>
                            <button type="button" class="btn-sm mt-2 ml-4 btn btn-warning">Buat
                                Spintax</button>
                            <button type="button" class="btn-sm mt-2 ml-2 btn btn-danger">Tes
                                Spintax</button>
                        </div>
                        <div class="">
                            <p class="help-block text-right"><strong><span>0</span> karakter</strong>
                            </p>
                        </div>
                    </div>
                    <div class="mx-5 mb-3" style="background-color: rgb(245, 247, 249);">
                        <blockquote class="blockquote" style="padding: 10px;">
                            <h6>Catatan:</h6>
                            <ol style="font-size: 14px;">
                                <li>Mendukung spintax dengan format {kata|kata|kata|...}, contoh: {Halo|Hoi|Apa Kabar}
                                </li>
                                <li>Nomor dari grup kontak mendukung nama kontak, kolom 1 (col1) hingga kolom 5 (col5),
                                    contoh: Halo @{{ name }} @{{ col1 }} @{{ col2 }}
                                    @{{ col3 }}</li>
                                <li>Nomor dari Excel mendukung nilai dari kolom A-Z, contoh: Selamat pagi
                                    @{{ A }}, saldo kamu sebesar @{{ B }}</li>
                            </ol>
                        </blockquote>
                    </div>
                    <div class="form-group mb-3">
                        <div class="controls">
                            <label class="switch switch-3d switch-success mx-1">
                                <input type="checkbox" class="switch-input" id="attachment">
                                <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                            </label>
                            <label class="form-check-label ml-2"><strong>Lampiran</strong></label>
                        </div>
                        <div class="d-none mx-5" id="attachmentContainer" style="margin-top: 10px;">
                            <div class="form-group">
                                <div class="controls">
                                    <label class="switch switch-3d switch-success mx-2 mb-2">
                                        <input type="checkbox" class="switch-input" id="mergeTextImage" checked>
                                        <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                                    </label>
                                    <label class="form-check-label ml-2 mb-2"><strong>Gabungkan teks dengan gambar</strong></label>
                                </div>
                            </div>
                            <div class="container form-group mb-3">
                                <section class="container">
                                    <div tabindex="0" class="dropzone"><input accept="image/*,video/*,audio/*,.pdf"
                                            multiple="" type="file" autocomplete="off" tabindex="-1"
                                            style="display: none;">
                                        <p>Tarik dan jatuhkan file lampiran disini, atau klik disini untuk memilih file
                                        </p>
                                        <em>(Hanya file gambar, video, audio, dan PDF yang diijinkan)</em>
                                    </div>
                                    <div class="file-list mt-3"></div>
                                </section>
                            </div>
                            <div class="ml-5" style="background-color: rgb(245, 247, 249);">
                                <blockquote class="blockquote" style="padding: 10px;">
                                    <h6>File yang diijinkan:</h6>
                                    <ol style="font-size: 14px;">
                                        <li>Gambar: ekstensi *.png, *.jpg, *jpeg, ukuran maksimal 1 Mb</li>
                                        <li>Video: ukuran maksimal 4 Mb (video/mp4, video/3gpp)</li>
                                        <li>Audio: ukuran maksimal 2 Mb</li>
                                        <li>Dokumen: PDF ukuran maksimal 2 Mb</li>
                                    </ol>
                                </blockquote>
                            </div>

                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <div class="controls">
                            <label class="switch switch-3d switch-success mx-1">
                                <input type="checkbox" class="switch-input" id="contact">
                                <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                            </label>
                            <label class="form-check-label ml-2"><strong>Kontak</strong></label>
                        </div>
                        <div class="mx-5 d-none" id="contactContainer" style="margin-top: 10px;">
                            <div class="form-group mb-3">
                                <label for="name" class="mb-2"><strong>Nama Kontak</strong></label>
                                <input name="contact_name" id="contact_name" placeholder="contact name"
                                    type="text" class="form-control" aria-invalid="false" value="">
                            </div>
                            <div class="form-group mb-3">
                                <label for="name" class="mb-2"><strong>Nomor Whatsapp</strong></label>
                                <input name="contact_whatsapp" id="contact_whatsapp" placeholder="contact whatsapp"
                                    type="text" class="form-control" aria-invalid="false" value="">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <div class="controls">
                            <label class="switch switch-3d switch-success mx-1">
                                <input type="checkbox" class="switch-input" id="location">
                                <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                            </label>
                            <label class="form-check-label ml-2"><strong>Lokasi</strong></label>
                        </div>
                        <div class="mx-5 d-none" id="locationContainer" style="margin-top: 10px;">
                            <div class="form-group mb-3">
                                <label for="name" class="mb-2"><strong>Search</strong></label>
                                <input name="location_search" id="location_search" placeholder="Search"
                                    type="text" class="form-control" aria-invalid="false" value="">
                                <div id="search-results" class="mt-1"
                                    style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; background: white; display: none;">
                                </div>
                            </div>
                            <div class="mb-3" id="map" style="height: 400px;"></div>
                            <div class="form-group mb-3">
                                <label for="name" class="mb-2"><strong>Latitude</strong></label>
                                <input name="location_latitude" id="location_latitude" placeholder="latitude"
                                    type="text" class="form-control" aria-invalid="false" value="">
                            </div>
                            <div class="form-group mb-3">
                                <label for="name" class="mb-2"><strong>Longitude</strong></label>
                                <input name="location_longitude" id="location_longitude" placeholder="longitude"
                                    type="text" class="form-control" aria-invalid="false" value="">
                            </div>
                            <div class="form-group mb-3">
                                <label for="name" class="mb-2"><strong>Nama Lokasi</strong></label>
                                <input name="location_name" id="location_name" placeholder="nama lokasi"
                                    type="text" class="form-control" aria-invalid="false" value="">
                            </div>
                            <div class="form-group mb-3">
                                <label for="name" class="mb-2"><strong>Alamat Lokasi</strong></label>
                                <input name="location_address" id="location_address" placeholder="alamat lokasi"
                                    type="text" class="form-control" aria-invalid="false" value="">
                            </div>
                            <div class="form-group mb-3">
                                <label for="name" class="mb-2"><strong>URL</strong></label>
                                <input name="location_url" id="location_url" placeholder="url" type="text"
                                    class="form-control" aria-invalid="false" value="">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <div class="controls">
                            <label class="switch switch-3d switch-success mx-1">
                                <input type="checkbox" class="switch-input" id="restSending">
                                <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                            </label>
                            <label class="form-check-label ml-2"><strong>Istirahat Pengiriman</strong></label>
                        </div>

                        <div class="mx-5 d-none" id="restSendingContainer" style="margin-top: 10px;">
                            <div class="form-group mb-3">
                                <label for="name" class="mb-2"><strong>Setiap kelipatan pesan</strong></label>
                                <input name="message_interval" id="message_interval" placeholder="message interval"
                                    type="text" class="form-control" aria-invalid="false" value="">
                            </div>
                            <div class="form-group mb-3">
                                <label for="name" class="mb-2"><strong>Lama istirahat (menit)</strong></label>
                                <input name="rest_duration" id="rest_duration" placeholder="rest duration"
                                    type="text" class="form-control" aria-invalid="false" value="">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3"><label for="name" class="mb-2"><strong>Waktu
                                Kirim</strong></label>
                        <div class="controls mt-2">
                            <label class="switch switch-3d switch-success mx-1">
                                <input type="checkbox" class="switch-input" id="sendNow" checked>
                                <span class="switch-slider" data-checked="On" data-unchecked="Off"></span>
                            </label>
                            <label class="form-check-label ml-2">Sekarang</label>
                            <div id="scheduleTime" style="display: none; margin-top: 10px;">
                                <label for="sendTime" class="mb-2"><strong>Jadwalkan Waktu Kirim</strong></label>
                                <input type="datetime-local" id="sendTime" name="sendTime" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-end card-footer"><button type="button" class="btn btn-info text-light">
                        <i class="bi bi-send me-2"></i> Buat Campaign</button></div>
            </div>
            {{--  --}}
        </div>
    </div>
    </div>
    {{-- end wizard --}}
    <script src="{{ asset('js/autoreply.js') }}"></script>
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/js/form-select2.js') }}"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/emoji-button@latest/dist/index.js"></script>
    <script>
        $(document).ready(function() {
            $('#device_idd_create').select2({
                placeholder: "Semua Whatsapp Server",
                allowClear: true, // Tambahkan tombol clear
                width: '100%' // Sesuaikan lebar
            });
            // Emoji picker
            $('#show-emoji').click(function() {
                const picker = new EmojiButton();
                picker.on('emoji', emoji => {
                    $('#message').val($('#message').val() + emoji);
                });
                picker.showPicker(this);
            });
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

            // Toggle data source options (radio-like behavior)
            const switches = ['manualInput', 'groupWhatsapp', 'groupContact', 'importExcel', 'randomNumber'];
            const groups = ['manualInputGroup', 'whatsappGroup', 'contactGroup', 'excelUpload',
                'randomNumberGroup'
            ];
            switches.forEach((sw, i) => {
                $('#' + sw).change(function() {
                    if ($(this).is(':checked')) {
                        switches.forEach((s, j) => {
                            if (j !== i) {
                                $('#' + s).prop('checked', false);
                            }
                        });
                        groups.forEach((g, j) => {
                            if (j === i) {
                                $('#' + g).removeClass('d-none');
                            } else {
                                $('#' + g).addClass('d-none');
                            }
                        });
                    } else {
                        const checkedCount = switches.filter(s => $('#' + s).is(':checked')).length;
                        if (checkedCount === 0) {
                            $('#manualInput').prop('checked', true);
                            $('#manualInputGroup').removeClass('d-none');
                        }
                    }
                });
            });
            // Drag and drop for Excel upload
            const dropzones = document.querySelectorAll('.dropzone');
            dropzones.forEach(dropzone => {
                let files = [];
                const fileInput = dropzone.querySelector('input[type="file"]');
                const listDiv = dropzone.closest('[id]').querySelector('.file-list');

                function updateDisplay() {
                    listDiv.innerHTML = '';
                    files.forEach((f, i) => {
                        const item = document.createElement('div');
                        item.className =
                            'file-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded';
                        const size = f.size > 1024 * 1024 ?
                            `${(f.size / (1024 * 1024)).toFixed(2)} MB` :
                            `${(f.size / 1024).toFixed(2)} KB`;
                        item.innerHTML = `<span>${f.name} (${size})</span>`;
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-sm btn-danger';
                        removeBtn.textContent = 'X';
                        removeBtn.onclick = () => {
                            files.splice(i, 1);
                            updateDisplay();
                            updateFiles();
                        };
                        item.appendChild(removeBtn);
                        listDiv.appendChild(item);
                    });
                }

                function updateFiles() {
                    const dt = new DataTransfer();
                    files.forEach(f => dt.items.add(f));
                    fileInput.files = dt.files;
                }

                dropzone.addEventListener('click', () => {
                    fileInput.click();
                });

                dropzone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropzone.classList.add('dragover');
                });

                dropzone.addEventListener('dragleave', () => {
                    dropzone.classList.remove('dragover');
                });

                dropzone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                    const droppedFiles = Array.from(e.dataTransfer.files);
                    droppedFiles.forEach(f => {
                        if (!files.find(ff => ff.name === f.name && ff.size === f.size)) {
                            files.push(f);
                        }
                    });
                    updateDisplay();
                    updateFiles();
                });

                fileInput.addEventListener('change', () => {
                    const selectedFiles = Array.from(fileInput.files);
                    selectedFiles.forEach(f => {
                        if (!files.find(ff => ff.name === f.name && ff.size === f.size)) {
                            files.push(f);
                        }
                    });
                    updateDisplay();
                    updateFiles();
                });
            });

            // Toggle additional options independently
            function initMap() {
                var map = L.map('map').setView([-7.815, 110.396], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                var marker = L.marker([-7.815, 110.396]).addTo(map);
                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    $('#location_latitude').val(e.latlng.lat.toFixed(6));
                    $('#location_longitude').val(e.latlng.lng.toFixed(6));
                    // Reverse geocoding
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}`, {
                            headers: {
                                'User-Agent': 'WA-Gateway/1.0'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.display_name) {
                                $('#location_address').val(data.display_name);
                                $('#location_name').val(data.name || data.display_name.split(',')[0]);
                                $('#location_url').val(
                                    `https://www.google.com/maps/@${e.latlng.lat},${e.latlng.lng},15z`
                                );
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
                // Search functionality with debouncing
                let searchTimeout;
                $('#location_search').on('input', function() {
                    clearTimeout(searchTimeout);
                    var query = $(this).val();
                    if (query.length > 2) {
                        searchTimeout = setTimeout(() => {
                            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`, {
                                    headers: {
                                        'User-Agent': 'WA-Gateway/1.0'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    var resultsHtml = '';
                                    data.forEach(item => {
                                        resultsHtml +=
                                            `<div class="search-result" data-lat="${item.lat}" data-lon="${item.lon}" data-name="${item.display_name}">${item.display_name}</div>`;
                                    });
                                    $('#search-results').html(resultsHtml).show();
                                })
                                .catch(error => console.error('Error:', error));
                        }, 500);
                    } else {
                        $('#search-results').hide();
                    }
                });
                $(document).on('click', '.search-result', function() {
                    var lat = $(this).data('lat');
                    var lon = $(this).data('lon');
                    var name = $(this).data('name');
                    marker.setLatLng([lat, lon]);
                    map.setView([lat, lon], 15);
                    $('#location_latitude').val(parseFloat(lat).toFixed(6));
                    $('#location_longitude').val(parseFloat(lon).toFixed(6));
                    $('#location_name').val(name.split(',')[0]);
                    $('#location_address').val(name);
                    $('#location_url').val(`https://www.google.com/maps/@${lat},${lon},15z`);
                    $('#search-results').hide();
                    $('#location_search').val('');
                });
            }
            const additionalSwitches = ['attachment', 'contact', 'location', 'restSending'];
            const additionalContainers = ['attachmentContainer', 'contactContainer', 'locationContainer',
                'restSendingContainer'
            ];
            additionalSwitches.forEach((sw, i) => {
                $('#' + sw).change(function() {
                    if ($(this).is(':checked')) {
                        $('#' + additionalContainers[i]).removeClass('d-none');
                        if (sw === 'location') {
                            initMap();
                        }
                    } else {
                        $('#' + additionalContainers[i]).addClass('d-none');
                    }
                });
            });
        });
    </script>
</x-layout-dashboard>
