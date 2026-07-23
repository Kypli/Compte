// CSS
import '../../styles/navbar/navbar.scss';
import $ from 'jquery';


// ---------Responsive-navbar-active-animation-----------


////////////
// ON LOAD
////////////

$(window).on('load', function(){
	moveActiveSelectorOnLoad();
});
$(document).ready(function(){
	moveActiveSelectorOnLoad();
});

////////////
// ON EVENTS
////////////

$(window).on('resize', function(){
	setTimeout(function(){ moveActiveSelector(); }, 500);
});
$(".navbar-toggler").click(function(){
	$(".navbar-collapse").slideToggle(300);
	setTimeout(function(){ moveActiveSelector(); }, 350);
});


////////////
// FONCTIONS
////////////
function moveActiveSelectorOnLoad(){
	moveActiveSelector(false);
	window.requestAnimationFrame(function(){
		moveActiveSelector(false);
	});
	setTimeout(function(){ moveActiveSelector(false); }, 150);
	setTimeout(function(){ moveActiveSelector(false); }, 500);

	if (document.fonts) {
		document.fonts.ready.then(function(){
			moveActiveSelector(false);
		});
	}
}

function moveActiveSelector(animate = true){
	var tabsNewAnim = $('#navbarSupportedContent');
	var activeItemNewAnim = tabsNewAnim.find('li.active').first();
	if (!activeItemNewAnim.length) {
		return;
	}

	var containerRect = tabsNewAnim[0].getBoundingClientRect();
	var activeItemRect = activeItemNewAnim[0].getBoundingClientRect();
	var topOffset = $(window).width() <= 991 ? 0 : 10;

	var selector = $(".hori-selector");
	if (!animate) {
		selector.css('transition', 'none');
	}

	selector.css({
		"top": (activeItemRect.top - containerRect.top + topOffset) + "px",
		"left": (activeItemRect.left - containerRect.left) + "px",
		"height": (activeItemRect.height - topOffset) + "px",
		"width": activeItemRect.width + "px"
	});
	selector.addClass('is-ready');

	if (!animate) {
		selector[0].offsetHeight;
		window.requestAnimationFrame(function(){
			selector.css('transition', '');
		});
	}
}

$("#navbarSupportedContent").on("click", "li", function(e){
	$('#navbarSupportedContent ul li').removeClass("active");
	$(this).addClass('active');
	moveActiveSelector();
});


// Add active class on another page linked
// ==========================================
// $(window).on('load',function () {
//     var current = location.pathname;
//     console.log(current);
//     $('#navbarSupportedContent ul li a').each(function(){
//         var $this = $(this);
//         // if the current path is like this link, make it active
//         if($this.attr('href').indexOf(current) !== -1){
//             $this.parent().addClass('active');
//             $this.parents('.menu-submenu').addClass('show-dropdown');
//             $this.parents('.menu-submenu').parent().addClass('active');
//         }else{
//             $this.parent().removeClass('active');
//         }
//     })
// });
