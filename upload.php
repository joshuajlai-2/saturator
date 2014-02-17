<?php

$dir = 'files';
$file = 'usage.txt';
$file = 'OmniGraffle_5_Keyboard_Shortcuts.graffle';

$chunkSize = 512 * 1024;
upload($dir . '/' . $file, $dir, $chunkSize);

function upload($file_path, 
                $chunk_temp_dir = '.', 
                $size = 2097152) 
{

    $hosts = array(
    );
    $host = '';

    if (!file_exists($file_path)) {
        throw new Exception('File not found');
    }

    // Figure out the filename and full size
    $path_parts = pathinfo($file_path);
    $file_name = $path_parts['basename'];
    $file_size = filesize($file_path);
    $fInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fInfo, $file_path);
    finfo_close($fInfo);

    // Get an upload ticket
    $params = array();

    // Split up the file if using multiple pieces
    $chunks = array();
    if (!is_writeable($chunk_temp_dir)) {
        throw new Exception('Could not write chunks. Make sure the specified folder has write access.');
    }

    // Create pieces
    $number_of_chunks = ceil(filesize($file_path) / $size);
    for ($i = 0; $i < $number_of_chunks; $i++) {
        $chunk_file_name = "{$chunk_temp_dir}/{$file_name}.{$i}";
        
        // Break it up
        $chunk = file_get_contents($file_path, FILE_BINARY, null, $i * $size, $size);
        $file = file_put_contents($chunk_file_name, $chunk);

        $chunks[] = array(
            'file' => realpath($chunk_file_name),
            'size' => filesize($chunk_file_name)
        );
    }
    $sessionId = md5($file_path . uniqid()); 

    // Upload each piece
    // rotate around hosts
    $count = 0;

    $start = microtime(true);
    foreach ($chunks as $i => $chunk) {
        $baseUrl = $hosts[$count % count($hosts)];
        $baseUrl .= '/upload/test/route';
        $curl   = curl_init($baseUrl);

        // Generate the OAuth signature
        $params = array(
            'file_data' => '@'.$chunk['file'] // don't include the file in the signature
        );

        // Post the file
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Host: ' . $host,
            'Content-Range: bytes ' . 
                ($i * $size) . '-' . ((($i * $size) + $chunk['size']) - 1) . '/' . $file_size,
            'X-Session-ID: ' . $sessionId, 
            'Content-Disposition: attachment; filename="' . $file_name . '"',
            'Content-Type: ' . $mimeType, 
            'Content-Length: ' . $chunk['size'], 
        ));
        curl_setopt($curl, CURLOPT_POSTFIELDS, file_get_contents($chunk['file']));
        curl_setopt($curl, CURLINFO_HEADER_OUT, 1);
        $rsp = curl_exec($curl);
        curl_close($curl);
        $count++;
    }
    echo 'Chunked upload took: ' . (microtime(true) - $start) . "\n";
}
