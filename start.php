<?php
$host = '103.175.221.221';
$port = 9001;
$docRoot = "public";

echo "PHP built-in server is running at http://{$host}:{$port}\n";
passthru("php -S {$host}:{$port} -t {$docRoot}");