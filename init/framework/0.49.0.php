<?php
/*
 * Add crypto walllet support
 */
cli_log('Adding support for crypto wallets');

sql_query('DROP TABLE IF EXISTS `crypto_transactions`');
sql_query('DROP TABLE IF EXISTS `crypto_addresses`');

sql_query('CREATE TABLE `crypto_addresses` (`id`        INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                            `createdon` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `createdby` INT(11)       NOT NULL,
                                            `status`    VARCHAR(16)       NULL,
                                            `users_id`  INT(11)       NOT NULL,
                                            `currency`  ENUM("BTC", "LTC", "ETC", "USD", "CAD", "MXN") NOT NULL,
                                            `provider`  ENUM("coinpayments", "coinbase", "local")      NOT NULL,
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

sql_query('CREATE TABLE `crypto_transactions` (`id`                  INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                               `createdon`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                               `users_id`            INT(11)       NOT NULL,
                                               `status`              VARCHAR(16)       NULL,
                                               `status_text`         VARCHAR(32)       NULL,
                                               `type`                ENUM("simple", "button", "cart", "donation", "deposit", "api", "withdrawal") NOT NULL,
                                               `mode`                ENUM("httpauth", "hmac")                                                     NOT NULL,
                                               `currency`            ENUM("BTC", "LTC", "ETC")                                                    NOT NULL,
                                               `confirms`            INT(11)       NOT NULL,
                                               `api_transactions_id` BIGINT        NOT NULL,
                                               `tx_id`               BIGINT        NOT NULL,
                                               `address`             VARCHAR(64)   NOT NULL,
                                               `amount`              BIGINT        NOT NULL,
                                               `amounti`             BIGINT        NOT NULL,
                                               `fee`                 BIGINT        NOT NULL,
                                               `feei`                BIGINT        NOT NULL,
                                               `exchange_rate`       FLOAT         NOT NULL,

                                                INDEX (`createdon`),
                                                INDEX (`users_id`),
                                                INDEX (`status`),
                                                INDEX (`type`),
                                                INDEX (`mode`),
                                                INDEX (`currency`),
                                                INDEX (`api_transactions_id`),
                                                INDEX (`tx_id`),

                                                CONSTRAINT `fk_crypto_transactions_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

?>
