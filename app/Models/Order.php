<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'payment_method',
        'payment_status',
        'order_status',
        'currency',
        'shipping_amount',
        'shipping_method',
        'note',
    ];

    /**
     * Get the user that made the order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items of the order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the address of the order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */

    public function address()
    {
        return $this->hasOne(Address::class);
    }
}
