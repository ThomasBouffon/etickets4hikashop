all: install
clean:
	if [ -e etickets.zip ] ; then rm etickets.zip ; fi

dist : clean
	zip -r  etickets.zip *

install : 
	cp -R $$PWD ~/public_html/Joomla/plugins/hikashop
	chown -R thomas:apache ~/public_html/Joomla/plugins/hikashop 2>/dev/null
	chmod g+rw ~/public_html/Joomla/plugins/hikashop 2>/dev/null
	
