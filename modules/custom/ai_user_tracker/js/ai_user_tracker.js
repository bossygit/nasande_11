(function ($, Drupal, drupalSettings) {
  'use strict';

  function uuidv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }

  function getSessionId() {
    try {
      var key = 'ai_tracker_sid';
      var sid = localStorage.getItem(key);
      if (!sid) {
        sid = uuidv4();
        localStorage.setItem(key, sid);
      }
      return sid;
    } catch (e) {
      return null;
    }
  }

  function postEvent(eventType, meta) {
    var payload = {
      event_type: eventType,
      page_path: window.location.pathname + window.location.search,
      metadata: meta || {},
      session_id: getSessionId()
    };

    try {
      navigator.sendBeacon('/api/user/behavior/track', new Blob([JSON.stringify(payload)], { type: 'application/json' }));
    } catch (e) {
      $.ajax({
        url: '/api/user/behavior/track',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload)
      });
    }

    // Bridge GA4 if available
    if (window.gtag) {
      window.gtag('event', eventType, meta || {});
    }
  }

  Drupal.behaviors.aiUserTracker = {
    attach: function (context, settings) {
      var path = window.location.pathname || '';

      // Page view
      if (!once('aiUserTrackerPage', 'html', context).length) {
        postEvent('page_view', { title: document.title });
      }

      // Clicks
      $(document, context).on('click', 'a, button, [data-track-click]', function () {
        var $el = $(this);
        postEvent('click', {
          tag: this.tagName,
          text: ($el.text() || '').trim().slice(0, 120),
          href: $el.attr('href') || null,
          id: $el.attr('id') || null,
          classes: ($el.attr('class') || '').split(/\s+/).slice(0, 10)
        });
      });

      // Scroll depth (25/50/75/100)
      var milestones = {25:false,50:false,75:false,100:false};
      $(window).on('scroll.aiTracker', function () {
        var doc = $(document).height() - $(window).height();
        if (doc <= 0) return;
        var scrolled = Math.round($(window).scrollTop() / doc * 100);
        [25,50,75,100].forEach(function (m) {
          if (!milestones[m] && scrolled >= m) {
            milestones[m] = true;
            postEvent('scroll_depth', { percent: m });
          }
        });
      });

      // Time on page (15s, 30s, 60s)
      [15, 30, 60].forEach(function (t) {
        setTimeout(function () { postEvent('time_on_page', { seconds: t }); }, t * 1000);
      });

      // Cart abandonment hook placeholder (requires Commerce integration)
      $(window).on('beforeunload', function () {
        // Placeholder: implement detection if cart has items and user leaves.
      });

      // --- GA4 Ecommerce mapping ---
      function gtagEvent(name, params) {
        if (window.gtag) {
          window.gtag('event', name, params || {});
        }
      }

      function extractOrderIdFromPath(p) {
        var m = p.match(/\/checkout\/(\d+)\/complete$/);
        return m ? m[1] : null;
      }

      // Begin checkout (any checkout route except complete)
      if (/\/checkout(?!\/[0-9]+\/complete)/.test(path)) {
        gtagEvent('begin_checkout', {});
      }

      // Purchase (checkout complete)
      if (/\/checkout\/[0-9]+\/complete$/.test(path)) {
        var orderId = extractOrderIdFromPath(path);
        var purchaseParams = { transaction_id: orderId };
        // Merge server-provided order details if available.
        try {
          var serverPurchase = (settings && settings.ai_user_tracker && settings.ai_user_tracker.ga4Purchase) ? settings.ai_user_tracker.ga4Purchase : null;
          if (serverPurchase) {
            purchaseParams.transaction_id = serverPurchase.transaction_id || purchaseParams.transaction_id;
            if (serverPurchase.value) purchaseParams.value = parseFloat(serverPurchase.value);
            if (serverPurchase.currency) purchaseParams.currency = serverPurchase.currency;
            if (serverPurchase.items) purchaseParams.items = serverPurchase.items;
          }
        } catch (e) {}
        gtagEvent('purchase', purchaseParams);
      }

      // Add to cart: intercept add-to-cart form submissions
      $(document, context).on('submit', 'form.commerce-order-add-to-cart-form, form[action*="/cart/add"]', function () {
        var $form = $(this);
        var productId = $form.find('[name="purchasable_entity_id"], [name="product_id"], [name="product"]').val();
        var qty = parseInt($form.find('[name="quantity"]').val() || '1', 10);
        var price = parseFloat($form.find('[data-role="price"],[name="price"],[data-price]').first().data('price') || '0');
        var name = ($form.find('[data-role="title"],[data-title]').first().data('title') || '').toString();
        var currency = ($form.find('[data-role="currency"],[data-currency]').first().data('currency') || '').toString();
        var params = {
          currency: currency || undefined,
          value: isNaN(price) ? undefined : price * (isNaN(qty) ? 1 : qty),
          items: [{ item_id: productId || undefined, item_name: name || undefined, price: isNaN(price) ? undefined : price, quantity: isNaN(qty) ? 1 : qty }]
        };
        gtagEvent('add_to_cart', params);
      });

      // Remove from cart: capture remove actions
      $(document, context).on('click', 'a[href*="/cart/remove"], button[name="remove"]', function () {
        var $btn = $(this);
        var productId = $btn.data('product-id') || undefined;
        var price = parseFloat($btn.data('price') || '0');
        var name = ($btn.data('title') || '').toString();
        var currency = ($btn.data('currency') || '').toString();
        var params = {
          currency: currency || undefined,
          value: isNaN(price) ? undefined : price,
          items: [{ item_id: productId, item_name: name || undefined, price: isNaN(price) ? undefined : price, quantity: 1 }]
        };
        gtagEvent('remove_from_cart', params);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);


