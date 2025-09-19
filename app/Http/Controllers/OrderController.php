<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    public function create(Request $request)
    {
        $numbers = Device::all();
        return view('pages.order.create', compact('numbers'));
    }

    public function createGeneral(Request $request)
    {
        $numbers = Device::all();
        return view('pages.order.create-general', compact('numbers'));
    }

    public function getJsonSetting($sheetName = 'tim jogja')
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope(Sheets::SPREADSHEETS_READONLY);

        $service = new Sheets($client);

        // Settings Message Spreadsheet
        $spreadsheetId = '1CnG5AshHbPbZ4IDSuemFxhYsLV_xIygKTOqZ_5WsQW8';

        try {
            // Get spreadsheet info to get all sheets
            $spreadsheet = $service->spreadsheets->get($spreadsheetId);
            $allSheets = $spreadsheet->getSheets();

            if (empty($allSheets)) {
                return response()->json([
                    'error' => 'No sheets found in the spreadsheet',
                    'spreadsheet_id' => $spreadsheetId
                ]);
            }

            // Extract sheet information
            $sheetsInfo = [];
            foreach ($allSheets as $sheet) {
                $properties = $sheet->getProperties();
                $sheetsInfo[] = [
                    'name' => $properties->getTitle(),
                    'id' => $properties->getSheetId(),
                    'index' => $properties->getIndex(),
                    'url' => route('google.settings.message.sheet', ['sheetName' => $properties->getTitle()])
                ];
            }

            // If no specific sheet requested, use first sheet
            if ($sheetName === null) {
                $sheetName = $sheetsInfo[0]['name'];
            }

            // Validate requested sheet exists
            $currentSheet = null;
            foreach ($sheetsInfo as $sheet) {
                // Case-insensitive comparison and trim whitespace
                if (strtolower(trim($sheet['name'])) === strtolower(trim($sheetName))) {
                    $currentSheet = $sheet;
                    break;
                }
            }

            if (!$currentSheet) {
                // If exact match fails, try to find closest match
                $closestMatch = null;
                $bestSimilarity = 0;

                foreach ($sheetsInfo as $sheet) {
                    $similarity = similar_text(
                        strtolower(trim($sheet['name'])),
                        strtolower(trim($sheetName)),
                        $percent
                    );

                    if ($percent > $bestSimilarity) {
                        $bestSimilarity = $percent;
                        $closestMatch = $sheet;
                    }
                }

                if ($closestMatch && $bestSimilarity > 80) {
                    $currentSheet = $closestMatch;
                } else {
                    return response()->json([
                        'error' => "Sheet '{$sheetName}' not found in spreadsheet",
                        'spreadsheet_id' => $spreadsheetId,
                        'available_sheets' => array_column($sheetsInfo, 'name'),
                        'sheets' => $sheetsInfo,
                        'debug_info' => [
                            'requested_sheet' => $sheetName,
                            'available_sheets' => array_column($sheetsInfo, 'name'),
                            'url_encoded_requested' => urlencode($sheetName)
                        ]
                    ]);
                }
            }

            // Try to read data from the selected sheet with custom logic based on sheet name
            $data = [];
            $header = [];
            $startRow = 1; // Default start row
            $headerRow = 1; // Default header row

            // Custom logic for each sheet
            // Custom structure: Skip A1-A2, structured columns starting from AQ5+
            $sampleRange = "'{$sheetName}'!A1:AZ30";
            $sampleResponse = $service->spreadsheets_values->get($spreadsheetId, $sampleRange);
            $sampleData = $sampleResponse->getValues() ?? [];

            $data = [];
            $header = [];

            // Define column ranges for each section
            $produkHeaders = ['PRODUK'];
            $ncSectionHeader = ['COPYWRITING BLASTING NC'];
            $ncHeaders = ['PERKENALAN', 'REMINDER', 'TIPS 1', 'DOA', 'KONSUL 1', 'KONV 1', 'KONV 2', 'TIPS 2', 'KONV 3', 'KONSUL 2', 'KONV 4'];
            $roSectionHeader = ['COPYWRITING BLASTING RO'];
            $roHeaders = ['KONSULTASI', 'KONVERSI 1', 'SOFT SELLING', 'KONVERSI 2'];
            $pasifHeaders = ['DATA PASIF'];

            // Read data starting from A5 (row 5, all available columns)
            $dataStartRow = 5;
            $dataRange = "'{$sheetName}'!A{$dataStartRow}:Q";
            $dataResponse = $service->spreadsheets_values->get($spreadsheetId, $dataRange);
            $rawData = $dataResponse->getValues() ?? [];

            // Process each data row
            foreach ($rawData as $rowIndex => $row) {
                if (!empty(array_filter($row))) {
                    $rowData = [];

                    // Extract PRODUK (column A, index 0)
                    $produkData = $row[0] ?? null;
                    // Skip certain products
                    if ($produkData === 'JAM ETAWALIN' || $produkData === 'TESTING DATA') {
                        continue;
                    }
                    $rowData['PRODUK'] = $produkData;

                    // Add NC section header
                    $rowData['COPYWRITING BLASTING NC'] = null; // Section header, no data

                    // Extract NC data (columns B-L, which is index 1-11)
                    $ncData = array_slice($row, 1, 11); // B to L (11 columns)
                    foreach ($ncHeaders as $i => $headerName) {
                        $rowData[$headerName] = $ncData[$i] ?? null;
                    }

                    // Add RO section header
                    $rowData['COPYWRITING BLASTING RO'] = null; // Section header, no data

                    // Extract RO data (columns M-P, which is index 12-15)
                    $roData = array_slice($row, 12, 4); // M to P (4 columns)
                    foreach ($roHeaders as $i => $headerName) {
                        $rowData[$headerName] = $roData[$i] ?? null;
                    }

                    // Extract DATA PASIF (column Q, which is index 16)
                    $pasifData = $row[16] ?? null;
                    $rowData['DATA PASIF'] = $pasifData;

                    // Add row number for reference
                    $rowData['No'] = $dataStartRow + $rowIndex;

                    $data[] = $rowData;
                }
            }

            // Create combined headers including section headers
            $header = array_merge($produkHeaders, $ncSectionHeader, $ncHeaders, $roSectionHeader, $roHeaders, $pasifHeaders);

            // If no structured data found, fall back to default
            if (empty($data)) {
                $headerRange = "'{$sheetName}'!A5:Q5";
                $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
                $header = $headerResponse->getValues()[0] ?? [];
                $startRow = 6;
                $range = "'{$sheetName}'!A{$startRow}:Q";
            } else {
                // Return structured data
                return response()->json([
                    'data' => $data,
                    // 'header' => array_values($header),
                    // 'spreadsheet_id' => $spreadsheetId,
                    // 'sheet_name' => $sheetName,
                    // 'sheets' => $sheetsInfo,
                    // 'current_sheet' => $currentSheet,
                    // 'file_name' => 'Settings Message - ' . $sheetName,
                    // 'total_rows' => count($data),
                    // 'total_columns' => count($header),
                    // 'last_updated' => now()->toDateTimeString(),
                    // 'info' => 'Sheet displayed with structured columns: PRODUK | COPYWRITING BLASTING NC (11 sub-headers) | COPYWRITING BLASTING RO (4 sub-headers) | DATA PASIF',
                    // 'raw_data_mode' => false
                ]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Handle specific error cases
            if (strpos($errorMessage, 'not found') !== false) {
                $errorData = [
                    'error' => 'Settings message spreadsheet not found or not accessible.',
                    'spreadsheet_id' => $spreadsheetId,
                    'possible_causes' => [
                        'Spreadsheet ID might be incorrect',
                        'Service account does not have access to the spreadsheet',
                        'Spreadsheet might have been deleted or moved'
                    ],
                    'solutions' => [
                        'Verify the spreadsheet ID: ' . $spreadsheetId,
                        'Make sure the spreadsheet is shared with the service account',
                        'Check if the spreadsheet exists at: https://docs.google.com/spreadsheets/d/' . $spreadsheetId
                    ]
                ];
            } elseif (strpos($errorMessage, 'permission') !== false || strpos($errorMessage, 'forbidden') !== false) {
                $errorData = [
                    'error' => 'Permission denied to access settings message spreadsheet.',
                    'spreadsheet_id' => $spreadsheetId,
                    'possible_causes' => [
                        'Service account does not have read access to the spreadsheet',
                        'Spreadsheet sharing settings are incorrect'
                    ],
                    'solutions' => [
                        'Share the spreadsheet with: ' . $this->getServiceAccountEmail(),
                        'Make sure the sharing permission is "Viewer" or "Editor"',
                        'Check spreadsheet at: https://docs.google.com/spreadsheets/d/' . $spreadsheetId
                    ]
                ];
            } else {
                $errorData = [
                    'error' => 'Error reading settings message: ' . $errorMessage,
                    'spreadsheet_id' => $spreadsheetId,
                    'technical_error' => $errorMessage
                ];
            }

            return response()->json($errorData);
        }
    }

    public function getJsonFolderCS($folderId = null)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope(Drive::DRIVE_READONLY);

        $service = new Drive($client);

        // Jika tidak ada folderId, gunakan folder ID dari URL yang diberikan
        if ($folderId === null) {
            $folderId = '15wnQzKz6z64r2owrXdoR8J-QvL42BuxW'; // ID dari URL yang diberikan
        }

        try {
            // Ambil informasi folder
            $folder = $service->files->get($folderId);

            // Ambil semua file dalam folder
            $optParams = [
                'q' => "'{$folderId}' in parents and trashed=false",
                'fields' => 'files(id,name,mimeType,size,createdTime,modifiedTime,webViewLink)',
                'orderBy' => 'name'
            ];

            $results = $service->files->listFiles($optParams);
            $files = $results->getFiles();

            $folderContents = [];

            foreach ($files as $file) {

                $name = $file->getName();
                // Skip certain products
                if ($name === '#REF!' || strpos($name, 'Undistribute_SOSCOM') === 0) {
                    continue;
                }
                $folderContents[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'mimeType' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'createdTime' => $file->getCreatedTime(),
                    'modifiedTime' => $file->getModifiedTime(),
                    'webViewLink' => $file->getWebViewLink(),
                    'isFolder' => $file->getMimeType() === 'application/vnd.google-apps.folder',
                    'isGoogleSheet' => $file->getMimeType() === 'application/vnd.google-apps.spreadsheet',
                    'isExcelFile' => in_array($file->getMimeType(), [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                        'application/vnd.ms-excel', // .xls
                        'text/csv' // .csv
                    ])
                ];
            }

            $data = [
                'folder' => [
                    'id' => $folder->getId(),
                    'name' => $folder->getName(),
                    'mimeType' => $folder->getMimeType()
                ],
                'files' => $folderContents,
                'total_files' => count($folderContents),
                // 'google_sheets_count' => count(array_filter($folderContents, function ($file) {
                //     return $file['isGoogleSheet'];
                // })),
                // 'excel_files_count' => count(array_filter($folderContents, function ($file) {
                //     return isset($file['isExcelFile']) && $file['isExcelFile'];
                // })),
                // 'subfolders_count' => count(array_filter($folderContents, function ($file) {
                //     return $file['isFolder'];
                // }))
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Handle specific error cases
            if (strpos($errorMessage, 'Google Drive API has not been used') !== false) {
                $errorData = [
                    'error' => 'Google Drive API masih belum aktif atau belum propagasi.',
                    'instructions' => [
                        'Pastikan sudah klik "Enable" di: https://console.developers.google.com/apis/api/drive.googleapis.com/overview?project=993043737786',
                        'Tunggu 2-5 menit untuk propagasi',
                        'Refresh halaman dan coba lagi'
                    ],
                    'technical_error' => $errorMessage
                ];
            } elseif (strpos($errorMessage, 'File not found') !== false || strpos($errorMessage, '404') !== false) {
                $errorData = [
                    'error' => 'Folder tidak ditemukan atau tidak ada permission.',
                    'possible_causes' => [
                        'Service account tidak memiliki akses ke folder ini',
                        'Folder ID salah atau folder sudah dihapus',
                        'Folder belum di-share ke service account email'
                    ],
                    'solutions' => [
                        'Pastikan folder di-share ke email service account: ' . $this->getServiceAccountEmail(),
                        'Atau gunakan folder ID yang berbeda',
                        'Atau gunakan method readSheetByUrl() jika tahu spreadsheet ID langsung'
                    ],
                    'folder_id_attempted' => $folderId,
                    'sharing_link' => 'https://drive.google.com/drive/folders/' . $folderId,
                    'technical_error' => $errorMessage
                ];
            } else {
                $errorData = [
                    'error' => 'Error reading folder: ' . $errorMessage,
                    'folder_id_attempted' => $folderId
                ];
            }

            return response()->json($errorData);
        }
    }

    public function getJsonReadExcelFile(Request $request, $fileId)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope(Drive::DRIVE_READONLY);

        $service = new Drive($client);

        try {
            // Get file info first
            $fileInfo = $service->files->get($fileId, ['fields' => 'name,mimeType,size']);

            // Authorize client dan dapatkan access token
            $client->fetchAccessTokenWithAssertion();
            $accessToken = $client->getAccessToken()['access_token'];

            // Buat HTTP request untuk download file
            $url = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Authorization: Bearer {$accessToken}\r\n"
                ]
            ]);

            $fileContent = file_get_contents($url, false, $context);

            if ($fileContent === false) {
                throw new \Exception('Failed to download file from Google Drive');
            }

            // Determine file extension based on mime type
            $extension = '.xlsx';
            if ($fileInfo->getMimeType() === 'application/vnd.ms-excel') {
                $extension = '.xls';
            } elseif ($fileInfo->getMimeType() === 'text/csv') {
                $extension = '.csv';
            }

            // Simpan sementara untuk diproses
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_') . $extension;
            file_put_contents($tempFile, $fileContent);

            // Load Excel file menggunakan Laravel Excel
            $data = Excel::toArray([], $tempFile);

            // Hapus file temporary
            unlink($tempFile);

            // Ambil sheet pertama
            $sheetData = $data[0] ?? [];

            if (empty($sheetData)) {
                $errorData = [
                    'error' => 'Excel file is empty or could not be read',
                    'file_name' => $fileInfo->getName(),
                    'instructions' => [
                        'The Excel file appears to be empty',
                        'Please check the file content',
                        'Make sure the file is not corrupted'
                    ]
                ];
                return response()->json($errorData);
            }

            // Ambil header dari baris pertama
            $header = !empty($sheetData) ? $sheetData[0] : [];
            $rows = array_slice($sheetData, 1);

            // Format data seperti Google Sheets
            $formattedData = array_map(function ($row) use ($header) {
                $row = array_pad($row, count($header), null);
                $row = array_slice($row, 0, count($header));
                return array_combine($header, $row);
            }, $rows);

            $viewData = [
                'file_id' => $fileId,
                'file_name' => $fileInfo->getName(),
                'sheet_name' => 'Sheet1',
                'total_rows' => count($formattedData),
                'header' => $header,
                'data' => $formattedData
            ];

            return response()->json($viewData);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Handle specific error cases
            if (strpos($errorMessage, 'permission') !== false || strpos($errorMessage, 'forbidden') !== false) {
                $errorData = [
                    'error' => 'Permission denied to access this file',
                    'file_id' => $fileId,
                    'instructions' => [
                        'The file needs to be shared with the service account',
                        'Share the file with: ' . $this->getServiceAccountEmail(),
                        'Give at least "Viewer" permission to the service account',
                        'Wait a few minutes after sharing for changes to take effect'
                    ]
                ];
            } elseif (strpos($errorMessage, 'not found') !== false) {
                $errorData = [
                    'error' => 'File not found',
                    'file_id' => $fileId,
                    'instructions' => [
                        'The file ID might be incorrect',
                        'The file might have been deleted or moved',
                        'Check if the file exists in Google Drive'
                    ]
                ];
            } else {
                $errorData = [
                    'error' => 'Error reading Excel file: ' . $errorMessage,
                    'file_id' => $fileId,
                    'instructions' => [
                        'Please try refreshing the page',
                        'Make sure the file is a valid Excel file',
                        'Check your internet connection',
                        'If problem persists, try converting the file to Google Sheets format'
                    ]
                ];
            }

            return response()->json($errorData);
        }
    }

    private function getServiceAccountEmail()
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        if (!file_exists($path)) {
            return 'credential-file-not-found';
        }

        try {
            $credentialsJson = file_get_contents($path);
            $credentials = json_decode($credentialsJson, true);
            return $credentials['client_email'] ?? 'email-not-found-in-credentials';
        } catch (\Exception $e) {
            return 'error-reading-credentials';
        }
    }

    public function store(Request $request)
    {
        dd($request->all());
        // Validasi input
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $device = Device::find($request->input('device_id'));
        $file = $request->file('file');

        // Proses file Excel
        $data = Excel::toArray([], $file);

        // Simpan data ke database atau proses sesuai kebutuhan
        // ...

        return redirect()->back()->with('success', 'Order processed successfully!');
    }
}
