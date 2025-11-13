/* eslint-disable no-undef */
(() => {
	'use strict';

	const CONTAINER_ID = 'neo-toast-container';
	let toastContainer = null;

	function ensureContainer() {
		if (!toastContainer) {
			toastContainer = document.getElementById(CONTAINER_ID);
			if (!toastContainer) {
				toastContainer = document.createElement('div');
				toastContainer.id = CONTAINER_ID;
				toastContainer.className = 'position-fixed top-0 end-0 p-3';
				toastContainer.style.zIndex = '9999';
				document.body.appendChild(toastContainer);
			}
		}
		return toastContainer;
	}

        /**
         * Shows a toast notification
         *
         * @param {string} type - Notification type: 'success', 'error', 'warning', 'info'
         * @param {string} message - Notification message
         * @param {Object} options - Configuration options
         * @param {number} options.duration - Duration in milliseconds (0 = infinite)
         * @param {string} options.title - Notification title
         * @param {boolean} options.autohide - Automatically hide notification
         * @returns {HTMLElement} Toast element for further manipulation
         */
	function toast(type, message, options = {}) {
		ensureContainer();

		const {
			duration = 5000,
			title = null,
			autohide = duration > 0,
		} = options;

		const typeConfig = {
			success: {
				bgClass: 'bg-success',
				textClass: 'text-white',
				icon: 'bi-check-circle-fill',
				defaultTitle: 'Erfolgreich',
			},
			error: {
				bgClass: 'bg-danger',
				textClass: 'text-white',
				icon: 'bi-x-circle-fill',
				defaultTitle: 'Fehler',
			},
			warning: {
				bgClass: 'bg-warning',
				textClass: 'text-dark',
				icon: 'bi-exclamation-triangle-fill',
				defaultTitle: 'Warnung',
			},
			info: {
				bgClass: 'bg-info',
				textClass: 'text-white',
				icon: 'bi-info-circle-fill',
				defaultTitle: 'Information',
			},
		};

		const config = typeConfig[type] || typeConfig.info;
		const displayTitle = title || config.defaultTitle;

		const toastId = `neo-toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

		const toastHtml = `
			<div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="${autohide}" data-bs-delay="${duration}">
				<div class="toast-header ${config.bgClass} ${config.textClass}">
					<i class="bi ${config.icon} me-2"></i>
					<strong class="me-auto">${displayTitle}</strong>
					<button type="button" class="btn-close ${config.textClass === 'text-white' ? 'btn-close-white' : ''}" data-bs-dismiss="toast" aria-label="Закрыть"></button>
				</div>
				<div class="toast-body" style="color: #000 !important;">
					${message}
				</div>
			</div>
		`;

		toastContainer.insertAdjacentHTML('beforeend', toastHtml);
		const toastElement = document.getElementById(toastId);
		const bsToast = new bootstrap.Toast(toastElement, {
			autohide: autohide,
			delay: duration,
		});

		toastElement.addEventListener('hidden.bs.toast', () => {
			toastElement.remove();
		});

		bsToast.show();

		return toastElement;
	}

	const toastMethods = {
		success: (message, options) => toast('success', message, options),
		error: (message, options) => toast('error', message, options),
		warning: (message, options) => toast('warning', message, options),
		info: (message, options) => toast('info', message, options),
	};

	function success(message, options) {
		return toastMethods.success(message, options);
	}

	function error(message, options) {
		return toastMethods.error(message, options);
	}

	function warning(message, options) {
		return toastMethods.warning(message, options);
	}

	function info(message, options) {
		return toastMethods.info(message, options);
	}

	if (typeof window.NeoDash === 'undefined') {
		window.NeoDash = {};
	}

	window.NeoDash.toast = toast;
	window.NeoDash.toastSuccess = success;
	window.NeoDash.toastError = error;
	window.NeoDash.toastWarning = warning;
	window.NeoDash.toastInfo = info;

	window.NeoDash.alert = (message, type = 'info') => {
		toast(type, message);
	};
})();

