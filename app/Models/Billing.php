<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'date',
        'month',
        'billing_number',
        'billing_details',
        'somme_all',
        'pdf_file',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime',
        'billing_details' => 'array',
    ];

    /**
     * Get the user that owns the billing.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
