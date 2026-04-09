<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class ModelActivityObserver
{
    protected static array $oldValues = [];

    public function updating(Model $model): void
    {
        self::$oldValues[spl_object_id($model)] = $model->getOriginal();
    }

    public function created(Model $model): void
    {
        ActivityLogger::logModelEvent('create', $model);
    }

    public function updated(Model $model): void
    {
        $key = spl_object_id($model);
        $oldValues = self::$oldValues[$key] ?? [];
        unset(self::$oldValues[$key]);

        ActivityLogger::logModelEvent('update', $model, $oldValues);
    }

    public function deleted(Model $model): void
    {
        ActivityLogger::logModelEvent('delete', $model, $model->getOriginal());
    }
}
