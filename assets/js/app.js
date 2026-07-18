/**
 * Select con busqueda (autocompletar) sin dependencias externas.
 *
 * Uso:
 *   <div class="searchable-select" id="mi-selector">
 *     <input type="text" class="ss-input" placeholder="Buscar...">
 *     <input type="hidden" name="producto_id">
 *     <div class="ss-panel"></div>
 *   </div>
 *   <script>
 *     new SearchableSelect(document.getElementById('mi-selector'), [
 *       {value: '1', label: 'Shampoo', meta: 'costo $0.75 / aplicacion'},
 *       ...
 *     ]);
 *   </script>
 */
function SearchableSelect(container, items, options) {
    options = options || {};
    var input = container.querySelector('.ss-input');
    var hidden = container.querySelector('input[type="hidden"]');
    var panel = container.querySelector('.ss-panel');
    var maxResults = options.maxResults || 40;

    function normalize(str) {
        return (str || '').toString().toLowerCase();
    }

    function render(list) {
        panel.innerHTML = '';
        if (!list.length) {
            var empty = document.createElement('div');
            empty.className = 'ss-empty';
            empty.textContent = 'Sin resultados';
            panel.appendChild(empty);
            return;
        }
        list.slice(0, maxResults).forEach(function (item) {
            var opt = document.createElement('div');
            opt.className = 'ss-option';
            opt.dataset.value = item.value;
            var label = document.createElement('div');
            label.textContent = item.label;
            opt.appendChild(label);
            if (item.meta) {
                var meta = document.createElement('div');
                meta.className = 'ss-option-meta';
                meta.textContent = item.meta;
                opt.appendChild(meta);
            }
            opt.addEventListener('mousedown', function (e) {
                e.preventDefault();
                select(item);
            });
            panel.appendChild(opt);
        });
    }

    function select(item) {
        hidden.value = item.value;
        input.value = item.label;
        close();
    }

    function open() {
        container.classList.add('open');
    }

    function close() {
        container.classList.remove('open');
    }

    function filterAndRender(query) {
        var q = normalize(query);
        var filtered = q === ''
            ? items
            : items.filter(function (item) { return normalize(item.label).indexOf(q) !== -1; });
        render(filtered);
    }

    input.addEventListener('focus', function () {
        filterAndRender(input.value === selectedLabel() ? '' : input.value);
        open();
    });

    input.addEventListener('input', function () {
        if (hidden.value) { hidden.value = ''; }
        filterAndRender(input.value);
        open();
    });

    function selectedLabel() {
        var found = items.find(function (item) { return item.value === hidden.value; });
        return found ? found.label : '';
    }

    document.addEventListener('click', function (e) {
        if (!container.contains(e.target)) {
            close();
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            close();
        }
    });
}
