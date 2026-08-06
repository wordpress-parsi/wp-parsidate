/******/ (() => { // webpackBootstrap
/*!**********************************************************!*\
  !*** ./assets/js-src/woocommerce-checkout-block-city.js ***!
  \**********************************************************/
(function () {
  'use strict';

  if (!window.WpPdWcBlockCityData || !window.WpPdWcBlockCityData.cities) {
    return;
  }

  class WpPdWcCitySelector {
    constructor(cfg) {
      this.CFG = cfg;
      this.queued = false;
      this.init();
    }

    wpData() {
      return (window.wp && window.wp.data) ? window.wp.data : null;
    }

    getAddress(type) {
      const wpDataStore = this.wpData();
      if (!wpDataStore) {
        return {};
      }
      let cartSelector;
      try {
        cartSelector = wpDataStore.select('wc/store/cart');
      } catch (e) {
        return {};
      }
      if (!cartSelector || !cartSelector.getCustomerData) {
        return {};
      }
      const c = cartSelector.getCustomerData() || {};
      if (type === 'billing') {
        return c.billingAddress || c.billingData || {};
      }
      return c.shippingAddress || {};
    }

    setCity(type, value) {
      const wpDataStore = this.wpData();
      if (!wpDataStore) {
        return;
      }
      let cartDispatch;
      try {
        cartDispatch = wpDataStore.dispatch('wc/store/cart');
      } catch (e) {
        return;
      }
      if (!cartDispatch) {
        return;
      }
      if (type === 'billing' && cartDispatch.setBillingAddress) {
        cartDispatch.setBillingAddress({city: value});
      }
      if (type === 'shipping' && cartDispatch.setShippingAddress) {
        cartDispatch.setShippingAddress({city: value});
      }
      if (value) {
        this.clearError(type);
      }
    }

    clearError(type) {
      const wpDataStore = this.wpData();
      if (!wpDataStore) {
        return;
      }
      try {
        const validationDispatch = wpDataStore.dispatch('wc/store/validation');
        if (validationDispatch && validationDispatch.clearValidationError) {
          validationDispatch.clearValidationError(type + '_city');
          validationDispatch.clearValidationError(type + '-city');
        }
      } catch (e) {
      }
    }

    getError(type) {
      const wpDataStore = this.wpData();
      if (!wpDataStore) {
        return null;
      }
      try {
        const cartSelector = wpDataStore.select('wc/store/validation');
        if (!cartSelector || !cartSelector.getValidationError) {
          return null;
        }
        return cartSelector.getValidationError(type + '_city') || cartSelector.getValidationError(type + '-city') || null;
      } catch (e) {
        return null;
      }
    }

    cityLabel(input) {
      const wrap = input.closest('.wc-block-components-text-input');
      const labelEl = wrap ? wrap.querySelector('label') : null;
      return (labelEl && labelEl.textContent) ? labelEl.textContent : 'City';
    }

    addOption(selectEl, value, label, selected) {
      const o = document.createElement('option');
      o.value = value;
      o.textContent = label;
      if (selected) {
        o.selected = true;
      }
      selectEl.appendChild(o);
    }

    fillOptions(selectEl, list, current) {
      selectEl.innerHTML = '';
      this.addOption(selectEl, '', list ? this.CFG.i18n.select : this.CFG.i18n.first, current === '');

      if (!list) {
        return;
      }

      for (let i = 0; i < list.length; i++) {
        this.addOption(selectEl, list[i], list[i], list[i] === current);
      }

      if (current && list.indexOf(current) === -1) {
        selectEl.value = '';
      }
    }

    buildHolder(type, input, wrap) {
      let holder = null;
      const stateEl = document.getElementById(type + '-state');
      const stateBox = (stateEl && stateEl.tagName === 'SELECT') ? stateEl.closest('.wc-block-components-select') : null;

      // Clone the State select wrapper so the city select looks identical.
      if (stateBox) {
        holder = stateBox.cloneNode(true);
        const junk = holder.querySelectorAll('.wc-block-components-validation-error');
        for (let j = 0; j < junk.length; j++) {
          junk[j].remove();
        }
      }

      if (!holder || !holder.querySelector('select')) {
        holder = document.createElement('div');
        holder.className = 'wc-block-components-select';
        holder.innerHTML = '<div class="wc-blocks-components-select">' + '<div class="wc-blocks-components-select__container">' + '<label class="wc-blocks-components-select__label"></label>' + '<select class="wc-blocks-components-select__select"></select>' + '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="wc-blocks-components-select__expand" aria-hidden="true" focusable="false"><path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"></path></svg>' + '</div></div>';
      }

      holder.id = 'wpp-' + type + '-city-holder';
      holder.classList.add('wppd-wc-city-select');
      holder.classList.remove('wc-block-components-address-form__state');

      // Carry over the layout class WooCommerce puts on the city column.
      const classes = (wrap && wrap.className) ? wrap.className.split(/\s+/) : [];
      for (let k = 0; k < classes.length; k++) {
        if (classes[k].indexOf('address-form__city') !== -1) {
          holder.classList.add(classes[k]);
        }
      }

      const selectEl = holder.querySelector('select');
      const labelEl = holder.querySelector('label');

      selectEl.id = 'wpp-' + type + '-city';
      selectEl.name = selectEl.id;
      selectEl.innerHTML = '';
      selectEl.disabled = false;
      selectEl.removeAttribute('aria-describedby');
      selectEl.removeAttribute('aria-invalid');
      selectEl.setAttribute('autocomplete', 'off');

      if (labelEl) {
        labelEl.setAttribute('for', selectEl.id);
        labelEl.textContent = this.cityLabel(input);
      }

      selectEl.addEventListener('change', () => {
        this.setCity(type, selectEl.value);
      });

      wrap.parentNode.insertBefore(holder, wrap);

      return holder;
    }

    paintError(type, holder, selectEl) {
      const err = this.getError(type);
      const show = err && !err.hidden && err.message;
      let box = holder.querySelector('.wpp-city-error');

      if (show) {
        if (!box) {
          box = document.createElement('div');
          box.className = 'wc-block-components-validation-error wpp-city-error';
          box.setAttribute('role', 'alert');
          box.appendChild(document.createElement('p'));
          holder.appendChild(box);
        }
        box.firstChild.textContent = err.message;
        holder.classList.add('has-error');
        if (selectEl) {
          selectEl.setAttribute('aria-invalid', 'true');
        }
      } else {
        if (box) {
          box.remove();
        }
        holder.classList.remove('has-error');
        if (selectEl) {
          selectEl.removeAttribute('aria-invalid');
        }
      }
    }

    ensure(type) {
      const input = document.getElementById(type + '-city');
      let holder = document.getElementById('wpp-' + type + '-city-holder');

      if (!input) {
        if (holder) {
          holder.remove();
        }
        return;
      }

      const address = this.getAddress(type);
      const country = address.country || '';
      const state = address.state || '';
      const wrap = input.closest('.wc-block-components-text-input') || input.parentElement;

      if (country !== 'IR') {
        if (holder) {
          holder.remove();
        }
        if (wrap) {
          wrap.style.removeProperty('display');
        }
        return;
      }

      if (wrap) {
        wrap.style.display = 'none';
      }

      if (!holder) {
        holder = this.buildHolder(type, input, wrap);
        if (!holder) {
          return;
        }
      }

      const selectEl = holder.querySelector('select');
      const list = this.CFG.cities[state] || null;
      const current = address.city || '';

      if (holder.getAttribute('data-wpp-state') !== state) {
        this.fillOptions(selectEl, list, current);
        holder.setAttribute('data-wpp-state', state);

        // Same rule as the classic dropdown: value not in the new list, clear it.
        if (current && (!list || list.indexOf(current) === -1)) {
          this.setCity(type, '');
        }
      } else if (selectEl.value !== current) {
        for (let i = 0; i < selectEl.options.length; i++) {
          if (selectEl.options[i].value === current) {
            selectEl.value = current;
            break;
          }
        }
      }

      selectEl.disabled = !list;
      this.paintError(type, holder, selectEl);
    }

    refresh() {
      if (this.queued) {
        return;
      }
      this.queued = true;
      window.setTimeout(() => {
        this.queued = false;
        this.ensure('shipping');
        this.ensure('billing');
      }, 50);
    }

    init() {
      const wpDataStore = this.wpData();
      if (wpDataStore && wpDataStore.subscribe) {
        wpDataStore.subscribe(() => this.refresh());
      }

      if (window.MutationObserver) {
        new MutationObserver(() => this.refresh()).observe(document.body, {childList: true, subtree: true});
      } else {
        window.setInterval(() => this.refresh(), 800);
      }

      this.refresh();
    }
  }

  function WpPdWcCitySelectorStart() {
    new WpPdWcCitySelector(window.WpPdWcBlockCityData);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', WpPdWcCitySelectorStart);
  } else {
    WpPdWcCitySelectorStart();
  }
})();

/******/ })()
;