<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
