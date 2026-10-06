<?php

return [

    'media' => [
        // Per-file upload limit in kilobytes (25 MB).
        'max_kb' => (int) env('SWAG_MEDIA_MAX_KB', 25600),

        // Extensions that are never accepted, in any position of the file name.
        // Executables and scripts must be uploaded inside a zip instead.
        'blocked_extensions' => [
            'exe', 'com', 'bat', 'cmd', 'scr', 'msi', 'msp', 'dll', 'ps1', 'psm1',
            'vbs', 'vbe', 'js', 'jse', 'wsf', 'wsh', 'hta', 'jar', 'sh', 'bash',
            'php', 'phtml', 'pl', 'py', 'rb', 'cgi', 'lnk', 'reg', 'app', 'dmg', 'apk',
        ],

        // MIME types the browser may show or play inline. Images display inside a page; audio
        // and video play in the new tab their link opens. Everything else, PDFs included,
        // downloads. Keep this list to types that cannot execute: inline responses skip the
        // sandbox CSP so the browser's media players work.
        'inline_mimes' => [
            'image/png', 'image/jpeg', 'image/gif', 'image/webp',
            'audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav',
            'video/mp4', 'video/webm', 'video/quicktime',
        ],
    ],

];
