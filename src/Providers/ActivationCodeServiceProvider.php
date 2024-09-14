<?php

namespace Crow\LaravelActivationCode\Providers;

use Crow\LaravelActivationCode\Console\Commands\ActivationCodeCommand;
use Illuminate\Support\ServiceProvider;

class ActivationCodeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadCustomCommands();
        $this->loadCustomConfig();
        $this->loadCustomPublished();
        $this->loadCustomLexicon();
    }

    private function loadCustomCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                ActivationCodeCommand::class
            );
        }
    }

    private function loadCustomConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/activation_code.php', 'activation_code');
    }

    private function loadCustomPublished(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../../config' => base_path('config')
                ],
                'config'
            );
        }
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../../migration' => database_path('migrations')
                ],
                'migration'
            );
        }
    }

    private function loadCustomLexicon(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'activationCode');
    }
}
