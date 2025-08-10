<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','amount','currency','txid','address','network','status','fees','confirmations','wallet_admin_id'
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'fees' => 'decimal:8',
        'confirmations' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function walletAdmin()
    {
        return $this->belongsTo(WalletAdmin::class, 'wallet_admin_id');
    }
}