<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesMedia;
use App\Http\Controllers\Concerns\StoresMedia;

class MediaController extends Controller
{
    use ServesMedia;
    use StoresMedia;
}
