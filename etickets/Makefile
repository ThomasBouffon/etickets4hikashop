all: install
clean:
	if [ -e etickets.zip ] ; then rm etickets.zip ; fi

dist : clean
	zip -r  etickets.zip COPYING  etickets.php  etickets.xml  etickets.zip  index.html  language  lib  sql  tmpl

install : 
	cp -R $$PWD ~/public_html/Joomla/plugins/hikashop
	chown -R thomas:apache ~/public_html/Joomla/plugins/hikashop 2>/dev/null
	chmod g+rw ~/public_html/Joomla/plugins/hikashop 2>/dev/null
	
