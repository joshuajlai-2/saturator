<?php

$dir = 'files';
$file1 = '02.mov';
$file2 = 'OmniGraffle_5_Keyboard_Shortcuts.graffle';

$chunkSize = 512 * 1024;
upload(
    $dir . '/' . $file1, 
    $dir . '/' . $file2,
    $chunkSize
);

function upload($file1,
                $file2,
                $size = 2097152) 
{

    $hosts = array(
    );
    $host = '';

    if (!file_exists($file1) || ! file_exists($file2)) {
        throw new Exception('File not found');
    }
    
    $mimeType = 'application/unknown';
    $fileName = 'corrupted_file.txt';
    $fileSize = filesize($file1);
    if (filesize($file2) < $fileSize) {
        $fileSize = filesize($file2);
    }

    $sessionId = md5($file1 . $file2 . uniqid()); 

    // Upload each piece
    // rotate around hosts
    $offset = 0;
    $start  = microtime(true);
    do {
        if (rand(0, 1) == 0) {
            $filePath = $file1;
        } else {
            $filePath = $file2;
        }
        $baseUrl = $hosts[rand(0, count($hosts) - 1)];
        $baseUrl .= '/upload/test/route';
        $curl   = curl_init($baseUrl);

        $range = $offset + $size 

        // Post the file
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Host: ' . $host,
            'Content-Range: bytes ' . 
                $offset . '-' . ($offset + $size - 1) . '/' . $fileSize,
            'X-Session-ID: ' . $sessionId, 
            'Content-Disposition: attachment; filename="' . $fileName . '"',
            'Content-Type: ' . $mimeType, 
            'Content-Length: ' . $size, 
        ));
        curl_setopt(
            $curl, 
            CURLOPT_POSTFIELDS, 
            file_get_contents($filePath, false, null, $offset, $size)
        );
        curl_setopt($curl, CURLINFO_HEADER_OUT, 1);
        $rsp    = curl_exec($curl);
        $info   = curl_getinfo($curl);
        $statusCode = $info['http_code'];
        print_r($info);
        if (200 == $statusCode) {
            $data = json_decode($rsp, true);
            print_r($data);
            echo $data['data']['diskName'] . "\n";
        } else {
            $error = $rsp;
            if (is_array($rsp)) {
                $error = implode("\n", $rsp);
            }
            echo $error . "\n";
        }
        curl_close($curl);
        $offset += $size;
    } while($offset < $fileSize);
    echo 'Corrupt upload took: ' . (microtime(true) - $start) . "\n";
}
