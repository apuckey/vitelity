<?php namespace Core3net\Vitelity;

use Illuminate\Support\ServiceProvider;

class VitelityServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('core3net/vitelity');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['vitelity'] = $this->app->share(function($app)
		        {
		            return new Vitelity;
		        });
		$this->app->booting(function()
		{
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Vitelity', 'Core3net\Vitelity\Facades\Vitelity');
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('vitelity');
	}

}