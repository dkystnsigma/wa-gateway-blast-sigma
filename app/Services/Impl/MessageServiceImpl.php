<?php

namespace App\Services\Impl;

use App\Services\MessageService;
use Illuminate\Support\Facades\Log;

class MessageServiceImpl implements MessageService
{
    public function formatText($text): array
    {
        return ['text' => $text];
    }

    public function formatLocation($latitude, $longitude): array
    {
        return [
            'location' => [
                'degreesLatitude' => $latitude,
                'degreesLongitude' => $longitude,
            ],
        ];
    }

    private function formatSticker($data)
    {
        //Log::info('data' . json_encode($data));
        $fileName = explode('/', $data->url);
        $fileName = explode('.', end($fileName));
        $fileName = implode('.', $fileName);
        $mediadetail = [
            'type' => 'sticker',
            'url' => $data->url,
            'filename' => $fileName,
        ];

        return $mediadetail;
    }

    public function formatVcard($name, $phone): array
    {
        $vcard =
            "BEGIN:VCARD\n" .
            "VERSION:3.0\n" .
            "FN:" . $name . "\n" .
            "TEL;type=CELL;type=VOICE;waid=" . $phone . ":+" . $phone . "\n" .
            "END:VCARD";

        return [
            'contacts' => [
                'displayName' => $name,
                'contacts' => [['vcard' => $vcard]]
            ]
        ];
    }

    public function formatImage($url, $caption = ''): array
    {
        return ['image' => ['url' => $url], 'caption' => $caption];
    }

    // formating buttons
    public function formatButtons($text, $buttons, $urlimage = '', $footer = ''): array
    {
        $buttons = array_values($buttons);

        $valueForText = $urlimage ? 'caption' : 'text';
        $message = [
            $valueForText => $text,
            'buttons' => $buttons,
            'footer' => $footer,
            'headerType' => 1,
            // 'viewOnce' => true,
        ];
        if ($urlimage) {
            $message['image'] = ['url' => $urlimage];
        }
        return $message;
    }

    // formating templates
    public function formatTemplates($text, $buttons, $urlimage = '', $footer = ''): array
    {
        $templateButtons = [];
        $i = 1;
        foreach ($buttons as $button) {

            $type = explode('|', $button)[0] . 'Button';
            $textButton = explode('|', $button)[1];
            $urlOrNumber = explode('|', $button)[2];
            $typeIcon = explode('|', $button)[0] === 'url' ? 'url' : 'phoneNumber';
            $templateButtons[] = [
                'index' => $i,
                $type => ['displayText' => $textButton, $typeIcon => $urlOrNumber],
            ];
            $i++;
        }
        $valueForText = $urlimage ? 'caption' : 'text';
        $templateMessage = [
            $valueForText => $text,
            'footer' => $footer,
            'templateButtons' => $templateButtons,
            'viewOnce' => true,
        ];
        //add image to templateMessage if exists
        if ($urlimage) {
            $templateMessage['image'] = ['url' => $urlimage];
        }
        return $templateMessage;
    }

    public function formatLists($text, $lists, $title, $buttonText, $footer = '', $urlimage = null): array
    {
        $list = [];
        $list['title'] = $title;
        $list['rows'] = [];
        foreach ($lists as $menu) {
            $list['rows'][] = [
                'title' => $menu,
                'description' => '--', // Anda bisa mengisi deskripsi jika diperlukan
            ];
        }
        $section = [
            [

                'buttonText' => $buttonText,
                'list' => [$list]
            ]
        ];
        // Membuat list message dengan format yang diminta
        $listMessage = [
            'text' => $text,
            'footer' => $footer ?? '..',
            'buttonText' => $buttonText,
            'sections' => $section,
        ];
        if ($urlimage) {
            $listMessage['image'] = ['url' => $urlimage];
        }
        return $listMessage;
    }



    public function format($type, $data): array
    {
        switch ($type) {
            case 'text':
                $reply = $this->formatText($data->message);
                break;
            case 'location':
                $reply = $this->formatLocation($data->latitude, $data->longitude);
                break;
            case 'vcard':
                $reply = $this->formatVcard($data->name, $data->phone);
                break;
            case 'sticker':
                $reply = $this->formatSticker($data);
                break;
            case 'image':
                $reply = $this->formatImage($data->image,  $data->caption);
                break;
            case 'button':
                $buttons = [];
                foreach ($data->button as $button) {
                    $buttons[] = $button;
                }
                $reply = $this->formatButtons($data->message, $buttons, $data->image ? $data->image : '', $data->footer ?? '');
                break;
            case 'template':
                $buttons = [];
                foreach ($data->template as $button) {
                    $buttons[] = $button;
                }
                try {
                    $reply = $this->formatTemplates(
                        $data->message,
                        $buttons,
                        $data->image ? $data->image : '',
                        $data->footer ?? ''
                    );
                } catch (\Throwable $th) {
                    throw new \Exception('Invalid button type');
                }

                break;
            case 'list':
                $reply = $this->formatLists($data->message, $data->list, $data->title, $data->buttontext, $data->footer, $data->image ?? null);

                break;
            case 'media':
                $reply = $this->formatMedia($data);
                break;
            default:
                # code...
                break;
        }

        return $reply;
    }


    private function formatMedia($data)
    {
        //Log::info('data' . json_encode($data));
        $fileName = explode('/', $data->url);
        $fileName = explode('.', end($fileName));
        $fileName = implode('.', $fileName);
        
        // Convert URL to file path if it's a local URL
        $url = $data->url;
        if (str_contains($url, '127.0.0.1:8000/storage') || str_contains($url, 'localhost:8000/storage')) {
            // Get the part after /storage/
            $path = explode('/storage/', $url)[1];
            // Convert to absolute path
            $url = storage_path('app/public/' . $path);
            Log::info("Converting URL to file path: " . $url);
        } elseif (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            // If it's not an absolute URL and not a local URL, make it absolute
            $url = env('APP_URL') . '/' . ltrim($url, '/');
        }
        
        $mediadetail = [
            'type' => $data->media_type,
            'url' => $url,
            //  'ppt' => $data->ptt,
            'filename' => $fileName,
            'caption' => $data->caption
        ];
        
        Log::info("Media detail prepared:", $mediadetail);
        return $mediadetail;
    }
}
