<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class InscriptionController extends Controller
{
    private static $BTCHistoricalData;

    public function summary()
    {
        return Inertia::render('Profile/pl');
    }

    public function data(Request $request)
    {
        Auth::user()->isFreeUser = $this->isFreeUser(Auth::user());

        self::$BTCHistoricalData = $this->loadBitcoinHistoricalValueData();

        $wallets = $request->user()->wallets()->get();

        [$statements, $ordAssets, $brcAssets] = $this->generateStatements($wallets);

        // Sort by profit from high to low
        usort($statements, function ($a, $b) {
            return $b['totalProfitBTC'] <=> $a['totalProfitBTC'];
        });

        if (Auth::user()->isFreeUser) {
            $statements = $this->filterForFreeUser($statements);
        } else {
            $statements = $this->filterForLicencedUser($statements);
        }

        // Breakout statements by type (BRC20 vs Ordinals)
        $brcStatements = [];
        $ordStatements = [];
        foreach ($statements as $statement) {
            if (str_starts_with($statement['ticker'], 'brc20_')) {
                $statement['ticker'] = str_replace('brc20_', '', $statement['ticker']);
                $brcStatements[] = $statement;
            } else {
                $ordStatements[] = $statement;
            }
        }

        // Calculate Lifetime Realized PL
        $realizedPL = array_reduce($statements, function ($carry, $statement) {
            return $carry + $statement['totalProfitBTC'];
        }, 0);

        // Calculate Realized PL broken down by year
        $realizedPLByYear = [];
        foreach ($statements as $statement) {
            foreach ($statement['eventsByYear'] as $year => $events) {
                $totalPriceForYear = array_reduce($events, function ($carry, $event) {
                    return $carry + $event['profitBTC'];
                }, 0);
                if (! isset($realizedPLByYear[$year])) {
                    $realizedPLByYear[$year] = 0;
                }
                $realizedPLByYear[$year] += $totalPriceForYear;
            }
        }

        // Calculate Lifetime Transaction Count
        $transactionCount = array_reduce($statements, function ($carry, $statement) {
            return $carry + count($statement['events']);
        }, 0);

        // Calculate Transaction Counts broken down by year
        $transactionCountsByYear = [];
        foreach ($statements as $statement) {
            foreach ($statement['eventsByYear'] as $year => $events) {
                if (! isset($transactionCountsByYear[$year])) {
                    $transactionCountsByYear[$year] = 0;
                }
                $transactionCountsByYear[$year] += count($events);
            }
        }

        // Calculate Unrealized PL
        $unrealizedOrdPL = array_reduce($ordAssets, function ($carry, $asset) {
            return $carry + $asset['unrealizedValueBTC'];
        }, 0);
        $unrealizedBrcPL = array_reduce($brcAssets, function ($carry, $asset) {
            return $carry + $asset['unrealizedValueBTC'];
        }, 0);
        $unrealizedPL = $unrealizedOrdPL + $unrealizedBrcPL;

        return json_encode([
            'brcTransactions' => $brcStatements,
            'ordTransactions' => $ordStatements,
            'ordAssets' => $ordAssets,
            'brcAssets' => $brcAssets,
            'realizedPL' => $realizedPL,
            'unrealizedPL' => $unrealizedPL,
            'realizedPLByYear' => $realizedPLByYear,
            'transactionCount' => $transactionCount,
            'transactionCountsByYear' => $transactionCountsByYear,
            'isFreeUser' => Auth::user()->isFreeUser,
        ]);
    }

    public function assets()
    {
        return Inertia::render('Profile/assets');
    }

    public function assetData(Request $request)
    {
        self::$BTCHistoricalData = $this->loadBitcoinHistoricalValueData();

        $wallets = $request->user()->wallets()->get();

        $withBRCValue = false;
        $ordAssets = [];
        $brcAssets = [];
        foreach ($wallets as $wallet) {
            // [$ord, $brc] = $this->getHeldAssets($wallet, $withBRCValue);
            // $ordAssets[] = $ord;
            // $brcAssets[] = $brc;
            $ordAssets[] = [];
            $brcAssets[] = [];
        }

        $combinedOrdAssets = $this->mergeHeldAssets($ordAssets);
        $combinedBrcAssets = $this->mergeHeldAssets($brcAssets);

        return json_encode([
            'brcAssets' => $combinedBrcAssets,
            'ordAssets' => $combinedOrdAssets,
        ]);
    }

    public function downloadTSV(Request $request)
    {
        $statements = collect($request->input('transactions'));
        $headers = [
            'ticker',                 // A
            'type',                   // B
            'date',                   // C
            'basisDate',              // D
            'amount',                 // E
            'priceBTC',               // F
            'priceUSD',               // G
            'bitcoinPriceAtPurchase', // H
            'bitcoinPriceAtSale',     // I
            'costBasisBTC',           // J
            'profitBTC',              // K
            'costBasisUSD',           // L
            'profitUSD',              // M
            'transactionWebURL',      // N
            'inscriptionId',          // O
        ];

        $rowNumber = 2; //Start at 2 to skip the header which is added after map

        $tsvString = $statements
            ->map(function ($transaction) use ($headers, &$rowNumber) {
                $values = array_intersect_key((array) $transaction, array_flip($headers));

                $values['costBasisUSD'] = "=ROUND((J$rowNumber*H$rowNumber),2)";
                $values['profitUSD'] = "=ROUND(((F$rowNumber*I$rowNumber)-(J$rowNumber*H$rowNumber)),2)";
                $rowNumber++;

                return implode('	', $values);
            })
            ->prepend(implode('	', $headers))
            ->implode("\n");

        return response()->streamDownload(function () use ($tsvString) {
            echo $tsvString;
        }, 'ProfitLoss.tsv', [
            'Content-Type' => 'text/tab-separated-values',
            'Content-Disposition' => 'attachment; filename="ProfitLoss.tsv"',
        ]);
    }

    public function generateStatements(Collection $wallets)
    {
        $transactions = [];
        $withBRCValue = false; // TODO: enable this once Unisat Token can fetch BRC values
        $ordAssets = [];
        $brcAssets = [];
        foreach ($wallets as $wallet) {
            $transactions[] = $this->getTransactions($wallet, 'buying_broadcasted');
            // [$ord, $brc] = $this->getHeldAssets($wallet, $withBRCValue);
            // $ordAssets[] = $ord;
            // $brcAssets[] = $brc;
            $ordAssets[] = [];
            $brcAssets[] = [];
        }

        $groupedTransactions = collect($transactions)
            ->flatMap(function ($transaction) {
                return $transaction;
            })
            ->groupBy(function ($transaction) {
                return $transaction['ticker'] ?: 'uncategorized transactions';
            });

        $statements = [];
        $groupedTransactions->each(function ($tickerTransactions, $ticker) use (&$statements) {
            $statements[] = ($this->calculatePL($ticker, $tickerTransactions));
        });

        $combinedOrdAssets = $this->mergeHeldAssets($ordAssets);
        $combinedBrcAssets = $this->mergeHeldAssets($brcAssets);

        return [$statements, $combinedOrdAssets, $combinedBrcAssets];
    }

    public function calculatePL(string $ticker, Collection $tickerTransactions): array
    {
        $purchases = $tickerTransactions->filter(function ($transaction) {
            // receive will have a cost basis price, transfer-in will have a cost basis of 0
            return ($transaction['type'] == 'buy') or ($transaction['type'] == 'transfer-in');
        })->sortBy('epoch')->all();

        $sales = $tickerTransactions->filter(function ($transaction) {
            // receive will have a sale price, transfer-out will have a sale price of 0
            return ($transaction['type'] == 'sale') or ($transaction['type'] == 'transfer-out');
        })->sortBy('epoch')->all();

        $totalProfitBTC = 0.0;
        $totalProfitUSD = 0.0;
        $events = [];
        $eventsByYear = [];
        $totalProfitBTCByYear = [];

        foreach ($sales as $sale) {
            $quantityToSell = $sale['amount'];
            $saleProfitBTC = 0.0;
            $saleProfitUSD = 0.0;

            $costBasisBTC = 0.0;
            $costBasisUSD = 0.0;

            $bitcoinPriceAtPurchase = 0.0;
            $bitcoinPriceAtSale = $this->getAdjustedClosedValueBTC($sale['date']);

            $basisDate = 'unknown';

            // If there are no purchases, the sale profit is the sale price
            if (count($purchases) < 1) {
                $saleProfitBTC = $sale['priceBTC'];
                $saleProfitUSD = $sale['priceUSD'];
            }

            while ($quantityToSell > 0 && count($purchases) > 0) {
                $purchase = reset($purchases);
                $costBasisBTC = $purchase['priceBTC'] * ($sale['amount'] / $purchase['amount']);
                $costBasisUSD = $purchase['priceUSD'];
                $bitcoinPriceAtPurchase = $this->getAdjustedClosedValueBTC($purchase['date']);

                if ($quantityToSell >= $purchase['amount']) {
                    array_shift($purchases);
                    $basisDate = $purchase['date'];
                    $saleProfitBTC += ($sale['priceBTC'] - $purchase['priceBTC']);
                    $saleProfitUSD += ($sale['priceUSD'] - $purchase['priceUSD']);
                    $quantityToSell -= $purchase['amount'];
                } else {
                    $basisDate = $purchase['date'];
                    $purchase['amount'] -= $quantityToSell;
                    $saleProfitBTC += ($sale['priceBTC'] - $purchase['priceBTC']);
                    $saleProfitUSD += ($sale['priceUSD'] - $purchase['priceUSD']);
                    $quantityToSell = 0;
                    array_unshift($purchases, $purchase);
                }
            }

            $totalProfitBTC += $saleProfitBTC;
            $totalProfitUSD += $saleProfitUSD;
            $txid = $sale['txid'];
            $txURL = '';
            if ($txid != null) {
                $txURL = "https://mempool.space/tx/$txid";
            }
            $event = [
                'type' => $sale['type'],
                'date' => $sale['date'],
                'epoch' => $sale['epoch'],
                'basisDate' => $basisDate,
                'amount' => $sale['amount'],
                'priceBTC' => $sale['priceBTC'],
                'priceUSD' => $sale['priceUSD'],
                'costBasisBTC' => $costBasisBTC,
                'costBasisUSD' => $costBasisUSD,
                'profitBTC' => $saleProfitBTC,
                'profitUSD' => $saleProfitUSD,
                'bitcoinPriceAtPurchase' => $bitcoinPriceAtPurchase,
                'bitcoinPriceAtSale' => $bitcoinPriceAtSale,
                'transactionWebURL' => $txURL,
                'inscriptionId' => $sale['inscriptionId'],
            ];
            $events[] = $event;

            $year = date('Y', strtotime($sale['date']));
            if (! isset($eventsByYear[$year])) {
                $eventsByYear[$year] = [];
            }
            array_push($eventsByYear[$year], $event);

            if (! isset($totalProfitBTCByYear[$year])) {
                $totalProfitBTCByYear[$year] = 0;
            }
            $totalProfitBTCByYear[$year] += $saleProfitBTC;
        }

        $purchases = $tickerTransactions->filter(function ($transaction) {
            return ($transaction['type'] == 'buy') or ($transaction['type'] == 'transfer-in');
        })->sortBy('epoch')->all();

        foreach ($purchases as $purchase) {
            $txid = $purchase['txid'];
            $txURL = '';
            if ($txid != null) {
                $txURL = "https://mempool.space/tx/$txid";
            }
            $event = [
                'type' => $purchase['type'],
                'date' => $purchase['date'],
                'epoch' => $purchase['epoch'],
                'basisDate' => $purchase['date'],
                'amount' => $purchase['amount'],
                'priceBTC' => $purchase['priceBTC'],
                'priceUSD' => $purchase['priceUSD'],
                'costBasisBTC' => $purchase['priceBTC'],
                'costBasisUSD' => $purchase['priceUSD'],
                'profitBTC' => 0,
                'profitUSD' => 0,
                'bitcoinPriceAtPurchase' => $this->getAdjustedClosedValueBTC($purchase['date']),
                'bitcoinPriceAtSale' => 0,
                'transactionWebURL' => $txURL,
                'inscriptionId' => $purchase['inscriptionId'],
            ];
            $events[] = $event;

            $year = date('Y', strtotime($purchase['date']));
            if (! isset($eventsByYear[$year])) {
                $eventsByYear[$year] = [];
            }
            array_push($eventsByYear[$year], $event);
        }

        usort($events, function ($a, $b) {
            return $a['epoch'] <=> $b['epoch'];
        });

        $statement = [
            'id' => 0,
            'ticker' => $ticker,
            'totalProfitBTC' => $totalProfitBTC,
            'totalProfitBTCByYear' => $totalProfitBTCByYear,
            'totalProfitUSD' => $totalProfitUSD,
            'events' => $events,
            'eventsByYear' => $eventsByYear,
        ];

        return $statement;
    }

    private function getHeldAssets(Wallet $wallet, bool $withValue)
    {
        $ordAssets = $this->getHeldOrdAssets($wallet);
        $brcAssets = $this->getHeldBrcAssets($wallet, $withValue);

        return [$ordAssets, $brcAssets];
    }

    private function mergeHeldAssets(array $assets)
    {
        $combinedAssets = collect();
        foreach ($assets as $asset) {
            $combinedAssets = $combinedAssets->merge($asset);
        }

        $heldAssets = $combinedAssets
            ->groupBy('ticker')
            ->map(function ($items) {
                $ticker = $items[0]['ticker'];
                $iconURL = $items[0]['iconURL'];
                $heldSum = $items->sum('heldCount');
                $valueFloorSum = $items->sum('unrealizedValueBTC');
                $balanceByWallets = $items->pluck('balanceByWallet')->all();

                return [
                    'ticker' => $ticker,
                    'iconURL' => $iconURL,
                    'heldCount' => $heldSum,
                    'unrealizedValueBTC' => $valueFloorSum,
                    'balanceByWallets' => $balanceByWallets,
                ];
            })->values()->toArray();

        return $heldAssets;
    }

    private function getHeldOrdAssets(Wallet $wallet): Collection
    {
        $address = $wallet->address;
        $token = config('auth.ordyssey_api_token');

        $response = Http::acceptJson()
            ->withToken($token)
            ->timeout(120)
            ->get("https://open-api.ordyssey.com/v1/dex/wallet_tracker/$address/assets");

        $response->throwUnlessStatus(200);

        $assets = collect($response->json()['assets'])
            ->map(function ($item) use ($address) {
                return [
                    'ticker' => $item['collection']['name'] ?? null,
                    'iconURL' => $item['collection']['icon_url'] ?? '',
                    'heldCount' => $item['count'] ?? null,
                    'unrealizedValueBTC' => ($item['total_value_by_collection_floor'] / 100000000) ?? 0,
                    'balanceByWallet' => [
                        'address' => $address,
                        'balance' => $item['count'] ?? 0,
                        'value' => ($item['total_value_by_collection_floor'] / 100000000) ?? 0,
                    ],
                ];
            })
            ->filter(function ($item) {
                return ! in_array(null, $item, true);
            });

        return $assets;
    }

    private function getHeldBrcAssets(Wallet $wallet, bool $withValue): Collection
    {
        $address = $wallet->address;
        $token = config('auth.unisat_api_token');
        $limit = 100;
        $offset = 0;
        $total = null;
        $tickers = [];

        // TODO: Batch these to stay under the rate limit of 10/sec

        do {
            $response = Http::acceptJson()->withToken($token)
                ->get("https://open-api.unisat.io/v1/indexer/address/$address/brc20/summary?start=$offset&limit=$limit");

            $response->throwUnlessStatus(200);

            $data = $response->json()['data'];

            if ($total === null) {
                $total = $data['total'];
            }

            $tickers[] = $data['detail'];

            $offset += $limit;
        } while ($offset < $total);

        $tickers = array_merge(...$tickers);

        $assets = collect();
        foreach ($tickers as $idx => $ticker) {
            if ($ticker['overallBalance'] != 0) {
                $unrealizedValueBTC = 0;
                if ($withValue) {
                    $unrealizedValueBTC = $this->getCurrentBrcBTCValue($ticker['ticker']);
                    // Every 5 tickers sleep for 1 second - very naive approach to avoiding rate limit of 10/sec
                    if ($idx % 5 == 0) {
                        sleep(1);
                    }
                }
                $assets[] = [
                    'ticker' => $ticker['ticker'],
                    'iconURL' => 'https://cdn04.cryptoslam.io/cs/sats-brc-20-nfts.png',
                    'heldCount' => $ticker['overallBalance'],
                    'unrealizedValueBTC' => $unrealizedValueBTC,
                    'balanceByWallet' => [
                        'address' => $address,
                        'balance' => $ticker['overallBalance'],
                        'value' => $unrealizedValueBTC,
                    ],
                ];
            }
        }

        return $assets;
    }

    private function getCurrentBrcBTCValue(string $ticker)
    {
        $token = config('auth.unisat_api_token');

        $response = Http::acceptJson()
            ->withToken($token)
            ->post('https://open-api.unisat.io/v3/market/brc20/auction/brc20_types_specified', [
                'tick' => $ticker,
                'timeType' => 'day1',
            ]);

        $response->throwUnlessStatus(200);

        $data = collect($response->json()['data']);

        $currentPrice = 0;
        if (isset($data['curPrice'])) {
            $currentPrice = $data['curPrice'] / 100000000;
        }

        return $currentPrice;
    }

    private function getTransactions(Wallet $wallet, string $type): Collection
    {
        $address = $wallet->address;
        $meApiKey = config('auth.magiceden_api_token');
        $meUrl = 'https://api-mainnet.magiceden.dev/v2/ord/btc/activities';

        $history = collect();
        $meKinds = ['buying_broadcasted', 'mint_broadcasted', 'transfer', 'coll_offer_fulfill_broadcasted'];

        foreach ($meKinds as $kind) {
            try {
                $offset = 0;
                do {
                    $response = Http::withHeaders([
                        'Authorization' => "Bearer {$meApiKey}",
                    ])->get($meUrl, [
                        'ownerAddress' => $address,
                        'kind' => $kind,
                        'limit' => 100,
                        'offset' => $offset,
                    ]);

                    $response->throwUnlessStatus(200);

                    $activities = $response->json()['activities'] ?? [];

                    $history = $history->merge(
                        collect($activities)->map(function ($item) use ($address) {
                            $epoch = strtotime($item['createdAt']);
                            $date = gmdate('Y-m-d H:i', $epoch);

                            $type = $this->getMeKind(
                                $item['kind'],
                                $item['oldOwner'],
                                $item['newOwner'],
                                $address
                            );

                            // Convert price from BTC to satoshis if needed
                            $priceBtc = $item['listedPrice'];

                            [$btc, $usd] = $this->getHistoricalValueBTC($priceBtc, $date);

                            return [
                                'ticker' => $item['collectionSymbol'],
                                'type' => strtolower($type),
                                'inscriptionId' => $item['tokenId'],
                                'txid' => $item['txId'],
                                'from' => $item['oldOwner'],
                                'to' => $item['newOwner'],
                                'amount' => '1', // MagicEden always returns 1 for NFTs
                                'epoch' => $epoch,
                                'date' => $date,
                                'priceBTC' => $btc,
                                'priceUSD' => $usd,
                            ];
                        })
                    );

                    $offset += 100;
                } while (! empty($activities));

            } catch (\Exception $e) {
                \Log::error('MagicEden API Error: '.$e->getMessage());

                continue;
            }
        }

        return $history;
    }

    private function getMeKind(string $kind, string $from, string $to, string $ownerAddress): string
    {
        if ($kind === 'buying_broadcasted' && $from === $ownerAddress) {
            return 'sale';
        } elseif ($kind === 'buying_broadcasted' && $to === $ownerAddress) {
            return 'buy';
        } elseif ($kind === 'coll_offer_fulfill_broadcasted' && $to === $ownerAddress) {
            return 'buy';
        } elseif ($kind === 'mint_broadcasted') {
            return 'mint';
        // } elseif ($kind === 'transfer' && $from === $ownerAddress) {
        //     return 'transfer-out';
        // } elseif ($kind === 'transfer' && $to === $ownerAddress) {
        //     return 'transfer-in';
        } else {
            return 'unknown';
        }
    }

    private function getHistoricalValueBTC(float $amount, string $date): array
    {
        $btc = ($amount / 100000000);

        $value = $btc * $this->getAdjustedClosedValueBTC($date);
        $formattedAmount = number_format($value, 2);
        $usd = floatval(str_replace(',', '', $formattedAmount));

        return [$btc, $usd];
    }

    private function getAdjustedClosedValueBTC(string $date): float
    {
        $dateTime = new DateTime($date);
        $dateTime->setTime(0, 0, 0); // Set time to midnight
        $searchTimestamp = $dateTime->getTimestamp(); // Get Unix timestamp for the provided date

        // Find the row in the collection where the timestamp matches the search timestamp
        $foundRow = self::$BTCHistoricalData->first(function ($row) use ($searchTimestamp) {
            return $row['timestamp'] === $searchTimestamp;
        });

        if ($foundRow === null) {
            return 0.0;
        }

        // Get the adjusted close value
        $historicalValue = $foundRow['adjclose'];

        return floatval($historicalValue);
    }

    // // private function getAdjustedClosedValueBTC(string $date): float
    // // {
    // //     $dateTime = new DateTime($date);
    // //     $searchDate = $dateTime->format('Y-m-d');

    // //     $foundRows = self::$BTCHistoricalData->first(function ($row) use ($searchDate) {
    // //         return $row[0] === $searchDate;
    // //     });

    // //     if ($foundRows == null) {
    // //         return 0.0;
    // //     }
    // //     $historicalValue = $foundRows[5];

    // //     return floatval(str_replace(',', '', $historicalValue));
    // // }

    private function loadBitcoinHistoricalValueData(): Collection
    {
        $yearStart = 1672531200; // 2023 Year Start Epoch

        $yearEnd = strtotime('yesterday 23:59:59');
        //$yearEnd = strtotime(date('Y-12-31 23:59:59')); //Current Year End Epoch
        // $yearEnd = 1704067199; // 2023 Year End Epoch

        $fullData = collect();
        $currentStart = $yearStart;

        do {
            // Adjust period2 to be one year ahead of currentStart or yearEnd, whichever is smaller
            $nextEnd = min($currentStart + (365 * 24 * 60 * 60), $yearEnd);

            $response = Http::withQueryParameters([
                'period1' => $currentStart,
                'period2' => $nextEnd,
                'interval' => '1d',
            ])->get('https://query1.finance.yahoo.com/v7/finance/chart/BTC-USD');

            $response->throwUnlessStatus(200);

            $data = json_decode($response->body(), true);
            $chartData = $data['chart']['result'][0];
            $timestamps = $chartData['timestamp'];
            $adjclose = $chartData['indicators']['adjclose'][0]['adjclose'];

            // Combine the timestamps and adjclose values into a collection
            $historicalData = collect($timestamps)->map(function ($timestamp, $index) use ($adjclose) {
                return [
                    'timestamp' => $timestamp,
                    'adjclose' => $adjclose[$index] ?? null,
                ];
            })->filter(function ($item) {
                return $item['adjclose'] !== null; // Filter out any null values
            });

            $fullData = $fullData->merge($historicalData);

            // Update the current start to the next day after the current period's end
            $currentStart = $nextEnd + 1;

        } while ($currentStart <= $yearEnd);

        return $fullData;
    }

    // // private function loadBitcoinHistoricalValueData(): Collection
    // // {
    // //     //$yearStart = strtotime(date('Y-01-01 00:00:00')); //Current Year Start Epoch
    // //     $yearStart = 1672531200; //2023 Year Start Epoch

    // //     $yearEnd = strtotime(date('Y-12-31 23:59:59')); //Current Year End Epoch
    // //     //$yearEnd = 1704067199 //2023 Year End Epoch

    // //     $response = Http::withQueryParameters([
    // //         'period1' => $yearStart,
    // //         'period2' => $yearEnd,
    // //         'interval' => '1d',
    // //         'events' => 'history',
    // //         'includeAdjustedClose' => 'true',
    // //     ])->get('https://query1.finance.yahoo.com/v7/finance/download/BTC-USD');

    // //     // TODO: Replace the above with --> https://query1.finance.yahoo.com/v7/finance/chart/BTC-USD?period1=1672531200&period2=1704067199&interval=1d
    // //     // Parsing data below will also have to change to pull the historical data from the new timeseries format
    // //     // We'll also need to loop over it to ensure the full date range is include since each call seems to only return 365 days of data at a time
    // //     // New data format:
    // //     // {
    // //     //   "chart": {
    // //     //     "result": [
    // //     //       {
    // //     //         "meta": {
    // //     //           ...
    // //     //         },
    // //     //         "timestamp": [
    // //     //           1672531200,
    // //     //           1672617600,
    // //     //           1672704000,
    // //     //           ...,
    // //     //         ],
    // //     //         "indicators": {
    // //     //           "quote": [
    // //     //             {
    // //     //               "open": [
    // //     //                 ...,
    // //     //               ],
    // //     //               "low": [
    // //     //                 ...,
    // //     //               ],
    // //     //               "close": [
    // //     //                 ...,
    // //     //               ],
    // //     //               "high": [
    // //     //                 ...,
    // //     //               ],
    // //     //               "volume": [
    // //     //                 ...,
    // //     //               ]
    // //     //             }
    // //     //           ],
    // //     //           "adjclose": [
    // //     //             {
    // //     //               "adjclose": [
    // //     //                 16625.080078125,
    // //     //                 16688.470703125,
    // //     //                 16679.857421875,
    // //     //                 ...,
    // //     //               ]
    // //     //             }
    // //     //           ]
    // //     //         }
    // //     //       }
    // //     //     ],
    // //     //     "error": null
    // //     //   }
    // //     // }
    // //     //
    // //     // In this data we the timestamp array contains unix epoch times corresponding to the day the historical data.
    // //     // the adjclose array follows the same index as the timestamp array, e.g. timestamp[0] is the date of adjclose[0][0]
    // //     // we only care about the the inner adjclose array. the outer array is always just index 0 to get to the inner adjclose object array with the actual data

    // //     $response->throwUnlessStatus(200);

    // //     $data = $response->body();
    // //     $csvData = Collection::make(explode("\n", $data))
    // //         ->map(function ($line) {
    // //             return str_getcsv($line);
    // //         })
    // //         ->filter(function ($row) {
    // //             return count($row) > 0;
    // //         });

    // //     return $csvData;
    // // }

    private function isFreeUser(User $user): bool
    {
        return $user->licenses()->count() == 0;
    }

    private function userLicenseYears(User $user): array
    {
        $licenses = $user->licenses()->get();

        $allYears = [];
        foreach ($licenses as $license) {
            $allYears = array_merge($allYears, $license['years']);
        }
        $allYears = array_unique($allYears);

        return $allYears;
    }

    // Free User Permissions:
    // - Only data from 2023 (not a rolling window)
    // - Max of 10 tickers total (5 brc and 5 ord)
    // - Max of 3 transactions per ticker
    private function filterForFreeUser(array $statements): array
    {
        foreach ($statements as &$statement) {
            // Group events by year and slice to include only the first 3 for each year
            $eventsGroupedByYear = [];
            foreach ($statement['events'] as $event) {
                $year = Carbon::parse($event['date'])->year;
                if (! isset($eventsGroupedByYear[$year])) {
                    $eventsGroupedByYear[$year] = [];
                }
                if (count($eventsGroupedByYear[$year]) < 3) {
                    array_push($eventsGroupedByYear[$year], $event);
                }
            }

            // Flatten grouped events to repopulate the 'events' array
            $statement['events'] = array_reduce($eventsGroupedByYear, function ($carry, $yearEvents) {
                return array_merge($carry, $yearEvents);
            }, []);

            // Replace eventsByYear with the new, sliced groups
            $statement['eventsByYear'] = $eventsGroupedByYear;

            // Recalculate totalProfitBTC based on the filtered events
            $statement['totalProfitBTC'] = array_reduce($statement['events'], function ($carry, $event) {
                return $carry + $event['profitBTC'];
            }, 0);

            // Recalculate totalProfitBTC only for specific year 2023
            $statement['totalProfitBTCByYear']['2023'] = array_reduce($statement['events'], function ($carry, $event) {
                return $carry + $event['profitBTC'];
            }, 0);
        }

        // Filter out statements with no events after limiting
        $statements = array_filter($statements, function ($statement) {
            return count($statement['events']) > 0;
        });

        // Separate statements into brc vs ord
        $brcStatements = [];
        $ordStatements = [];
        foreach ($statements as $statement) {
            if (str_starts_with($statement['ticker'], 'brc20_')) {
                $brcStatements[] = $statement;
            } else {
                $ordStatements[] = $statement;
            }
        }

        // Limit each array to 5 statements if there are more than that
        $brcStatements = array_slice($brcStatements, 0, 6);
        $ordStatements = array_slice($ordStatements, 0, 6);

        // Combine the filtered and sliced arrays
        $filteredStatements = array_merge($brcStatements, $ordStatements);

        return $filteredStatements;
    }

    // Licensed User Permissions:
    // - Only transactions within the years the user is licensed for
    // - User licenses will either be a year (e.g. 2023) or 0 for lifetime licenses
    private function filterForLicencedUser(array $statements): array
    {
        $years = $this->userLicenseYears(Auth::user());

        // Check for lifetime license
        if (in_array(0, $years)) {
            return $statements;
        }

        // Filter out years not found in licenses
        foreach ($statements as &$statement) {
            $statement['events'] = array_filter($statement['events'], function ($event) use ($years) {
                return in_array(Carbon::parse($event['date'])->year, $years);
            });

            $filteredEventsByYear = [];
            foreach ($years as $year) {
                // Initialize array for this year
                $filteredEventsByYear[$year] = [];

                // Filter events and push to array
                array_push(
                    $filteredEventsByYear[$year],
                    ...array_filter($statement['events'], function ($event) use ($year) {
                        return Carbon::parse($event['date'])->year == $year;
                    })
                );
            }
            $statement['eventsByYear'] = $filteredEventsByYear;
        }

        return $statements;
    }
}
