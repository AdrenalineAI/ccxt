<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception as Exception; // a common import

class braziliex extends Exchange {

    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'braziliex',
            'name' => 'Braziliex',
            'countries' => array ( 'BR' ),
            'rateLimit' => 1000,
            'has' => array (
                'fetchCurrencies' => true,
                'fetchTickers' => true,
                'fetchOpenOrders' => true,
                'fetchMyTrades' => true,
                'fetchDepositAddress' => true,
            ),
            'urls' => array (
                'logo' => 'https://user-images.githubusercontent.com/1294454/34703593-c4498674-f504-11e7-8d14-ff8e44fb78c1.jpg',
                'api' => 'https://braziliex.com/api/v1',
                'www' => 'https://braziliex.com/',
                'doc' => 'https://braziliex.com/exchange/api.php',
                'fees' => 'https://braziliex.com/exchange/fees.php',
                'referral' => 'https://braziliex.com/?ref=5FE61AB6F6D67DA885BC98BA27223465',
            ),
            'api' => array (
                'public' => array (
                    'get' => array (
                        'currencies',
                        'ticker',
                        'ticker/{market}',
                        'orderbook/{market}',
                        'tradehistory/{market}',
                    ),
                ),
                'private' => array (
                    'post' => array (
                        'balance',
                        'complete_balance',
                        'open_orders',
                        'trade_history',
                        'deposit_address',
                        'sell',
                        'buy',
                        'cancel_order',
                    ),
                ),
            ),
            'commonCurrencies' => array (
                'EPC' => 'Epacoin',
                'ABC' => 'Anti Bureaucracy Coin',
            ),
            'fees' => array (
                'trading' => array (
                    'maker' => 0.005,
                    'taker' => 0.005,
                ),
            ),
            'precision' => array (
                'amount' => 8,
                'price' => 8,
            ),
            'options' => array (
                'fetchCurrencies' => array (
                    'expires' => 1000, // 1 second
                ),
            ),
        ));
    }

    public function fetch_currencies_from_cache ($params = array ()) {
        // this method is $now redundant
        // currencies are $now fetched before markets
        $options = $this->safe_value($this->options, 'fetchCurrencies', array());
        $timestamp = $this->safe_integer($options, 'timestamp');
        $expires = $this->safe_integer($options, 'expires', 1000);
        $now = $this->milliseconds ();
        if (($timestamp === null) || (($now - $timestamp) > $expires)) {
            $response = $this->publicGetCurrencies ($params);
            $this->options['fetchCurrencies'] = array_merge ($options, array (
                'response' => $response,
                'timestamp' => $now,
            ));
        }
        return $this->safe_value($this->options['fetchCurrencies'], 'response');
    }

    public function fetch_currencies ($params = array ()) {
        $response = $this->fetch_currencies_from_cache ($params);
        //
        //     {
        //         brl => array (
        //             name => "Real",
        //             withdrawal_txFee =>  0.0075,
        //             txWithdrawalFee =>  9,
        //             MinWithdrawal =>  30,
        //             minConf =>  1,
        //             minDeposit =>  0,
        //             txDepositFee =>  0,
        //             txDepositPercentageFee =>  0,
        //             minAmountTradeFIAT =>  5,
        //             minAmountTradeBTC =>  0.0001,
        //             minAmountTradeUSDT =>  0.0001,
        //             decimal =>  8,
        //             decimal_withdrawal =>  8,
        //             $active =>  1,
        //             dev_active =>  1,
        //             under_maintenance =>  0,
        //             order => "010",
        //             is_withdrawal_active =>  1,
        //             is_deposit_active =>  1,
        //             is_token_erc20 =>  0,
        //             is_fiat =>  1,
        //             gateway =>  0,
        //         ),
        //         btc => {
        //             name => "Bitcoin",
        //             txWithdrawalMinFee =>  0.000125,
        //             txWithdrawalFee =>  0.00015625,
        //             MinWithdrawal =>  0.0005,
        //             minConf =>  1,
        //             minDeposit =>  0,
        //             txDepositFee =>  0,
        //             txDepositPercentageFee =>  0,
        //             minAmountTradeFIAT =>  5,
        //             minAmountTradeBTC =>  0.0001,
        //             minAmountTradeUSDT =>  0.0001,
        //             decimal =>  8,
        //             decimal_withdrawal =>  8,
        //             $active =>  1,
        //             dev_active =>  1,
        //             under_maintenance =>  0,
        //             order => "011",
        //             is_withdrawal_active =>  1,
        //             is_deposit_active =>  1,
        //             is_token_erc20 =>  0,
        //             is_fiat =>  0,
        //             gateway =>  1,
        //         }
        //     }
        //
        $this->options['currencies'] = array (
            'timestamp' => $this->milliseconds (),
            'response' => $response,
        );
        $ids = is_array($response) ? array_keys($response) : array();
        $result = array();
        for ($i = 0; $i < count ($ids); $i++) {
            $id = $ids[$i];
            $currency = $response[$id];
            $precision = $this->safe_integer($currency, 'decimal');
            $uppercase = strtoupper($id);
            $code = $this->common_currency_code($uppercase);
            $active = $this->safe_integer($currency, 'active') === 1;
            $maintenance = $this->safe_integer($currency, 'under_maintenance');
            if ($maintenance !== 0) {
                $active = false;
            }
            $canWithdraw = $this->safe_integer($currency, 'is_withdrawal_active') === 1;
            $canDeposit = $this->safe_integer($currency, 'is_deposit_active') === 1;
            if (!$canWithdraw || !$canDeposit) {
                $active = false;
            }
            $result[$code] = array (
                'id' => $id,
                'code' => $code,
                'name' => $currency['name'],
                'active' => $active,
                'precision' => $precision,
                'funding' => array (
                    'withdraw' => array (
                        'active' => $canWithdraw,
                        'fee' => $this->safe_float($currency, 'txWithdrawalFee'),
                    ),
                    'deposit' => array (
                        'active' => $canDeposit,
                        'fee' => $this->safe_float($currency, 'txDepositFee'),
                    ),
                ),
                'limits' => array (
                    'amount' => array (
                        'min' => pow(10, -$precision),
                        'max' => pow(10, $precision),
                    ),
                    'price' => array (
                        'min' => pow(10, -$precision),
                        'max' => pow(10, $precision),
                    ),
                    'cost' => array (
                        'min' => null,
                        'max' => null,
                    ),
                    'withdraw' => array (
                        'min' => $this->safe_float($currency, 'MinWithdrawal'),
                        'max' => pow(10, $precision),
                    ),
                    'deposit' => array (
                        'min' => $this->safe_float($currency, 'minDeposit'),
                        'max' => null,
                    ),
                ),
                'info' => $currency,
            );
        }
        return $result;
    }

    public function fetch_markets ($params = array ()) {
        $currencies = $this->fetch_currencies_from_cache ($params);
        $response = $this->publicGetTicker ();
        //
        //     {
        //         btc_brl => array (
        //             $active => 1,
        //             $market => 'btc_brl',
        //             last => 14648,
        //             percentChange => -0.95,
        //             baseVolume24 => 27.856,
        //             quoteVolume24 => 409328.039,
        //             baseVolume => 27.856,
        //             quoteVolume => 409328.039,
        //             highestBid24 => 14790,
        //             lowestAsk24 => 14450.01,
        //             highestBid => 14450.37,
        //             lowestAsk => 14699.98
        //         ),
        //         ...
        //     }
        //
        $ids = is_array($response) ? array_keys($response) : array();
        $result = array();
        for ($i = 0; $i < count ($ids); $i++) {
            $id = $ids[$i];
            $market = $response[$id];
            list($baseId, $quoteId) = explode('_', $id);
            $uppercaseBaseId = strtoupper($baseId);
            $uppercaseQuoteId = strtoupper($quoteId);
            $base = $this->common_currency_code($uppercaseBaseId);
            $quote = $this->common_currency_code($uppercaseQuoteId);
            $symbol = $base . '/' . $quote;
            $baseCurrency = $this->safe_value($currencies, $baseId, array());
            $quoteCurrency = $this->safe_value($currencies, $quoteId, array());
            $quoteIsFiat = $this->safe_integer($quoteCurrency, 'is_fiat', 0);
            $minCost = null;
            if ($quoteIsFiat) {
                $minCost = $this->safe_float($baseCurrency, 'minAmountTradeFIAT');
            } else {
                $minCost = $this->safe_float($baseCurrency, 'minAmountTrade' . $uppercaseQuoteId);
            }
            $isActive = $this->safe_integer($market, 'active');
            $active = ($isActive === 1);
            $precision = array (
                'amount' => 8,
                'price' => 8,
            );
            $result[] = array (
                'id' => $id,
                'symbol' => strtoupper($symbol),
                'base' => $base,
                'quote' => $quote,
                'baseId' => $baseId,
                'quoteId' => $quoteId,
                'active' => $active,
                'precision' => $precision,
                'limits' => array (
                    'amount' => array (
                        'min' => pow(10, -$precision['amount']),
                        'max' => pow(10, $precision['amount']),
                    ),
                    'price' => array (
                        'min' => pow(10, -$precision['price']),
                        'max' => pow(10, $precision['price']),
                    ),
                    'cost' => array (
                        'min' => $minCost,
                        'max' => null,
                    ),
                ),
                'info' => $market,
            );
        }
        return $result;
    }

    public function parse_ticker ($ticker, $market = null) {
        $symbol = $market['symbol'];
        $timestamp = $ticker['date'];
        $ticker = $ticker['ticker'];
        $last = $this->safe_float($ticker, 'last');
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => $this->safe_float($ticker, 'highestBid24'),
            'low' => $this->safe_float($ticker, 'lowestAsk24'),
            'bid' => $this->safe_float($ticker, 'highestBid'),
            'bidVolume' => null,
            'ask' => $this->safe_float($ticker, 'lowestAsk'),
            'askVolume' => null,
            'vwap' => null,
            'open' => null,
            'close' => $last,
            'last' => $last,
            'previousClose' => null,
            'change' => $this->safe_float($ticker, 'percentChange'),
            'percentage' => null,
            'average' => null,
            'baseVolume' => $this->safe_float($ticker, 'baseVolume24'),
            'quoteVolume' => $this->safe_float($ticker, 'quoteVolume24'),
            'info' => $ticker,
        );
    }

    public function fetch_ticker ($symbol, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $ticker = $this->publicGetTickerMarket (array_merge (array (
            'market' => $market['id'],
        ), $params));
        $ticker = array (
            'date' => $this->milliseconds (),
            'ticker' => $ticker,
        );
        return $this->parse_ticker($ticker, $market);
    }

    public function fetch_tickers ($symbols = null, $params = array ()) {
        $this->load_markets();
        $tickers = $this->publicGetTicker ($params);
        $result = array();
        $timestamp = $this->milliseconds ();
        $ids = is_array($tickers) ? array_keys($tickers) : array();
        for ($i = 0; $i < count ($ids); $i++) {
            $id = $ids[$i];
            $market = $this->markets_by_id[$id];
            $symbol = $market['symbol'];
            $ticker = array (
                'date' => $timestamp,
                'ticker' => $tickers[$id],
            );
            $result[$symbol] = $this->parse_ticker($ticker, $market);
        }
        return $result;
    }

    public function fetch_order_book ($symbol, $limit = null, $params = array ()) {
        $this->load_markets();
        $orderbook = $this->publicGetOrderbookMarket (array_merge (array (
            'market' => $this->market_id($symbol),
        ), $params));
        return $this->parse_order_book($orderbook, null, 'bids', 'asks', 'price', 'amount');
    }

    public function parse_trade ($trade, $market = null) {
        $timestamp = null;
        if (is_array($trade) && array_key_exists('date_exec', $trade)) {
            $timestamp = $this->parse8601 ($trade['date_exec']);
        } else {
            $timestamp = $this->parse8601 ($trade['date']);
        }
        $price = $this->safe_float($trade, 'price');
        $amount = $this->safe_float($trade, 'amount');
        $symbol = $market['symbol'];
        $cost = $this->safe_float($trade, 'total');
        $orderId = $this->safe_string($trade, 'order_number');
        return array (
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'symbol' => $symbol,
            'id' => $this->safe_string($trade, '_id'),
            'order' => $orderId,
            'type' => 'limit',
            'side' => $trade['type'],
            'price' => $price,
            'amount' => $amount,
            'cost' => $cost,
            'fee' => null,
            'info' => $trade,
        );
    }

    public function fetch_trades ($symbol, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $trades = $this->publicGetTradehistoryMarket (array_merge (array (
            'market' => $market['id'],
        ), $params));
        return $this->parse_trades($trades, $market, $since, $limit);
    }

    public function fetch_balance ($params = array ()) {
        $this->load_markets();
        $balances = $this->privatePostCompleteBalance ($params);
        $result = array( 'info' => $balances );
        $currencies = is_array($balances) ? array_keys($balances) : array();
        for ($i = 0; $i < count ($currencies); $i++) {
            $id = $currencies[$i];
            $balance = $balances[$id];
            $currency = $this->common_currency_code($id);
            $account = array (
                'free' => floatval ($balance['available']),
                'used' => 0.0,
                'total' => floatval ($balance['total']),
            );
            $account['used'] = $account['total'] - $account['free'];
            $result[$currency] = $account;
        }
        return $this->parse_balance($result);
    }

    public function parse_order ($order, $market = null) {
        $symbol = null;
        if ($market === null) {
            $marketId = $this->safe_string($order, 'market');
            if ($marketId)
                if (is_array($this->markets_by_id) && array_key_exists($marketId, $this->markets_by_id))
                    $market = $this->markets_by_id[$marketId];
        }
        if ($market)
            $symbol = $market['symbol'];
        $timestamp = $this->safe_value($order, 'timestamp');
        if (!$timestamp)
            $timestamp = $this->parse8601 ($order['date']);
        $price = $this->safe_float($order, 'price');
        $cost = $this->safe_float($order, 'total', 0.0);
        $amount = $this->safe_float($order, 'amount');
        $filledPercentage = $this->safe_float($order, 'progress');
        $filled = $amount * $filledPercentage;
        $remaining = floatval ($this->amount_to_precision($symbol, $amount - $filled));
        $info = $order;
        if (is_array($info) && array_key_exists('info', $info))
            $info = $order['info'];
        return array (
            'id' => $order['order_number'],
            'datetime' => $this->iso8601 ($timestamp),
            'timestamp' => $timestamp,
            'lastTradeTimestamp' => null,
            'status' => 'open',
            'symbol' => $symbol,
            'type' => 'limit',
            'side' => $order['type'],
            'price' => $price,
            'cost' => $cost,
            'amount' => $amount,
            'filled' => $filled,
            'remaining' => $remaining,
            'trades' => null,
            'fee' => $this->safe_value($order, 'fee'),
            'info' => $info,
        );
    }

    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $method = 'privatePost' . $this->capitalize ($side);
        $response = $this->$method (array_merge (array (
            'market' => $market['id'],
            // 'price' => $this->price_to_precision($symbol, $price),
            // 'amount' => $this->amount_to_precision($symbol, $amount),
            'price' => $price,
            'amount' => $amount,
        ), $params));
        $success = $this->safe_integer($response, 'success');
        if ($success !== 1)
            throw new InvalidOrder($this->id . ' ' . $this->json ($response));
        $parts = explode(' / ', $response['message']);
        $parts = mb_substr ($parts, 1);
        $feeParts = explode(' ', $parts[5]);
        $order = $this->parse_order(array (
            'timestamp' => $this->milliseconds (),
            'order_number' => $response['order_number'],
            'type' => strtolower($parts[0]),
            'market' => strtolower($parts[0]),
            'amount' => explode(' ', $parts[2])[1],
            'price' => explode(' ', $parts[3])[1],
            'total' => explode(' ', $parts[4])[1],
            'fee' => array (
                'cost' => floatval ($feeParts[1]),
                'currency' => $feeParts[2],
            ),
            'progress' => '0.0',
            'info' => $response,
        ), $market);
        $id = $order['id'];
        $this->orders[$id] = $order;
        return $order;
    }

    public function cancel_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $result = $this->privatePostCancelOrder (array_merge (array (
            'order_number' => $id,
            'market' => $market['id'],
        ), $params));
        return $result;
    }

    public function fetch_open_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $orders = $this->privatePostOpenOrders (array_merge (array (
            'market' => $market['id'],
        ), $params));
        return $this->parse_orders($orders['order_open'], $market, $since, $limit);
    }

    public function fetch_my_trades ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $trades = $this->privatePostTradeHistory (array_merge (array (
            'market' => $market['id'],
        ), $params));
        return $this->parse_trades($trades['trade_history'], $market, $since, $limit);
    }

    public function fetch_deposit_address ($code, $params = array ()) {
        $this->load_markets();
        $currency = $this->currency ($code);
        $response = $this->privatePostDepositAddress (array_merge (array (
            'currency' => $currency['id'],
        ), $params));
        $address = $this->safe_string($response, 'deposit_address');
        $this->check_address($address);
        $tag = $this->safe_string($response, 'payment_id');
        return array (
            'currency' => $code,
            'address' => $address,
            'tag' => $tag,
            'info' => $response,
        );
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $url = $this->urls['api'] . '/' . $api;
        $query = $this->omit ($params, $this->extract_params($path));
        if ($api === 'public') {
            $url .= '/' . $this->implode_params($path, $params);
            if ($query)
                $url .= '?' . $this->urlencode ($query);
        } else {
            $this->check_required_credentials();
            $query = array_merge (array (
                'command' => $path,
                'nonce' => $this->nonce (),
            ), $query);
            $body = $this->urlencode ($query);
            $signature = $this->hmac ($this->encode ($body), $this->encode ($this->secret), 'sha512');
            $headers = array (
                'Content-type' => 'application/x-www-form-urlencoded',
                'Key' => $this->apiKey,
                'Sign' => $this->decode ($signature),
            );
        }
        return array( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function request ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $response = $this->fetch2 ($path, $api, $method, $params, $headers, $body);
        if (is_array($response) && array_key_exists('success', $response)) {
            $success = $this->safe_integer($response, 'success');
            if ($success === 0) {
                $message = $this->safe_string($response, 'message');
                if ($message === 'Invalid APIKey')
                    throw new AuthenticationError($message);
                throw new ExchangeError($message);
            }
        }
        return $response;
    }
}
