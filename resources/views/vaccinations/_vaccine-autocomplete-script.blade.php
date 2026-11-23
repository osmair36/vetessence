@push('scripts')
<script>
(function() {
    const allOptions = @json($vaccineOptions ?? []);
    const petSpeciesMap = @json($petSpeciesMap ?? (object) []);
    const fixedSpecies = @json($fixedPetSpecies ?? null);
    const speciesLabels = {
        canine: 'Canino',
        feline: 'Felino',
        equine: 'Equino',
        bird: 'Ave'
    };

    const petSelect = document.querySelector('[name="pet_id"]');
    const vaccineSelect = document.getElementById('vac_vaccine');
    const productSelect = document.getElementById('vac_product_id');
    const hintEl = document.getElementById('vac_vaccine_hint');

    if (!vaccineSelect) return;

    function getSelectedPetSpecies() {
        if (fixedSpecies) return String(fixedSpecies).toLowerCase();
        if (!petSelect) return '';
        const val = petSelect.tomselect ? petSelect.tomselect.getValue() : petSelect.value;
        if (!val) return '';
        if (petSpeciesMap && Object.prototype.hasOwnProperty.call(petSpeciesMap, String(val))) {
            return String(petSpeciesMap[String(val)] || '').toLowerCase();
        }
        const opt = petSelect.querySelector('option[value="' + CSS.escape(String(val)) + '"]');
        return opt ? String(opt.getAttribute('data-species') || '').toLowerCase() : '';
    }

    function buildFilteredList(species) {
        const seen = Object.create(null);
        const primary = [];
        const secondary = [];

        allOptions.forEach(function(item) {
            const key = String(item.name || '').trim().toLowerCase();
            if (!key) return;
            const itemSp = String(item.species || 'all').toLowerCase();
            const matchesSpecies = !species || itemSp === species || itemSp === 'all';

            if (matchesSpecies) {
                if (!seen[key]) {
                    seen[key] = true;
                    primary.push(item);
                }
            } else if (!species) {
                if (!seen[key]) {
                    seen[key] = true;
                    secondary.push(item);
                }
            }
        });

        // Se selecionou espécie, inclui apenas vacinas da espécie + gerais; se nenhuma opção encontrada, mostra todas
        if (species && primary.length > 0) {
            return primary;
        }
        return primary.concat(secondary);
    }

    function refreshVaccineOptions(keepCurrentValue) {
        const ts = vaccineSelect.tomselect;
        if (!ts) return;

        const species = getSelectedPetSpecies();
        const currentVal = keepCurrentValue ? String(ts.getValue() || vaccineSelect.value || '').trim() : '';
        const list = buildFilteredList(species);

        ts.clearOptions();

        const added = Object.create(null);
        list.forEach(function(item) {
            const name = String(item.name || '').trim();
            if (!name || added[name.toLowerCase()]) return;
            added[name.toLowerCase()] = true;
            const suffix = item.source === 'protocol' ? ' (Protocolo)' : '';
            ts.addOption({
                value: name,
                text: name + suffix,
                product_id: item.product_id || null
            });
        });

        // Preserva o valor atual se não estiver na lista filtrada (ex.: edição ou digitação livre)
        if (currentVal) {
            if (!added[currentVal.toLowerCase()]) {
                ts.addOption({ value: currentVal, text: currentVal, product_id: null });
            }
            ts.setValue(currentVal, true);
        } else {
            ts.clear(true);
        }

        ts.refreshOptions(false);

        if (hintEl) {
            const spLabel = species ? (speciesLabels[species] || species) : null;
            if (spLabel) {
                hintEl.textContent = 'Exibindo ' + Object.keys(added).length + ' vacinas para ' + spLabel + ' — clique para selecionar ou digite para filtrar.';
            } else {
                hintEl.textContent = 'Clique para listar as vacinas existentes ou digite parte do nome para filtrar.';
            }
        }
    }

    function bindAfterTomSelectReady() {
        if (!vaccineSelect.tomselect) {
            setTimeout(bindAfterTomSelectReady, 50);
            return;
        }

        refreshVaccineOptions(true);

        if (petSelect) {
            const onPetChange = function() {
                refreshVaccineOptions(true);
            };
            petSelect.addEventListener('change', onPetChange);
            if (petSelect.tomselect) {
                petSelect.tomselect.on('change', onPetChange);
            }
        }

        // Ao selecionar uma vacina com produto vinculado no protocolo, sugere o produto se vazio
        vaccineSelect.addEventListener('change', function() {
            const ts = vaccineSelect.tomselect;
            if (!ts || !productSelect) return;
            const val = ts.getValue();
            const opt = ts.options[val];
            const currentProd = productSelect.tomselect ? productSelect.tomselect.getValue() : productSelect.value;
            if (opt && opt.product_id && !currentProd) {
                if (productSelect.tomselect) {
                    productSelect.tomselect.setValue(String(opt.product_id), false);
                } else {
                    productSelect.value = String(opt.product_id);
                    productSelect.dispatchEvent(new Event('change'));
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(bindAfterTomSelectReady, 30);
    });
})();
</script>
@endpush
