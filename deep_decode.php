<?php
function deep_decode($string) {
    // Layer 1: Base64 decode
    $decoded = base64_decode($string);
    if ($decoded === false) return $string;
    
    // Layer 2: Gzinflate if compressed
    $inflated = @gzinflate($decoded);
    if ($inflated !== false) {
        $decoded = $inflated;
    }
    
    // Layer 3: Replace special characters
    $str = ['ý','ê','ã','í','û','æ','ñ','á','õ','ë','±'];
    $rplc = ['a','i','u','e','o','d','s','h','v','t',' '];
    $decoded = str_replace($str, $rplc, $decoded);
    
    // Layer 4: Try base64 decode again if result looks encoded
    if (preg_match('/^[A-Za-z0-9+\/=]+$/', trim($decoded))) {
        $second_decode = base64_decode($decoded);
        if ($second_decode !== false) {
            $decoded = $second_decode;
        }
    }
    
    // Layer 5: Try gzinflate again
    $final_inflated = @gzinflate($decoded);
    if ($final_inflated !== false) {
        $decoded = $final_inflated;
    }
    
    return $decoded;
}

// Baca file yang terenkripsi
$encoded_content = file_get_contents(__DIR__ . '/app/Http/Controllers/ShowMessageController.php');

// Ekstrak semua string yang mungkin terenkripsi
$strings = [];
if (preg_match('/\$SISTEMIT_COM_ENC = "(.*?)";/', $encoded_content, $matches)) {
    $strings['SISTEMIT_COM_ENC'] = $matches[1];
}
if (preg_match('/\$rand = base64_decode\("(.*?)"\);/', $encoded_content, $matches)) {
    $strings['rand'] = $matches[1];
}

// Sekarang kita decode sesuai urutan yang benar
echo "=== Mendecode ShowMessageController ===\n\n";

if (isset($strings['SISTEMIT_COM_ENC'])) {
    $SISTEMIT_COM_ENC = $strings['SISTEMIT_COM_ENC'];
    
    // Ikuti langkah yang ada di variable $rand
    $nav = gzinflate(base64_decode($SISTEMIT_COM_ENC));
    
    // Ganti karakter khusus (seperti di $rand yang sudah di-decode)
    $str = ['ý','ê','ã','í','û','æ','ñ','á','õ','ë','µ'];
    $rplc = ['a','i','u','e','o','d','s','h','v','t',' '];
    $nav = str_replace($str, $rplc, $nav);
    
    // Cek apakah ada variable $SISTEMIT_COM_ENC lagi di dalam hasil decode
    if (preg_match('/\$SISTEMIT_COM_ENC = "(.*?)";/', $nav, $inner_matches)) {
        echo "Ditemukan encoding level kedua, mendecode...\n\n";
        $inner_encoded = $inner_matches[1];
        
        // Decode level kedua
        $inner_nav = gzinflate(base64_decode($inner_encoded));
        $inner_nav = str_replace($str, $rplc, $inner_nav);
        
        echo "=== HASIL DECODE LEVEL 2 ===\n";
        echo str_repeat('=', 80) . "\n";
        echo $inner_nav;
        echo "\n" . str_repeat('=', 80) . "\n";
        
        // Cek apakah masih ada level ketiga
        if (preg_match('/\$SISTEMIT_COM_ENC = "(.*?)";/', $inner_nav, $inner_matches2)) {
            echo "\nDitemukan encoding level ketiga, mendecode...\n\n";
            $inner_encoded2 = $inner_matches2[1];
            
            // Decode level ketiga
            $inner_nav2 = gzinflate(base64_decode($inner_encoded2));
            $inner_nav2 = str_replace($str, $rplc, $inner_nav2);
            
            echo "=== HASIL DECODE LEVEL 3 (FINAL) ===\n";
            echo str_repeat('=', 80) . "\n";
            echo $inner_nav2;
            echo "\n" . str_repeat('=', 80) . "\n";
        }
    } else {
        echo "=== HASIL DECODE FINAL ===\n";
        echo str_repeat('=', 80) . "\n";
        echo $nav;
        echo "\n" . str_repeat('=', 80) . "\n";
    }
}
