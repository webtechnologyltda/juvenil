<?php

namespace App\Support\Financeiro;

use Illuminate\Database\Eloquent\Builder;

final class SelectRegistrationIdentityColumns
{
    public function __invoke(Builder $query): Builder
    {
        return $query->select(['id', 'nome']);
    }
}
