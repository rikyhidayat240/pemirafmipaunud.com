<?php

namespace Tests;

use Illuminate\Database\Connection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Fluent;

abstract class TestCase extends BaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Connection::resolverFor('sqlite', function ($connection, $database, $prefix, $config) {
            return new class($connection, $database, $prefix, $config) extends SQLiteConnection {
                public function statement($query, $bindings = [])
                {
                    if (str_contains($query, 'MODIFY')) {
                        return true;
                    }
                    return parent::statement($query, $bindings);
                }

                protected function getDefaultSchemaGrammar()
                {
                    $grammar = new class($this) extends SQLiteGrammar {
                        public function compileDropForeign(Blueprint $blueprint, Fluent $command)
                        {
                            return 'SELECT 1';
                        }
                    };
                    return $grammar->setTablePrefix($this->tablePrefix);
                }
            };
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ProgramStudiSeeder::class);
    }
}
