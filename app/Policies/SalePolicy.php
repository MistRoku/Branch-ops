<?php

namespace App\Policies;

use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function create(User $user): bool { return true; }
    public function refund(User $user): bool { return $user->isSuperAdmin() || $user->isBranchManager(); }
}
