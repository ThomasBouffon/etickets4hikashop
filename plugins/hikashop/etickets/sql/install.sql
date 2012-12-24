CREATE TABLE IF NOT EXISTS `#__hikashop_etickets` (
  `id` varchar(24) NOT NULL,
  `tn` integer NOT NULL AUTO_INCREMENT,
  `order_product_id` integer NOT NULL,
  `product_id` integer NOT NULL,
  `order_id` integer NOT NULL,
  `status` integer NOT NULL,
  PRIMARY KEY  (`product_id`,`tn`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS  `#__hikashop_eticket_info` (
  `product_id` integer NOT NULL,
  `address` varchar(500) NOT NULL,
  `eventdate`  date NOT NULL,
 PRIMARY KEY  (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS  `#__hikashop_eticket_config` (
  `config_key` varchar(100) NOT NULL,
  `config_value` varchar(100) NOT NULL,
 PRIMARY KEY  (`config_key`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

 
 
