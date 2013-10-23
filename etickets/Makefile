DESTDIR:=~thomas/public_html/J3
all: install
clean:
	if [ -e etickets.zip ] ; then rm etickets.zip ; fi

dist : clean
	zip -r  etickets.zip COPYING  etickets.php  etickets.xml  etickets.zip  index.html  language  lib  sql  tmpl

install : 
	cp -R $$PWD $(DESTDIR)/plugins/hikashop
	su -c "chown -R thomas:apache $(DESTDIR) ; chmod -R g+rw $(DESTDIR)"
	
