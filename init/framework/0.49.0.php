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

sql_query('CREATE TABLE `crypto_transactions` (`id`                 INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                               `createdon`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                               `createdby`          INT(11)       NOT NULL,
                                               `status`             VARCHAR(16)       NULL,
                                               `status_text`        VARCHAR(32)       NULL,
                                               `type`               ENUM("simple", "button", "cart", "donation", "deposit", "api", "withdrawal") NOT NULL,
                                               `mode`               ENUM("httpauth", "hmac")                                                     NOT NULL,
                                               `currency`           ENUM("BTC", "LTC", "ETC")                                                    NOT NULL,
                                               `confirms`           INT(11)       NOT NULL,
                                               `api_transaction_id` BIGINT        NOT NULL,
                                               `tx_id`              BIGINT        NOT NULL,
                                               `address`            INT(11)       NOT NULL,
                                               `amount`             BIGINT        NOT NULL,
                                               `amounti`            BIGINT        NOT NULL,
                                               `fee`                BIGINT        NOT NULL,
                                               `feei`               BIGINT        NOT NULL,
                                               `exchange_rate`      FLOAT         NOT NULL,

                                                INDEX (`createdon`),
                                                INDEX (`createdby`),
                                                INDEX (`status`),
                                                INDEX (`source`),
                                                INDEX (`target`),
                                                INDEX (`source_currency`),
                                                INDEX (`destination_currency`),

                                                CONSTRAINT `fk_crypto_transactions_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                                CONSTRAINT `fk_crypto_transactions_source`    FOREIGN KEY (`source`)    REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                                CONSTRAINT `fk_crypto_transactions_target`    FOREIGN KEY (`target`)    REFERENCES `users` (`id`) ON DELETE RESTRICT

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

?>
