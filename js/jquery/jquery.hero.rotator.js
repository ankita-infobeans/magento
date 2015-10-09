(function($){		
	$(function(){
		initSlideshow();
	});

	var rotateTimer;
	var rotateTimerLength = 5000;
	function initSlideshow() {
		initSlideshowNav();
		rotateTimer = setTimeout('rotate();', rotateTimerLength);
	}
	window.initSlideshow = initSlideshow; 

	function initSlideshowNav() {
		var slideShowHtml = "";	
		$('#home-slideshow').children().each(function(){
			slideShowHtml += '<li><a href="javascript:void(0);"></a></li>';
		});

		$('#home-slideshow').append('<ul id="home-slideshow-nav">' + slideShowHtml + '</ul>');
		$('#home-slideshow-nav').children(':first').find('a').addClass("active");

		$("#home-slideshow-nav > li > a").click(function() {
			if (!$(this).hasClass('active')) {				
				switchSlide($(this).parent().index());
				clearTimeout(rotateTimer);
			}
		});
	}
	window.initSlideshowNav = initSlideshowNav; 

	function rotate() {
		var ndx = $('#home-slideshow').children(':visible').not('ul').index();
		ndx++;
		if (ndx >= $('#home-slideshow').children().not('ul').length) ndx = 0;
		switchSlide(ndx);
		rotateTimer = setTimeout('rotate();', rotateTimerLength);
	}
	window.rotate = rotate; 

	function switchSlide(ndx) {
		ndx++; //adjust for 1 based nth-child
		$('#home-slideshow').children().hide();
		$('#home-slideshow').children(':nth-child(' + ndx + ')').show();
		$('#home-slideshow-nav a').removeClass('active');
		$('#home-slideshow-nav li:nth-child(' + ndx + ') > a').addClass('active');
	}
	window.switchSlide = switchSlide; 

})(jQuery);