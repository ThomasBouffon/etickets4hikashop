CREATE TABLE IF NOT EXISTS `#__hikashop_etickets` (
  `id` varchar(24) NOT NULL,
  `order_product_id` integer NOT NULL,
  `order_id` integer NOT NULL,
  `status` integer NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS  `#__hikashop_eticket_info` (
  `product_id` integer NOT NULL,
  `address` varchar(500) NOT NULL,
  `eventdate`  date NOT NULL,
 PRIMARY KEY  (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

 
 
