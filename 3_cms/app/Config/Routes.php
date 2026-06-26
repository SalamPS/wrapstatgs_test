<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Dashboard::index');

$routes->group('users', static function (RouteCollection $routes) {
	$routes->get('/', 'UsersController::index');
	$routes->get('create', 'UsersController::create');
	$routes->post('store', 'UsersController::store');
	$routes->get('edit/(:num)', 'UsersController::edit/$1');
	$routes->post('update/(:num)', 'UsersController::update/$1');
	$routes->post('delete/(:num)', 'UsersController::delete/$1');
});

$routes->group('products', static function (RouteCollection $routes) {
	$routes->get('/', 'ProductsController::index');
	$routes->get('create', 'ProductsController::create');
	$routes->post('store', 'ProductsController::store');
	$routes->get('edit/(:num)', 'ProductsController::edit/$1');
	$routes->post('update/(:num)', 'ProductsController::update/$1');
	$routes->post('delete/(:num)', 'ProductsController::delete/$1');
});

$routes->group('transactions', static function (RouteCollection $routes) {
	$routes->get('/', 'TransactionsController::index');
	$routes->get('create', 'TransactionsController::create');
	$routes->post('store', 'TransactionsController::store');
	$routes->get('edit/(:num)', 'TransactionsController::edit/$1');
	$routes->post('update/(:num)', 'TransactionsController::update/$1');
	$routes->post('delete/(:num)', 'TransactionsController::delete/$1');
});
