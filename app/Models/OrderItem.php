<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'unit_amount',
        'total_amount',
        'quantity',
    ];


    /**
     * The order that this order item belongs to.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The product that this order item is for.
     */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
