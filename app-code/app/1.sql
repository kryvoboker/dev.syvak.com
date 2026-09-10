ALTER TABLE `sdby_promo_code_usages`
    ADD COLUMN `discount_type` VARCHAR(20) NOT NULL AFTER `order_id`,
    ADD COLUMN `promo_type` VARCHAR(20) NOT NULL AFTER `discount_type`;

ALTER TABLE `sdby_order_customers`
    ADD COLUMN `no_call` TINYINT(1) NOT NULL DEFAULT 0 AFTER `telephone`;

CREATE TABLE `sdby_order_promo_code_products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `promo_code_usage_id` BIGINT UNSIGNED NOT NULL,
    `order_product_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NULL,
    `product_variant_id` BIGINT UNSIGNED NULL,
    `is_eligible` TINYINT(1) NOT NULL DEFAULT 0,
    `override` VARCHAR(20) NULL,
    `discount_amount` DECIMAL(15, 4) NOT NULL DEFAULT 0.0000,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `sdby_order_promo_code_products_usage_product_unique` (`promo_code_usage_id`, `order_product_id`),
    KEY `sdby_order_promo_code_products_order_eligible_index` (`order_id`, `is_eligible`),
    CONSTRAINT `sdby_order_promo_code_products_order_id_foreign`
        FOREIGN KEY (`order_id`) REFERENCES `sdby_orders` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `sdby_order_promo_code_products_promo_code_usage_id_foreign`
        FOREIGN KEY (`promo_code_usage_id`) REFERENCES `sdby_promo_code_usages` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `sdby_order_promo_code_products_order_product_id_foreign`
        FOREIGN KEY (`order_product_id`) REFERENCES `sdby_order_products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `sdby_order_promo_code_products_product_variant_id_foreign`
        FOREIGN KEY (`product_variant_id`) REFERENCES `sdby_product_variants` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
