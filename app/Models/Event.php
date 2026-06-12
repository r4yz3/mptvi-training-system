<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function activityLabel(): string
    {
        return "event {$this->title}";
    }
}
