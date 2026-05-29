<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Placeholder for Laravel auth config. EnrollEase uses DEORIS SSO sessions only.
 */
class PortalAccount extends Authenticatable
{
    protected $table = 'sessions';
}
