$(function() {
	
	// Automatically add Zoom interaction
	$('article.content img').attr('data-action', 'zoom');

	// Make captions from Alt tags
	$('img.captioned').each(function() {
		var caption = $(this).attr('alt') || false;
		if (caption) {
			$(this).after('<p class="caption">' + caption + '</p>');
		}
	});

	// Auto focus on the giant search box
	var search = $('input.giant.search');
	search.focus().val(search.val());

	// Fire up that gallery
	$(window).load(function () {
		collage();
	});

	// Anima

	$('#nav-main .search').focus(function() {
		$(this).addClass('grow');
	}).blur(function() {
		$(this).removeClass('grow');
	});
});

function collage() {
	$('.gallery-images').collagePlus({
		'fadeSpeed' : 300
    });
}

// Reinitialize the gallery on browser resize.
var resizeTimer = null;
$(window).bind('resize', function() {
    $('.gallery-images img').css("opacity", 0);
    
    if (resizeTimer) clearTimeout(resizeTimer);
    
    resizeTimer = setTimeout(collage, 200);
});