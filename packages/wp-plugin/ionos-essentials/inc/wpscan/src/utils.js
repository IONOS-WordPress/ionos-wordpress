/* global adminUrl */
import { __ } from '@wordpress/i18n';

/**
 * Disable a button functionally and visibly.
 * uses custom css "noclick" class
 *
 * @param {Element} eButton
 */
export function disableButton( eButton ) {
	eButton.classList.add( 'button-disabled', 'noclick' );
}

/**
 * Find scan data for slug and return severity
 *
 * @param {string} slug
 * @return {string|false} severity
 */

export function getSeverityBySlug( slug ) {
	// window.parent is needed for iframe cases. works for non iframe cases, because window.parent === window
	return window.parent.wpScan?.find( ( _ ) => _.slug === slug )?.severity;
}

/**
 * Adds a notice with the specified text and classes and appends them to the given eParentNode
 *
 * @param {string}  severity    "none", "medium", "high" or "unknown"
 * @param {boolean} themeStatus "scanned", "installed" or "active"
 * @param {Element} eParentNode
 * @param {string}  selector    can be used to specify what element to append before, default is first childElement.
 */

export function addResultNotice(
	severity,
	themeStatus,
	eParentNode,
	selector
) {
	if (
		! [ 'none', 'medium', 'high', 'unknown' ].includes( severity ) ||
		! [ 'scanned', 'installed', 'active', 'updatable' ].includes(
			themeStatus
		)
	) {
		return;
	}

	// dont render notice for themes without vulnerabilities
	if (
		( themeStatus === 'installed' || themeStatus === 'active' ) &&
		( severity === 'unknown' || severity === 'none' )
	) {
		return;
	}
	const baseTexts = {
		none: __(
			'The vulnerability scan did not find any issues.',
			'ionos-security'
		),
		unknown: __(
			'The vulnerability scan did not produce any results.',
			'ionos-security'
		),
		medium: __(
			'The vulnerability scan has found issues.',
			'ionos-security'
		),
		high: __(
			'The vulnerability scan has found critical issues.',
			'ionos-security'
		),
	};

	// next best actions (based on severity and themeStatus)
	const nbaTexts = {
		unknown: {
			installed: __( 'Activation is not recommended.', 'ionos-security' ),
			scanned: __( 'Installation is not recommended.', 'ionos-security' ),
		},
		medium: {
			installed: __( 'Activation is not recommended.', 'ionos-security' ),
			scanned: __( 'Installation is not recommended.', 'ionos-security' ),
		},
		high: {
			installed: __( 'Activation is not possible.', 'ionos-security' ),
			scanned: __( 'Installation is not possible.', 'ionos-security' ),
		},
	};

	const nbaText = nbaTexts[ severity ]?.[ themeStatus ];
	// adminUrl as set by php code. only makes sense if installed already	const moreInfoAnchor =
	const moreInfoAnchor =
		themeStatus !== 'scanned' &&
		`<a href="${ adminUrl }" target="_parent">${ __(
			'More information.',
			'ionos-security'
		) }</a>`;
	const noticeText = [ baseTexts[ severity ], nbaText, moreInfoAnchor ]
		.filter( ( _ ) => _ )
		.join( ' ' );

	const noticeClasses = {
		none: 'notice-success',
		unknown: 'notice-warning',
		medium: 'notice-warning',
		high: 'notice-error',
	};

	const wpscanNotice = document.createElement( 'div' );
	// weird: setting outerHTML only works on Elements in DOM. Therefore insert first, then set outerHTML
	eParentNode.insertBefore(
		wpscanNotice,
		selector
			? eParentNode.querySelector( selector )
			: eParentNode.firstChild
	);
	wpscanNotice.outerHTML = `<div class="notice notice-alt ${ noticeClasses[ severity ] } inline"><p>${ noticeText }</p></div>`;
}

/**
 * On the trimary Button is an indicator if the theme/plugin is active, installed, or not installed yet.
 *
 * @param {Element} ePrimaryButton
 * @return
 */

export function getStatusByButton( ePrimaryButton ) {
	if (
		ePrimaryButton.disabled ||
		ePrimaryButton.classList.contains( 'disabled' )
	) {
		return 'active';
	} else if (
		ePrimaryButton.classList.contains( 'activate' ) ||
		ePrimaryButton.classList.contains( 'activate-now' )
	) {
		return 'installed';
	} else if ( ePrimaryButton.classList.contains( 'update-now' ) ) {
		return 'updatable';
	}
	return 'scanned';
}
