<?php namespace EloquentVersioned\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class VersioningScope implements Scope
{

    protected $extensions = ['WithOldVersions', 'OnlyOldVersions'];

    public function apply(Builder $builder, Model $model)
    {
        if (is_null($model::getSpecificMomentVal())) {
            $builder->where($model->getQualifiedIsCurrentVersionColumn(), 1);
        } else {
            $model = $builder->getModel();
            $modelIdColumn = \EloquentVersioned\Traits\Versioned::getModelIdColumn();
            $versionColumn = \EloquentVersioned\Traits\Versioned::getVersionColumn();
            $table = $model->getTable();

            $builder->whereRaw("($modelIdColumn, $versionColumn) IN (SELECT $modelIdColumn, MAX($versionColumn) FROM $table WHERE actual_from <= ? AND actual_to > ? GROUP BY $modelIdColumn, $versionColumn)", [$model::getSpecificMomentVal(), $model::getSpecificMomentVal()]);
        }
    }

    /**
     * @param Builder $builder
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    /**
     * @param Builder $builder
     */
    protected function addWithOldVersions(Builder $builder)
    {
        $builder->macro('withOldVersions', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * @param Builder $builder
     */
    protected function addOnlyOldVersions(Builder $builder)
    {
        $builder->macro('onlyOldVersions', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where($model->getQualifiedIsCurrentVersionColumn(),0);

            return $builder;
        });
    }
}
