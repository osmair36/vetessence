/**
 * Vetessense / Amigo Bicho — Global Autocomplete Enhancer
 *
 * Centraliza a inicializacao de campos de pesquisa com autocomplete (Tom Select / Select2)
 * garantindo:
 *  - Abertura imediata da lista de opcoes ao clicar ou receber foco (sem exigir digitar)
 *  - Filtragem continua normalmente ao digitar
 *  - Limite seguro de opcoes renderizadas no DOM (desempenho em listas com milhares de registros)
 *  - Suporte a carregamento remoto via AJAX com cache em memoria (data-ajax-url)
 *  - Protecao contra reabertura repetitiva apos selecao
 *  - Compatibilidade total com Livewire v3, modais Bootstrap e DOM dinamico
 */
(function (window, document) {
    'use strict';

    var DEFAULT_MAX_OPTIONS = 80;
    var AJAX_CACHE_TTL_MS = 60000;
    var ajaxCache = Object.create(null);

    function getCachedAjax(cacheKey) {
        var entry = ajaxCache[cacheKey];
        if (!entry) return null;
        if ((Date.now() - entry.ts) > AJAX_CACHE_TTL_MS) {
            delete ajaxCache[cacheKey];
            return null;
        }
        return entry.items;
    }

    function setCachedAjax(cacheKey, items) {
        ajaxCache[cacheKey] = { ts: Date.now(), items: items };
    }

    function normalizeAjaxItems(payload) {
        var list = Array.isArray(payload)
            ? payload
            : (payload && (payload.items || payload.data || payload.results)) || [];

        if (!Array.isArray(list)) return [];

        return list.map(function (item) {
            if (typeof item === 'string' || typeof item === 'number') {
                return { value: String(item), text: String(item) };
            }
            var val = item.value !== undefined ? item.value : (item.id !== undefined ? item.id : '');
            var txt = item.text !== undefined ? item.text : (item.label !== undefined ? item.label : (item.name || String(val)));
            return Object.assign({}, item, { value: String(val), text: String(txt) });
        });
    }

    function buildTomSelectConfig(el) {
        var isMultiple = el.hasAttribute('multiple');
        var allowCreate = el.dataset.create === 'true';
        var maxOptAttr = parseInt(el.dataset.maxOptions || '', 10);
        var maxOptions = (!isNaN(maxOptAttr) && maxOptAttr > 0) ? maxOptAttr : DEFAULT_MAX_OPTIONS;
        var ajaxUrl = el.dataset.ajaxUrl || '';

        var lastSelectTs = 0;

        var config = {
            maxOptions: maxOptions,
            openOnFocus: true,
            closeAfterSelect: !isMultiple,
            hideSelected: false,
            allowEmptyOption: !isMultiple,
            diacritics: true,
            create: allowCreate,
            createOnBlur: allowCreate,
            render: {
                no_results: function (data, escape) {
                    var term = (data && data.input) ? ' para "' + escape(data.input) + '"' : '';
                    return '<div class="no-results">Nenhum registro encontrado' + term + '</div>';
                },
                option_create: function (data, escape) {
                    return '<div class="create">Usar <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                }
            },
            onItemAdd: function () {
                lastSelectTs = Date.now();
                if (!isMultiple) {
                    this.blur();
                }
            },
            onDropdownOpen: function () {
                this._justOpenedTs = Date.now();
            },
            onFocus: function () {
                // Evita reabrir no mesmo ciclo imediato apos selecionar um item
                if ((Date.now() - lastSelectTs) < 250) {
                    this.close();
                    return;
                }
                if (!this.isOpen && !this.isDisabled) {
                    this.open();
                    this._justOpenedTs = Date.now();
                }
            },
            onChange: function (value) {
                var wireModel = el.dataset.wire;
                if (wireModel && window.Livewire) {
                    var componentEl = el.closest('[wire\\:id]');
                    if (componentEl) {
                        var component = window.Livewire.find(componentEl.getAttribute('wire:id'));
                        if (component) {
                            component.set(wireModel, value);
                        }
                    }
                }
            }
        };

        if (ajaxUrl) {
            config.preload = 'focus';
            config.shouldLoad = function () {
                return true;
            };
            config.loadThrottle = 250;
            config.load = function (query, callback) {
                var q = (query || '').trim();
                var cacheKey = ajaxUrl + '|' + q.toLowerCase();
                var cached = getCachedAjax(cacheKey);
                if (cached !== null) {
                    callback(cached);
                    return;
                }

                var sep = ajaxUrl.indexOf('?') === -1 ? '?' : '&';
                var url = ajaxUrl + sep + 'q=' + encodeURIComponent(q) + '&limit=' + encodeURIComponent(maxOptions);

                fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (resp) {
                        if (!resp.ok) throw new Error('HTTP ' + resp.status);
                        return resp.json();
                    })
                    .then(function (json) {
                        var items = normalizeAjaxItems(json);
                        setCachedAjax(cacheKey, items);
                        callback(items);
                    })
                    .catch(function () {
                        callback([]);
                    });
            };
        }

        return config;
    }

    function initSingleTomSelect(el) {
        if (!el || typeof window.TomSelect === 'undefined') return;
        if (el.dataset.tsReady === '1' || el.tomselect) return;
        if (el.closest('.ts-wrapper')) return;

        var wrapper = el.closest('.tom-select-wrapper');
        var initialValue = wrapper ? wrapper.dataset.value : '';
        if (initialValue) {
            el.value = initialValue;
        }

        el.dataset.tsReady = '1';
        try {
            var instance = new window.TomSelect(el, buildTomSelectConfig(el));

            // Previne que o TomSelect feche o dropdown imediatamente no mesmo clique que o abriu
            if (instance && instance.control) {
                instance.control.addEventListener('click', function (e) {
                    // Ignora cliques no botao de remover item em selecao multipla
                    if (e.target && e.target.closest && e.target.closest('.remove')) {
                        return;
                    }

                    // Se o dropdown acabou de ser aberto (pelo foco do clique ou acao recente),
                    // impede que o TomSelect interprete este mesmo clique como acao de fechar (toggle)!
                    var timeSinceOpen = Date.now() - (instance._justOpenedTs || 0);
                    if (timeSinceOpen < 450) {
                        e.stopImmediatePropagation();
                        return;
                    }

                    // Se estiver fechado e o usuario clicar no controle, abre imediatamente
                    if (!instance.isOpen && !instance.isDisabled) {
                        instance.open();
                        instance._justOpenedTs = Date.now();
                        e.stopImmediatePropagation();
                    }
                }, true); // Fase de captura (useCapture = true) para executar antes do onClick interno do TomSelect
            }
        } catch (e) {
            if (!e || e.message !== 'Tom Select already initialized on this element') {
                throw e;
            }
        }
    }

    function initTomSelects(container) {
        container = container || document;
        if (!container || typeof container.querySelectorAll !== 'function') return;

        var selectors = 'select.tom-select';
        // Se Select2 nao estiver carregado na pagina, ativa Tom Select tambem nos selects marcados com .select2
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 !== 'function') {
            selectors += ', select.select2';
        }

        container.querySelectorAll(selectors).forEach(initSingleTomSelect);
    }

    function destroyTomSelects(container) {
        container = container || document;
        if (!container || typeof container.querySelectorAll !== 'function') return;

        container.querySelectorAll('.ts-wrapper').forEach(function (wrapper) {
            try {
                if (wrapper.tomselect) wrapper.tomselect.destroy();
            } catch (e) {}
            wrapper.remove();
        });
        container.querySelectorAll('select.tom-select[data-ts-ready], select.select2[data-ts-ready]').forEach(function (el) {
            delete el.dataset.tsReady;
        });
    }

    function enhanceSelect2IfPresent() {
        if (typeof window.jQuery === 'undefined') return;
        var $ = window.jQuery;

        if (typeof $.fn.select2 === 'function') {
            // Garante que Select2 abra ao receber foco via teclado sem loop apos selecao
            $(document).on('focus', '.select2-selection.select2-selection--single', function () {
                var $select = $(this).closest('.select2-container').prev('select');
                if ($select.length && !$select.prop('disabled') && !$select.data('tsJustClosed')) {
                    $select.select2('open');
                }
            });
            $(document).on('select2:close', 'select', function () {
                var $s = $(this);
                $s.data('tsJustClosed', true);
                setTimeout(function () { $s.data('tsJustClosed', false); }, 200);
            });
        } else {
            // Fallback seguro caso alguma view chame $('.select2').select2(...) sem a lib Select2 incluida
            $.fn.select2 = function () {
                this.each(function () { initSingleTomSelect(this); });
                return this;
            };
        }
    }

    // Exporta funcoes globais preservando a API esperada pelo restante da aplicacao
    window.initTomSelects = initTomSelects;
    window.destroyTomSelects = destroyTomSelects;

    document.addEventListener('DOMContentLoaded', function () {
        enhanceSelect2IfPresent();
        initTomSelects(document);

        // Observa elementos inseridos dinamicamente via AJAX/JS sem custo perceptivel
        if (typeof window.MutationObserver !== 'undefined' && document.body) {
            var observer = new window.MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var added = mutations[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var node = added[j];
                        if (node && node.nodeType === 1) {
                            if (node.matches && node.matches('select.tom-select, select.select2')) {
                                initSingleTomSelect(node);
                            } else if (node.querySelector && node.querySelector('select.tom-select, select.select2')) {
                                initTomSelects(node);
                            }
                        }
                    }
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    });
})(window, document);
