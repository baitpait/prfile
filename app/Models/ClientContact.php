<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientContact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'label', 'first_name', 'last_name',
        'email', 'phone_home', 'phone_mobile', 'notes', 'source_row_json',
    ];

    protected $casts = ['source_row_json' => 'array'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
