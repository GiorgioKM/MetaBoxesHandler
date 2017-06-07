(function($) {
	if ( typeof wp !== 'undefined' && wp.media && wp.media.editor) {
		$('body').on('click', '[data-action="add-media"]', function(e) {
			e.preventDefault();
			
			var $this = $(e.target);
			
			var $target = $($(this).data('target'));
			var $preview = ($(this).data('preview') ? $($(this).data('preview')) : $(this));
			
			var frame = wp.media({
				title: mbh_vars.select_media_image,
				button: {
					text: mbh_vars.use_this_image
				},
				multiple: false
			});
			
			frame.on('select', function() {
				var attachment = frame.state().get('selection').first().toJSON();
				var previewImg = (attachment.sizes.thumbnail == undefined ? attachment.sizes.full : attachment.sizes.thumbnail);
				
				$preview.addClass('loading-image');
				$preview.html('<img src="'+ previewImg.url +'" srcset="'+ attachment.sizes.full.url +' '+ attachment.sizes.full.width +'w" data-action="add-media" data-target="'+ $preview.data('target') +'" data-preview="'+ $preview.data('preview') +'" style="width: 100%; height: auto; cursor: pointer;"><br><p><b style="color:#922;">'+ mbh_vars.save_confirm_mod +'</b></p>');
				$preview.parent().find('[data-action="detach-media"]').show();
				$target.val(attachment.id);
				
				if ($this.is('a.button'))
					$this.html(mbh_vars.change_image);
			});
			
			frame.on('open', function(){
				var selection = frame.state().get('selection');
				var selected = $target.val();
				if (selected) {
					selection.add(wp.media.attachment(selected));
				}
			});
			
			frame.open();
		});
		
		$('[data-action="detach-media"]').click(function(e) {
			e.preventDefault();
			
			var $this = $(e.target);
			
			var $target = $($(this).data('target'));
			var $preview = $($(this).data('preview'));
			
			$preview.removeClass('loading-image');
			$preview.html('<p><b style="color:#922;">'+ mbh_vars.save_confirm_delete +'</b></p>');
			$preview.off('click');
			$target.val('');
			
			$this.prev('[data-action="add-media"]').html(mbh_vars.set_image);
			
			$(this).hide();
		});
	}
})(jQuery);