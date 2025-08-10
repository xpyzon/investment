<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'wallet_admin_id', 'deposit_address', 'deposit_tag', 'address_generated', 'status'
    ];

    protected $casts = [
        'address_generated' => 'boolean',
    ];

    public function walletAdmin()
    {
        return $this->belongsTo(WalletAdmin::class, 'wallet_admin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}