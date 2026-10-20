(function () {
	'use strict';

	var seen = new WeakSet();
	var pending = new WeakSet();
	var resumed = new WeakSet();

	function showError(form) {
		var container = form.closest('.et_pb_contact_form_container');
		var message = container && container.querySelector('.et-pb-contact-message');
		if (!message) {
			return;
		}
		var error = document.createElement('p');
		error.className = 'et_pb_contact_error_text';
		error.setAttribute('role', 'alert');
		error.textContent = window.wbcDiviCaptcha.error;
		message.replaceChildren(error);
	}

	// Divi serializes synchronously. Wait for a fresh single-use v3 token before
	// allowing its existing AJAX submit handler to run.
	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!(form instanceof HTMLFormElement) || !form.querySelector('.wbc-divi-captcha')) {
			return;
		}
		if (resumed.has(form)) {
			resumed.delete(form);
			return;
		}
		var field = form.querySelector('.wbc-divi-captcha input[name="wbc_recaptcha_divi_token"]');
		if (!field) {
			return;
		}
		event.preventDefault();
		event.stopImmediatePropagation();
		if (pending.has(form)) {
			return;
		}
		pending.add(form);
		var submitter = event.submitter;
		var refresh = window.wbcRecaptchaV3;
		var job = refresh ? refresh.refresh(field.id) : Promise.resolve('');
		job.then(function (token) {
			pending.delete(form);
			if (!token || !form.isConnected) {
				showError(form);
				return;
			}
			resumed.add(form);
			form.requestSubmit(submitter || undefined);
		}).catch(function () {
			pending.delete(form);
			showError(form);
		});
	}, true);

	function initializeReplacement(wrapper, attempt) {
		if (!wrapper.isConnected) {
			return;
		}
		var widget = wrapper.querySelector('.g-recaptcha, .h-captcha, .cf-turnstile');
		if (!widget || widget.querySelector('iframe')) {
			return;
		}
		var api = widget.classList.contains('h-captcha') ? window.hcaptcha :
			(widget.classList.contains('cf-turnstile') ? window.turnstile : window.grecaptcha);
		if (!api || typeof api.render !== 'function') {
			if (attempt < 100) {
				setTimeout(function () { initializeReplacement(wrapper, attempt + 1); }, 200);
			}
			return;
		}
		var options = {sitekey: widget.dataset.sitekey};
		['theme', 'size', 'badge'].forEach(function (key) {
			if (widget.dataset[key]) {
				options[key] = widget.dataset[key];
			}
		});
		var callback = window[widget.dataset.callback];
		if (typeof callback === 'function') {
			options.callback = callback;
		}
		try {
			api.render(widget, options);
		} catch (error) {
			// Provider auto-render can win the race; do not render a second widget.
			console.debug('WBC Divi CAPTCHA initialization:', error);
		}
	}

	function scan(initial) {
		document.querySelectorAll('.wbc-divi-captcha').forEach(function (wrapper) {
			if (seen.has(wrapper)) {
				return;
			}
			seen.add(wrapper);
			if (!initial) {
				// jQuery.load(selector) strips the response's scripts. Existing v3
				// refreshers use stable form IDs; explicit widgets must render again.
				initializeReplacement(wrapper, 0);
			}
		});
	}

	function start() {
		scan(true);
		new MutationObserver(function (records) {
			if (records.some(function (record) { return record.addedNodes.length; })) {
				scan(false);
			}
		}).observe(document.body, {childList: true, subtree: true});
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
