<?php

$file = 'files/OmniGraffle_5_Keyboard_Shortcuts.graffle';
$file = 'files/usage.txt';
$file = 'files/2gfile';

simple_upload($file);

function simple_upload($file) 
{
    $host = '';
    $box = array(
    );
    if (!file_exists($file)) {
        throw new RuntimeException('File not found');
    }

    // Figure out the filename and full size
    $path_parts = pathinfo($file);
    $file_name = $path_parts['basename'];
    $file_size = filesize($file);
    $fInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fInfo, $file);
    finfo_close($fInfo);
    
    $baseUrl    = $box[rand(0, count($box) - 1)] . '/upload-test-route';
    $curl       = curl_init($baseUrl);

    // Generate the OAuth signature
    $params = array(
        'asset' => '@' . $file,
        'operation' => 'create',
        'userId' => 75447,
        'clientCode' => 'dev',
        'package' => 'project'
        'folderId' => 1,
        'projectId' => 2,
    );

    // Post the file
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Host: ' . $host,
        'Content-Disposition: attachment; filename="' . $file_name . '"',
        'Content-Type: ' . $mimeType, 
    ));
    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
    curl_setopt($curl, CURLINFO_HEADER_OUT, 1);
    
    $start  = microtime(true);
    $rsp    = curl_exec($curl);
    $info   = curl_getinfo($curl);
    $statusCode = $info['http_code'];
    if (200 != $statusCode){
        $error = $rsp;
        if (is_array($rsp)) {
            $error = implode("\n", $rsp);
        }
        throw new RuntimeException(
            'Failure uploading file: code:' . $statusCode . '|' . $error
        );
    }
    $data = json_decode($rsp, true);
    echo $data['data']['diskName'] . "\n";
    curl_close($curl);
    echo 'Simple upload took: ' . (microtime(true) - $start) . "\n";
}
