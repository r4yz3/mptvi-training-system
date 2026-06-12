<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
            'enabled' => 'boolean',
            'show_in_list' => 'boolean',
            'filterable' => 'boolean',
            'show_on_dashboard' => 'boolean',
        ];
    }
}
