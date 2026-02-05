<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Laravel adapter tests use Orchestra Testbench as the base test case.
| Core tests use the default PHPUnit TestCase.
|
*/

uses(Orchestra\Testbench\TestCase::class)
    ->beforeEach(function () {
        $this->app->register(\Codemetry\Laravel\CodemetryServiceProvider::class);
    })
    ->in(__DIR__ . '/../packages/laravel/tests');
