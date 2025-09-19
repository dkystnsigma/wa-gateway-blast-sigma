<x-layout-dashboard title="Create Order">

    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://unpkg.com/emoji-button@latest/dist/index.css" rel="stylesheet" />
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
        <div class="breadcrumb-title pe-3">Form Order</div>
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
            <h6 class="mb-0 text-uppercase">Create Your Order</h6>
            <hr />
            {{--  --}}
            <div class="shadow card">
                <div class="border-0 card-header bg-light">
                    <i class="bi bi-send me-2"></i><strong> Order Baru</strong>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Nama</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Produk</strong></label>
                        <input name="product" id="product" placeholder="product name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Tanggal Hari Ini</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Tanggal Blasting</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Waktu Blasting</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Keterangan Blasting</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Copy Writing</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Teks Writing</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Konten</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Keterangan Tambahan</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Total Data</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Data Terkirim</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="mb-2"><strong>Data Non Wa</strong></label>
                        <input name="campaign" id="campaign" placeholder="campaign name" type="text"
                            class="form-control" aria-invalid="false" value="">
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
        });
    </script>
</x-layout-dashboard>
