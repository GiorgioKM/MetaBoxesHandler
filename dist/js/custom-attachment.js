(function($) {
	if ($('.custom-attachment').length > 0) {
		if ( typeof wp !== 'undefined' && wp.media && wp.media.editor) {
			$('.wrap').on('click', '.custom-attachment', function(e) {
				e.preventDefault();
				var button = $(this);
				
				var $input = button.parent().next("td").find("input");
				
				wp.media.editor.send.attachment = function(props, attachment) {
					$input.val(attachment.url);
				};
				
				wp.media.editor.open(button);
				
				return false;
			});
		}
	};
})(jQuery);