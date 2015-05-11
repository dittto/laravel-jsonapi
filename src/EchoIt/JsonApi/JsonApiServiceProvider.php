<?php
namespace EchoIt\JsonApi;

use Illuminate\Support\ServiceProvider;
use EchoIt\JsonApi\JsonApiRequest;

class JsonApiServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		//
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind('EchoIt\JsonApi\JsonApiRequest', function($app){
			return new JsonApiRequest($app['request']);
		});
	}

}
