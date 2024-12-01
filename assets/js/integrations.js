jQuery(function ($) {
  const $searchInput = $('#wpp-plugin-search');
  const $pluginsGrid = $('#wpp-plugins-grid');

  // Snackbar function
  function showSnackbar(message, type = 'success') {
    // Remove existing snackbars
    $('.wpp-snackbar').remove();

    const $snackbar = $(`
      <div class="wpp-snackbar ${type}">
        <div class="wpp-snackbar-content">
          <i class="dashicons ${type === 'success' ? 'dashicons-yes-alt' : 'dashicons-dismiss'}"></i>
          <span>${message}</span>
        </div>
      </div>
    `).appendTo('body');

    // Animate and auto-remove
    $snackbar.addClass('show');
    setTimeout(() => {
      $snackbar.addClass('hide');
      setTimeout(() => $snackbar.remove(), 300);
    }, 3000);
  }

  function createPluginSkeletonLoader() {
    return `
            <div class="plugin-card skeleton-loader">
                <div class="skeleton-logo"></div>
                <div class="skeleton-details">
                    <div class="skeleton-name"></div>
                    <div class="skeleton-actions"></div>
                </div>
            </div>
        `;
  }

  function createPluginCard(plugin) {
    const isInstalled = plugin.is_installed;
    const isActive = plugin.is_active;
    const isIntegrated = plugin.integrated;
    const hasOptions = plugin.has_options;

    const $card = $(`
      <div 
        class="plugin-card skeleton-loading ${!isInstalled ? 'not-installed' : ''} ${!isActive ? 'not-active' : ''}"
        data-tags="${plugin.tags}"
      >
        <div class="plugin-logo">
          <img src="${plugin.logo}" alt="${plugin.name} Logo" loading="lazy">
        </div>
        <div class="plugin-details">
          <div class="plugin-content-wrapper">
            <h3>${plugin.name}</h3>
            <p>${plugin.description}</p>
            
            ${!isInstalled ? `
              <div class="plugin-notice">
                <p>${wppIntegrations.i18n.notInstalled}</p>
                <a href="${plugin.install_url}" class="button button-primary">${wppIntegrations.i18n.installPlugin}</a>
              </div>
            ` : (!isActive ? `
              <div class="plugin-notice">
                <p>${wppIntegrations.i18n.notActivated}</p>
                <a href="${plugin.activate_url}" class="button button-secondary">${wppIntegrations.i18n.activePlugin}</a>
              </div>
            ` : ``)}
          </div>
          
          ${isInstalled && isActive ? `
            <label class="plugin-toggle-container">
              <div class="plugin-toggle ${plugin.force_enable ? 'force-enable' : ''}">
                <div class="toggle-wrapper">
                  <input type="checkbox" 
                    class="integration-toggle" 
                    data-plugin="${plugin.slug}" 
                    ${isIntegrated || plugin.force_enable ? 'checked' : ''}
                    ${plugin.force_enable ? 'disabled' : ''}
                  >
                  <span class="slider round"></span>
                </div>
                <span class="toggle-label">
                  ${plugin.force_enable ? wppIntegrations.i18n.alwaysEnabled : wppIntegrations.i18n.enableIntegration}
                </span>
              </div>
              ${hasOptions && isIntegrated ? `
                <a href="${wppIntegrations.baseOptionsURL + plugin.slug}" 
                   class="button button-secondary plugin-options-btn">
                  ${wppIntegrations.i18n.options}
                </a>
              ` : ''}
            </label>
          ` : ``}
        </div>
      </div>
    `);

    // Image load handler to remove skeleton and loading state
    $card.find('.plugin-logo img').on('load', function () {
      $(this).closest('.plugin-card').removeClass('skeleton-loading');

      // If this is the first image loaded, remove all skeleton loaders
      if ($('.plugin-card.skeleton-loading').length === 0) {
        $('.skeleton-loader').remove();
      }
    });

    // Error handler in case image fails to load
    $card.find('.plugin-logo img').on('error', function () {
      $(this).closest('.plugin-card').removeClass('skeleton-loading');

      // If this is the first image processed, remove all skeleton loaders
      if ($('.plugin-card.skeleton-loading').length === 0) {
        $('.skeleton-loader').remove();
      }
    });

    // Toggle integration handler
    $card.find('.integration-toggle').on('change', function () {
      const $toggle = $(this);
      const $wrapper = $toggle.closest('.toggle-wrapper');
      const pluginSlug = $toggle.data('plugin');
      const isEnabled = $toggle.is(':checked');

      // Disable until ajax done
      $toggle.prop('disabled', true);
      $wrapper.addClass('is-disabled');

      $.ajax({
        url: wppIntegrations.ajax_url,
        method: 'POST',
        data: {
          action: 'wpp_toggle_integration',
          nonce: wppIntegrations.nonce,
          plugin_slug: pluginSlug,
          is_enabled: isEnabled
        },
        success: function (response) {
          // Enable the toggle
          $toggle.prop('disabled', false);
          $wrapper.removeClass('is-disabled');

          // Show snackbar
          showSnackbar(response.data.message);

          // Refresh options button if needed
          const $optionsBtn = $toggle.closest('.plugin-toggle-container')
            .find('.plugin-options-btn');
          if (response.data.status && wppIntegrations.supportedPlugins[pluginSlug]?.has_options) {
            if ($optionsBtn.length === 0) {
              $toggle.closest('.plugin-toggle-container').append(`
                                <a href="${wppIntegrations.baseOptionsURL + pluginSlug}" 
                                   class="button button-secondary plugin-options-btn">
                                    Options
                                </a>
                            `);
            }
          } else {
            $optionsBtn.remove();
          }
        },
        error: function () {
          // Enable the toggle
          $toggle.prop('disabled', false);
          $wrapper.removeClass('is-disabled');

          // Show error
          showSnackbar('Error processing integration', 'error');
        }
      });
    });

    return $card;
  }

  function renderPlugins(plugins) {
    // Ensure plugins is an array
    const pluginArray = Array.isArray(plugins)
      ? plugins
      : Object.values(plugins || {});

    // Render plugins directly
    pluginArray.forEach(plugin => {
      const $pluginCard = createPluginCard(plugin);
      $pluginsGrid.append($pluginCard);
    });
  }

  // Show skeleton loaders
  for (let i = 0; i < 8; i++) {
    $pluginsGrid.append(createPluginSkeletonLoader());
  }

  // Initial load
  renderPlugins(wppIntegrations.supportedPlugins);

  // Enhanced search functionality
  $searchInput.on('input', function () {
    const searchTerm = $(this).val().toLowerCase();

    if (searchTerm === '') {
      // Show all plugins if search is empty
      $pluginsGrid.find('.plugin-card').show().animate({opacity: 1, transform: 'translateY(0)'}, 300);
      return;
    }

    $pluginsGrid.find('.plugin-card').each(function () {
      const $card = $(this);
      const tags = $card.attr('data-tags').toLowerCase();

      if (tags.includes(searchTerm)) {
        $card.show().animate({opacity: 1, transform: 'translateY(0)'}, 300);
      } else {
        $card.animate({opacity: 0, transform: 'translateY(20px)'}, 300, function () {
          $card.hide();
        });
      }
    });
  });
});