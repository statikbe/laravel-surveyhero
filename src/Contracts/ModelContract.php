<?php

namespace Statikbe\Surveyhero\Contracts;

/**
 * @property int $id
 */
interface ModelContract
{
    /**
     * Begin querying the model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
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
}
