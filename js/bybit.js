'use strict';

//  ---------------------------------------------------------------------------

const Exchange = require ('./base/Exchange');
const { AddressPending, AuthenticationError, DDoSProtection, ExchangeError, InsufficientFunds, InvalidNonce, OrderNotFound } = require ('./base/errors');
const { TRUNCATE, DECIMAL_PLACES } = require ('./base/functions/number');

//  ---------------------------------------------------------------------------

module.exports = class bybit extends Exchange {
    describe () {
        return this.deepExtend (super.describe (), {
            'id': 'bybit',
            'name': 'ByBit',
            'countries': [
                'ZH',
            ],
            'version': 'v1',
            'rateLimit': 250,
            'certified': false,
            // new metainfo interface
            'has': {
                'fetchClosedOrders': false,
                'fetchOrderBooks': false,
                'fetchOHLCV': false,
                'fetchCurrencies': false,
                'fetchTransactions': false,
                'fetchOrder': true,
                'transferToExchange': false,
                'createDepositAddress': false,
                'cancelOrders': false,
                'fetchDepositAddress': false,
                'fetchOpenOrders': true,
                'transfer': false,
                'fetchTradingFee': false,
                'fetchOrders': true,
                'fetchLedger': false,
                'fetchTicker': false,
                'cancelAllOrders': false,
                'fetchL2OrderBook': false,
                'createLimitOrder': true,
                'createMarketOrder': false,
                'fetchBalance': false,
                'fetchWithdrawals': false,
                'fetchTradingFees': false,
                'fetchMarkets': false,
                'fetchTradingLimits': false,
                'CORS': false,
                'fetchBidsAsks': false,
                'fetchOrderBook': false,
                'createOrder': true,
                'fetchDeposits': false,
                'deposit': false,
                'withdraw': false,
                'fetchTickers': false,
                'cancelOrder': true,
                'fetchMyTrades': false,
                'fetchDepositAddresses': false,
            },
            'hostname': 'bybit.com',
            'timeframes': {
            },
            'urls': {
                'logo': 'https://boaexchange.com/4cdef72eb47d4a759d2c72e619f48827.png',
                'api': {
                    'v1': 'https://api.{hostname}/api',
                },
                'www': 'https://bybit.com',
                'doc': [
                ],
                'fees': [
                ],
            },
            'api': {
                'v1': {
                    'get': [
                        'open-api/order/list',
                        'open-api/stop-order/list',
                        'user/leverage',
                        'position/list',
                        'open-api/funding/prev-funding-rate',
                        'open-api/funding/prev-funding',
                        'open-api/funding/predicted-funding',
                        'v2/private/execution/list',
                    ],
                    'post': [
                        'open-api/order/create',
                        'open-api/order/cancel',
                        'open-api/stop-order/create',
                        'open-api/stop-order/cancel',
                        'user/leverage/save',
                        'position/change-position-margin',
                    ],
                    'delete': [
                    ],
                },
            },
            'requiredCredentials': {
                'secret': true,
                'apiKey': true,
            },
            'fees': {
                'trading': {
                    'tierBased': false,
                    'percentage': false,
                    'maker': 0.15,
                    'taker': 0.15,
                },
                'funding': {
                    'tierBased': false,
                    'percentage': false,
                    'withdraw': {
                    },
                    'deposit': {
                    },
                },
            },
            'exceptions': {
            },
            'options': {
                // price precision by quote currency code
                'pricePrecisionByCode': {
                    'USD': 3,
                },
                'symbolSeparator': '_',
                'tag': {
                },
            },
            'commonCurrencies': {
            },
        });
    }

    costToPrecision (symbol, cost) {
        return this.decimalToPrecision (cost, TRUNCATE, this.markets[symbol]['precision']['price'], DECIMAL_PLACES);
    }

    feeToPrecision (symbol, fee) {
        return this.decimalToPrecision (fee, TRUNCATE, this.markets[symbol]['precision']['price'], DECIMAL_PLACES);
    }

    nonce () {
        return this.seconds ();
    }

    async createOrder (symbol, type, side, amount, price = undefined, params = {}) {
        if (type !== 'limit')
            throw new ExchangeError (this.id + ' allows limit orders only');
        await this.loadMarkets ();
        let market = this.market (symbol);
        let order = {
            'label': market['id'],
            'qty': this.amountToPrecision (symbol, amount),
            'price': this.priceToPrecision (symbol, price),
            'side': side,
        };
        let response = await this.v1PostOpenApiOrderCreate (this.extend (order, params));
        return this.extend (this.parseOrder (response['data'], market), {
            'status': 'open',
            'price': order['price_field'],
            'symbol': symbol,
            'amount': order['amount'],
            'side': side,
            'type': type,
            'id': response['data']['id'],
        });
    }
    async createLimitOrder (symbol, type, side, amount, price = undefined, params = {}) {
        let response = await this.createOrder (symbol, type, side, price, params);
        return response;

    }

    async cancelOrder (id, symbol = undefined, params = {}) {
        await this.loadMarkets ();
        let request = { 'order_id': id };
        let response = await this.v1PostOpenApiOrderCancel (this.extend (request, params));
        return this.extend (this.parseOrder (response), {
            'status': 'canceled',
        });
    }

    async fetchMarkets (params = {}) {
        const response = await this.v1GetMarkets ();
        return this.parseMarkets (response['data']);
    }

    async fetchOpenOrders (symbol = undefined, since = 0, limit = 0, params = {}) {
        await this.loadMarkets ();
        let request = {
            'begin': since,
            'limit': limit,
        };
        let market = undefined;
        if (symbol !== undefined) {
            market = this.market (symbol);
            request['label'] = market['id'];
        }
        let response = await this.v1GetOpenApiOrderList (this.extend (request, params));
        let orders = this.parseOrders (response['data'], market, since, limit);
        return this.filterBySymbol (orders, symbol);
    }

    async fetchOrder (id, symbol = undefined, params = {}) {
        await this.loadMarkets ();
        let response = undefined;
        try {
            let request = { 'order_id': id };
            response = await this.v1GetV2PrivateExecutionList (this.extend (request, params));
            return this.parseOrder (response['data']);
        } catch (e) {
            throw e;
        }
    }

    parseMarket (market) {
        let id = market['id'];
        let baseId = market['coin_market']['code'];
        let quoteId = market['coin_traded']['code'];
        let base = this.commonCurrencyCode (baseId);
        let quote = this.commonCurrencyCode (quoteId);
        let symbol = base + '/' + quote;
        let pricePrecision = 8;
        if (quote in this.options['pricePrecisionByCode'])
            pricePrecision = this.options['pricePrecisionByCode'][quote];
        let precision = {
            'amount': 8,
            'price': pricePrecision,
        };
        let paused = this.safeValue (market, 'paused', false);
        if (paused === 'false' || !paused) {
            paused = true;
        }
        return {
            'id': id,
            'symbol': symbol,
            'base': base,
            'quote': quote,
            'baseId': baseId,
            'quoteId': quoteId,
            'active': !paused,
            'info': market,
            'precision': precision,
            'limits': {
                'amount': {
                    'min': undefined,
                    'max': undefined,
                },
                'price': {
                    'min': Math.pow (10, -precision['price']),
                    'max': undefined,
                },
            },
        };
    }

    parseMarkets (markets) {
        let results = [];
        for (let i = 0; i < markets.length; i++) {
            results.push (this.parseMarket (markets[i]));
        }
        return results;
    }


    parseOrder (order, market = undefined) {
        let side = this.safeString (order, 'side');
        let remaining = this.safeFloat (order, 'amount');
        // We parse different fields in a very specific order.
        // Order might well be closed and then canceled.
        let status = undefined;
        if (remaining > 0)
            status = 'open';
        if (this.safeValue (order, 'cancelled', false))
            status = 'canceled';
        if (remaining === 0)
            status = 'closed';
        let symbol = undefined;
        if ('market' in order) {
            let marketId = order['market'];
            if (marketId in this.markets_by_id) {
                market = this.markets_by_id[marketId];
                symbol = market['symbol'];
            } else {
                symbol = this.parseSymbol (marketId);
            }
        } else {
            if (market !== undefined) {
                symbol = market['symbol'];
            }
        }
        let timestamp = undefined;
        if ('created' in order)
            timestamp = order['created'];
        let lastTradeTimestamp = undefined;
        if (('closed' in order) && (order['closed'] !== 0))
            lastTradeTimestamp = order['closed'];
        if (timestamp === undefined)
            timestamp = lastTradeTimestamp;
        let price = this.safeFloat (order, 'price_field');
        let amount = this.safeFloat (order, 'amount_start');
        let cost = this.safeFloat (order, 'cost');
        let filled = undefined;
        if (amount !== undefined && remaining !== undefined) {
            filled = amount - remaining;
        }
        let id = this.safeString (order, 'id');
        let result = {
            'info': order,
            'id': id,
            'timestamp': timestamp,
            'datetime': this.iso8601 (timestamp),
            'lastTradeTimestamp': lastTradeTimestamp,
            'symbol': symbol,
            'type': 'limit',
            'side': side,
            'price': price,
            'cost': cost,
            'average': undefined,
            'amount': amount,
            'filled': filled,
            'remaining': remaining,
            'status': status,
            'fee': undefined,
        };
        return result;
    }

    parseSymbol (id) {
        let [ quote, base ] = id.split (this.options['_']);
        base = this.commonCurrencyCode (base);
        quote = this.commonCurrencyCode (quote);
        return base + '/' + quote;
    }

    parseTicker (ticker, market = undefined) {
        return {
            'symbol': this.safeString (ticker, 'symbol'),
            'timestamp': undefined,
            'datetime': undefined,
            'high': this.safeFloat (ticker, 'high_price'),
            'low': this.safeFloat (ticker, 'low_price'),
            'bid': undefined,
            'bidVolume': undefined,
            'ask': undefined,
            'askVolume': undefined,
            'vwap': undefined,
            'open': undefined,
            'close': this.safeFloat (ticker, 'price'),
            'last': this.safeFloat (ticker, 'price'),
            'previousClose': undefined,
            'change': undefined,
            'percentage': this.safeFloat (ticker, 'price_change'),
            'average': undefined,
            'baseVolume': this.safeFloat (ticker, 'volume_market'),
            'quoteVolume': this.safeFloat (ticker, 'volume_traded'),
            'info': ticker,
        };
    }


    sign (path, api = 'public', method = 'GET', params = {}, headers = undefined, body = undefined) {
        let url = this.implodeParams (this.urls['api'][api], {
            'hostname': this.hostname,
        }) + '/v1/';
        url += this.implodeParams (path, params);
        params['limit'] = 500;
        url += '?' + this.urlencode (params);
        this.checkRequiredCredentials ();
        let nonce = this.nonce ().toString ();
        let signature = this.hmac (this.encode (nonce), this.encode (this.secret), 'sha256');
        headers = {
            'sign': signature,
            'api_key': this.apiKey,
            'timestamp': nonce,
            'Content-Type': 'application/json',
        };
        return { 'url': url, 'method': method, 'body': body, 'headers': headers };
    }

    handleErrors (code, reason, url, method, headers, body, response) {
        if (body[0] === '{') {
            let data = this.safeValue (response, 'data');
            let errors = this.safeValue (response, 'errors');
            const feedback = this.id + ' ' + this.json (response);
            if (errors !== undefined) {
                const message = errors[0];
                if (message in this.exceptions)
                    throw new this.exceptions[message] (feedback);
                throw new ExchangeError (this.id + ' an error occoured: ' + this.json (errors));
            }
            if (data === undefined)
                throw new ExchangeError (this.id + ': malformed response: ' + this.json (response));
        }
    }

    async request (path, api = '', method = 'GET', params = {}, headers = undefined, body = undefined) {
        let response = await this.fetch2 (path, api, method, params, headers, body);
        return response;
    }
};
