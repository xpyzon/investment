<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletAdmin extends Model
{
    use HasFactory;

    protected $table = 'wallet_admin';

    protected $fillable = [
        'name', 'currency', 'network', 'address_template', 'requires_tag', 'tag_label',
        'confirmations', 'icon_url', 'is_enabled', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'requires_tag' => 'boolean',
        'is_enabled' => 'boolean',
        'confirmations' => 'integer',
    ];

    public function userWallets()
    {
        return $this->hasMany(UserWallet::class, 'wallet_admin_id');
    }
}