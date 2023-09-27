<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable= [
        'customer_id',
        'paid'
    ];

    public function orderDetails() {
       return $this->hasMany(OrderDetail::class, 'order_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'customer_id');
     }
}
