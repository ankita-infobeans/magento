/*------------------------------------------------------------------------------------
Lightbox functionality
This lightbox allows for multiple lightbox content within the same page.  
The lightbox is created on first open, and the content moved from it's location
in the DOM to inside the light box.  When the lightbox is reopend, the content
is shuffled between being hidden at the end of the body, or displayed in the 
lightbox

- place your content in a div with a class of modalpopup and a unique id
- open the lightbox in two ways:
	1. Give an element a class of 'modalelement' and a rel='unique id of content'
	2. Call openLightbox('unique id of element');
- close the lightbox in two ways:
	1. Give an element a class of 'modalclose'
	2. Call closeLightbox();
	
Required CSS
adjust #lightbox z-index accordingly
--------------------------------------------------------------------------------------
#lightbox                   { width:100%; height:100%; position:absolute; top:0px; left:0px; z-index:20; }
#lightbox .lightboxBg       { width:100%; height:100%; background-color:#FFFFFF; position:absolute; 
							  top:0px; left:0px; opacity:.70; filter: alpha(opacity=70); -moz-opacity: 0.70;  }
#lightbox .contentOuter     { position:absolute; z-index:1; }
.modalpopup		        { background-color:#ffffff; display:none; }
.modalelement, .modalclose	{ cursor:pointer; }
------------------------------------------------------------------------------------*/
(function($){		
function _openLightbox(obj) {
	openLightbox(obj.attr("rel"));
}

function openLightbox(elementId) {
    //create lightbox if it doesn't exist yet;
    if (!$("#lightbox")[0]) {
	    var lightbox = document.createElement("div");
	    $(lightbox).attr('id', "lightbox");

	    var lightboxBg = document.createElement("div");
	    $(lightboxBg).addClass("lightboxBg");
	    $(lightbox).append(lightboxBg);
	    $(lightboxBg).click(function() { closeLightbox(); });

	    var contentOuter = document.createElement("div");
	    $(contentOuter).addClass("contentOuter");
	    $(lightbox).append(contentOuter);
  
    	$("body").append(lightbox);
		
		$(window).resize(
			function() {
				positionLightbox();
            });
        $(window).scroll(
			function () {
			    positionLightbox();
			});
	}
		
	$("body").append($(".contentOuter .modalpopup").hide());	
	$(".contentOuter").append($("#" + elementId).show());

	$("#lightbox").show();
	positionLightbox();
	$("#lightbox").hide();

	$("#lightbox").show();

}

/* Calculates the position of the lightboxed element. Centers the element on screen.*/
function positionLightbox() {
	var contentOuter = $("#lightbox").find(".contentOuter");
	var element = $("#lightbox").find(".modalpopup");
	var x = ($(window).width() - $(element).width()) / 2;
	var y = ($(window).height() - $(element).height()) / 2;
    y = y - 50;
	$(contentOuter).css({top:y + "px", left:x + "px"});
	$("#lightbox").height($(document).height());
	$("#lightbox").css("top", $(document).scrollTop() + "px");
}

function closeLightbox() {
	$("#lightbox").hide();
}

function bindLightbox() {
    $(".modalelement").not(".event-attached").addClass("event-attached").click(function() { _openLightbox($(this)); });
    $(".modalclose").not(".event-attached").addClass("event-attached").click(function() { closeLightbox(); });
}
window.bindLightbox = bindLightbox;

function initLightbox() {
    bindLightbox();	
	if ($("#lightbox")[0]) {
		$(window).resize(
			function() {
				positionLightbox();
            });
        $(window).scroll(
			function () {
			    positionLightbox();
			});
	    $(".lightboxBg").click(function() { closeLightbox(); });
	}
}

$( function() { 	
	initLightbox();
});
})(jQuery);