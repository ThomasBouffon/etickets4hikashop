all: clean package
clean:
	if [ -e etickets4hikashop.zip ]; then rm etickets4hikashop.zip ; fi
	if [ -e admin/etickets.zip ] ; then rm admin/etickets.zip ; fi
	if [ -e admin/hikashopeticketsdisplay.zip ] ; then rm admin/hikashopeticketsdisplay.zip; fi

package :
	cd plugins/hikashop/etickets ;\
	pwd ; \
	zip -r ../../../admin/etickets.zip .
	cd plugins/system/hikashopeticketsdisplay; \
	zip -r ../../../admin/hikashopeticketsdisplay.zip .
	zip -r etickets4hikashop.zip etickets4hikashop.xml install.php admin  

install : 
	cp -R plugins/system/hikashopeticketsdisplay ~/public_html/Joomla/plugins/system
	cp -R plugins/hikashop/etickets ~/public_html/Joomla/plugins/hikashop
