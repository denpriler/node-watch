<?php

namespace App\Models;

use App\Enum\Monitor\MonitorMethod;
use App\Enum\Monitor\MonitorStatus;
use Database\Factories\MonitorFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpFoundation\Response;

#[Unguarded]
class Monitor extends Model
{
    /** @use HasFactory<MonitorFactory> */
    use HasFactory;

    protected $attributes = [
        'check_interval' => 30,
        'timeout' => 30,
        'expected_status' => Response::HTTP_OK,
        'regions' => '[]',
        'is_active' => true,
        'last_status' => MonitorStatus::PENDING,
        'method' => MonitorMethod::HEAD,
    ];

    protected $casts = [
        'user_id' => 'integer',
        'check_interval' => 'integer',
        'timeout' => 'integer',
        'expected_status' => 'integer',
        'regions' => 'array',
        'is_active' => 'boolean',
        'next_check_at' => 'datetime',
        'last_status' => MonitorStatus::class,
        'method' => MonitorMethod::class,
    ];

    // region Relationships

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // endregion
}
