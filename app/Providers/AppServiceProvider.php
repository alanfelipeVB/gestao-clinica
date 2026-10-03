<?php

namespace App\Providers;

use App\Services\SiteService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Fora de produção, acusa consultas N+1 (relacionamento carregado sob demanda).
        Model::preventLazyLoading(! $this->app->isProduction());

        // Nome e logo da clínica disponíveis em todos os layouts (menu, login, site).
        View::composer('components.layouts.*', function ($view) {
            $site = app(SiteService::class);

            $view->with('marca', [
                'nome' => $site->textos()['site_nome'],
                'logo' => $site->urlLogo(),
            ]);
        });
    }
}
