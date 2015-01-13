(function($){
	$(window).on('load', function(){
		$('#submit-searchboxitemdiv').on('click', function(e){
			e.preventDefault();
			$('#searchboxitemdiv').addSelectedToMenu();
		});
		
		$('#searchboxitemdiv a.bop-nav-search-box-item-view-more').on('click', function(e){
			e.preventDefault();
			var $howto = $('#searchboxitemdiv p.howto');
			if( $howto.hasClass('bop-nav-search-box-item-showing') ){
				$howto.removeClass('bop-nav-search-box-item-showing');
				$(this).html(bop_nav_search_box_item_admin_script_local.show_dev_info);
			}else{
				$howto.addClass('bop-nav-search-box-item-showing');
				$(this).html(bop_nav_search_box_item_admin_script_local.hide_dev_info);
			}
		}).trigger('click').trigger('click');
	});
})(jQuery);