-- DELIVERIES

ALTER TABLE `products_deliveries`
    CHANGE `type` `delivery_type` enum('delivery','pickup') COLLATE 'utf8_general_ci' NOT NULL COMMENT 'Тип: доставка или самовывоз' AFTER `location_id`;

ALTER TABLE `products_deliveries_grouped`
    CHANGE `type` `delivery_type` enum('delivery','pickup') COLLATE 'utf8_general_ci' NOT NULL COMMENT 'Тип: доставка или самовывоз' AFTER `location_id`;

DROP TABLE IF EXISTS `deliveries`;
CREATE TABLE `deliveries` (
                              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                              `name` varchar(100) NOT NULL,
                              `code` int(10) unsigned NOT NULL COMMENT 'Код в 1С',
                              `type` enum('tc','to_client') NOT NULL COMMENT 'tc - транспортной компанией, to_client - до клиента',
                              `published` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Видимость на сайте',
                              `location_id` int(10) unsigned NOT NULL COMMENT 'Регион',
                              `zone_id` int(10) unsigned DEFAULT NULL COMMENT 'Зона доставки',
                              `price` int(10) unsigned NOT NULL COMMENT 'Цена',
                              `interval` varchar(50) NOT NULL COMMENT 'Интервал доставки',
                              `week_days` varchar(50) DEFAULT NULL COMMENT 'Дни недели, в которые осуществляется доставка',
                              `sort` int(10) unsigned NOT NULL COMMENT 'Порядок вывода',
                              `description` tinytext NOT NULL COMMENT 'Описание',
                              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                              `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                              `guid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'GUID 1С',
                              PRIMARY KEY (`id`),
                              UNIQUE KEY `guid` (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Доставка';


DROP TABLE IF EXISTS `deliveries_periods`;
CREATE TABLE `deliveries_periods` (
                                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                      `storage_id` int(10) unsigned NOT NULL,
                                      `location_id` int(10) unsigned NOT NULL,
                                      `week_day` tinyint(1) unsigned NOT NULL COMMENT 'День недели',
                                      `time_from` time NOT NULL COMMENT 'Период С',
                                      `time_to` time NOT NULL COMMENT 'Период По',
                                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `storage_id_location_id_week_day_time_from_time_to` (`storage_id`,`location_id`,`week_day`,`time_from`,`time_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Периоды доставок';


DROP TABLE IF EXISTS `deliveries_schedules`;
CREATE TABLE `deliveries_schedules` (
                                        `period_id` int(10) unsigned NOT NULL,
                                        `delivery_type` enum('delivery','pickup') NOT NULL COMMENT 'Тип: доставка или самовывоз',
                                        `delivery_id` int(10) unsigned NOT NULL COMMENT 'ID типа доставки или пункта самовывоза',
                                        `delivery_days` tinyint(3) unsigned NOT NULL COMMENT 'Кол-во дней доставки',
                                        `delivery_time_from` time NOT NULL COMMENT 'Время доставки С',
                                        UNIQUE KEY `period_id_delivery_type_delivery_id` (`period_id`,`delivery_type`,`delivery_id`),
                                        CONSTRAINT `deliveries_schedules_ibfk_2` FOREIGN KEY (`period_id`) REFERENCES `deliveries_periods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Расписание доставки';


-- 2023 04 07
ALTER TABLE `models`
    CHANGE `model_id` `code` varchar(100) COLLATE 'ascii_bin' NOT NULL COMMENT 'TEMP: ID модели в старой структуре' AFTER `name`,
    CHANGE `description` `description` text COLLATE 'utf8_general_ci' NULL AFTER `code`,
    CHANGE `aliases` `aliases` text COLLATE 'utf8_general_ci' NULL AFTER `description`,
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

ALTER TABLE `products`
    CHANGE `code` `code` varchar(150) COLLATE 'ascii_bin' NOT NULL COMMENT 'TEMP: ID товара в старой структуре' AFTER `name`,
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;


ALTER TABLE `models`
    CHANGE `brand_id` `brand_id` int(10) unsigned NOT NULL COMMENT 'ID бренда модели' AFTER `id`,
    CHANGE `name` `name` varchar(100) COLLATE 'utf8_general_ci' NOT NULL COMMENT 'Наименование модели' AFTER `brand_id`,
    ADD `product_type_id` int unsigned NOT NULL COMMENT 'ID типа товара' AFTER `code`,
    CHANGE `description` `description` text COLLATE 'utf8_general_ci' NULL COMMENT 'Описание модели' AFTER `product_type_id`,
    CHANGE `aliases` `aliases` text COLLATE 'utf8_general_ci' NULL COMMENT 'Алиасы названия модели' AFTER `description`,
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
ALTER TABLE `models`
    CHANGE `product_type_id` `product_type_id` int(10) unsigned NULL COMMENT 'ID типа товара' AFTER `code`,
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;


-- 2023 04 08
ALTER TABLE `models`
DROP `code`;

