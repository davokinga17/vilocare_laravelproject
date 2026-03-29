<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
protected $table = 'patients';

protected $fillable = [
    'art_number',
    'first_name',
    'last_name',
    'sex',
    'phone'
];
}
