<?php

namespace App\Controller;

class BoardController
{
    public function index(): void
    {
        require __DIR__ . '/../View/board/index.php';
    }
}
