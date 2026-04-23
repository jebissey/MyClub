<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class LoanApi implements RouteInterface
{
	private array $routes = [];

	public function __construct(private ApiFactory $apiFactory) {}

	public function get(): array
	{
		$loanApi = fn() => $this->apiFactory->makeLoanApi();

		$this->routes[] = new Route('GET /api/loan/calendar',                    $loanApi, 'getCalendarEvents');
		$this->routes[] = new Route('GET /api/loan/availability/@itemId:[0-9]+', $loanApi, 'getAvailability');

		$this->routes[] = new Route('GET  /api/loan/items',                   $loanApi, 'getItems');
		$this->routes[] = new Route('GET  /api/loan/item/@id:[0-9]+',         $loanApi, 'getItem');
		$this->routes[] = new Route('POST /api/loan/item/save',               $loanApi, 'saveItem');
		$this->routes[] = new Route('POST /api/loan/item/delete/@id:[0-9]+',  $loanApi, 'deleteItem');

		$this->routes[] = new Route('GET  /api/loan/records',                  $loanApi, 'getLoans');
		$this->routes[] = new Route('GET  /api/loan/record/@id:[0-9]+',        $loanApi, 'getLoan');
		$this->routes[] = new Route('POST /api/loan/record/save',              $loanApi, 'saveLoan');
		$this->routes[] = new Route('POST /api/loan/record/return/@id:[0-9]+', $loanApi, 'returnLoan');
		$this->routes[] = new Route('POST /api/loan/record/cancel/@id:[0-9]+', $loanApi, 'cancelLoan');

		$this->routes[] = new Route('GET  /api/loan/reservations',                  $loanApi, 'getReservations');
		$this->routes[] = new Route('GET  /api/loan/reservation/@id:[0-9]+',        $loanApi, 'getReservation');
		$this->routes[] = new Route('POST /api/loan/reservation/save',              $loanApi, 'saveReservation');
		$this->routes[] = new Route('POST /api/loan/reservation/cancel/@id:[0-9]+', $loanApi, 'cancelReservation');

		return $this->routes;
	}
}