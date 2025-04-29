<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    protected $table = 'gift_cards';
    protected $fillable = [
        'name',
        'description',
        'discount',
        'min_amount',
        'max_amount',
        'image',
    ];
}
