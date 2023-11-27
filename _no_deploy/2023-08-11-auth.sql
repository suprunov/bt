-- NEW DB

-- TODO move to config
INSERT INTO `settings` (`id`, `class`, `key`, `value`, `type`, `context`, `created_at`, `updated_at`) VALUES
(1,	'SmsCenter',	'login',	'btwebsms',	'string',	NULL,	'2023-08-11 09:54:51',	'2023-08-11 09:54:51'),
(2,	'SmsCenter',	'password',	'ByW%*Mu%4h56',	'string',	NULL,	'2023-08-11 09:55:01',	'2023-08-11 09:55:01');

ALTER TABLE `users`
    ADD UNIQUE `username_deleted_at` (`username`, `deleted_at`),
DROP INDEX `username`;

ALTER TABLE `users`
    ADD `phone` int(10) unsigned NOT NULL AFTER `last_location_id`;

ALTER TABLE `users`
    ADD `blocked` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `active`;

ALTER TABLE `users`
    CHANGE `phone` `phone` bigint(10) unsigned NOT NULL AFTER `last_location_id`;

ALTER TABLE `users`
    ADD `inn` bigint(12) unsigned NULL AFTER `middle_name`,
ADD `email_main` varchar(70) NULL AFTER `phone_notify`;

ALTER TABLE `users`
    ADD `legal_status` enum('fiz','yur') COLLATE 'ascii_bin' NOT NULL AFTER `username`;

ALTER TABLE `users`
    CHANGE `legal_status` `legal_status` enum('fiz','yur') COLLATE 'ascii_bin' NOT NULL COMMENT 'Extra: Юр статус' AFTER `username`,
    CHANGE `full_name` `name` varchar(100) COLLATE 'utf8_general_ci' NOT NULL COMMENT 'Extra: Полное имя/наименование' AFTER `legal_status`,
    CHANGE `inn` `inn` bigint(12) unsigned NULL COMMENT 'Extra: ИНН' AFTER `middle_name`,
    CHANGE `blocked` `blocked` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Extra: бан' AFTER `active`,
    CHANGE `email_main` `email_main` varchar(70) COLLATE 'utf8_general_ci' NULL COMMENT 'Extra: Email' AFTER `phone_notify`;

ALTER TABLE `users`
    ADD `price_type_id` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Extra: ID типа цены' AFTER `inn`;

