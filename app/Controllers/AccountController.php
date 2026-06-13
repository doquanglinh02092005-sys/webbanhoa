<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\User;
use Throwable;

final class AccountController extends Controller
{
    public function show(): void
    {
        $sessionUser = require_login();
        try {
            ensure_shop_schema(db());
            $user = (new User())->find((int) $sessionUser['id']);
            if (!$user || $user['status'] !== 'active') { logout_user(); redirect('login.php'); }
            $recentOrders = (new Order())->recentForUser((int) $sessionUser['id']);
            $loyaltyHistory = (new User())->loyaltyHistory((int) $sessionUser['id']);
        } catch (Throwable $exception) {
            $user = $sessionUser + ['phone' => '', 'loyalty_points' => 0, 'created_at' => null, 'last_login_at' => null];
            $recentOrders = [];
            $loyaltyHistory = [];
        }
        $this->view('account/show', compact('user', 'recentOrders', 'loyaltyHistory') + ['pageTitle' => 'Tài khoản của tôi', 'flashMessage' => pull_flash()]);
    }
}
