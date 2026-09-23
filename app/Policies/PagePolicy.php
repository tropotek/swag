<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PagePolicy
{
    public function view(User $user, Page $page): Response
    {
        return $this->owns($user, $page);
    }

    public function update(User $user, Page $page): Response
    {
        return $this->owns($user, $page);
    }

    public function delete(User $user, Page $page): Response
    {
        return $this->owns($user, $page);
    }

    private function owns(User $user, Page $page): Response
    {
        return $page->user()->is($user) ? Response::allow() : Response::denyAsNotFound();
    }
}
