<?php

use Illuminate\Support\Facades\Http;

if (!function_exists('str_extract')) {
    function str_extract($str, $pattern, $get = null, $default = null)
    {
        $result = [];
        preg_match($pattern, $str, $matches);
        preg_match_all('/(\(\?P\<(?P<name>.+)\>\.\+\)+)/U', $pattern, $captures);
        $names = $captures['name'] ?? [];
        foreach ($names as $name) {
            $result[$name] = $matches[$name] ?? null;
        }
        return $get ? $result[$get] ?? $default : $result;
    }
}

if (!function_exists('wrap_str')) {
    function wrap_str($str = '', $first_delimiter = "'", $last_delimiter = null)
    {
        if (!$last_delimiter) {
            return $first_delimiter . $str . $first_delimiter;
        }

        return $first_delimiter . $str . $last_delimiter;
    }
}

if (!function_exists('getExtensionImageFromUrl')) {
    function getExtensionImageFromUrl($url)
    {
        $url = explode('.', $url);
        $extension = end($url);
        return $extension;
    }
}

if (!function_exists('clearCacheNode')) {
    function clearCacheNode($key = null, $mode = 1)
    {
        try { 
            $modeMap = [
                1 => 'all',
                2 => 'prefix',
                3 => 'specific',
            ];
           
            $modeString = $modeMap[$mode] ?? 'all';
            $data = [
                'mode' => $modeString,
            ];
            if (in_array($modeString, ['prefix', 'specific']) && $key !== null) {
                $data['key'] = $key;
            }

            Http::withOptions(['verify' => false])
                ->asForm()
                ->post(env('WA_URL_SERVER') . '/backend-clearCache', $data);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}

if (!function_exists('setEnv')) {
    function setEnv(string $key, string $value)
    {
        $env = array_reduce(
            file(base_path('.env'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
            function ($carry, $item) {
                list($key, $val) = explode('=', $item, 2);

                $carry[$key] = $val;

                return $carry;
            },
            []
        );
        $env[$key] = $value;
        foreach ($env as $k => &$v) {
            $v = "{$k}={$v}";
        }

        file_put_contents(base_path('.env'), implode("\r\n", $env));
    }
}

if (!function_exists('backWithFlash')) {
    function backWithFlash($type, $message)
    {
        return redirect()->back()->with('alert', ['type' => $type, 'msg' => $message]);
    }
}

if (!function_exists('redirectWithFlash')) {
    function redirectWithFlash($type, $message, $url)
    {
        return redirect($url)->with('alert', ['type' => $type, 'msg' => $message]);
    }
}

if (!function_exists('logBlast')) {
    function logBlast($message, $level = 'info', $context = [])
    {
        try {
            // Buat folder blast jika belum ada
            $basePath = function_exists('storage_path') ? storage_path('logs/blast') : dirname(__DIR__) . '/storage/logs/blast';
            if (!file_exists($basePath)) {
                mkdir($basePath, 0755, true);
            }
            
            // Set timezone sesuai config Laravel atau default ke Asia/Jakarta
            $timezone = 'Asia/Jakarta';
            if (function_exists('config')) {
                $timezone = config('app.timezone', 'Asia/Jakarta');
            }
            
            // Format tanggal dan waktu dengan timezone yang benar
            $dateTime = new DateTime('now', new DateTimeZone($timezone));
            $date = $dateTime->format('Y-m-d');
            $timestamp = $dateTime->format('Y-m-d H:i:s');
            
            $logFile = $basePath . '/' . $date . '.log';
            
            // Format log message
            $levelUpper = strtoupper($level);
            $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
            $logEntry = "[{$timestamp}] local.{$levelUpper}: {$message}{$contextStr}" . PHP_EOL;
            
            // Tulis ke file (append mode)
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            
            return true;
        } catch (\Exception $e) {
            // Fallback ke error_log PHP biasa jika gagal
            error_log("Failed to write blast log: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getBlastLogPath')) {
    function getBlastLogPath($date = null)
    {
        $basePath = function_exists('storage_path') ? storage_path('logs/blast') : dirname(__DIR__) . '/storage/logs/blast';
        
        // Set timezone sesuai config Laravel atau default ke Asia/Jakarta
        $timezone = 'Asia/Jakarta';
        if (function_exists('config')) {
            $timezone = config('app.timezone', 'Asia/Jakarta');
        }
        
        if ($date) {
            $targetDate = $date;
        } else {
            $dateTime = new DateTime('now', new DateTimeZone($timezone));
            $targetDate = $dateTime->format('Y-m-d');
        }
        
        return $basePath . '/' . $targetDate . '.log';
    }
}

if (!function_exists('getBlastTimezone')) {
    function getBlastTimezone()
    {
        return function_exists('config') ? config('app.timezone', 'Asia/Jakarta') : 'Asia/Jakarta';
    }
}

if (!function_exists('getBlastCurrentTime')) {
    function getBlastCurrentTime($format = 'Y-m-d H:i:s')
    {
        $timezone = getBlastTimezone();
        $dateTime = new DateTime('now', new DateTimeZone($timezone));
        return $dateTime->format($format);
    }
}

if (!function_exists('logBlastInfo')) {
    function logBlastInfo($message, $context = [])
    {
        return logBlast($message, 'info', $context);
    }
}

if (!function_exists('logBlastWarning')) {
    function logBlastWarning($message, $context = [])
    {
        return logBlast($message, 'warning', $context);
    }
}

if (!function_exists('logBlastError')) {
    function logBlastError($message, $context = [])
    {
        return logBlast($message, 'error', $context);
    }
}

if (!function_exists('logBlastDebug')) {
    function logBlastDebug($message, $context = [])
    {
        return logBlast($message, 'debug', $context);
    }
}
