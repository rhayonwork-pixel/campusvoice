<?php
// Copy to config.local.php on the server and fill in. Never commit the real file.
return [
    'db' => [
        'host' => 'sqlXXX.infinityfree.com',   // MySQL Hostname from the InfinityFree panel
        'name' => 'if0_XXXXXXXX_campusvoice',
        'user' => 'if0_XXXXXXXX',
        'pass' => 'YOUR_DB_PASSWORD',
    ],
    'app' => ['debug' => false, 'mail_from' => 'no-reply@yourdomain.example'],
];
