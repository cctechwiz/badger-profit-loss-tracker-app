<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\InscriptionController;

class InscriptionControllerTest extends TestCase
{

    // Test simple single purchase/sale profit
    public function testCalculatePL_SinglePurchaseAndSale_Profit() {
        $inscriptionController = new InscriptionController();

        $transactions = collect([
            [
                'type' => 'receive',
                'amount' => 1,
                'priceBTC' => 10,
                'priceUSD' => 100,
                'epoch' => 1,
                'date' => '2023-12-05',
            ],
            [
                'type' => 'send',
                'amount' => 1,
                'priceBTC' => 12,
                'priceUSD' => 120,
                'epoch' => 2,
                'date' => '2023-12-05',
            ]
        ]);

        $result = $inscriptionController->calculatePL('BTC', $transactions);

        $this->assertEquals(2, $result['totalProfitBTC']);
        $this->assertEquals(20, $result['totalProfitUSD']);
    }

    // Test simple single purchase/sale loss
    public function testCalculatePL_SinglePurchaseAndSale_Loss() {
        $inscriptionController = new InscriptionController();

        $transactions = collect([
            [
                'type' => 'receive',
                'amount' => 1,
                'priceBTC' => 20,
                'priceUSD' => 200,
                'epoch' => 1,
                'date' => '2023-12-05',
            ],
            [
                'type' => 'send',
                'amount' => 1,
                'priceBTC' => 12,
                'priceUSD' => 120,
                'epoch' => 2,
                'date' => '2023-12-05',
            ]
        ]);

        $result = $inscriptionController->calculatePL('BTC', $transactions);

        $this->assertEquals(-8, $result['totalProfitBTC']);
        $this->assertEquals(-80, $result['totalProfitUSD']);
    }

    // Test multiple purchases to one sale profit
    public function testCalculatePL_MultiplePurchaseSingleSale_Profit() {
        $inscriptionController = new InscriptionController();

        $transactions = collect([
            [
                'type' => 'receive',
                'amount' => 1,
                'priceBTC' => 10,
                'priceUSD' => 100,
                'epoch' => 1,
                'date' => '2023-12-05',
            ],
            [
                'type' => 'receive',
                'amount' => 1,
                'priceBTC' => 12,
                'priceUSD' => 120,
                'epoch' => 2,
                'date' => '2023-12-05',
            ],
            [
                'type' => 'send',
                'amount' => 2,
                'priceBTC' => 15,
                'priceUSD' => 150,
                'epoch' => 3,
                'date' => '2023-12-05',
            ]
        ]);

        $result = $inscriptionController->calculatePL('BTC', $transactions);

        $this->assertEquals(8, $result['totalProfitBTC']);
        $this->assertEquals(80, $result['totalProfitUSD']);
    }

    // Test multiple purchases to one sale loss
    public function testCalculatePL_MultiplePurchaseSingleSale_Loss() {
        $inscriptionController = new InscriptionController();

        $transactions = collect([
            [
                'type' => 'receive',
                'amount' => 1,
                'priceBTC' => 20,
                'priceUSD' => 200,
                'epoch' => 1,
                'date' => '2023-12-05',
            ],
            [
                'type' => 'receive',
                'amount' => 1,
                'priceBTC' => 22,
                'priceUSD' => 220,
                'epoch' => 2,
                'date' => '2023-12-05',
            ],
            [
                'type' => 'send',
                'amount' => 2,
                'priceBTC' => 15,
                'priceUSD' => 150,
                'epoch' => 3,
                'date' => '2023-12-05',
            ]
        ]);

        $result = $inscriptionController->calculatePL('BTC', $transactions);

        $this->assertEquals(-12, $result['totalProfitBTC']);
        $this->assertEquals(-120, $result['totalProfitUSD']);
    }

    // Test purchase amount exceeds sale amount profit
    public function testCalculatePL_PurchaseExceedsSales_Profit() {
        $inscriptionController = new InscriptionController();

        $transactions = collect([
            [
                'type' => 'receive',
                'amount' => 2,
                'priceBTC' => 10,
                'priceUSD' => 100,
                'epoch' => 1,
                'date' => '2023-12-05',
            ],
            [
                'type' => 'send',
                'amount' => 1,
                'priceBTC' => 15,
                'priceUSD' => 150,
                'epoch' => 2,
                'date' => '2023-12-05',
            ]
        ]);

        $result = $inscriptionController->calculatePL('BTC', $transactions);

        $this->assertEquals(5, $result['totalProfitBTC']);
        $this->assertEquals(50, $result['totalProfitUSD']);
    }

    // Test purchase amount exceeds sale amount loss
    public function testCalculatePL_PurchaseExceedsSales_Loss() {
        $inscriptionController = new InscriptionController();

        $transactions = collect([
            [
                'type' => 'receive',
                'amount' => 2,
                'priceBTC' => 20,
                'priceUSD' => 200,
                'epoch' => 1,
                'date' => '2023-12-05',
            ],
            [
                'type' => 'send',
                'amount' => 1,
                'priceBTC' => 15,
                'priceUSD' => 150,
                'epoch' => 2,
                'date' => '2023-12-05',
            ]
        ]);

        $result = $inscriptionController->calculatePL('BTC', $transactions);

        $this->assertEquals(-5, $result['totalProfitBTC']);
        $this->assertEquals(-50, $result['totalProfitUSD']);
    }
}
