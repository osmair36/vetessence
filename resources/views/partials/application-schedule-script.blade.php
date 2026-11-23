@push('scripts')
<script>
(function() {
    const config = @json($scheduleConfig);
    const endpoint = "{{ route('application-schedule.calculate') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "{{ csrf_token() }}";

    const petEl = config.petSelector ? document.querySelector(config.petSelector) : null;
    const productEl = config.productSelector ? document.querySelector(config.productSelector) : null;
    const itemNameEl = config.itemNameSelector ? document.querySelector(config.itemNameSelector) : null;
    const activeIngredientEl = config.activeIngredientSelector ? document.querySelector(config.activeIngredientSelector) : null;
    const appDateEl = document.querySelector(config.applicationDateSelector);
    const nextDateEl = document.querySelector(config.nextDateSelector);
    const manualFlagEl = document.querySelector(config.manualFlagSelector);
    const doseEl = config.doseSelector ? document.querySelector(config.doseSelector) : null;
    const feedbackEl = document.querySelector(config.feedbackSelector);

    if (!appDateEl || !nextDateEl || !feedbackEl) return;

    let isManual = Boolean(config.initiallyManual);
    let lastAutoValue = null;
    let lastAutoProductName = null;
    let lastSuggested = null;
    let debounceTimer = null;
    let requestSeq = 0;

    function syncManualFlag() {
        if (manualFlagEl) {
            manualFlagEl.value = isManual ? '1' : '0';
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function renderFeedback(state, message, suggestion) {
        if (state === 'auto') {
            feedbackEl.innerHTML =
                '<span class="badge badge-success mr-1"><i class="fas fa-magic mr-1"></i>Calculada automaticamente</span>' +
                '<span class="text-muted">' + escapeHtml(message) + '</span>';
        } else if (state === 'manual') {
            let html =
                '<span class="badge badge-warning mr-1"><i class="fas fa-edit mr-1"></i>Ajustada manualmente</span>' +
                '<span class="text-muted">A alteração manual prevalecerá ao salvar.</span>';
            if (suggestion && suggestion.calculated && suggestion.next_date) {
                html += ' <button type="button" class="btn btn-link btn-xs p-0 ml-1 align-baseline js-apply-schedule-suggestion">' +
                    '<i class="fas fa-sync-alt mr-1"></i>Aplicar sugestão do protocolo (' + escapeHtml(suggestion.next_date_formatted) + ')</button>';
            }
            feedbackEl.innerHTML = html;
        } else if (state === 'none') {
            feedbackEl.innerHTML =
                '<span class="badge badge-light border text-muted mr-1"><i class="fas fa-info-circle mr-1"></i>Sem protocolo</span>' +
                '<span class="text-muted">' + escapeHtml(message) + '</span>';
        } else if (state === 'loading') {
            feedbackEl.innerHTML =
                '<span class="text-muted"><i class="fas fa-spinner fa-spin mr-1"></i>Consultando protocolo...</span>';
        } else {
            feedbackEl.innerHTML = '';
        }
    }

    function getFieldValue(el) {
        if (!el) return '';
        if (el.tomselect) {
            return String(el.tomselect.getValue() || '').trim();
        }
        return String(el.value || '').trim();
    }

    function setFieldValue(el, val) {
        if (!el) return;
        if (el.tomselect) {
            if (val && !el.tomselect.options[val]) {
                el.tomselect.addOption({ value: val, text: val });
            }
            el.tomselect.setValue(val || '', true);
        } else {
            el.value = val || '';
        }
    }

    function getPetId() {
        if (config.fixedPetId) return config.fixedPetId;
        return getFieldValue(petEl);
    }

    function calculate(forceApply = false) {
        const appDate = appDateEl.value ? appDateEl.value.trim() : '';
        const productId = getFieldValue(productEl);
        const itemName = getFieldValue(itemNameEl);
        const petId = getPetId();
        const dose = doseEl ? doseEl.value.trim() : '';

        if (!appDate || (!productId && !itemName)) {
            if (!isManual && forceApply) {
                renderFeedback('clear', '');
            }
            return;
        }

        const seq = ++requestSeq;
        if (!isManual || forceApply) {
            renderFeedback('loading', '');
        }

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                application_date: appDate,
                product_id: productId ? parseInt(productId, 10) : null,
                pet_id: petId ? parseInt(petId, 10) : null,
                item_name: itemName || null,
                context: config.context || 'general',
                exclude_id: config.excludeId ? parseInt(config.excludeId, 10) : null,
                dose: dose || null
            })
        })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .then(data => {
            if (seq !== requestSeq) return;
            lastSuggested = data;

            // Preenche nome / principio ativo do produto quando selecionado (se campo ainda vazio ou preenchido automaticamente antes)
            if (data.product) {
                const currentItem = getFieldValue(itemNameEl);
                if (itemNameEl && (currentItem === '' || currentItem === lastAutoProductName)) {
                    setFieldValue(itemNameEl, data.product.name);
                    lastAutoProductName = data.product.name;
                }
                if (activeIngredientEl && data.product.active_ingredient && activeIngredientEl.value.trim() === '') {
                    activeIngredientEl.value = data.product.active_ingredient;
                }
            }

            if (forceApply) {
                isManual = false;
                syncManualFlag();
            }

            if (isManual) {
                renderFeedback('manual', '', data);
                return;
            }

            if (data.calculated && data.next_date) {
                nextDateEl.value = data.next_date;
                lastAutoValue = data.next_date;
                syncManualFlag();
                renderFeedback('auto', data.message, data);
            } else {
                // Só limpa se o valor atual era fruto de cálculo automático anterior
                if (lastAutoValue && nextDateEl.value === lastAutoValue) {
                    nextDateEl.value = '';
                }
                lastAutoValue = null;
                renderFeedback('none', data.message || 'Nenhum protocolo cadastrado para este produto. Informe a próxima data manualmente.');
            }
        })
        .catch(() => {
            if (seq !== requestSeq) return;
            if (!isManual) {
                renderFeedback('clear', '');
            }
        });
    }

    function scheduleCalculate(immediate = false) {
        clearTimeout(debounceTimer);
        if (immediate) {
            calculate(false);
        } else {
            debounceTimer = setTimeout(() => calculate(false), 350);
        }
    }

    // Alteração manual pelo usuário no campo de próxima data
    nextDateEl.addEventListener('input', function() {
        isManual = true;
        syncManualFlag();
        renderFeedback('manual', '', lastSuggested);
    });
    nextDateEl.addEventListener('change', function() {
        if (nextDateEl.value !== lastAutoValue) {
            isManual = true;
            syncManualFlag();
            renderFeedback('manual', '', lastSuggested);
        }
    });

    // Clique no botão "Aplicar sugestão do protocolo"
    feedbackEl.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-apply-schedule-suggestion');
        if (!btn) return;
        e.preventDefault();
        if (lastSuggested && lastSuggested.calculated && lastSuggested.next_date) {
            isManual = false;
            syncManualFlag();
            nextDateEl.value = lastSuggested.next_date;
            lastAutoValue = lastSuggested.next_date;
            renderFeedback('auto', lastSuggested.message, lastSuggested);
        } else {
            calculate(true);
        }
    });

    // Gatilhos de recálculo (compatível com inputs nativos e TomSelect)
    appDateEl.addEventListener('change', () => scheduleCalculate(true));
    if (petEl) petEl.addEventListener('change', () => scheduleCalculate(true));
    if (productEl) {
        productEl.addEventListener('change', () => {
            const selectedVal = getFieldValue(productEl);
            const opt = selectedVal ? productEl.querySelector('option[value="' + CSS.escape(selectedVal) + '"]') : null;
            if (opt && opt.value) {
                const pName = opt.getAttribute('data-name') || '';
                const pActive = opt.getAttribute('data-active-ingredient') || '';
                const currentItem = getFieldValue(itemNameEl);
                if (itemNameEl && pName && (currentItem === '' || currentItem === lastAutoProductName)) {
                    setFieldValue(itemNameEl, pName);
                    lastAutoProductName = pName;
                }
                if (activeIngredientEl && pActive && activeIngredientEl.value.trim() === '') {
                    activeIngredientEl.value = pActive;
                }
            }
            scheduleCalculate(true);
        });
    }
    if (itemNameEl) {
        itemNameEl.addEventListener('input', () => scheduleCalculate(false));
        itemNameEl.addEventListener('change', () => scheduleCalculate(true));
    }
    if (doseEl) {
        doseEl.addEventListener('change', () => scheduleCalculate(true));
    }

    syncManualFlag();
    if (isManual && nextDateEl.value) {
        renderFeedback('manual', '', null);
        // Carrega sugestão em segundo plano sem sobrescrever a data existente
        scheduleCalculate(true);
    } else if (!isManual && appDateEl.value && ((productEl && productEl.value) || (itemNameEl && itemNameEl.value.trim()))) {
        scheduleCalculate(true);
    }
})();
</script>
@endpush