CREATE TABLE `users_contact_persons` (
                                         `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `user_id` int(11) unsigned NOT NULL,
                                         `first_name` varchar(100) NOT NULL,
                                         `last_name` varchar(100) NOT NULL,
                                         `phone` bigint(10) unsigned NOT NULL
) COMMENT='Контактные лица пользователя';

ALTER TABLE `users_contact_persons`
    ADD `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
ADD `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
                                                                                                                                          ADD `guid` varchar(36) COLLATE 'ascii_bin' NULL AFTER `updated_at`;

ALTER TABLE `users_contact_persons`
    ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `users_contact_persons`
    CHANGE `last_name` `last_name` varchar(100) COLLATE 'utf8mb4_general_ci' NULL AFTER `first_name`,
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

CREATE TABLE `users_addresses` (
                                   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                   `user_id` int(11) unsigned NOT NULL COMMENT 'ID пользователя',
                                   `address` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Полный адрес',
                                   `address_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'JSON: Адрес в формате сервиса dadata',
                                   `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                                   `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                                   PRIMARY KEY (`id`),
                                   KEY `client_id` (`user_id`),
                                   CONSTRAINT `users_addresses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Адреса пользователей';

ALTER TABLE `users_contact_persons`
    ADD `used_at` timestamp NULL AFTER `phone`,
CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

ALTER TABLE `users_addresses`
    ADD `used_at` timestamp NULL AFTER `address_json`,
CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

--

ALTER TABLE `users`
    ADD `business_region_id` smallint(3) unsigned NULL COMMENT 'Extra: Бизнес регион' AFTER `passport`;

ALTER TABLE `users`
    ADD `legal_data` json NULL COMMENT 'Extra: Юр. реквизиты в формате сервиса dadata' AFTER `business_region_id`;

ALTER TABLE `users`
    CHANGE `legal_data` `legal_data` longtext COLLATE 'utf8mb4_bin' NULL COMMENT 'Extra: Юр. реквизиты' AFTER `business_region_id`;

ALTER TABLE `users`
    CHANGE `legal_status` `legal_status` enum('fiz','yur') COLLATE 'ascii_bin' NULL COMMENT 'Extra: Юр статус' AFTER `username`;

ALTER TABLE `users_contact_persons`
    CHANGE `used_at` `used_at` timestamp NOT NULL DEFAULT current_timestamp() AFTER `phone`,
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

ALTER TABLE `users_addresses`
    CHANGE `used_at` `used_at` timestamp NOT NULL DEFAULT current_timestamp() AFTER `address_json`,
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

ALTER TABLE `users`
    CHANGE `inn` `inn` varchar(12) NULL COMMENT 'Extra: ИНН' AFTER `middle_name`;

ALTER TABLE `users`
    ADD `published` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Extra: является действующим в системе 1С' AFTER `status_message`,
CHANGE `active` `active` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Является действующим в нашей системе' AFTER `published`;

ALTER TABLE `users`
    CHANGE `published` `published` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Extra: является действующим в системе 1С' AFTER `status_message`;

ALTER TABLE `users`
DROP `published`;

ALTER TABLE `users`
    CHANGE `email_confirmed` `email_verified` tinyint(1) unsigned NOT NULL COMMENT 'Extra: Владение электронной почтой подтверждено' AFTER `email_main`;

ALTER TABLE `users`
    ADD INDEX `phone` (`phone`),
ADD INDEX `email_main` (`email_main`),
DROP INDEX `username_deleted_at`;

--

create table contacts like blacktyres.bt_contacts;
insert into contacts select * from blacktyres.bt_contacts;

ALTER TABLE `contacts`
    ADD `id` int unsigned NOT NULL AUTO_INCREMENT UNIQUE FIRST,
CHANGE `city_id` `location_id` smallint(4) unsigned NOT NULL AFTER `id`,
CHANGE `phones` `phones` json NULL AFTER `location_id`,
CHANGE `emails` `emails` json NULL AFTER `phones`,
CHANGE `schedules` `schedules` json NULL COMMENT 'График работы' AFTER `emails`,
CHANGE `schedules_call` `schedules_call` json NULL COMMENT 'График работы Колл-центра' AFTER `schedules`,
CHANGE `sort` `sort` int unsigned NOT NULL DEFAULT '100' AFTER `schedules_call`,
ADD `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
ADD `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
                                                                                        COMMENT='Контактные данные магазина';

ALTER TABLE `users_addresses`
    ADD UNIQUE `user_id_address` (`user_id`, `address`),
DROP INDEX `client_id`;

ALTER TABLE `users_contact_persons`
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
                                                                                           ADD `deleted_at` timestamp NULL AFTER `updated_at`;

ALTER TABLE `users`
    CHANGE `phone` `phone` bigint(10) unsigned NULL AFTER `last_location_id`;

ALTER TABLE `users`
    CHANGE `business_region_id` `location_id` int unsigned NULL COMMENT 'Extra: Регион пользователя' AFTER `passport`;

ALTER TABLE `users_addresses`
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
                                                                                           ADD `deleted_at` timestamp NULL;

ALTER TABLE `users_addresses`
    ADD UNIQUE `user_id_address_deleted_at` (`user_id`, `address`, `deleted_at`),
DROP INDEX `user_id_address`;

-- OLD DB



UPDATE `bt_client` SET
    `code` = 'yur'
WHERE `id` = '3';

ALTER TABLE `bt_orders_status`
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `date_order`,
    ADD `user_id` int unsigned NULL COMMENT 'ID пользователя' AFTER `num_order`;

ALTER TABLE `bt_orders_status`
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `date_order`,
    CHANGE `id_client` `id_client` int(10) unsigned NOT NULL COMMENT 'Deprecated: Ид клиента' AFTER `user_id`,
    CHANGE `face` `face` tinyint(1) unsigned NULL COMMENT 'Deprecated' AFTER `delivery_paiment`,
    CHANGE `face_name` `face_name` varchar(100) COLLATE 'utf8_general_ci' NULL COMMENT 'Deprecated' AFTER `face`,
    CHANGE `delivery_km_price` `delivery_km_price` decimal(6,0) unsigned NULL COMMENT 'Deprecated: Стоимость за км от МКАД' AFTER `valuta_name`,
    CHANGE `delivery_km` `delivery_km` int(3) unsigned NULL COMMENT 'Deprecated: Расстояние от МКАД' AFTER `delivery_km_price`;

ALTER TABLE `bt_orders_article`
    CHANGE `id_client` `id_client` int(10) NULL COMMENT 'Deprecated: Ид.Клиента' AFTER `id_orders`;

ALTER TABLE `bt_orders_service`
    CHANGE `id_client` `id_client` int(11) NULL COMMENT 'Deprecated' AFTER `id_zn`;

ALTER TABLE `bt_orders_status`
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `date_order`,
    CHANGE `id_client` `id_client` int(10) unsigned NULL COMMENT 'Deprecated: Ид клиента' AFTER `user_id`;

ALTER TABLE `bt_orders_status`
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `date_order`,
    ADD `contact_person_id` int(10) unsigned NULL COMMENT 'ID контактного лица пользователя' AFTER `user_id`,
    ADD `address_id` int(10) unsigned NULL COMMENT 'ID адреса доставки' AFTER `contact_person_id`;


ALTER TABLE `bt_orders_status`
    ADD INDEX `user_id` (`user_id`);


ALTER TABLE `bt_season_orders`
    ADD `user_guid` varchar(36) COLLATE 'ascii_bin' NOT NULL AFTER `id`,
    CHANGE `client_id` `client_id` int(10) unsigned NOT NULL COMMENT 'DEPRECATED: user_id from users table' AFTER `doc_date`;

ALTER TABLE `bt_season_orders`
    ADD INDEX `user_guid` (`user_guid`),
DROP INDEX `client_id`;

UPDATE bt_season_orders so, users u
SET so.user_guid = u.1c_code
WHERE so.client_id = u.id;

ALTER TABLE `user_order`
CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `date`,
CHANGE `id_user` `id_user` varchar(36) COLLATE 'utf8_general_ci' NOT NULL COMMENT 'ID пользователя' AFTER `stat`,
CHANGE `client_id` `contact_person_id` int(10) unsigned NULL COMMENT 'ID контактного лица пользоателя' AFTER `id_user`,
CHANGE `id_orders` `id_orders` varchar(12) COLLATE 'utf8_general_ci' NOT NULL AFTER `contact_person_id`,
DROP `user_id`;

ALTER TABLE `user_order`
CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `date`,
CHANGE `id_user` `id_user` varchar(36) COLLATE 'utf8_general_ci' NOT NULL COMMENT 'GUID пользователя' AFTER `stat`,
ADD `user_id` int(10) unsigned NULL COMMENT 'ID пользователя' AFTER `id_user`;

ALTER TABLE `user_order`
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `date`,
ADD `pickup_id` int unsigned NULL AFTER `tc_name`;

ALTER TABLE `user_order`
    CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `date`,
CHANGE `pickup_id` `pickup_id` varchar(36) NULL AFTER `tc_name`;






--------------------------------------- 1)
UPDATE `bt_client` SET
`code` = 'yur'
WHERE `id` = '3';
--------------------------------------- 2)
-- BEFORE FULL USER IMPORT !!!!!!!!!!!!!!!!
insert into black4.users (id, guid, created_at, updated_at)
    (select id, 1c_code, now(), now()
     from `users` u1
     where u1.1c_code != '1' and u1.1c_code != '' and length(u1.1c_code) = 36
and not exists (select 1 from users u2 where u2.1c_code = u1.1c_code and u2.id > u1.id));


create table black4.contacts like bt_contacts;
insert into black4.contacts select * from bt_contacts;
ALTER TABLE black4.contacts
    ADD `id` int unsigned NOT NULL AUTO_INCREMENT UNIQUE FIRST,
CHANGE `city_id` `location_id` smallint(4) unsigned NOT NULL AFTER `id`,
CHANGE `phones` `phones` json NULL AFTER `location_id`,
CHANGE `emails` `emails` json NULL AFTER `phones`,
CHANGE `schedules` `schedules` json NULL COMMENT 'График работы' AFTER `emails`,
CHANGE `schedules_call` `schedules_call` json NULL COMMENT 'График работы Колл-центра' AFTER `schedules`,
CHANGE `sort` `sort` int unsigned NOT NULL DEFAULT '100' AFTER `schedules_call`,
ADD `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
ADD `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
 COMMENT='Контактные данные магазина';


UPDATE bt_season_orders so, users u
SET so.user_guid = u.1c_code
WHERE so.client_id = u.id;

update `user_order` uo, black4.users u
set uo.user_id = u.id
where uo.id_user = u.guid;







--------------------------------------- 3) (not necessary)
-- AFTER RELEASE
ALTER TABLE `bt_cart_entry` RENAME TO `bt_cart_entry_deprecated`;
ALTER TABLE `bt_orders_client` RENAME TO `bt_orders_client_deprecated`;
ALTER TABLE `users` RENAME TO `users_deprecated`;
ALTER TABLE `user_delivery` RENAME TO `user_delivery_deprecated`;
ALTER TABLE `user_contact` RENAME TO `user_contact_deprecated`;
ALTER TABLE `user_profile_fiz` RENAME TO `user_profile_fiz_deprecated`;
ALTER TABLE `user_profile_ur` RENAME TO `user_profile_ur_deprecated`;

DELETE
FROM `bt_season_orders`
WHERE `user_guid` = '';








