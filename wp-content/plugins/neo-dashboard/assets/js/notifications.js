/**
 * Neo Dashboard – Notification Client
 * -----------------------------------
 * v3.1.0 – 20 May 2025
 *
 * Ruft aktive Notifications via REST-API ab,
 * rendert sie als Bootstrap-Alerts und sorgt
 * für persistentes Dismiss (POST /dismiss).
 */

/* eslint-disable no-undef */
(() => {
	'use strict';

	// ---------------------------------------------------------------------
	// Konfiguration
	// ---------------------------------------------------------------------
	const CONFIG = {
		apiRoot:
			(typeof NeoDash !== 'undefined' && NeoDash.restUrl) ||
			(window?.wpApiSettings?.root ?? '/wp-json/neo-dashboard/v1'),
		nonce:
			(typeof NeoDash !== 'undefined' && NeoDash.nonce) ||
			window?.wpApiSettings?.nonce ||
			'',
		containerSelector: '#neo-notification-container',
	};

	const log = (...args) => {
		/* global console */
		if (window.localStorage?.getItem('neoDashDebug') === '1') {
			console.debug('[NotificationClient]', ...args);
		}
	};

	// ---------------------------------------------------------------------
	// Helper
	// ---------------------------------------------------------------------

	/**
	 * Liefert HTTP-Header inkl. Nonce, sofern vorhanden.
	 */
	const defaultHeaders = () => {
		const headers = { 'Content-Type': 'application/json' };
		if (CONFIG.nonce) {
			headers['X-WP-Nonce'] = CONFIG.nonce;
		}
		return headers;
	};

	/**
	 * Erstellt ein Alert-Element aus einer Notification-Payload.
	 *
	 * @param {{id:string, message:string, type:string, dismissible:boolean}} note
	 * @returns {HTMLElement}
	 */
	const createAlert = (note) => {
		const alert = document.createElement('div');
		alert.className = `alert alert-${note.type} ${
			note.dismissible ? 'alert-dismissible' : ''
		} fade show mb-2`;
		alert.setAttribute('role', 'alert');
		alert.dataset.id = note.id;

		alert.innerHTML = `
            <span class="neo-note-message">${note.message}</span>
            ${
				note.dismissible
					? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
					: ''
			}
        `;

		if (note.dismissible) {
			// Bootstrap `closed.bs.alert` Event feuert NACH Animation
			alert.addEventListener('closed.bs.alert', () => dismiss(note.id));
		}

		return alert;
	};

	/**
	 * POST /dismiss, anschließend Eintrag lokal entfernen.
	 *
	 * @param {string} id
	 */
	const dismiss = async (id) => {
		try {
			await fetch(`${CONFIG.apiRoot}/notifications/${id}/dismiss`, {
				method: 'POST',
				headers: defaultHeaders(),
			});
			log('dismissed', id);
		} catch (err) /* istanbul ignore next */ {
			console.error('Dismiss failed', err);
		}
	};

	/**
	 * Lädt Notifications, rendert sie in den Container.
	 */
	const loadNotifications = async () => {
		const container = document.querySelector(CONFIG.containerSelector);
		if (!container) {
			log('Container not found – abort');
			return;
		}

		try {
			const res = await fetch(`${CONFIG.apiRoot}/notifications`, {
				headers: defaultHeaders(),
			});
			if (!res.ok) throw new Error(res.statusText);
			const list = await res.json();

			// Aufräumen & neu rendern
			container.innerHTML = '';
			list.forEach((note) => container.appendChild(createAlert(note)));

			log('rendered', list.length, 'notifications');
		} catch (err) {
			console.error('Load failed', err);
		}
	};

	// ---------------------------------------------------------------------
	// Init
	// ---------------------------------------------------------------------
	document.addEventListener('DOMContentLoaded', loadNotifications);
})();
