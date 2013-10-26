DESTDIR:=~thomas/public_html/J3
VERSION:=$(shell xmllint etickets.xml --xpath 'string(/extension/version)')
all: install
clean:
	if [ -e etickets4hikashop-$(VERSION).zip ] ; then rm etickets4hikashop-$(VERSION).zip ; fi

dist : clean
	zip -r  etickets4hikashop-$(VERSION).zip COPYING  etickets.php  etickets.xml    index.html  language  lib  sql  tmpl

install : 
	php -l etickets.php 
	php -l tmpl/eticket.php
	php -l tmpl/etickets4hikashopform.php
	cp -R $$PWD $(DESTDIR)/plugins/hikashop
	su -c "rm -rf $(DESTDIR)/plugins/hikashop/etickets; cp -R $$PWD $(DESTDIR)/plugins/hikashop ; chown -R thomas:apache $(DESTDIR) ; chmod -R g+rw $(DESTDIR)"
	
