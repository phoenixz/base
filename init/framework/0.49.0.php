<?php
/*
 * Add crypto walllet support
 */
cli_log('Adding support for crypto wallets');

sql_query('UPDATE `users` SET `credits` = 0');
sql_query('DROP TABLE IF EXISTS `crypto_transactions`');
sql_query('DROP TABLE IF EXISTS `crypto_addresses`');
sql_query('DROP TABLE IF EXISTS `crypto_rates`');

sql_query('CREATE TABLE `crypto_addresses` (`id`        INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                            `createdon` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `createdby` INT(11)       NOT NULL,
                                            `status`    VARCHAR(16)       NULL,
                                            `users_id`  INT(11)       NOT NULL,
                                            `currency`  ENUM("internal", "BTC", "LTC", "USD", "CAD", "EUR", "XRP", "ADC", "AUD", "BITB", "BLK", "BRK", "BRL", "BSD", "BTC.Bitstamp", "BTC.SnapSwap", "CAD.RippleUnion", "CHF", "CLOAK", "CNY", "COP", "CRB", "CRW", "CURE", "CZK", "DASH", "DBIX", "DCR", "DOGE", "ETC", "ETH", "EUR.Bitstamp", "EXP", "FLC", "GAME", "GBP", "GBP.Bitstamp", "GCR", "GLD", "GNT", "GRC", "GRS", "HKD", "IDR", "INR", "INSANE", "ISK", "JPY", "KRW", "LAK", "LEO", "LEO.Old", "MAID", "MCAP", "MUE", "MUE.Old", "MXN", "MYR", "NAV", "NMC", "NXS", "NXT", "NZD", "OMNI", "PEN", "PHP", "PINK", "PIVX", "PKR", "PLN", "POSW", "POT", "PPC", "PROC", "PSB", "QRK", "RUB", "SBD", "SEK", "SGD", "STEEM", "STRAT", "SYS", "THB", "TKN", "TOR", "TTKN", "TWD", "USD.Bitstamp", "USD.SnapSwap", "USDT", "VOX", "VTC", "WAVES", "XAUR", "XMR", "XPM", "XSPEC", "XVG", "ZAR", "ZEC", "LTCT", "MXN", "CAD") NOT NULL,
                                            `provider`  ENUM("coinpayments", "coinbase", "local") NOT NULL,
                                            `address`   VARCHAR(64)   NOT NULL,

                                             INDEX                      (`createdon`),
                                             INDEX                      (`createdby`),
                                             INDEX                      (`status`),
                                             INDEX                      (`users_id`),
                                             INDEX                      (`currency`),
                                             UNIQUE `users_id_currency` (`users_id`, `currency`),
                                             INDEX                      (`provider`),

                                             CONSTRAINT `fk_crypto_addresses_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_crypto_addresses_users_id`  FOREIGN KEY (`users_id`)  REFERENCES `users` (`id`) ON DELETE RESTRICT

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `crypto_transactions` (`id`                  INT(11)        NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                               `createdon`           TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                               `modifiedon`          DATETIME           NULL,
                                               `users_id`            INT(11)            NULL,
                                               `status`              VARCHAR(16)        NULL,
                                               `status_text`         VARCHAR(48)        NULL,
                                               `type`                ENUM("simple", "button", "cart", "donation", "deposit", "api", "withdrawal", "payment") NOT NULL,
                                               `mode`                ENUM("httpauth", "hmac", "internal")                                                    NOT NULL,
                                               `currency`            ENUM("internal", "BTC", "LTC", "USD", "CAD", "EUR", "XRP", "ADC", "AUD", "BITB", "BLK", "BRK", "BRL", "BSD", "BTC.Bitstamp", "BTC.SnapSwap", "CAD.RippleUnion", "CHF", "CLOAK", "CNY", "COP", "CRB", "CRW", "CURE", "CZK", "DASH", "DBIX", "DCR", "DOGE", "ETC", "ETH", "EUR.Bitstamp", "EXP", "FLC", "GAME", "GBP", "GBP.Bitstamp", "GCR", "GLD", "GNT", "GRC", "GRS", "HKD", "IDR", "INR", "INSANE", "ISK", "JPY", "KRW", "LAK", "LEO", "LEO.Old", "MAID", "MCAP", "MUE", "MUE.Old", "MXN", "MYR", "NAV", "NMC", "NXS", "NXT", "NZD", "OMNI", "PEN", "PHP", "PINK", "PIVX", "PKR", "PLN", "POSW", "POT", "PPC", "PROC", "PSB", "QRK", "RUB", "SBD", "SEK", "SGD", "STEEM", "STRAT", "SYS", "THB", "TKN", "TOR", "TTKN", "TWD", "USD.Bitstamp", "USD.SnapSwap", "USDT", "VOX", "VTC", "WAVES", "XAUR", "XMR", "XPM", "XSPEC", "XVG", "ZAR", "ZEC", "LTCT", "MXN", "CAD") NOT NULL,
                                               `confirms`            INT(11)            NULL,
                                               `api_transactions_id` VARCHAR(32)        NULL,
                                               `tx_id`               VARCHAR(64)        NULL,
                                               `merchant`            VARCHAR(32)        NULL,
                                               `address`             VARCHAR(64)        NULL,
                                               `amount`              BIGINT             NULL,
                                               `amounti`             BIGINT             NULL,
                                               `amount_btc`          BIGINT             NULL,
                                               `amount_usd`          BIGINT             NULL,
                                               `amount_usd_rounded`  BIGINT             NULL,
                                               `fee`                 BIGINT             NULL,
                                               `feei`                BIGINT             NULL,
                                               `exchange_rate`       BIGINT             NULL,
                                               `description`         VARCHAR(32)        NULL,

                                                INDEX (`createdon`),
                                                INDEX (`users_id`),
                                                INDEX (`status`),
                                                INDEX (`type`),
                                                INDEX (`mode`),
                                                INDEX (`currency`),
                                                UNIQUE(`api_transactions_id`),
                                                UNIQUE(`tx_id`),

                                                CONSTRAINT `fk_crypto_transactions_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `crypto_rates`        (`id`        INT(11)        NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                               `createdon` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                               `status`    VARCHAR(16)        NULL,
                                               `provider`  ENUM("coinpayments", "coinbase", "local") NOT NULL,
                                               `currency`  ENUM("BTC", "LTC", "USD", "CAD", "EUR", "XRP", "ADC", "AUD", "BITB", "BLK", "BRK", "BRL", "BSD", "BTC.Bitstamp", "BTC.SnapSwap", "CAD.RippleUnion", "CHF", "CLOAK", "CNY", "COP", "CRB", "CRW", "CURE", "CZK", "DASH", "DBIX", "DCR", "DOGE", "ETC", "ETH", "EUR.Bitstamp", "EXP", "FLC", "GAME", "GBP", "GBP.Bitstamp", "GCR", "GLD", "GNT", "GRC", "GRS", "HKD", "IDR", "INR", "INSANE", "ISK", "JPY", "KRW", "LAK", "LEO", "LEO.Old", "MAID", "MCAP", "MUE", "MUE.Old", "MXN", "MYR", "NAV", "NMC", "NXS", "NXT", "NZD", "OMNI", "PEN", "PHP", "PINK", "PIVX", "PKR", "PLN", "POSW", "POT", "PPC", "PROC", "PSB", "QRK", "RUB", "SBD", "SEK", "SGD", "STEEM", "STRAT", "SYS", "THB", "TKN", "TOR", "TTKN", "TWD", "USD.Bitstamp", "USD.SnapSwap", "USDT", "VOX", "VTC", "WAVES", "XAUR", "XMR", "XPM", "XSPEC", "XVG", "ZAR", "ZEC", "LTCT", "MXN", "CAD") NOT NULL,
                                               `rate_btc`  BIGINT         NOT NULL,
                                               `fee`       BIGINT         NOT NULL,

                                                INDEX (`createdon`),
                                                INDEX (`currency`)

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

?>
