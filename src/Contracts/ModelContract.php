<?php

namespace Statikbe\Surveyhero\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 */
interface ModelContract
{
    /**
     * Begin querying the model.
     *
     * @return Builder
     */
    public static function query();

    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \LogicException
     */
    public function delete();

    /**
     * Returns the table name of the model.
     */
    public function getTable(): string;
}
