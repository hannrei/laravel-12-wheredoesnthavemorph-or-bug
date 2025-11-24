<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    protected function casts()
    {
        return [
            'is_urgent' => 'boolean',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
