<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Document;

class DocumentPolicy
{
    public function view(User $user): bool { return true; }
    public function delete(User $user): bool { return $user->isSuperAdmin() || $user->isBranchManager(); }
}
