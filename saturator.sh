#!/usr/local/bin/bash                                                                                                                                                                                                                                                                                                                                                     

while [ 1 ]
do
    php upload.php &
    php simple_upload.php &
    php upload.php &
    php simple_upload.php &
    php upload.php &
    sleep 10 
done
