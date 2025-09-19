
  (function ($) {
    const POPUP_ID = popupvars.elementor_popup_id;
    const POPUP_MODAL_SELECTOR = '#elementor-popup-modal-' + POPUP_ID;

    function apiUrl(postId) {
      // If wpApiSettings is available, use it; else fall back to origin.
      const root = (window.wpApiSettings && window.wpApiSettings.root)
        ? window.wpApiSettings.root.replace(/\/+$/, '') // trim trailing slash
        : (window.location.origin + '/wp-json');
      return root + '/studentresources/v1/post/' + postId;
    }

    function setLoadingState() {
      const $modal = $(POPUP_MODAL_SELECTOR);
      if (!$modal.length) return;
      $modal.find('.popup-post-title').text('Loading…');
      $modal.find('.popup-post-content').html('<p>Loading…</p>');
    }

    function injectContent(data) {
      const $modal = $(POPUP_MODAL_SELECTOR);
      if (!$modal.length) {
        // If popup DOM isn’t ready yet, inject once it opens.
        $(document).one('elementor/popup/show', function (event, id) {
          if (id === POPUP_ID) injectContent(data);
        });
        return;
      }

      $modal.find('.popup-post-title').text(data.post_title || '');
      $modal.find('.popup-post-content').html(data.content || '');
    }

    function showError(message) {
      const $modal = $(POPUP_MODAL_SELECTOR);
      if (!$modal.length) return;
      $modal.find('.popup-post-title').text('Oops');
      $modal.find('.popup-post-content').html('<p>' + (message || 'Unable to load this resource.') + '</p>');
    }

    $(document).on('click', 'a.resource-page-link', function (e) {
      e.preventDefault();

      const postId = parseInt($(this).data('resource-page-id'), 10);
      if (!postId) return;

      // Open the popup and prepare a loading state once it’s visible.
      $(document).one('elementor/popup/show', function (event, id) {
        if (id === POPUP_ID) setLoadingState();
      });

      // Open the Elementor popup
      if (window.elementorProFrontend && elementorProFrontend.modules && elementorProFrontend.modules.popup) {
        elementorProFrontend.modules.popup.showPopup({ id: POPUP_ID });
      }

      // Fetch the post content from your custom REST endpoint.
      fetch(apiUrl(postId), { credentials: 'same-origin' })
        .then(async (res) => {
          if (!res.ok) {
            // Try to pull WP_Error message if present
            let msg = 'Unable to load this resource.';
            try {
              const j = await res.json();
              if (j && j.message) msg = j.message;
            } catch (e) {}
            throw new Error(msg);
          }
          return res.json();
        })
        .then((data) => injectContent(data))
        .catch((err) => showError(err.message));
    });
  })(jQuery);
