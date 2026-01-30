<?php

stream_set_blocking(STDIN, false);
$r = [STDIN];
while(stream_select($r, $w, $e, 20) !== false) {
    echo "[" . fread(STDIN, 8192) . "]";
    if(feof(STDIN)) {
        break;
    }
    $r = [STDIN];
}