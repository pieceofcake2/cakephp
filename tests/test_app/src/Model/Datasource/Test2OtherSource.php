<?php

namespace TestApp\Model\Datasource;

use Cake\Model\Datasource\DataSource;
use Cake\Model\Model;

class Test2OtherSource extends DataSource
{
    public function describe(Model|string $model): array|false|null
    {
        return compact('model');
    }

    public function listSources(?array $data = null): ?array
    {
        return ['test_source'];
    }

    public function create(
        Model $model,
        ?array $fields = null,
        ?array $values = null,
    ): bool {
        return true;
    }

    public function read(
        Model $model,
        array $queryData = [],
        ?int $recursive = null,
    ): array|false {
        return compact('model', 'queryData');
    }

    public function update(
        Model $model,
        ?array $fields = [],
        ?array $values = [],
        mixed $conditions = null,
    ): bool {
        return true;
    }

    public function delete(
        Model $model,
        mixed $conditions = null,
    ): bool {
        return true;
    }
}
