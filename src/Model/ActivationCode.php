<?php

namespace Crow\LaravelActivationCode\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * @property-read int $id
 * @property string $receiver
 * @property string $code
 * @property string $type
 * @property int $record_id
 * @property int $attempt
 * @property Carbon $expires_at
 * @property Carbon $created_at
 */
class ActivationCode extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $casts = [
        'record_id' => 'integer',
        'attempt' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = Config::get('activation_code.table');

        parent::__construct($attributes);
    }
}
