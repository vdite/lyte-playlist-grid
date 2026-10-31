/**
 * LYTE Playlist Grid: opens grid videos in a lightbox instead of LYTE's inline player.
 * Browsers without <dialog> support fall back to the inline player.
 */
( function () {
	'use strict';

	if ( typeof HTMLDialogElement !== 'function' ) {
		return;
	}

	var dialog, frame, closeBtn;

	function build() {
		dialog = document.createElement( 'dialog' );
		dialog.className = 'lpg-lightbox';
		closeBtn = document.createElement( 'button' );
		closeBtn.type = 'button';
		closeBtn.className = 'lpg-lightbox-close';
		closeBtn.textContent = '×';
		closeBtn.addEventListener( 'click', function () {
			dialog.close();
		} );
		frame = document.createElement( 'div' );
		frame.className = 'lpg-lightbox-frame';
		dialog.appendChild( closeBtn );
		dialog.appendChild( frame );
		// Clicks on the backdrop hit the dialog element itself.
		dialog.addEventListener( 'click', function ( e ) {
			if ( e.target === dialog ) {
				dialog.close();
			}
		} );
		// Removing the iframe stops playback (also after Esc).
		dialog.addEventListener( 'close', function () {
			frame.textContent = '';
			document.documentElement.classList.remove( 'lpg-lightbox-open' );
		} );
		document.body.appendChild( dialog );
	}

	function open( id, title, closeLabel ) {
		if ( ! dialog ) {
			build();
		}
		var iframe = document.createElement( 'iframe' );
		iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0&playsinline=1';
		iframe.title = title;
		iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
		iframe.allowFullscreen = true;
		frame.textContent = '';
		frame.appendChild( iframe );
		dialog.setAttribute( 'aria-label', title );
		closeBtn.setAttribute( 'aria-label', closeLabel );
		document.documentElement.classList.add( 'lpg-lightbox-open' );
		dialog.showModal();
		closeBtn.focus();
	}

	// Capture phase: runs before LYTE's own click handler on the thumbnail.
	document.addEventListener( 'click', function ( e ) {
		var player = e.target.closest && e.target.closest( '[data-lpg-lightbox] .lyte-playlist-player' );
		var item = player && player.closest( '[data-lpg-video]' );
		if ( ! item || player.querySelector( 'iframe' ) ) {
			return;
		}
		var id = item.getAttribute( 'data-lpg-video' );
		if ( ! /^[A-Za-z0-9_-]{11}$/.test( id ) ) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		var title = item.querySelector( '.lyte-playlist-title' );
		var grid = item.closest( '[data-lpg-lightbox]' );
		open( id, title ? title.textContent : '', grid.getAttribute( 'data-lpg-close' ) || 'Close' );
	}, true );
}() );
