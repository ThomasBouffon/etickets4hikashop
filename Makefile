all: clean install
clean:
	if [ -e etickets4hikashop.zip ]; then rm etickets4hikashop.zip ; fi
	if [ -e admin/etickets.zip ] ; then rm admin/etickets.zip ; fi
	if [ -e admin/hikashopeticketsdisplay.zip ] ; then rm admin/hikashopeticketsdisplay.zip; fi

install :
	cd plugins/hikashop/etickets ;\
	pwd ; \
	zip -r ../../../admin/etickets.zip .
	cd plugins/system/hikashopeticketsdisplay; \
	zip -r ../../../admin/hikashopeticketsdisplay.zip .
	zip -r etickets4hikashop.zip etickets4hikashop.xml install.php admin  
