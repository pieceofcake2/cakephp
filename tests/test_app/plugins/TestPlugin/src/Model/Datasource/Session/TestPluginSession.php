<?php
/**
 * Test suite plugin session handler
 */

namespace TestPlugin\Model\Datasource\Session;

use Cake\Model\Datasource\Session\CakeSessionHandlerInterface;

class TestPluginSession implements CakeSessionHandlerInterface
{
    public function open(): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): mixed
    {
        return '';
    }

    public function write(string $id, mixed $data): bool
    {
        return true;
    }

    public function destroy(string $id): bool|int
    {
        return true;
    }

    public function gc(?int $expires = null): bool
    {
        return true;
    }
}
