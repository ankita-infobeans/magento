jQuery( document ).ready(function() {
        
            jQuery('#sharepoint-slideshow').children('p').hide();
            jQuery('#sharepoint-left-advertisement-slideshow').children('p').hide();
            jQuery('#sharepoint-right-advertisement-slideshow').children('p').hide();
            
            ndx = getRandomInt(1, jQuery('#sharepoint-slideshow').children().not('ul').length);
            jQuery('#sharepoint-slideshow').children().hide();
            jQuery('#sharepoint-slideshow').children(':nth-child(' + ndx + ')').show();

            ldx = getRandomInt(1, jQuery('#sharepoint-left-advertisement-slideshow').children().not('ul').length);
            jQuery('#sharepoint-left-advertisement-slideshow').children().hide();
            jQuery('#sharepoint-left-advertisement-slideshow').children(':nth-child(' + ldx + ')').show();

            rdx = getRandomInt(1, jQuery('#sharepoint-right-advertisement-slideshow').children().not('ul').length);
            jQuery('#sharepoint-right-advertisement-slideshow').children().hide();
            jQuery('#sharepoint-right-advertisement-slideshow').children(':nth-child(' + rdx + ')').show();
            myVar = setTimeout(rotates, 30000);
        });

	function rotates() {
            var ndx = jQuery('#sharepoint-slideshow').children(':visible').not('ul').index();
            ndx++;
            if (ndx >= jQuery('#sharepoint-slideshow').children().not('ul').length) ndx = 0;

            var ldx = jQuery('#sharepoint-left-advertisement-slideshow').children(':visible').not('ul').index();
            ldx++;
            if (ldx >= jQuery('#sharepoint-left-advertisement-slideshow').children().not('ul').length) ldx = 0;

            var rdx = jQuery('#sharepoint-right-advertisement-slideshow').children(':visible').not('ul').index();
            rdx++;
            if (rdx >= jQuery('#sharepoint-right-advertisement-slideshow').children().not('ul').length) rdx = 0;
            switchSlides();
            myVar = setTimeout(rotates, 30000);
	}

	function switchSlides() {

            ndx = getRandomInt(1, jQuery('#sharepoint-slideshow').children().not('ul').length);
            jQuery('#sharepoint-slideshow').children().hide();
            jQuery('#sharepoint-slideshow').children(':nth-child(' + ndx + ')').show();

            ldx = getRandomInt(1, jQuery('#sharepoint-left-advertisement-slideshow').children().not('ul').length);
            jQuery('#sharepoint-left-advertisement-slideshow').children().hide();
            jQuery('#sharepoint-left-advertisement-slideshow').children(':nth-child(' + ldx + ')').show();

            rdx = getRandomInt(1, jQuery('#sharepoint-right-advertisement-slideshow').children().not('ul').length);
            jQuery('#sharepoint-right-advertisement-slideshow').children().hide();
            jQuery('#sharepoint-right-advertisement-slideshow').children(':nth-child(' + rdx + ')').show();
	}

        function getRandomInt(min, max) {
            return Math.floor(Math.random() * (max - min)) + min;
        }
