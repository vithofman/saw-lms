/* SAW LMS – Editor Metabox Unlock (kill divider & fixed height) */
(function () {
	// bezpečně až po inicializaci BE
	if (window.wp && window.wp.domReady) {
		wp.domReady(run);
	} else {
		document.addEventListener('DOMContentLoaded', run);
	}

	function applyFix(root) {
		if (!root) root = document;

		var area = root.querySelector('.edit-post-meta-boxes-area');
		if (!area) return;

		// ResizableBox container
		var containers = area.querySelectorAll('.components-resizable-box__container');
		containers.forEach(function (c) {
			c.style.height = 'auto';
			c.style.maxHeight = 'none';
			c.style.overflow = 'visible';
			c.style.resize = 'none';
		});

		// Kill all handles (divider)
		var handles = area.querySelectorAll('[class*="resizable-box__handle"]');
		handles.forEach(function (h) { h.remove(); });

		// Parent must not clip
		area.style.height = 'auto';
		area.style.maxHeight = 'none';
		area.style.overflow = 'visible';

		// One scroll for whole page
		var content = document.querySelector('.interface-interface-skeleton__content');
		if (content) {
			content.style.overflow = 'visible';
		}
	}

	function run() {
		// pouze naše CPT
		var body = document.body;
		if (!body) return;
		if (!/(post-type-saw_course|post-type-saw_section|post-type-saw_lesson|post-type-saw_quiz)/.test(body.className)) {
			return;
		}

		// jednorázově smaž uložené výšky/resize preferencí
		try {
			Object.keys(localStorage).forEach(function (k) {
				if (k.indexOf('metabox') !== -1 || k.indexOf('edit-post') !== -1) {
					localStorage.removeItem(k);
				}
			});
		} catch (e) {}

		// Aplikuj fix ihned
		applyFix(document);

		// Sleduj re-render (Gutenberg přemontovává oblast metaboxů)
		var mo = new MutationObserver(function (muts) {
			for (var i = 0; i < muts.length; i++) {
				var m = muts[i];
				if (!m.addedNodes) continue;
				for (var j = 0; j < m.addedNodes.length; j++) {
					var n = m.addedNodes[j];
					if (!(n instanceof HTMLElement)) continue;
					if (
						n.matches('.edit-post-meta-boxes-area') ||
						n.querySelector?.('.edit-post-meta-boxes-area')
					) {
						applyFix(n);
					}
					// kdyby se přidaly nové handle
					if (n.matches?.('[class*="resizable-box__handle"]') || n.querySelector?.('[class*="resizable-box__handle"]')) {
						applyFix(document);
					}
				}
			}
		});
		mo.observe(document.body, { childList: true, subtree: true });
	}
})();
