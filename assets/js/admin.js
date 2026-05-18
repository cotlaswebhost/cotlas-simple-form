(function($) {
	'use strict';

	function loadDashboardTab($dashboard, tab, settingsTab, formType) {
		var $tabs = $dashboard.find('[data-csf-dashboard-tab]');
		var $panel = $dashboard.find('[data-csf-dashboard-panel]');

		$tabs.removeClass('is-active').attr('aria-selected', 'false');
		if (tab === 'add_form' && formType) {
			$tabs.filter('[data-tab="' + tab + '"][data-form-type="' + formType + '"]').addClass('is-active').attr('aria-selected', 'true');
		} else {
			$tabs.filter('[data-tab="' + tab + '"]').not('[data-form-type]').addClass('is-active').attr('aria-selected', 'true');
		}
		$panel.addClass('is-loading').html('<div class="csf-loading">Loading...</div>');

		$.post(csfAdmin.ajaxUrl, {
			action: 'csf_dashboard_tab',
			nonce: csfAdmin.nonce,
			tab: tab,
			settings_tab: settingsTab || '',
			form_type: formType || ''
		}).done(function(response) {
			if (response && response.success && response.data && response.data.html) {
				$panel.html(response.data.html);
				window.history.replaceState(null, '', response.data.url);
				return;
			}

			$panel.html('<div class="notice notice-error"><p>Unable to load this section.</p></div>');
		}).fail(function() {
			$panel.html('<div class="notice notice-error"><p>Unable to load this section.</p></div>');
		}).always(function() {
			$panel.removeClass('is-loading');
		});
	}

	$(document).on('click', '[data-csf-dashboard-tab], [data-csf-dashboard-action]', function(event) {
		var $button = $(this);
		var tab = $button.data('tab');
		var settingsTab = $button.data('settings-tab') || '';
		var formType = $button.data('form-type') || '';
		var $dashboard = $button.closest('.csf-admin-dashboard');

		if (!tab || !$dashboard.length) {
			return;
		}

		event.preventDefault();
		loadDashboardTab($dashboard, tab, settingsTab, formType);
	});

	$(document).on('submit', '[data-csf-create-form]', function(event) {
		var $form = $(this);
		var $result = $form.find('[data-csf-create-result]');

		event.preventDefault();
		$result.empty();

		$.post(csfAdmin.ajaxUrl, $form.serialize() + '&action=csf_dashboard_create_form&nonce=' + encodeURIComponent(csfAdmin.nonce))
			.done(function(response) {
				if (response && response.success && response.data && response.data.redirect) {
					window.location.href = response.data.redirect;
					return;
				}

				var message = response && response.data && response.data.message ? response.data.message : 'Unable to create the form.';
				$result.html('<div class="notice notice-error inline"><p>' + message + '</p></div>');
			})
			.fail(function() {
				$result.html('<div class="notice notice-error inline"><p>Unable to create the form.</p></div>');
			});
	});

	$(document).on('submit', '[data-csf-settings-form]', function(event) {
		var $form = $(this);
		var $result = $form.find('[data-csf-settings-result]');

		event.preventDefault();
		$result.empty();

		$.post(csfAdmin.ajaxUrl, $form.serialize() + '&action=csf_dashboard_save_settings&nonce=' + encodeURIComponent(csfAdmin.nonce))
			.done(function(response) {
				var message = response && response.data && response.data.message ? response.data.message : 'Settings saved.';
				var type = response && response.success ? 'success' : 'error';
				$result.html('<div class="notice notice-' + type + ' inline"><p>' + message + '</p></div>');
			})
			.fail(function() {
				$result.html('<div class="notice notice-error inline"><p>Unable to save settings.</p></div>');
			});
	});

	$(document).on('click', '[data-csf-view-submission]', function(event) {
		var $button = $(this);
		var submissionId = $button.data('csf-view-submission');
		var $panel = $('[data-csf-submission-result]').first();

		event.preventDefault();
		$panel.html('<div class="csf-loading">Loading submission...</div>');

		$.post(csfAdmin.ajaxUrl, {
			action: 'csf_dashboard_view_submission',
			nonce: csfAdmin.nonce,
			submission_id: submissionId
		}).done(function(response) {
			if (response && response.success && response.data && response.data.html) {
				$panel.html(response.data.html);
				return;
			}

			var message = response && response.data && response.data.message ? response.data.message : 'Unable to load submission.';
			$panel.html('<div class="notice notice-error inline"><p>' + message + '</p></div>');
		}).fail(function() {
			$panel.html('<div class="notice notice-error inline"><p>Unable to load submission.</p></div>');
		});
	});

	$(document).on('click', '[data-csf-delete-submission]', function(event) {
		var $button = $(this);
		var submissionId = $button.data('csf-delete-submission');
		var $row = $('[data-submission-row="' + submissionId + '"]');
		var $panel = $('[data-csf-submission-result]').first();

		event.preventDefault();
		if (!window.confirm('Delete this submission permanently?')) {
			return;
		}

		$.post(csfAdmin.ajaxUrl, {
			action: 'csf_dashboard_delete_submission',
			nonce: csfAdmin.nonce,
			submission_id: submissionId
		}).done(function(response) {
			if (response && response.success) {
				$row.remove();
				$panel.html('<div class="notice notice-success inline"><p>Submission deleted.</p></div>');
				return;
			}

			var message = response && response.data && response.data.message ? response.data.message : 'Unable to delete submission.';
			$panel.html('<div class="notice notice-error inline"><p>' + message + '</p></div>');
		}).fail(function() {
			$panel.html('<div class="notice notice-error inline"><p>Unable to delete submission.</p></div>');
		});
	});
})(jQuery);
