<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MediaPolicy
{
    public function view(User $user, Media $media): Response
    {
        return $media->user()->is($user) ? Response::allow() : Response::denyAsNotFound();
    }
}
