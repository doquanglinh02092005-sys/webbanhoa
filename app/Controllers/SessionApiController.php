<?php
declare(strict_types=1);

namespace App\Controllers;

use Throwable;

final class SessionApiController
{
    public function show(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        try {
            $user = refresh_current_user();
        } catch (Throwable $exception) {
            $user = current_user();
        }
        echo json_encode([
            'authenticated' => $user !== null,
            'user' => $user ? ['id'=>$user['id'],'full_name'=>$user['full_name'],'email'=>$user['email'],'role'=>$user['role']] : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
