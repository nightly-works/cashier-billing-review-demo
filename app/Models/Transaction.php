<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['booked_on', 'label', 'amount_cents'])]
class Transaction extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['booked_on' => 'date'];
    }
}
