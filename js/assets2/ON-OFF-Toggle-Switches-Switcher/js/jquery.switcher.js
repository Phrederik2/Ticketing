;(function ($) {

    $.switcher = function (filter) {

        var $haul = $('input[type=checkbox],input[type=radio]');

        if (filter !== undefined && filter.length) {
            $haul = $haul.filter(filter);
        }

        $haul.each(function () {
			
			// create title ON
			var tON="";
			var ON = $(this).attr('ON');
			if(ON !== undefined){
				var ClassON = ON.replace(' ','_');
				var NameClassON = ".ui-switcher_"+ClassON+"[aria-checked=true]:before";
				tON = "ui-switcher_"+ClassON;
				if(document.getElementsByClassName(ClassON).length==0){
					
					var style = document.createElement('style');
					style.type = 'text/css';
					style.innerHTML = NameClassON +"  { content:'"+ON+"'!important; }";
					document.getElementsByTagName('head')[0].appendChild(style);
				}
			}
			
			//create title OFF
			var tOFF="";
			var OFF = $(this).attr('OFF');
			if(OFF !== undefined){
				var ClassOFF = OFF.replace(' ','_');
				var NameClassOFF = ".ui-switcher_"+ClassOFF+"[aria-checked=false]:before";
				tOFF = "ui-switcher_"+ClassOFF;
				if(document.getElementsByClassName(ClassOFF).length==0){
					
					var style = document.createElement('style');
					style.type = 'text/css';
					style.innerHTML = NameClassOFF +"  { content:'"+OFF+"'!important; }";
					document.getElementsByTagName('head')[0].appendChild(style);
				}
			}
			
			
			
            var $checkbox = $(this).hide(),
                $switcher = $(document.createElement('div'))
                    .addClass(tON + ' ' + tOFF + ' ui-switcher')
                    .attr('aria-checked', $checkbox.is(':checked'));
                   
            if ('radio' === $checkbox.attr('type')) {
                $switcher.attr('data-name', $checkbox.attr('name'));
            }

            toggleSwitch = function (e) {
                if (e.target.type === undefined) {
                    $checkbox.trigger(e.type);
                }
                $switcher.attr('aria-checked', $checkbox.is(':checked'));
                if ('radio' === $checkbox.attr('type')) {
                    $('.ui-switcher[data-name=' + $checkbox.attr('name') + ']')
                        .not($switcher.get(0))
                        .attr('aria-checked', false);
                }
            };

            $switcher.on('click', toggleSwitch);
            $checkbox.on('click', toggleSwitch);

            $switcher.insertBefore($checkbox);
        });

    };

})(jQuery);
