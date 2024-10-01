<?php

/**
 * Explanation
 *
 * The system allows each organization to have its own numbering formats for inventory and sales documents.
 * So I have moved that specific functionality to a model trait to achieve it without code repetition.
 */

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $number_prefix
 * @property string $number
 */
trait HasDocumentNumber
{
    abstract protected function getStartingNumber(): int;

    abstract protected function getNumberPrefix(): ?string;

    public static function bootHasDocumentNumber(): void
    {
        static::creating(function (Model $model): void {
            $model->number_prefix = $model->getNumberPrefix();

            $model->number = max(
                $model->getStartingNumber() ?? 1,
                1 + $model->query()
                    ->whereBelongsTo($model->organization)
                    ->when($model->company, fn (Builder $query) => $query->whereBelongsTo($model->company))
                    ->withTrashed()
                    ->max('number'),
            );
        });
    }

    protected function number(): Attribute
    {
        return Attribute::get(function (int $value) {
            return $this->number_prefix.str_pad($value, 5, '0', STR_PAD_LEFT);
        });
    }
}
