<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

trait SafeRefreshDatabase
{
    use RefreshDatabase {
        beforeRefreshingDatabase as protected laravelBeforeRefreshingDatabase;
    }

    protected function beforeRefreshingDatabase()
    {
        if (! $this->usingInMemoryDatabases()) {
            throw new RuntimeException(
                'Bloqueado: los tests intentaron refrescar la base de datos "'
                .config('database.default')
                .'" ('
                .config('database.connections.'.config('database.default').'.database')
                .'). Los tests deben usar sqlite :memory: (.env.testing) para no borrar tu MySQL de desarrollo.'
            );
        }

        $this->laravelBeforeRefreshingDatabase();
    }
}
