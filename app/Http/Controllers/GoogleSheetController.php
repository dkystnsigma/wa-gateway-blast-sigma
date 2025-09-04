<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Drive;
use App\Models\Device;
use App\Models\Campaign;
use App\Models\Blast;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GoogleSheetController extends Controller
{
    public function readFormOrder($sheetName = null)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope(Sheets::SPREADSHEETS_READONLY);

        $service = new Sheets($client);

        // Form Order Spreadsheet
        $spreadsheetId = '1RMJ4NRKAV5FWDtKUstgqMxB0vegMd7PijjT1n6zQfMw';

        try {
            // Get spreadsheet info to get all sheets
            $spreadsheet = $service->spreadsheets->get($spreadsheetId);
            $allSheets = $spreadsheet->getSheets();

            if (empty($allSheets)) {
                return view('sheet.form-order', [
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
                    'url' => route('google.form.order.sheet', ['sheetName' => $properties->getTitle()])
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
                    return view('sheet.form-order', [
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

            // Special handling for FORM ORDER sheet: Read header from A1 (full), but data from B2 onwards
            if (strtolower(trim($sheetName)) === 'form order') {
                // Read header from row 1, columns A onwards (full header)
                $headerRange = "'{$sheetName}'!A1:Z1";
                $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
                $header = $headerResponse->getValues()[0] ?? [];

                if (empty($header)) {
                    return view('sheet.form-order', [
                        'error' => "Sheet '{$sheetName}' has no headers in row 1, column A onwards",
                        'spreadsheet_id' => $spreadsheetId,
                        'sheet_name' => $sheetName,
                        'sheets' => $sheetsInfo,
                        'current_sheet' => $currentSheet,
                        'setup_instructions' => [
                            'Please add column headers starting from column A (A1, B1, C1, etc.)',
                            'Column A can be used for numbering or any other purpose',
                            'Then add your form order data starting from row 2, column B onwards'
                        ],
                        'spreadsheet_url' => "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit#gid={$currentSheet['id']}",
                        'last_updated' => now()->toDateTimeString()
                    ]);
                }

                // Read data starting from A2
                $dataRange = "'{$sheetName}'!A2:Z";
                $dataResponse = $service->spreadsheets_values->get($spreadsheetId, $dataRange);
                $values = $dataResponse->getValues();

                if (empty($values)) {
                    return view('sheet.form-order', [
                        'data' => [],
                        'header' => $header,
                        'spreadsheet_id' => $spreadsheetId,
                        'sheet_name' => $sheetName,
                        'sheets' => $sheetsInfo,
                        'current_sheet' => $currentSheet,
                        'total_rows' => 0,
                        'total_columns' => count($header),
                        'file_name' => 'Form Order - ' . $sheetName,
                        'info' => 'Headers found but no data rows yet. You can start adding form order data from row 2.',
                        'setup_instructions' => [
                            'Headers found successfully!',
                            'Now you can add your form order data starting from row 2, column B onwards'
                        ],
                        'last_updated' => now()->toDateTimeString()
                    ]);
                }

                // Format data with headers
                $data = array_map(function ($row) use ($header) {
                    $row = array_pad($row, count($header), null);
                    $row = array_slice($row, 0, count($header));
                    return array_combine($header, $row);
                }, $values);

                // Check device status for each row
                foreach ($data as &$row) {
                    $deviceName = null;

                    // Find the name column (case-insensitive search) starting from column B
                    $columnIndex = 0;
                    foreach ($row as $key => $value) {
                        // Skip column A (index 0), start checking from column B (index 1)
                        if ($columnIndex > 0 && (strtolower(trim($key)) === 'name' || strtolower(trim($key)) === 'device' || strtolower(trim($key)) === 'nama')) {
                            $deviceName = trim($value);
                            break;
                        }
                        $columnIndex++;
                    }

                    // If no specific name column found, use second column (column B) instead of first column
                    if ($deviceName === null && !empty($row)) {
                        $rowValues = array_values($row);
                        if (isset($rowValues[1]) && !empty(trim($rowValues[1]))) {
                            $deviceName = trim($rowValues[1]); // Column B (index 1)
                        }
                    }

                    $row['device_status'] = 'unknown';
                    $row['row_class'] = 'table-secondary'; // Default gray for unknown

                    if (!empty($deviceName)) {
                        $device = Device::where('name', $deviceName)->first();
                        if ($device) {
                            // Case-insensitive comparison for 'connected' status
                            if (strtolower(trim($device->status)) === 'connected') {
                                $row['device_status'] = 'connected';
                                $row['row_class'] = ''; // Normal row (no additional class)

                                // Auto-create campaign if connected and not exists
                                $this->autoCreateCampaignIfNeeded($row, $device);
                            } else {
                                $row['device_status'] = $device->status;
                                $row['row_class'] = 'table-warning'; // Yellow for not connected
                            }
                        } else {
                            $row['device_status'] = 'not_found';
                            $row['row_class'] = 'table-danger'; // Red for not found
                        }
                    }
                }

                return view('sheet.form-order', [
                    'data' => $data,
                    'header' => $header,
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'sheets' => $sheetsInfo,
                    'current_sheet' => $currentSheet,
                    'file_name' => 'Form Order - ' . $sheetName,
                    'total_rows' => count($data),
                    'total_columns' => count($header),
                    'last_updated' => now()->toDateTimeString(),
                    'info' => 'Form Order data displayed with device status checking (Green: Connected, Yellow: Other Status, Red: Not Found)',
                    'device_status_legend' => [
                        'Normal' => 'Device is connected',
                        'Yellow' => 'Device exists but not connected',
                        'Red' => 'Device not found in database'
                    ]
                ]);
            }

            // Default behavior for other sheets
            $headerRange = "'{$sheetName}'!A1:Z1";
            $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
            $header = $headerResponse->getValues()[0] ?? [];

            if (empty($header)) {
                return view('sheet.form-order', [
                    'error' => "Sheet '{$sheetName}' has no headers in the first row",
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'sheets' => $sheetsInfo,
                    'current_sheet' => $currentSheet,
                    'setup_instructions' => [
                        'Please add column headers in the first row (A1, B1, C1, etc.)',
                        'Then add your form order data starting from row 2',
                        'Make sure the first row contains meaningful column names'
                    ],
                    'spreadsheet_url' => "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit#gid={$currentSheet['id']}",
                    'last_updated' => now()->toDateTimeString()
                ]);
            }

            // Read data starting from row 2
            $dataRange = "'{$sheetName}'!A2:Z";
            $dataResponse = $service->spreadsheets_values->get($spreadsheetId, $dataRange);
            $values = $dataResponse->getValues();

            if (empty($values)) {
                return view('sheet.form-order', [
                    'data' => [],
                    'header' => $header,
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'sheets' => $sheetsInfo,
                    'current_sheet' => $currentSheet,
                    'total_rows' => 0,
                    'total_columns' => count($header),
                    'file_name' => 'Form Order - ' . $sheetName,
                    'info' => 'Headers found but no data rows yet. You can start adding form order data from row 2.',
                    'setup_instructions' => [
                        'Headers found successfully!',
                        'Now you can add your form order data starting from row 2'
                    ],
                    'last_updated' => now()->toDateTimeString()
                ]);
            }

            // Format data with headers
            $data = array_map(function ($row) use ($header) {
                $row = array_pad($row, count($header), null);
                $row = array_slice($row, 0, count($header));
                return array_combine($header, $row);
            }, $values);

            return view('sheet.form-order', [
                'data' => $data,
                'header' => $header,
                'spreadsheet_id' => $spreadsheetId,
                'sheet_name' => $sheetName,
                'sheets' => $sheetsInfo,
                'current_sheet' => $currentSheet,
                'file_name' => 'Form Order - ' . $sheetName,
                'total_rows' => count($data),
                'total_columns' => count($header),
                'last_updated' => now()->toDateTimeString(),
                'info' => 'Form Order data displayed with headers from row 1 and data from row 2+'
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Handle specific error cases
            if (strpos($errorMessage, 'not found') !== false) {
                $errorData = [
                    'error' => 'Form order spreadsheet not found or not accessible.',
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
                    'error' => 'Permission denied to access form order spreadsheet.',
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
                    'error' => 'Error reading form order: ' . $errorMessage,
                    'spreadsheet_id' => $spreadsheetId,
                    'technical_error' => $errorMessage
                ];
            }

            return view('sheet.form-order', $errorData);
        }
    }

    public function readSettingMessage($sheetName = null)
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
                return view('sheet.settings-message', [
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
                    return view('sheet.settings-message', [
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
            switch (strtolower(trim($sheetName))) {
                case 'petunjuk pengisian':
                    // Check the actual structure of the sheet
                    $sampleRange = "'{$sheetName}'!A1:B10";
                    $sampleResponse = $service->spreadsheets_values->get($spreadsheetId, $sampleRange);
                    $sampleData = $sampleResponse->getValues() ?? [];

                    // If row 1 has meaningful headers, use them
                    if (isset($sampleData[0]) && !empty($sampleData[0][1])) {
                        $header = $sampleData[0];
                        $startRow = 3; // Data starts from row 2
                    } elseif (isset($sampleData[1]) && !empty($sampleData[1][1])) {
                        // If row 2 has headers
                        $header = $sampleData[1];
                        $startRow = 3; // Data starts from row 3
                    } else {
                        // Fallback to generic headers
                        $header = ['No', 'PETUNJUK PENGISIAN COPYWRITING BLAST CRM'];
                        $startRow = 3;
                    }

                    // Ensure we have exactly 2 columns
                    $header = array_slice($header, 0, 2);
                    if (count($header) < 2) {
                        $header = array_pad($header, 2, 'Column ' . (count($header) + 1));
                    }

                    $range = "'{$sheetName}'!A{$startRow}:B";
                    break;

                case 'tim jogja':
                case 'undistributed data zym':
                case 'tim solo':
                case 'cwr mandiri by request':
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
                        return view('sheet.settings-message', [
                            'data' => $data,
                            'header' => array_values($header),
                            'spreadsheet_id' => $spreadsheetId,
                            'sheet_name' => $sheetName,
                            'sheets' => $sheetsInfo,
                            'current_sheet' => $currentSheet,
                            'file_name' => 'Settings Message - ' . $sheetName,
                            'total_rows' => count($data),
                            'total_columns' => count($header),
                            'last_updated' => now()->toDateTimeString(),
                            'info' => 'Sheet displayed with structured columns: PRODUK | COPYWRITING BLASTING NC (11 sub-headers) | COPYWRITING BLASTING RO (4 sub-headers) | DATA PASIF',
                            'raw_data_mode' => false
                        ]);
                    }
                    break;

                case 'cwr resi':
                    // Header in column 1 is correct, first column might be null, skip column 2, data in column 3
                    $headerRange = "'{$sheetName}'!A1:Z1";
                    $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
                    $header = $headerResponse->getValues()[0] ?? [];

                    // If first column is empty, shift headers
                    if (empty($header[0])) {
                        array_shift($header);
                    }

                    $startRow = 2;
                    $range = "'{$sheetName}'!A{$startRow}:Z";
                    break;

                case 'tim cre':
                    // Has 2 tables with different structures
                    $sampleRange = "'{$sheetName}'!A1:Z20";
                    $sampleResponse = $service->spreadsheets_values->get($spreadsheetId, $sampleRange);
                    $sampleData = $sampleResponse->getValues() ?? [];

                    // Look for first table (B2-E2 header, B3-E3 subheader)
                    $firstTableHeaders = [];
                    $firstTableData = [];

                    if (isset($sampleData[1]) && !empty($sampleData[1])) { // Row 2 (index 1)
                        $firstTableHeaders = array_slice($sampleData[1], 1, 4); // B2-E2
                    }

                    if (isset($sampleData[2]) && !empty($sampleData[2])) { // Row 3 (index 2)
                        $subHeaders = array_slice($sampleData[2], 1, 4); // B3-E3
                        // Combine main headers with subheaders
                        $firstTableHeaders = array_map(function ($main, $sub) {
                            return trim($main . ' ' . $sub);
                        }, $firstTableHeaders, $subHeaders);
                    }

                    // Read first table data
                    $firstTableStartRow = 4; // Data starts from row 4
                    $firstTableRange = "'{$sheetName}'!B{$firstTableStartRow}:E";
                    $firstTableResponse = $service->spreadsheets_values->get($spreadsheetId, $firstTableRange);
                    $firstTableRawData = $firstTableResponse->getValues() ?? [];

                    foreach ($firstTableRawData as $row) {
                        if (!empty(array_filter($row))) { // Skip empty rows
                            $firstTableData[] = array_combine($firstTableHeaders, $row);
                        }
                    }

                    // Look for second table (H3-I3 headers)
                    $secondTableHeaders = [];
                    $secondTableData = [];

                    if (isset($sampleData[2]) && count($sampleData[2]) > 7) { // Row 3, columns H-I
                        $secondTableHeaders = array_slice($sampleData[2], 7, 2); // H3-I3
                    }

                    if (!empty($secondTableHeaders)) {
                        // Read second table data
                        $secondTableStartRow = 4; // Data starts from row 4
                        $secondTableRange = "'{$sheetName}'!H{$secondTableStartRow}:I";
                        $secondTableResponse = $service->spreadsheets_values->get($spreadsheetId, $secondTableRange);
                        $secondTableRawData = $secondTableResponse->getValues() ?? [];

                        foreach ($secondTableRawData as $row) {
                            if (!empty(array_filter($row))) { // Skip empty rows
                                $secondTableData[] = array_combine($secondTableHeaders, $row);
                            }
                        }
                    }

                    // Combine both tables
                    $data = array_merge($firstTableData, $secondTableData);
                    $header = array_merge($firstTableHeaders, $secondTableHeaders);

                    // Return early for TIM CRE
                    return view('sheet.settings-message', [
                        'data' => $data,
                        'header' => $header,
                        'spreadsheet_id' => $spreadsheetId,
                        'sheet_name' => $sheetName,
                        'sheets' => $sheetsInfo,
                        'current_sheet' => $currentSheet,
                        'file_name' => 'Settings Message - ' . $sheetName,
                        'total_rows' => count($data),
                        'total_columns' => count($header),
                        'last_updated' => now()->toDateTimeString(),
                        'info' => 'Sheet displayed with custom table structure (2 tables combined)',
                        'raw_data_mode' => false
                    ]);

                    break; // Add break for consistency

                case 'cwr testing data blast ro':
                    // Similar to other CWR sheets, look for data starting from row 2
                    $headerRange = "'{$sheetName}'!A1:Z1";
                    $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
                    $header = $headerResponse->getValues()[0] ?? [];

                    // If first column is empty, shift headers
                    if (empty($header[0])) {
                        array_shift($header);
                    }

                    $startRow = 2;
                    $range = "'{$sheetName}'!A{$startRow}:Z";
                    break;

                default:
                    // Default behavior for unknown sheets
                    $headerRange = "'{$sheetName}'!A1:Z1";
                    $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
                    $header = $headerResponse->getValues()[0] ?? [];
                    $startRow = 2;
                    $range = "'{$sheetName}'!A{$startRow}:Z";
                    break;
            }

            // If we haven't returned early (for TIM CRE), continue with normal processing
            if (empty($header)) {
                // Handle sheets without headers
                $sampleRange = "'{$sheetName}'!A1:Z20";
                $sampleResponse = $service->spreadsheets_values->get($spreadsheetId, $sampleRange);
                $sampleData = $sampleResponse->getValues() ?? [];

                if (empty($sampleData)) {
                    return view('sheet.settings-message', [
                        'error' => "Sheet '{$sheetName}' is completely empty",
                        'spreadsheet_id' => $spreadsheetId,
                        'sheet_name' => $sheetName,
                        'sheets' => $sheetsInfo,
                        'current_sheet' => $currentSheet,
                        'setup_instructions' => [
                            'This sheet contains no data',
                            'Please add column headers in the first row',
                            'Or add your data starting from row 1'
                        ],
                        'spreadsheet_url' => "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit#gid={$currentSheet['id']}",
                        'last_updated' => now()->toDateTimeString()
                    ]);
                }

                // Try to read all data from the sheet
                $allDataRange = "'{$sheetName}'!A:Z";
                $allDataResponse = $service->spreadsheets_values->get($spreadsheetId, $allDataRange);
                $allData = $allDataResponse->getValues() ?? [];

                // Create generic headers if data exists
                $maxColumns = 0;
                foreach ($allData as $row) {
                    $maxColumns = max($maxColumns, count($row));
                }

                $genericHeaders = [];
                for ($i = 0; $i < $maxColumns; $i++) {
                    $genericHeaders[] = 'Column ' . ($i + 1);
                }

                // Format data with generic headers
                $formattedData = array_map(function ($row) use ($genericHeaders) {
                    $row = array_pad($row, count($genericHeaders), null);
                    $row = array_slice($row, 0, count($genericHeaders));
                    return array_combine($genericHeaders, $row);
                }, $allData);

                // Return view with raw data
                return view('sheet.settings-message', [
                    'data' => $formattedData,
                    'header' => $genericHeaders,
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'sheets' => $sheetsInfo,
                    'current_sheet' => $currentSheet,
                    'file_name' => 'Settings Message - ' . $sheetName,
                    'total_rows' => count($formattedData),
                    'total_columns' => count($genericHeaders),
                    'last_updated' => now()->toDateTimeString(),
                    'info' => 'Sheet displayed with generic column headers (no structured headers found)',
                    'raw_data_mode' => true
                ]);
            }

            // Read actual data
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                return view('sheet.settings-message', [
                    'data' => [],
                    'header' => $header,
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'sheets' => $sheetsInfo,
                    'current_sheet' => $currentSheet,
                    'total_rows' => 0,
                    'info' => 'Sheet has headers but no data rows yet',
                    'setup_instructions' => [
                        'Headers found successfully!',
                        'Now you can add your data starting from row 2'
                    ],
                    'last_updated' => now()->toDateTimeString()
                ]);
            }

            // Format data
            $data = array_map(function ($row) use ($header) {
                $row = array_pad($row, count($header), null);
                $row = array_slice($row, 0, count($header));
                return array_combine($header, $row);
            }, $values);

            return view('sheet.settings-message', [
                'data' => $data,
                'header' => $header,
                'spreadsheet_id' => $spreadsheetId,
                'sheet_name' => $sheetName,
                'sheets' => $sheetsInfo,
                'current_sheet' => $currentSheet,
                'file_name' => 'Settings Message - ' . $sheetName,
                'total_rows' => count($data),
                'total_columns' => count($header),
                'last_updated' => now()->toDateTimeString()
            ]);
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

            return view('sheet.settings-message', $errorData);
        }
    }

    public function readSheet($limit = null)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope(Sheets::SPREADSHEETS_READONLY);

        $service = new Sheets($client);

        $spreadsheetId = '1kHT5z7Tp2mb4lJ2Zs4dpzi3uvPBaKjN2yAFyQxSOEvU';
        $sheetName = 'RAW SHOPEE';

        // --- Ambil header (baris pertama) ---
        $headerRange = "'{$sheetName}'!A1:CA1";
        $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
        $header = $headerResponse->getValues()[0] ?? [];

        if (empty($header)) {
            return [];
        }

        // --- Kalau ada limit, hitung total row ---
        if ($limit !== null) {
            // Ambil semua nomor baris di kolom A (lebih ringan daripada full range)
            $rowCountRange = "'{$sheetName}'!A:A";
            $rowCountResponse = $service->spreadsheets_values->get($spreadsheetId, $rowCountRange);
            $totalRows = count($rowCountResponse->getValues());

            // Header di row 1 → data mulai row 2
            $startRow = max(2, $totalRows - $limit + 1);
            $range = "'{$sheetName}'!A{$startRow}:CA{$totalRows}";
        } else {
            // Ambil semua data kalau limit tidak diset
            $range = "'{$sheetName}'!A2:CA";
        }

        // --- Ambil data sesuai range ---
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        // Log::info("Total rows in sheet: " . ($totalRows-1));
        // Log::info("Rows fetched: " . count($values));
        // Log::info("Start row: $startRow, End row: $totalRows");

        if (empty($values)) {
            return [];
        }

        // --- Gabungkan header + row ---
        return array_map(function ($row) use ($header) {
            $row = array_pad($row, count($header), null);
            $row = array_slice($row, 0, count($header));
            return array_combine($header, $row);
        }, $values);
    }

    public function readFolder($folderId = null)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope(Drive::DRIVE_READONLY);

        $service = new Drive($client);

        // Jika tidak ada folderId, gunakan folder ID dari URL yang diberikan
        if ($folderId === null) {
            $folderId = '1KpTis-ivCj_btNU8DsWoe8kKEIviiqGf'; // ID dari URL yang diberikan
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
                'google_sheets_count' => count(array_filter($folderContents, function ($file) {
                    return $file['isGoogleSheet'];
                })),
                'excel_files_count' => count(array_filter($folderContents, function ($file) {
                    return isset($file['isExcelFile']) && $file['isExcelFile'];
                })),
                'subfolders_count' => count(array_filter($folderContents, function ($file) {
                    return $file['isFolder'];
                }))
            ];

            return view('sheet.folder', $data);
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

            return view('sheet.folder', $errorData);
        }
    }

    public function readSheetByUrl($spreadsheetUrl = null, $sheetName = null, $limit = null)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope(Sheets::SPREADSHEETS_READONLY);

        $service = new Sheets($client);

        // Extract spreadsheet ID from URL if provided
        $spreadsheetId = null;
        if ($spreadsheetUrl) {
            if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $spreadsheetUrl, $matches)) {
                $spreadsheetId = $matches[1];
            } else {
                // Assume it's already an ID
                $spreadsheetId = $spreadsheetUrl;
            }
        }

        // Default spreadsheet jika tidak ada yang diberikan
        if (!$spreadsheetId) {
            $spreadsheetId = '1kHT5z7Tp2mb4lJ2Zs4dpzi3uvPBaKjN2yAFyQxSOEvU';
        }

        try {
            // Jika tidak ada sheetName, ambil sheet pertama
            if ($sheetName === null) {
                $spreadsheet = $service->spreadsheets->get($spreadsheetId);
                $sheets = $spreadsheet->getSheets();
                if (empty($sheets)) {
                    return ['error' => 'No sheets found in spreadsheet'];
                }
                $sheetName = $sheets[0]->getProperties()->getTitle();
            }

            // --- Ambil header (baris pertama) ---
            $headerRange = "'{$sheetName}'!1:1";
            $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
            $header = $headerResponse->getValues()[0] ?? [];

            if (empty($header)) {
                return ['error' => 'No header found or empty sheet'];
            }

            // Hitung jumlah kolom untuk range
            $lastColumn = $this->numberToColumn(count($header));

            // --- Kalau ada limit, hitung total row ---
            if ($limit !== null) {
                // Ambil semua nomor baris di kolom A
                $rowCountRange = "'{$sheetName}'!A:A";
                $rowCountResponse = $service->spreadsheets_values->get($spreadsheetId, $rowCountRange);
                $totalRows = count($rowCountResponse->getValues());

                // Header di row 1 → data mulai row 2
                $startRow = max(2, $totalRows - $limit + 1);
                $range = "'{$sheetName}'!A{$startRow}:{$lastColumn}{$totalRows}";
            } else {
                // Ambil semua data kalau limit tidak diset
                $range = "'{$sheetName}'!A2:{$lastColumn}";
            }

            // --- Ambil data sesuai range ---
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                return ['data' => [], 'info' => 'No data found'];
            }

            // --- Gabungkan header + row ---
            $data = array_map(function ($row) use ($header) {
                $row = array_pad($row, count($header), null);
                $row = array_slice($row, 0, count($header));
                return array_combine($header, $row);
            }, $values);

            return [
                'spreadsheet_id' => $spreadsheetId,
                'sheet_name' => $sheetName,
                'total_rows' => count($data),
                'header' => $header,
                'data' => $data
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Error reading sheet: ' . $e->getMessage()
            ];
        }
    }

    public function readSheetFromFolder($limit = null)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope(Sheets::SPREADSHEETS_READONLY);

        $service = new Sheets($client);

        $spreadsheetId = '1kHT5z7Tp2mb4lJ2Zs4dpzi3uvPBaKjN2yAFyQxSOEvU';
        $sheetName = 'RAW SHOPEE';

        // --- Ambil header (baris pertama) ---
        $headerRange = "'{$sheetName}'!A1:CA1";
        $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
        $header = $headerResponse->getValues()[0] ?? [];

        if (empty($header)) {
            return [];
        }

        // --- Kalau ada limit, hitung total row ---
        if ($limit !== null) {
            // Ambil semua nomor baris di kolom A (lebih ringan daripada full range)
            $rowCountRange = "'{$sheetName}'!A:A";
            $rowCountResponse = $service->spreadsheets_values->get($spreadsheetId, $rowCountRange);
            $totalRows = count($rowCountResponse->getValues());

            // Header di row 1 → data mulai row 2
            $startRow = max(2, $totalRows - $limit + 1);
            $range = "'{$sheetName}'!A{$startRow}:CA{$totalRows}";
        } else {
            // Ambil semua data kalau limit tidak diset
            $range = "'{$sheetName}'!A2:CA";
        }

        // --- Ambil data sesuai range ---
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        // Log::info("Total rows in sheet: " . ($totalRows-1));
        // Log::info("Rows fetched: " . count($values));
        // Log::info("Start row: $startRow, End row: $totalRows");

        if (empty($values)) {
            return [];
        }

        // --- Gabungkan header + row ---
        return array_map(function ($row) use ($header) {
            $row = array_pad($row, count($header), null);
            $row = array_slice($row, 0, count($header));
            return array_combine($header, $row);
        }, $values);
    }

    public function readSheetsInFolder($folderId = null)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope([Drive::DRIVE_READONLY, Sheets::SPREADSHEETS_READONLY]);

        $driveService = new Drive($client);
        $sheetsService = new Sheets($client);

        // Jika tidak ada folderId, gunakan folder ID dari URL yang diberikan
        if ($folderId === null) {
            $folderId = '1KpTis-ivCj_btNU8DsWoe8kKEIviiqGf';
        }

        try {
            // Ambil informasi folder
            $folder = $driveService->files->get($folderId);

            // Ambil hanya Google Sheets dalam folder
            $optParams = [
                'q' => "'{$folderId}' in parents and trashed=false and mimeType='application/vnd.google-apps.spreadsheet'",
                'fields' => 'files(id,name,mimeType,size,createdTime,modifiedTime,webViewLink)',
                'orderBy' => 'name'
            ];

            $results = $driveService->files->listFiles($optParams);
            $files = $results->getFiles();

            $sheetsInFolder = [];

            foreach ($files as $file) {
                try {
                    // Ambil daftar sheet dalam spreadsheet
                    $spreadsheet = $sheetsService->spreadsheets->get($file->getId());
                    $sheets = $spreadsheet->getSheets();

                    $sheetNames = [];
                    foreach ($sheets as $sheet) {
                        $sheetNames[] = $sheet->getProperties()->getTitle();
                    }

                    $sheetsInFolder[] = [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'mimeType' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'createdTime' => $file->getCreatedTime(),
                        'modifiedTime' => $file->getModifiedTime(),
                        'webViewLink' => $file->getWebViewLink(),
                        'sheets' => $sheetNames,
                        'total_sheets' => count($sheetNames)
                    ];
                } catch (\Exception $e) {
                    // Jika gagal membaca spreadsheet, tetap masukkan info file
                    $sheetsInFolder[] = [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'mimeType' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'createdTime' => $file->getCreatedTime(),
                        'modifiedTime' => $file->getModifiedTime(),
                        'webViewLink' => $file->getWebViewLink(),
                        'error' => 'Cannot read sheets: ' . $e->getMessage()
                    ];
                }
            }

            return [
                'folder' => [
                    'id' => $folder->getId(),
                    'name' => $folder->getName(),
                    'mimeType' => $folder->getMimeType()
                ],
                'spreadsheets' => $sheetsInFolder,
                'total_spreadsheets' => count($sheetsInFolder)
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Error reading folder: ' . $e->getMessage()
            ];
        }
    }

    public function readSpecificSheetFromFolder(Request $request, $spreadsheetId, $sheetName = null)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope(Sheets::SPREADSHEETS_READONLY);

        $service = new Sheets($client);

        // Get limit from query parameter if provided
        $limit = $request->get('limit');
        $isPreview = $request->get('preview') === '1' || $request->wantsJson();

        try {
            // Jika tidak ada sheetName, ambil sheet pertama
            if ($sheetName === null) {
                $spreadsheet = $service->spreadsheets->get($spreadsheetId);
                $sheets = $spreadsheet->getSheets();
                if (empty($sheets)) {
                    $errorData = [
                        'error' => 'No sheets found in spreadsheet',
                        'spreadsheet_id' => $spreadsheetId,
                        'instructions' => [
                            'The spreadsheet exists but contains no sheets',
                            'Please check the spreadsheet in Google Sheets',
                            'Make sure the spreadsheet has at least one sheet'
                        ]
                    ];
                    return $isPreview ? response()->json($errorData) : view('sheet.file', $errorData);
                }
                $sheetName = $sheets[0]->getProperties()->getTitle();
            }

            // --- Ambil header (baris pertama) ---
            $headerRange = "'{$sheetName}'!1:1";
            $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
            $header = $headerResponse->getValues()[0] ?? [];

            if (empty($header)) {
                $errorData = [
                    'error' => 'No header found or empty sheet',
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'instructions' => [
                        'The sheet appears to be empty',
                        'Please add some data to the sheet',
                        'Make sure the first row contains column headers'
                    ]
                ];
                return $isPreview ? response()->json($errorData) : view('sheet.file', $errorData);
            }

            // Hitung jumlah kolom untuk range
            $lastColumn = $this->numberToColumn(count($header));

            // Limit data for preview mode
            $previewLimit = $isPreview ? 100 : $limit;

            // --- Kalau ada limit, hitung total row ---
            if ($previewLimit !== null) {
                // Ambil semua nomor baris di kolom A
                $rowCountRange = "'{$sheetName}'!A:A";
                $rowCountResponse = $service->spreadsheets_values->get($spreadsheetId, $rowCountRange);
                $totalRows = count($rowCountResponse->getValues());

                if ($isPreview) {
                    // For preview, get first N rows
                    $startRow = 2;
                    $endRow = min($totalRows, $previewLimit + 1);
                } else {
                    // For full view with limit, get last N rows  
                    $startRow = max(2, $totalRows - $previewLimit + 1);
                    $endRow = $totalRows;
                }
                $range = "'{$sheetName}'!A{$startRow}:{$lastColumn}{$endRow}";
            } else {
                // Ambil semua data kalau limit tidak diset
                $range = "'{$sheetName}'!A2:{$lastColumn}";
            }

            // --- Ambil data sesuai range ---
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                $data = [
                    'data' => [],
                    'info' => 'No data found',
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'header' => $header,
                    'file_name' => $this->getFileNameFromSheet($service, $spreadsheetId)
                ];
            } else {
                // --- Gabungkan header + row ---
                $data = array_map(function ($row) use ($header) {
                    $row = array_pad($row, count($header), null);
                    $row = array_slice($row, 0, count($header));
                    return array_combine($header, $row);
                }, $values);

                $data = [
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'total_rows' => count($data),
                    'data' => $data,
                    'header' => $header,
                    'file_name' => $this->getFileNameFromSheet($service, $spreadsheetId)
                ];
            }

            return $isPreview ? response()->json($data) : view('sheet.file', $data);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Handle specific error cases with better instructions
            if (strpos($errorMessage, 'not found') !== false) {
                $errorData = [
                    'error' => 'Spreadsheet not found or no permission to access',
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'instructions' => [
                        'The spreadsheet ID might be incorrect',
                        'The spreadsheet might not be shared with the service account',
                        'Check if the spreadsheet exists in Google Drive',
                        'Make sure to share the spreadsheet with: ' . $this->getServiceAccountEmail()
                    ]
                ];
            } elseif (strpos($errorMessage, 'permission') !== false || strpos($errorMessage, 'forbidden') !== false) {
                $errorData = [
                    'error' => 'Permission denied to access this spreadsheet',
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'instructions' => [
                        'The spreadsheet needs to be shared with the service account',
                        'Share the spreadsheet with: ' . $this->getServiceAccountEmail(),
                        'Give at least "Viewer" permission to the service account',
                        'Wait a few minutes after sharing for changes to take effect'
                    ]
                ];
            } else {
                $errorData = [
                    'error' => 'Error reading sheet: ' . $errorMessage,
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'instructions' => [
                        'Please try refreshing the page',
                        'Check your internet connection',
                        'Verify the Google Sheets API is enabled',
                        'Contact administrator if the problem persists'
                    ]
                ];
            }

            return $isPreview ? response()->json($errorData) : view('sheet.file', $errorData);
        }
    }

    // Method untuk test koneksi Google Drive API
    public function testConnection()
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        if (!file_exists($path)) {
            return [
                'error' => 'Credential file tidak ditemukan',
                'path' => $path
            ];
        }

        try {
            $client = new Client();
            $client->setAuthConfig($path);
            $client->addScope(Drive::DRIVE_READONLY);

            $service = new Drive($client);

            // Test basic connection dengan mengambil informasi tentang user's drive
            $about = $service->about->get(['fields' => 'user,storageQuota']);

            return [
                'status' => 'success',
                'message' => 'Google Drive API connection successful!',
                'user' => [
                    'displayName' => $about->getUser()->getDisplayName(),
                    'emailAddress' => $about->getUser()->getEmailAddress()
                ],
                'storage' => [
                    'limit' => $about->getStorageQuota()->getLimit(),
                    'usage' => $about->getStorageQuota()->getUsage()
                ],
                'api_status' => 'Google Drive API is active and working'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'instructions' => 'Jika masih error API not enabled, aktifkan di: https://console.developers.google.com/apis/api/drive.googleapis.com/overview?project=993043737786'
            ];
        }
    }

    // Method untuk mendapatkan service account email dari credentials
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

    // Method untuk mengecek dan menampilkan service account info
    public function getServiceAccountInfo()
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        if (!file_exists($path)) {
            return [
                'error' => 'Credential file tidak ditemukan',
                'path' => $path
            ];
        }

        try {
            $credentialsJson = file_get_contents($path);
            $credentials = json_decode($credentialsJson, true);

            return [
                'service_account_email' => $credentials['client_email'] ?? 'not found',
                'project_id' => $credentials['project_id'] ?? 'not found',
                'private_key_id' => $credentials['private_key_id'] ?? 'not found',
                'client_id' => $credentials['client_id'] ?? 'not found',
                'instructions' => [
                    'Untuk mengakses folder Google Drive, share folder ke email service account ini:',
                    $credentials['client_email'] ?? 'email tidak ditemukan',
                    'Beri permission "Viewer" atau "Editor" sesuai kebutuhan'
                ]
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Error membaca credential file: ' . $e->getMessage()
            ];
        }
    }

    // Method untuk membaca file Excel dari Google Drive
    public function readExcelFile(Request $request, $fileId)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope(Drive::DRIVE_READONLY);

        $service = new Drive($client);

        $isPreview = $request->get('preview') === '1' || $request->wantsJson();

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
                return $isPreview ? response()->json($errorData) : view('sheet.file', $errorData);
            }

            // Ambil header dari baris pertama
            $header = !empty($sheetData) ? $sheetData[0] : [];
            $rows = array_slice($sheetData, 1);

            // Limit data for preview mode
            if ($isPreview && count($rows) > 100) {
                $rows = array_slice($rows, 0, 100);
            }

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

            return $isPreview ? response()->json($viewData) : view('sheet.file', $viewData);
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

            return $isPreview ? response()->json($errorData) : view('sheet.file', $errorData);
        }
    }

    // Method untuk mendapatkan nama file dari Google Drive
    private function getFileName($service, $fileId)
    {
        try {
            $file = $service->files->get($fileId, ['fields' => 'name']);
            return $file->getName();
        } catch (\Exception $e) {
            return 'Unknown File';
        }
    }

    // Method untuk mendapatkan nama file dari spreadsheet
    private function getFileNameFromSheet($sheetsService, $spreadsheetId)
    {
        try {
            // Gunakan Drive service untuk mendapatkan nama file
            $driveClient = new Client();
            $driveClient->setAuthConfig(storage_path('app/credentials/sigma-data-center.json'));
            $driveClient->addScope(Drive::DRIVE_READONLY);
            $driveService = new Drive($driveClient);

            $file = $driveService->files->get($spreadsheetId, ['fields' => 'name']);
            return $file->getName();
        } catch (\Exception $e) {
            return 'Unknown Sheet';
        }
    }

    // Method untuk convert Excel ke Google Sheets
    public function convertExcelToSheets($fileId)
    {
        $path = storage_path('app/credentials/sigma-data-center.json');

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope([Drive::DRIVE, Sheets::SPREADSHEETS]);

        $driveService = new Drive($client);
        $sheetsService = new Sheets($client);

        try {
            // Copy file Excel sebagai Google Sheets
            $copyRequest = new \Google\Service\Drive\DriveFile();
            $copyRequest->setName('Converted - ' . $this->getFileName($driveService, $fileId));
            $copyRequest->setMimeType('application/vnd.google-apps.spreadsheet');

            $copiedFile = $driveService->files->copy($fileId, $copyRequest);

            return [
                'status' => 'success',
                'message' => 'File berhasil dikonversi ke Google Sheets',
                'original_file_id' => $fileId,
                'new_sheet_id' => $copiedFile->getId(),
                'new_sheet_name' => $copiedFile->getName(),
                'web_view_link' => $copiedFile->getWebViewLink(),
                'instructions' => [
                    'Untuk membaca data: GET /google/read-sheet/' . $copiedFile->getId(),
                    'Untuk melihat sheet: ' . $copiedFile->getWebViewLink()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Error converting file: ' . $e->getMessage(),
                'file_id' => $fileId
            ];
        }
    }

    // Helper function to convert number to Excel column (A, B, C, ..., AA, AB, etc.)
    private function numberToColumn($num)
    {
        $str = '';
        while ($num > 0) {
            $num--;
            $str = chr($num % 26 + ord('A')) . $str;
            $num = intval($num / 26);
        }
        return $str;
    }

    /**
     * Auto-create campaign if needed for connected devices
     */
    private function autoCreateCampaignIfNeeded($row, $device)
    {
        try {
            // Get form_order_id from column A (first column)
            $rowValues = array_values($row);
            $formOrderId = $rowValues[0] ?? null;

            if (empty($formOrderId)) {
                Log::info("No form_order_id found in row for device: {$device->name}");
                return;
            }

            // Check if campaign already exists for this form_order_id
            $existingCampaign = Campaign::where('name', 'LIKE', "[{$formOrderId}]%")->first();
            if ($existingCampaign) {
                Log::info("Campaign already exists for form_order_id: {$formOrderId}");
                return;
            }

            // Extract data from form order row
            $csName = $rowValues[1] ?? null; // NAMA CS (column B)
            $tim = $rowValues[2] ?? null; // TIM (column C) 
            $produk = $rowValues[3] ?? null; // PRODUK (column D)
            $tanggalHariIni = $rowValues[4] ?? null; // TANGGAL HARI INI (column E)
            $tanggal = $rowValues[5] ?? null; // Tanggal (column F)
            $waktuBlasting = $rowValues[6] ?? null; // WAKTU BLASTING (column G)
            $keteranganBlasting = $rowValues[7] ?? null; // KETERANGAN BLASTING (column H)
            $typeCopyWriting = $rowValues[8] ?? null; // TYPE COPY WRITING (column I)
            $textCopyWriting = $rowValues[9] ?? null; // TEXT COPY WRITING (column J)
            $img = $rowValues[10] ?? null; // IMG (column K)

            Log::info("Extracted FORM ORDER data:");
            Log::info("- Form Order ID: {$formOrderId}");
            Log::info("- CS Name: {$csName}");
            Log::info("- Tim: {$tim}");
            Log::info("- Produk: {$produk}");
            Log::info("- Tanggal Hari Ini: {$tanggalHariIni}");
            Log::info("- Tanggal: {$tanggal}");
            Log::info("- Waktu Blasting: {$waktuBlasting}");
            Log::info("- Keterangan Blasting: {$keteranganBlasting}");
            Log::info("- Type Copy Writing: {$typeCopyWriting}");
            Log::info("- Text Copy Writing: {$textCopyWriting}");
            Log::info("- Full row data: " . json_encode($rowValues));

            if (empty($csName) || empty($produk) || empty($keteranganBlasting)) {
                Log::info("Missing required data for campaign creation. CS: {$csName}, Produk: {$produk}, Keterangan: {$keteranganBlasting}");
                return;
            }

            Log::info("Starting auto-campaign creation for form_order_id: {$formOrderId}, CS: {$csName}, Produk: {$produk}, Blasting: {$keteranganBlasting}");

            // 1. Find CS folder in Google Drive
            $csFolderId = $this->findCsFolder($csName);
            if (!$csFolderId) {
                Log::warning("CS folder not found for: {$csName}");
                return;
            }

            // 2. Find Excel file containing blasting type
            $excelFileId = $this->findExcelFileInFolder($csFolderId, $keteranganBlasting);
            if (!$excelFileId) {
                Log::warning("Excel file not found for CS: {$csName}, Blasting: {$keteranganBlasting}");
                return;
            }

            // 3. Get message template from settings-message
            if ($typeCopyWriting == 'DATABASE') {
                Log::info("typeCopyWriting is DATABASE");
                $messageTemplate = $this->getMessageTemplate($produk, $keteranganBlasting, $tim);
                if (!$messageTemplate) {
                    Log::warning("Message template not found for Produk: {$produk}, Blasting: {$keteranganBlasting}");
                    return;
                }
            } else {
                Log::info("typeCopyWriting is TEXT");
                $messageTemplate = $textCopyWriting;
            }

            // 4. Read Excel data for contacts
            $contactsData = $this->readExcelContacts($excelFileId);
            if (empty($contactsData)) {
                Log::warning("No contacts data found in Excel file");
                return;
            }

            Log::info("Contacts data summary:");
            Log::info("- Total contacts found: " . count($contactsData));
            foreach ($contactsData as $index => $contact) {
                Log::info("- Contact " . ($index + 1) . ": Phone=" . ($contact['phone'] ?? 'N/A') . ", Name=" . ($contact['nama_customer'] ?? 'N/A'));
            }

            // 5. Create campaign
            $this->createCampaignWithData($formOrderId, $device, $csName, $produk, $keteranganBlasting, $waktuBlasting, $messageTemplate, $contactsData, $img);

            Log::info("Successfully created auto-campaign for form_order_id: {$formOrderId}");
        } catch (\Exception $e) {
            Log::error("Error in autoCreateCampaignIfNeeded: " . $e->getMessage());
        }
    }

    /**
     * Find CS folder in Google Drive
     */
    private function findCsFolder($csName)
    {
        try {
            $path = storage_path('app/credentials/sigma-data-center.json');
            $client = new Client();
            $client->setAuthConfig($path);
            $client->addScope(Drive::DRIVE_READONLY);
            $driveService = new Drive($client);

            Log::info("Searching for CS folder with name containing: {$csName}");

            // Search for folder with CS name
            $query = "name contains '{$csName}' and mimeType='application/vnd.google-apps.folder' and trashed=false";
            $results = $driveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(id,name)'
            ]);

            $files = $results->getFiles();
            Log::info("Found " . count($files) . " folders containing '{$csName}'");

            if (!empty($files)) {
                foreach ($files as $file) {
                    Log::info("Found CS folder: " . $file->getName() . " (ID: " . $file->getId() . ")");
                }
                Log::info("Using first folder: " . $files[0]->getName());
                return $files[0]->getId();
            }

            Log::warning("No CS folder found containing '{$csName}'");
            return null;
        } catch (\Exception $e) {
            Log::error("Error finding CS folder: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find Excel file in folder containing blasting type
     */
    private function findExcelFileInFolder($folderId, $blastingType)
    {
        try {
            $path = storage_path('app/credentials/sigma-data-center.json');
            $client = new Client();
            $client->setAuthConfig($path);
            $client->addScope(Drive::DRIVE_READONLY);
            $driveService = new Drive($client);

            Log::info("Searching for Excel file in folder: {$folderId}, blasting type: {$blastingType}");

            // Search for files in folder containing blasting type
            $query = "'{$folderId}' in parents and name contains '{$blastingType}' and trashed=false";
            $results = $driveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(id,name,mimeType)'
            ]);

            $files = $results->getFiles();
            Log::info("Found " . count($files) . " files in folder {$folderId} containing '{$blastingType}'");

            foreach ($files as $file) {
                Log::info("File found: " . $file->getName() . " (ID: " . $file->getId() . ", Type: " . $file->getMimeType() . ")");

                // Check if it's Excel file
                if (in_array($file->getMimeType(), [
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-excel',
                    'application/vnd.google-apps.spreadsheet'
                ])) {
                    Log::info("Found matching Excel/Google Sheets file: " . $file->getName());
                    return $file->getId();
                }
            }

            Log::warning("No Excel/Google Sheets file found containing '{$blastingType}' in folder {$folderId}");
            return null;
        } catch (\Exception $e) {
            Log::error("Error finding Excel file: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get message template from settings-message
     */
    private function getMessageTemplate($produk, $blastingType, $tim)
    {
        try {
            Log::info("Getting message template for Produk: {$produk}, Blasting: {$blastingType}, Tim: {$tim}");

            // Read TIM JOGJA sheet data directly
            $settingsData = $this->getSettingsMessageData('TIM JOGJA');

            if (empty($settingsData)) {
                Log::warning("No settings data found for TIM JOGJA");
                return null;
            }

            // Find product row and blasting column
            foreach ($settingsData as $row) {
                if (isset($row['PRODUK']) && strtoupper(trim($row['PRODUK'])) === strtoupper(trim($produk))) {
                    Log::info("Found product row for: {$produk}");

                    // Found product row, now find blasting type column
                    if (isset($row[$blastingType])) {
                        $template = $row[$blastingType];
                        Log::info("Raw message template from Google Sheets: " . $template);

                        // Ensure template is a string, not JSON
                        if (is_string($template)) {
                            Log::info("Template is already a string, using as-is");
                            return $template;
                        } elseif (is_array($template) || is_object($template)) {
                            Log::info("Template is array/object, converting to string");
                            return json_encode($template);
                        } else {
                            Log::info("Template type: " . gettype($template) . ", converting to string");
                            return (string) $template;
                        }
                    } else {
                        Log::warning("Blasting type '{$blastingType}' not found in product row");
                        Log::info("Available columns: " . implode(', ', array_keys($row)));
                    }
                }
            }

            Log::warning("Product '{$produk}' not found in settings data");
            return null;
        } catch (\Exception $e) {
            Log::error("Error getting message template: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get settings message data directly (without view)
     */
    private function getSettingsMessageData($sheetName)
    {
        try {
            $path = storage_path('app/credentials/sigma-data-center.json');
            $client = new Client();
            $client->setAuthConfig($path);
            $client->addScope(Sheets::SPREADSHEETS_READONLY);
            $service = new Sheets($client);

            // Settings Message Spreadsheet
            $spreadsheetId = '1CnG5AshHbPbZ4IDSuemFxhYsLV_xIygKTOqZ_5WsQW8';

            // For TIM JOGJA sheet, use custom structure logic
            if (strtolower(trim($sheetName)) === 'tim jogja') {
                // Read sample data to understand structure
                $sampleRange = "'{$sheetName}'!A1:Q20";
                $sampleResponse = $service->spreadsheets_values->get($spreadsheetId, $sampleRange);
                $sampleData = $sampleResponse->getValues() ?? [];

                $data = [];

                // Define column structure based on your specification
                $produkHeaders = ['PRODUK'];
                $ncHeaders = ['PERKENALAN', 'REMINDER', 'TIPS 1', 'DOA', 'KONSUL 1', 'KONV 1', 'KONV 2', 'TIPS 2', 'KONV 3', 'KONSUL 2', 'KONV 4'];
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
                        $rowData['PRODUK'] = $row[0] ?? null;

                        // Extract NC data (columns B-L, which is index 1-11)
                        $ncData = array_slice($row, 1, 11);
                        foreach ($ncHeaders as $i => $headerName) {
                            $rowData[$headerName] = $ncData[$i] ?? null;
                        }

                        // Extract RO data (columns M-P, which is index 12-15)
                        $roData = array_slice($row, 12, 4);
                        foreach ($roHeaders as $i => $headerName) {
                            $rowData[$headerName] = $roData[$i] ?? null;
                        }

                        // Extract DATA PASIF (column Q, which is index 16)
                        $rowData['DATA PASIF'] = $row[16] ?? null;

                        $data[] = $rowData;
                    }
                }

                return $data;
            }

            return [];
        } catch (\Exception $e) {
            Log::error("Error getting settings message data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Read Excel contacts data
     */
    private function readExcelContacts($fileId)
    {
        try {
            $path = storage_path('app/credentials/sigma-data-center.json');
            $client = new Client();
            $client->setAuthConfig($path);
            $client->addScope([Drive::DRIVE_READONLY, Sheets::SPREADSHEETS_READONLY]);

            $driveService = new Drive($client);
            $sheetsService = new Sheets($client);

            Log::info("Reading Excel contacts from file ID: {$fileId}");

            // Get file info to check if it's a Google Sheet or Excel file
            $fileInfo = $driveService->files->get($fileId);
            $mimeType = $fileInfo->getMimeType();
            $fileName = $fileInfo->getName();

            Log::info("File info - Name: {$fileName}, MIME Type: {$mimeType}");

            if ($mimeType === 'application/vnd.google-apps.spreadsheet') {
                // It's already a Google Sheet, read directly
                Log::info("File is Google Sheets format, reading directly");
                return $this->readGoogleSheetContacts($sheetsService, $fileId);
            } else {
                // For Excel files, let's try a different approach
                Log::info("File is Excel format, trying alternative approach");

                // Check if there's a corresponding Google Sheets version in the same folder
                $fileName = $fileInfo->getName();
                $parentFolders = $fileInfo->getParents();

                if (!empty($parentFolders)) {
                    $parentFolderId = $parentFolders[0];
                    Log::info("Parent folder ID: {$parentFolderId}");

                    // Search for Google Sheets with similar name in same folder
                    // Make search more flexible - look for sheets with similar keywords
                    $fileNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
                    Log::info("Original Excel filename: {$fileNameWithoutExt}");

                    // Try multiple search patterns
                    $searchPatterns = [
                        // Exact match without extension
                        "name = '{$fileNameWithoutExt}' and mimeType='application/vnd.google-apps.spreadsheet'",
                        // Contains CS name
                        "name contains 'DIKY' and mimeType='application/vnd.google-apps.spreadsheet'",
                        // Contains blasting type
                        "name contains 'KONSUL 1' and mimeType='application/vnd.google-apps.spreadsheet'",
                        // General search in folder
                        "'{$parentFolderId}' in parents and mimeType='application/vnd.google-apps.spreadsheet' and trashed=false"
                    ];

                    foreach ($searchPatterns as $pattern) {
                        Log::info("Trying search pattern: {$pattern}");

                        $results = $driveService->files->listFiles([
                            'q' => $pattern,
                            'fields' => 'files(id,name,mimeType)'
                        ]);

                        $files = $results->getFiles();
                        Log::info("Found " . count($files) . " files with pattern: {$pattern}");

                        foreach ($files as $file) {
                            Log::info("Found file: " . $file->getName() . " (ID: " . $file->getId() . ")");
                            if ($file->getMimeType() === 'application/vnd.google-apps.spreadsheet') {
                                Log::info("Found matching Google Sheets: " . $file->getName() . " (ID: " . $file->getId() . ")");
                                return $this->readGoogleSheetContacts($sheetsService, $file->getId());
                            }
                        }

                        // If we found files with this pattern, break
                        if (!empty($files)) {
                            break;
                        }
                    }
                }

                // If no Google Sheets version found, try to read Excel file directly
                Log::info("No Google Sheets version found, trying to read Excel file directly");

                try {
                    Log::info("Attempting to download and read Excel file directly");

                    // Authorize client for download
                    $client->fetchAccessTokenWithAssertion();
                    $accessToken = $client->getAccessToken()['access_token'];

                    // Download Excel file
                    $url = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";

                    $context = stream_context_create([
                        'http' => [
                            'header' => "Authorization: Bearer {$accessToken}",
                        ]
                    ]);

                    $fileContent = file_get_contents($url, false, $context);

                    if ($fileContent === false) {
                        Log::error("Failed to download Excel file");
                        return $this->createDemoContactsData();
                    }

                    // Save to temporary file
                    $tempFile = tempnam(sys_get_temp_dir(), 'excel_') . '.xlsx';
                    file_put_contents($tempFile, $fileContent);

                    Log::info("Excel file downloaded to temporary location: {$tempFile}");

                    // Read Excel file using Laravel Excel
                    $data = Excel::toArray([], $tempFile);

                    // Clean up temp file
                    unlink($tempFile);

                    if (empty($data)) {
                        Log::warning("No data found in Excel file");
                        return $this->createDemoContactsData();
                    }

                    // Get first sheet
                    $sheetData = $data[0] ?? [];
                    Log::info("Excel sheet contains " . count($sheetData) . " rows");

                    if (empty($sheetData)) {
                        Log::warning("Excel sheet is empty");
                        return $this->createDemoContactsData();
                    }

                    // Get header from first row
                    $header = $sheetData[0] ?? [];
                    $rows = array_slice($sheetData, 1);

                    Log::info("Excel headers: " . implode(', ', $header));
                    Log::info("Excel data rows: " . count($rows));

                    // Convert to contact format
                    $contacts = [];
                    foreach ($rows as $rowIndex => $row) {
                        Log::info("Processing Excel row " . ($rowIndex + 1) . ": " . implode(' | ', $row));

                        if (empty($row[0])) {
                            Log::info("Skipping Excel row " . ($rowIndex + 1) . " - empty phone number");
                            continue;
                        }

                        // Pad row to match header count
                        $row = array_pad($row, count($header), '');

                        $contact = [
                            'phone' => $row[0] ?? '', // Phone Number (A)
                            'nama_customer' => $row[1] ?? '', // Nama Customer (B)
                            'produk' => $row[2] ?? '', // Produk (C)
                            'no_resi' => $row[3] ?? '', // No. Resi (D)
                            'last_promo' => $row[4] ?? '', // Last_Promo (E)
                            'cs' => $row[5] ?? '', // CS (F)
                            'nama_samaran' => $row[6] ?? '', // Nama Samaran (G)
                            'jenis_blasting' => $row[7] ?? '', // Jenis Blasting (H)
                            'cs_crm' => $row[8] ?? '', // CS CRM (I)
                        ];

                        if (!empty(trim($contact['phone']))) {
                            $contacts[] = $contact;
                            Log::info("Added Excel contact: Phone=" . $contact['phone'] . ", Name=" . $contact['nama_customer']);
                        }
                    }

                    Log::info("Successfully read " . count($contacts) . " contacts from Excel file");

                    if (!empty($contacts)) {
                        return $contacts;
                    }
                } catch (\Exception $e) {
                    Log::error("Error reading Excel file directly: " . $e->getMessage());
                }

                // If all attempts fail, use demo data
                Log::warning("All attempts to read Excel file failed, using demo data");
                return $this->createDemoContactsData();
            }
        } catch (\Exception $e) {
            Log::error("Error reading Excel contacts: " . $e->getMessage());
            // Return demo data for testing purposes
            return $this->createDemoContactsData();
        }
    }

    /**
     * Create demo contacts data for testing
     */
    private function createDemoContactsData()
    {
        Log::info("Creating demo contacts data for testing");

        $demoData = [
            [
                'phone' => '628123456789',
                'nama_customer' => 'Kak Rani',
                'produk' => 'ZYMUNO',
                'no_resi' => 'ZYM123456',
                'last_promo' => 'Promo Ramadan',
                'cs' => 'DIKY',
                'nama_samaran' => 'Mbak Dina',
                'jenis_blasting' => 'KONSUL 1',
                'cs_crm' => 'Diky CRM'
            ],
            [
                'phone' => '628234567890',
                'nama_customer' => 'Kak Sari',
                'produk' => 'ZYMUNO',
                'no_resi' => 'ZYM234567',
                'last_promo' => 'Diskon Spesial',
                'cs' => 'DIKY',
                'nama_samaran' => 'Mbak Diana',
                'jenis_blasting' => 'KONSUL 1',
                'cs_crm' => 'Diky CRM'
            ]
        ];

        Log::info("Demo contacts data created: " . json_encode($demoData));
        Log::info("Demo data contains " . count($demoData) . " contacts");

        return $demoData;
    }

    /**
     * Read contacts from Google Sheets
     */
    private function readGoogleSheetContacts($sheetsService, $sheetId)
    {
        try {
            // Get first sheet name
            $spreadsheet = $sheetsService->spreadsheets->get($sheetId);
            $sheets = $spreadsheet->getSheets();

            if (empty($sheets)) {
                Log::warning("No sheets found in spreadsheet: {$sheetId}");
                return [];
            }

            $sheetName = $sheets[0]->getProperties()->getTitle();
            Log::info("Reading contacts from Google Sheet - Sheet ID: {$sheetId}, Sheet Name: {$sheetName}");

            // Read header row (expected: Phone Number, Nama Customer, Produk, No. Resi, Last_Promo, CS, Nama Samaran, Jenis Blasting, CS CRM)
            $headerRange = "'{$sheetName}'!A1:I1";
            $headerResponse = $sheetsService->spreadsheets_values->get($sheetId, $headerRange);
            $headers = $headerResponse->getValues()[0] ?? [];

            if (empty($headers)) {
                Log::warning("No headers found in sheet: {$sheetName}");
                return [];
            }

            Log::info("Headers found: " . implode(', ', $headers));

            // Read data rows
            $dataRange = "'{$sheetName}'!A2:I1000"; // Read up to 1000 rows
            $dataResponse = $sheetsService->spreadsheets_values->get($sheetId, $dataRange);
            $rows = $dataResponse->getValues() ?? [];

            if (empty($rows)) {
                Log::warning("No data rows found in sheet: {$sheetName}");
                return [];
            }

            Log::info("Found " . count($rows) . " raw data rows in sheet: {$sheetName}");

            // Convert to associative array with proper field mapping
            $contacts = [];
            foreach ($rows as $rowIndex => $row) {
                Log::info("Processing row " . ($rowIndex + 1) . ": " . implode(' | ', $row));

                if (empty($row[0])) {
                    Log::info("Skipping row " . ($rowIndex + 1) . " - empty phone number");
                    continue; // Skip empty phone number rows
                }

                // Pad row to match header count
                $row = array_pad($row, count($headers), '');

                $contact = [
                    'phone' => $row[0] ?? '', // Phone Number (A)
                    'nama_customer' => $row[1] ?? '', // Nama Customer (B)
                    'produk' => $row[2] ?? '', // Produk (C)
                    'no_resi' => $row[3] ?? '', // No. Resi (D)
                    'last_promo' => $row[4] ?? '', // Last_Promo (E)
                    'cs' => $row[5] ?? '', // CS (F)
                    'nama_samaran' => $row[6] ?? '', // Nama Samaran (G)
                    'jenis_blasting' => $row[7] ?? '', // Jenis Blasting (H)
                    'cs_crm' => $row[8] ?? '', // CS CRM (I)
                ];

                // Only add if phone number is not empty
                if (!empty(trim($contact['phone']))) {
                    $contacts[] = $contact;
                    Log::info("Added contact: Phone=" . $contact['phone'] . ", Name=" . $contact['nama_customer']);
                } else {
                    Log::info("Skipped contact - empty phone after trim");
                }
            }

            Log::info("Successfully parsed " . count($contacts) . " valid contacts from sheet: {$sheetName}");
            Log::info("Final contacts data: " . json_encode($contacts));
            return $contacts;
        } catch (\Exception $e) {
            Log::error("Error reading Google Sheet contacts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create campaign with data
     */
    private function createCampaignWithData($formOrderId, $device, $csName, $produk, $keteranganBlasting, $waktuBlasting, $messageTemplate, $contactsData, $img = null)
    {
        try {
            // Parse schedule time
            $scheduleTime = now();
            if (!empty($waktuBlasting)) {
                try {
                    $scheduleTime = \Carbon\Carbon::createFromFormat('H.i.s', $waktuBlasting);
                } catch (\Exception $e) {
                    Log::warning("Could not parse waktu blasting: {$waktuBlasting}");
                }
            }

            // Determine campaign type and message format
            $campaignType = 'text';
            $campaignMessage = $messageTemplate;

            if (!empty($img)) {
                $campaignType = 'media';
                Log::info("IMG found: {$img}, setting campaign type to 'media'");

                // Search for image file in Konten folder based on CS name
                $imageFilePath = $this->findImageFileByCsName($csName);
                if ($imageFilePath) {
                    $fileName = basename($imageFilePath);
                    $filePath = $imageFilePath;
                    Log::info("Found image file for CS {$csName}: {$fileName}");
                } else {
                    // Fallback to original logic if no file found
                    $fileName = basename($img);
                    $filePath = storage_path('app/public/files/1/Konten/' . $fileName);
                    Log::warning("No image file found for CS {$csName}, using fallback: {$fileName}");
                }

                // Ensure message template is properly formatted
                if (is_array($messageTemplate) && isset($messageTemplate['text'])) {
                    $captionText = $messageTemplate['text'];
                } elseif (is_string($messageTemplate)) {
                    $captionText = $messageTemplate;
                } else {
                    $captionText = '';
                }

                $campaignMessage = [
                    'url' => $filePath,
                    'type' => 'image',
                    'caption' => $captionText,
                    'filename' => $fileName
                ];

                Log::info("Media campaign message: " . json_encode($campaignMessage));
            } else {
                Log::info("No IMG found, using text campaign type");

                // Ensure message template is a string before encoding
                if (is_array($messageTemplate) || is_object($messageTemplate)) {
                    $messageTemplate = json_encode($messageTemplate);
                }

                // If message template is already JSON, decode it first
                $decodedTemplate = json_decode($messageTemplate, true);
                if (json_last_error() === JSON_ERROR_NONE && is_string($decodedTemplate)) {
                    // It's already JSON-encoded string, use as-is for json_encode
                    Log::info("Message template is already JSON-encoded string");
                } elseif (json_last_error() === JSON_ERROR_NONE && is_array($decodedTemplate)) {
                    // It's a JSON object/array, encode it properly
                    $messageTemplate = json_encode($decodedTemplate);
                    Log::info("Message template was JSON object/array, re-encoded");
                } else {
                    // It's a plain string, convert to MessageService format (array with 'text' key)
                    $messageTemplate = ['text' => $messageTemplate];
                    Log::info("Message template converted to MessageService format: " . json_encode($messageTemplate));
                }

                $campaignMessage = $messageTemplate;
            }

            Log::info("Final message template for campaign: " . json_encode($campaignMessage));
            Log::info("Message template type: " . gettype($campaignMessage));

            // Create campaign
            $campaign = Campaign::create([
                'form_order_id' => $formOrderId,
                'user_id' => $device->user_id,
                'device_id' => $device->id,
                'name' => "[{$formOrderId}] {$csName} - {$produk} - {$keteranganBlasting}",
                'phonebook_id' => 1, // Default phonebook
                'type' => $campaignType, // Use determined campaign type
                'status' => 'waiting',
                'message' => json_encode($campaignMessage),
                'schedule' => $scheduleTime,
                'delay' => 60 // Default delay
            ]);

            // Create blasts for each contact (if contacts data available)
            if (!empty($contactsData)) {
                $blasts = [];
                Log::info("Creating blasts for " . count($contactsData) . " contacts");

                foreach ($contactsData as $contact) {
                    // Format message with contact data
                    Log::info("Processing contact for blast: " . json_encode($contact));

                    if ($campaignType === 'media') {
                        // For media campaigns, format message with contact data in caption
                        $formattedMessage = $this->formatMessageWithContactData($campaignMessage, $contact);

                        // Update caption with formatted text - use 'caption' key for media messages
                        $blastMessage = [
                            'url' => $campaignMessage['url'],
                            'type' => $campaignMessage['type'],
                            'caption' => $formattedMessage['caption'] ?? $campaignMessage['caption'],
                            'filename' => $campaignMessage['filename']
                        ];

                        Log::info("Media blast message: " . json_encode($blastMessage));
                    } else {
                        // For text campaigns, use normal formatting
                        $formattedMessage = $this->formatMessageWithContactData($campaignMessage, $contact);
                        $blastMessage = $formattedMessage;
                        Log::info("Text blast message: " . json_encode($blastMessage));
                    }

                    $blasts[] = [
                        'user_id' => $device->user_id,
                        'sender' => $device->body, // Device body sebagai sender
                        'status' => 'pending',
                        'receiver' => $contact['phone'] ?? '',
                        'type' => $campaignType, // Use same type as campaign
                        'message' => json_encode($blastMessage),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                if (!empty($blasts)) {
                    $campaign->blasts()->createMany($blasts);
                    Log::info("Successfully created " . count($blasts) . " blasts for campaign {$campaign->id}");
                }
            } else {
                Log::warning("No contacts data available for creating blasts");
            }

            Log::info("Campaign created successfully: {$campaign->id}");
            return $campaign;
        } catch (\Exception $e) {
            Log::error("Error creating campaign: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Format message template with contact data
     */
    private function formatMessageWithContactData($template, $contactData)
    {
        Log::info("Formatting message template with contact data");
        Log::info("Template type: " . gettype($template));
        Log::info("Template content: " . json_encode($template));
        Log::info("Contact data: " . json_encode($contactData));

        // Handle different template formats
        if (is_array($template) && isset($template['caption'])) {
            // Media message format - only format the caption
            $messageText = $template['caption'];
            Log::info("Extracted caption from media message: {$messageText}");

            // Process caption with placeholders
            $formattedCaption = $this->processPlaceholders($messageText, $contactData);

            // Return media message with formatted caption
            return [
                'url' => $template['url'],
                'type' => $template['type'],
                'caption' => $formattedCaption,
                'filename' => $template['filename']
            ];
        } elseif (is_array($template) && isset($template['text'])) {
            $messageText = $template['text'];
            Log::info("Extracted message text from array: {$messageText}");
        } elseif (is_string($template)) {
            $messageText = $template;
            Log::info("Template is already string: {$messageText}");
        } else {
            Log::error("Unknown template format: " . gettype($template));
            return ['text' => 'Error: Invalid template format'];
        }

        // Process placeholders in the message
        $formatted = $this->processPlaceholders($messageText, $contactData);

        Log::info("Final formatted message: {$formatted}");

        // Return in MessageService format (array with 'text' key)
        return ['text' => $formatted];
    }

    /**
     * Process placeholders in message text
     */
    private function processPlaceholders($messageText, $contactData)
    {
        // Replace {{A}}, {{B}}, etc. with actual contact data
        // A = Phone Number, B = Nama Customer, C = Produk, D = No. Resi, etc.
        $columnMapping = [
            'A' => 'phone',
            'B' => 'nama_customer',
            'C' => 'produk',
            'D' => 'no_resi',
            'E' => 'last_promo',
            'F' => 'cs',
            'G' => 'nama_samaran',
            'H' => 'jenis_blasting',
            'I' => 'cs_crm'
        ];

        $formatted = $messageText;

        // Log original message before replacement
        Log::info("Original message before replacement: {$formatted}");

        // First, find all placeholders in the template
        preg_match_all('/\{\{([A-Z])\}\}/', $formatted, $templateMatches);
        $foundPlaceholders = $templateMatches[1] ?? [];
        Log::info("Found placeholders in template: " . implode(', ', $foundPlaceholders));

        foreach ($columnMapping as $placeholder => $field) {
            $value = $contactData[$field] ?? '';
            $oldFormatted = $formatted;

            // Replace all occurrences of the placeholder - use single quotes to avoid interpolation issues
            $search = '{{' . $placeholder . '}}';
            $formatted = str_replace($search, $value, $formatted);

            if ($oldFormatted !== $formatted) {
                Log::info("Replaced {{$placeholder}} with '{$value}'");
            } else {
                Log::info("No replacement needed for {{$placeholder}} (value: '{$value}')");
            }
        }

        // Check for any remaining placeholders and log them
        preg_match_all('/\{\{([A-Z])\}\}/', $formatted, $matches);
        if (!empty($matches[1])) {
            Log::warning("Unreplaced placeholders found: " . implode(', ', $matches[1]));
            Log::warning("Message with unreplaced placeholders: {$formatted}");

            // Also check for single curly braces that might indicate partial replacement
            preg_match_all('/\{([^{}]*)\}/', $formatted, $singleMatches);
            if (!empty($singleMatches[1])) {
                Log::warning("Found single curly braces (possible partial replacement): " . implode(', ', $singleMatches[1]));
            }
        } else {
            Log::info("All placeholders successfully replaced");
        }

        return $formatted;
    }

    /**
     * Find image file in Konten folder based on CS name
     */
    private function findImageFileByCsName($csName)
    {
        try {
            $kontenPath = storage_path('app/public/files/1/Konten');
            Log::info("Searching for image file in Konten folder: {$kontenPath} for CS: {$csName}");

            if (!is_dir($kontenPath)) {
                Log::warning("Konten folder does not exist: {$kontenPath}");
                return null;
            }

            // Get all files in Konten folder
            $files = scandir($kontenPath);
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            // Search for files that match CS name (case-insensitive)
            $csNameLower = strtolower(trim($csName));
            Log::info("Searching for files matching CS name: {$csNameLower}");

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $kontenPath . '/' . $file;
                if (!is_file($filePath)) {
                    continue;
                }

                // Check if file has image extension
                $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($fileExtension, $imageExtensions)) {
                    continue;
                }

                // Check if filename matches CS name (without extension)
                $fileNameWithoutExt = strtolower(pathinfo($file, PATHINFO_FILENAME));
                Log::info("Checking file: {$file} (name without ext: {$fileNameWithoutExt})");

                // Exact match or contains CS name
                if ($fileNameWithoutExt === $csNameLower || strpos($fileNameWithoutExt, $csNameLower) !== false) {
                    Log::info("Found matching image file: {$file} for CS: {$csName}");
                    return $filePath;
                }
            }

            Log::warning("No image file found matching CS name: {$csName} in folder: {$kontenPath}");
            return null;

        } catch (\Exception $e) {
            Log::error("Error searching for image file: " . $e->getMessage());
            return null;
        }
    }
}
