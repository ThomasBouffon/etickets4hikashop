DESTDIR:=~thomas/public_html/J3
VERSION:=$(shell xmllint etickets.xml --xpath 'string(/extension/version)')
all: install
clean:
	if [ -e etickets4hikashop-$(VERSION).zip ] ; then rm etickets4hikashop-$(VERSION).zip ; fi
	#if [ -e etickets4hikashop-$(VERSION)WithoutTCPDF.zip ] ; then rm etickets.zip ; fi

dist : clean
	zip -r  etickets4hikashop-$(VERSION).zip COPYING  etickets.php  etickets.xml    index.html  language  lib  sql  tmpl
	zip -r  etickets4hikashop-$(VERSION)WithoutTCPDF.zip COPYING  etickets.php  etickets.xml  etickets.zip  index.html  language  lib  sql  tmpl
	zip etickets4hikashop-$(VERSION)WithoutTCPDF.zip  ../../etickets4hikashop/README_COULD_NOT_UPLOAD_TCPDF.txt

install : 
	cp -R $$PWD $(DESTDIR)/plugins/hikashop
	su -c "rm -rf $(DESTDIR)/plugins/hikashop/etickets; cp -R $$PWD $(DESTDIR)/plugins/hikashop ; chown -R thomas:apache $(DESTDIR) ; chmod -R g+rw $(DESTDIR)"
	
