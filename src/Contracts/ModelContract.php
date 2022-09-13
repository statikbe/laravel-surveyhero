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
         * Create or update a record matching the attributes, and fill it with values.
         *
         * @param  array  $attributes
         * @param  array  $values
         * @return \Illuminate\Database\Eloquent\Model|static
         */
        public function updateOrCreate(array $attributes, array $values = []);

        public static function truncate();
    }
