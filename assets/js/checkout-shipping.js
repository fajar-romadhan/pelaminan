/**
 * Checkout Shipping Rates & Pickup Method Handler
 * Distributor Pelaminan Family
 */

document.addEventListener('DOMContentLoaded', function () {
  const pickupMethodSelect = document.getElementById('pickup_method');
  const pickupMethodRadios = document.querySelectorAll('input[name="pickup_method"]');
  const citySelect = document.getElementById('shipping_city');
  const shippingRateSelect = document.getElementById('shipping_rate_id');
  const shippingCostPreview = document.getElementById('shipping-cost-preview');

  // Summary Card Elements
  const displayShipping = document.getElementById('displayShippingCost');
  const displaySubtotal = document.getElementById('displaySubtotal');
  const displayGrandTotal = document.getElementById('displayGrandTotal');
  const displayDp = document.getElementById('displayDpAmount');

  if (!citySelect || !shippingRateSelect) return;

  const rates = Array.isArray(window.SHIPPING_RATES) ? window.SHIPPING_RATES : [];

  const previousCity = citySelect.dataset.selectedCity || citySelect.value || '';
  const previousRateId = shippingRateSelect.dataset.selectedRateId || shippingRateSelect.value || '';

  function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(Number(value) || 0);
  }

  function getSelectedPickupMethod() {
    if (pickupMethodSelect) {
      return pickupMethodSelect.value;
    }
    let val = 'diantar';
    pickupMethodRadios.forEach(radio => {
      if (radio.checked) val = radio.value;
    });
    return val;
  }

  function resetDistrictOptions() {
    shippingRateSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
    if (shippingCostPreview) {
      shippingCostPreview.textContent = 'Pilih kecamatan untuk melihat ongkir.';
    }
    updateSummaryTotals(0);
  }

  function updateSummaryTotals(shippingCost = 0) {
    const cost = Number(shippingCost) || 0;

    if (displayShipping) {
      displayShipping.textContent = formatRupiah(cost);
    }

    const subtotalVal = displaySubtotal ? (Number(displaySubtotal.dataset.value) || 0) : 0;
    const grandTotal = subtotalVal + cost;
    const dpAmount = grandTotal * 0.5;

    if (displayGrandTotal) {
      displayGrandTotal.textContent = formatRupiah(grandTotal);
    }

    if (displayDp) {
      displayDp.textContent = formatRupiah(dpAmount);
    }
  }

  window.updateSummaryTotals = updateSummaryTotals;

  function updateShippingPreview() {
    const selectedPickup = getSelectedPickupMethod();

    if (selectedPickup === 'diambil') {
      if (shippingCostPreview) {
        shippingCostPreview.textContent = 'Diambil sendiri — ongkir Rp0.';
      }
      updateSummaryTotals(0);
      return;
    }

    const selectedOption = shippingRateSelect.options[shippingRateSelect.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
      if (shippingCostPreview) {
        shippingCostPreview.textContent = 'Pilih kecamatan untuk melihat ongkir.';
      }
      updateSummaryTotals(0);
      return;
    }

    const cost = Number(selectedOption.dataset.cost) || 0;
    if (shippingCostPreview) {
      shippingCostPreview.textContent = 'Estimasi ongkir: ' + formatRupiah(cost);
    }
    updateSummaryTotals(cost);
  }

  function populateDistricts(city, selectedRateId = '') {
    resetDistrictOptions();
    if (!city) return;

    const matchingRates = rates.filter(function (rate) {
      return String(rate.city).trim().toLowerCase() === String(city).trim().toLowerCase();
    });

    matchingRates.forEach(function (rate) {
      const option = document.createElement('option');
      option.value = String(rate.id);
      option.textContent = String(rate.district) + ' — ' + formatRupiah(rate.cost);
      option.dataset.cost = String(rate.cost);

      if (String(rate.id) === String(selectedRateId)) {
        option.selected = true;
      }
      shippingRateSelect.appendChild(option);
    });

    updateShippingPreview();
  }

  function updatePickupFields() {
    const selectedPickup = getSelectedPickupMethod();
    const isDelivery = selectedPickup === 'diantar';

    citySelect.disabled = !isDelivery;
    shippingRateSelect.disabled = !isDelivery;

    citySelect.required = isDelivery;
    shippingRateSelect.required = isDelivery;

    if (!isDelivery) {
      if (shippingCostPreview) {
        shippingCostPreview.textContent = 'Diambil sendiri — ongkir Rp0.';
      }
      updateSummaryTotals(0);
    } else {
      populateDistricts(citySelect.value, previousRateId || shippingRateSelect.value);
    }
  }

  citySelect.addEventListener('change', function () {
    populateDistricts(citySelect.value);
  });

  shippingRateSelect.addEventListener('change', updateShippingPreview);

  if (pickupMethodSelect) {
    pickupMethodSelect.addEventListener('change', updatePickupFields);
  }

  pickupMethodRadios.forEach(radio => {
    radio.addEventListener('change', updatePickupFields);
  });

  if (previousCity) {
    citySelect.value = previousCity;
    populateDistricts(previousCity, previousRateId);
  }

  updatePickupFields();
});
