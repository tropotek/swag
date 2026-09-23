<?php

it('responds on the health route', function () {
    $this->get('/up')->assertOk();
});
