DROP TABLE IF EXISTS `#__hikashop_etickets`;
 
CREATE TABLE `#__hikashop_etickets` (
  `id` varchar(24) NOT NULL,
  `order_product_id` integer NOT NULL,
  `status` integer NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
 
