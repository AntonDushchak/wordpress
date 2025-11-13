/* eslint-disable no-undef */
(() => {
	'use strict';

        /**
         * Creates and shows a confirmation modal window
         *
         * @param {string} message - Message to display
         * @param {Object} options - Configuration options
         * @param {string} options.title - Modal window title
         * @param {string} options.confirmText - Text for the confirmation button
         * @param {string} options.cancelText - Text for the cancel button
         * @param {string} options.type - Window type: 'danger', 'warning', 'info', 'success'
         * @param {Function} options.onConfirm - Callback on confirmation
         * @param {Function} options.onCancel - Callback on cancellation
         * @returns {Promise<boolean>} Promise that resolves to true on confirmation, false on cancellation
         */
	function confirm(message, options = {}) {
		return new Promise((resolve) => {
			const {
				title = 'Bestätigung',
				confirmText = 'Bestätigen',
				cancelText = 'Abbrechen',
				type = 'warning',
				onConfirm = null,
				onCancel = null,
			} = options;

			const existingModal = document.getElementById('neo-confirm-modal');
			if (existingModal) {
				const bsModal = bootstrap.Modal.getInstance(existingModal);
				if (bsModal) {
					bsModal.dispose();
				}
				existingModal.remove();
			}

			const typeClasses = {
				danger: {
					header: 'bg-danger text-white',
					confirmBtn: 'btn-danger',
					icon: 'bi-exclamation-triangle-fill',
				},
				warning: {
					header: 'bg-warning text-dark',
					confirmBtn: 'btn-warning',
					icon: 'bi-exclamation-triangle-fill',
				},
				info: {
					header: 'bg-info text-white',
					confirmBtn: 'btn-info',
					icon: 'bi-info-circle-fill',
				},
				success: {
					header: 'bg-success text-white',
					confirmBtn: 'btn-success',
					icon: 'bi-check-circle-fill',
				},
			};

			const typeConfig = typeClasses[type] || typeClasses.warning;

			const modalId = 'neo-confirm-modal';
			const modalHtml = `
				<div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
					<div class="modal-dialog modal-dialog-centered">
						<div class="modal-content">
							<div class="modal-header ${typeConfig.header}">
								<h5 class="modal-title d-flex align-items-center gap-2" id="${modalId}Label">
									<i class="bi ${typeConfig.icon}"></i>
									${title}
								</h5>
								<button type="button" class="btn-close ${type === 'warning' ? 'btn-close-white' : ''}" data-bs-dismiss="modal" aria-label="Закрыть"></button>
							</div>
							<div class="modal-body">
								<p class="mb-0">${message}</p>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="${modalId}-cancel">
									${cancelText}
								</button>
								<button type="button" class="btn ${typeConfig.confirmBtn}" id="${modalId}-confirm">
									${confirmText}
								</button>
							</div>
						</div>
					</div>
				</div>
			`;

			document.body.insertAdjacentHTML('beforeend', modalHtml);
			const modalElement = document.getElementById(modalId);
			const bsModal = new bootstrap.Modal(modalElement, {
				backdrop: 'static',
				keyboard: false,
			});

			const confirmBtn = document.getElementById(`${modalId}-confirm`);
			confirmBtn.addEventListener('click', () => {
				bsModal.hide();
				
				modalElement.addEventListener('hidden.bs.modal', () => {
					modalElement.remove();
					if (onConfirm) {
						onConfirm();
					}
				}, { once: true });

				resolve(true);
			});

			const cancelBtn = document.getElementById(`${modalId}-cancel`);
			const cancelHandler = () => {
				bsModal.hide();
				
				modalElement.addEventListener('hidden.bs.modal', () => {
					modalElement.remove();
					if (onCancel) {
						onCancel();
					}
				}, { once: true });

				resolve(false);
			};

			cancelBtn.addEventListener('click', cancelHandler);
			
			const backdrop = document.querySelector('.modal-backdrop');
			if (backdrop) {
				backdrop.addEventListener('click', cancelHandler);
			}

			bsModal.show();
		});
	}

	if (typeof window.NeoDash === 'undefined') {
		window.NeoDash = {};
	}
	
	window.NeoDash.confirm = confirm;
})();

