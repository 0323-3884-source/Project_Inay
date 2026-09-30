<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DswdStaff extends Model
{
    protected $table = 'dswd_staff';

    protected $fillable = ['name', 'email', 'password', 'office', 'is_active'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'password' => 'hashed'];
    }
}
