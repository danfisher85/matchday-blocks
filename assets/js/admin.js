/**
 * Admin JavaScript
 *
 * Handles admin page interactions for Matchday Blocks.
 *
 * @package   Matchday_Blocks
 * @since     1.0.0
 */

(function ($) {
	'use strict';

	/**
	 * Initialize admin functionality
	 */
	function init() {
		handleCacheClear();
	}

	/**
	 * Handle cache clear button click
	 */
	function handleCacheClear() {
		const $clearBtn = $('#matchday-clear-cache-btn');
		const $message = $('#matchday-cache-message');

		if (!$clearBtn.length) {
			return;
		}

		$clearBtn.on('click', function (e) {
			e.preventDefault();

			// Confirm action
			if (!confirm(matchdayBlocks.i18n.confirmClear)) {
				return;
			}

			const $btn = $(this);
			const originalText = $btn.text();

			// Disable button and show loading state
			$btn.prop('disabled', true).text(matchdayBlocks.i18n.clearing);
			$message.removeClass('success error').text('');

			// Make AJAX request
			$.ajax({
				url: matchdayBlocks.ajaxUrl,
				type: 'POST',
				data: {
					action: 'matchday_blocks_clear_cache',
					nonce: matchdayBlocks.nonce,
				},
				success: function (response) {
					if (response.success) {
						$message
							.addClass('success')
							.css('color', '#46b450')
							.text(response.data.message);

						// Reload page after 1 second to update cache status
						setTimeout(function () {
							location.reload();
						}, 1000);
					} else {
						$message
							.addClass('error')
							.css('color', '#dc3232')
							.text(response.data.message || matchdayBlocks.i18n.error);
						$btn.prop('disabled', false).text(originalText);
					}
				},
				error: function () {
					$message
						.addClass('error')
						.css('color', '#dc3232')
						.text(matchdayBlocks.i18n.error);
					$btn.prop('disabled', false).text(originalText);
				},
			});
		});
	}

	// Initialize on document ready
	$(document).ready(init);
})(jQuery);
