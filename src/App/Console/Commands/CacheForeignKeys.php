<?php

namespace Spatie\QueryBuilder\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;

class CacheForeignKeys extends Command
{
    protected $signature = 'query-builder:cache-foreign-keys';

    protected $description = 'Cache foreign keys for the QueryBuilder package.';

    public function __invoke(): void
    {
        $modelsDirectory = app_path('Models');

        $files = File::allFiles($modelsDirectory);

        $foreignKeys = [];
        $this->info('Fetching all models in App\Models...' . PHP_EOL);

        foreach ($files as $file) {
            // Build the full class name
            $fullClassName = 'App\\Models' . '\\' . $file->getRelativePath() . '\\' . pathinfo($file->getRelativePathname(), PATHINFO_FILENAME);
            $fullClassName = Str::replace('/', '\\', $fullClassName);

            // Check if the class exists
            if (class_exists($fullClassName) && is_subclass_of($fullClassName, Model::class)) {
                // Create a ReflectionClass instance
                $reflectionClass = new ReflectionClass($fullClassName);

                // Check if the class is instantiable
                if ($reflectionClass->isInstantiable()) {
                    // Instantiate the class
                    $instance = $reflectionClass->newInstance();
                    $table = $instance->getTable();

                    // Get all foreign keys for the table
                    $tableForeignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys($table);

                    // Add the foreign keys to the array
                    $foreignKeys[$table] = array_reduce($tableForeignKeys, function ($carry, $foreignKey) {
                        return array_merge($carry, $foreignKey->getLocalColumns());
                    }, []);

                    // Add the primary key to the array
                    $foreignKeys[$table][] = $instance->getKeyName();
                } else {
                    $this->error("The class $fullClassName is not instantiable.");
                }
            } else {
                $this->warn("The class $fullClassName does not exist or does not extend " . Model::class . '.');
            }
        }
        $this->info(PHP_EOL . 'Cached foreign keys for ' . count($foreignKeys) . ' tables.');

        Cache::forever('QUERY_BUILDER_FKS', $foreignKeys);
    }

    public static function getForTable(string $table): array
    {
        return Cache::get('QUERY_BUILDER_FKS')[$table] ?? [];
    }

}
