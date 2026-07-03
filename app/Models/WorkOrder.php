<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'work_orders';

    // Task types
    public const TASK_OPEX = 'OPEX';
    public const TASK_CAPEX = 'CAPEX';
    public const TASK_TYPES = [self::TASK_OPEX, self::TASK_CAPEX];

    // Fracttal statuses
    public const FRACTTAL_NO_OT = 'No OT';
    public const FRACTTAL_IN_PROGRESS = 'In Progress';
    public const FRACTTAL_UNDER_REVIEW = 'Under Review';
    public const FRACTTAL_FINISHED = 'Finished';
    public const FRACTTAL_STATUSES = [
        self::FRACTTAL_NO_OT,
        self::FRACTTAL_IN_PROGRESS,
        self::FRACTTAL_UNDER_REVIEW,
        self::FRACTTAL_FINISHED,
    ];

    // Work order workflow statuses
    public const STATUS_QUOTED = 'Quoted';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_FINISHED = 'Finished';
    public const STATUS_INVOICED = 'Invoiced';
    public const WORK_ORDER_STATUSES = [
        self::STATUS_QUOTED,
        self::STATUS_APPROVED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_FINISHED,
        self::STATUS_INVOICED,
    ];

    protected $fillable = [
        'request_id',
        'ot_number',
        'task_type', // OPEX or CAPEX
        'start_date',
        'end_date',
        'fracttal_status', // No OT, In Progress, Under Review, Finished
        'revision_ot',
        'finalized_ot',
        'purchase_order',
        'migo',
        'work_order_status', // Quoted, Approved, In Progress, Finished, Invoiced
        'work_order_comments',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    // Relations
    /**
     * Belongs to Request
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_QUOTED => 'Cotizado',
            self::STATUS_APPROVED => 'Aprobado',
            self::STATUS_IN_PROGRESS => 'En progreso',
            self::STATUS_FINISHED => 'Finalizado',
            self::STATUS_INVOICED => 'Facturado',
        ];
    }
    public static function getTasksOptions(): array
    {
        return [
            self::TASK_OPEX => 'OPEX',
            self::TASK_CAPEX => 'CAPEX',
        ];
    }
    public static function getFracttalStatusOptions(): array
    {
        return [
            self::FRACTTAL_NO_OT => 'Sin OT',
            self::FRACTTAL_IN_PROGRESS => 'En Progreso',
            self::FRACTTAL_UNDER_REVIEW => 'En Revisión',
            self::FRACTTAL_FINISHED => 'Finalizado',
        ];
    }
    /* Scopes */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('work_order_status', $status);
    }

    public function scopeByTaskType($query, string $type)
    {
        return $query->where('task_type', $type);
    }

    public function scopeByFracttalStatus($query, string $status)
    {
        return $query->where('fracttal_status', $status);
    }

    /* Helpers */
    public function isFinished(): bool
    {
        return $this->work_order_status === self::STATUS_FINISHED;
    }

    public function isInvoiced(): bool
    {
        return $this->work_order_status === self::STATUS_INVOICED;
    }

    public function markAs(string $status): bool
    {
        if (!in_array($status, self::WORK_ORDER_STATUSES, true)) {
            return false;
        }

        $this->work_order_status = $status;
        return $this->save();
    }
}
