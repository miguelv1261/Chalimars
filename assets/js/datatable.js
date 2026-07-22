/**
 * Mejora automaticamente cualquier tabla dentro de un contenedor
 * marcado con [data-table] (ademas de la clase .table-wrap ya usada en
 * todo el sistema): agrega un buscador que filtra filas por texto,
 * columnas ordenables (clic en el <th>) y, para <th data-filter>, un
 * select con los valores unicos de esa columna.
 *
 * Sin dependencias externas, sigue el estilo de SearchableSelect en
 * este mismo archivo/carpeta. Se activa solo con el marcado:
 *   <div class="table-wrap" data-table>
 *     <table>...</table>
 *   </div>
 */
(function () {
    function normalize(str) {
        return (str || '').toString().toLowerCase().trim();
    }

    function parseNumeric(str) {
        var cleaned = (str || '').toString().replace(/[^0-9.,-]/g, '').replace(/,/g, '');
        if (cleaned === '' || cleaned === '-') { return null; }
        var num = parseFloat(cleaned);
        return isNaN(num) ? null : num;
    }

    function enhanceTable(wrap) {
        var table = wrap.querySelector('table');
        if (!table) { return; }
        var thead = table.querySelector('thead');
        var tbody = table.querySelector('tbody');
        if (!thead || !tbody) { return; }
        var headerCells = Array.prototype.slice.call(thead.querySelectorAll('th'));

        function bodyRows() {
            return Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (tr) {
                return tr.children.length === headerCells.length;
            });
        }
        var rows = bodyRows();
        if (!rows.length) { return; }

        // ---------- Toolbar: buscador + filtros por columna ----------
        var toolbar = document.createElement('div');
        toolbar.className = 'table-toolbar';

        var searchInput = document.createElement('input');
        searchInput.type = 'search';
        searchInput.placeholder = 'Buscar en la tabla...';
        searchInput.className = 'table-search';
        toolbar.appendChild(searchInput);

        var filterSelects = [];
        headerCells.forEach(function (th, colIndex) {
            if (!th.hasAttribute('data-filter')) { return; }
            var values = {};
            rows.forEach(function (tr) {
                var text = normalize(tr.children[colIndex].textContent);
                if (text !== '') { values[text] = tr.children[colIndex].textContent.trim(); }
            });
            var select = document.createElement('select');
            select.className = 'table-filter';
            select.dataset.col = colIndex;
            var optAll = document.createElement('option');
            optAll.value = '';
            optAll.textContent = th.textContent.trim() + ': todos';
            select.appendChild(optAll);
            Object.keys(values).sort().forEach(function (key) {
                var opt = document.createElement('option');
                opt.value = key;
                opt.textContent = values[key];
                select.appendChild(opt);
            });
            toolbar.appendChild(select);
            filterSelects.push(select);
        });

        wrap.parentNode.insertBefore(toolbar, wrap);

        function applyFilters() {
            var query = normalize(searchInput.value);
            var activeFilters = filterSelects.map(function (select) {
                return { col: parseInt(select.dataset.col, 10), value: select.value };
            }).filter(function (f) { return f.value !== ''; });

            var visibleCount = 0;
            rows.forEach(function (tr) {
                var matchesSearch = query === '' || normalize(tr.textContent).indexOf(query) !== -1;
                var matchesFilters = activeFilters.every(function (f) {
                    return normalize(tr.children[f.col].textContent) === f.value;
                });
                var visible = matchesSearch && matchesFilters;
                tr.style.display = visible ? '' : 'none';
                if (visible) { visibleCount++; }
            });

            var emptyRow = tbody.querySelector('.dt-empty-message');
            if (visibleCount === 0) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'dt-empty-message';
                    var td = document.createElement('td');
                    td.colSpan = headerCells.length;
                    td.className = 'muted';
                    td.textContent = 'Sin resultados para el filtro aplicado.';
                    emptyRow.appendChild(td);
                    tbody.appendChild(emptyRow);
                }
                emptyRow.style.display = '';
            } else if (emptyRow) {
                emptyRow.style.display = 'none';
            }
        }

        searchInput.addEventListener('input', applyFilters);
        filterSelects.forEach(function (select) { select.addEventListener('change', applyFilters); });

        // ---------- Orden por columna ----------
        var sortState = { col: null, dir: 1 };
        headerCells.forEach(function (th, colIndex) {
            th.classList.add('sortable');
            var arrow = document.createElement('span');
            arrow.className = 'sort-arrow';
            th.appendChild(arrow);

            th.addEventListener('click', function () {
                var dir = (sortState.col === colIndex) ? -sortState.dir : 1;
                sortState = { col: colIndex, dir: dir };

                headerCells.forEach(function (h) { h.classList.remove('sorted-asc', 'sorted-desc'); });
                th.classList.add(dir === 1 ? 'sorted-asc' : 'sorted-desc');

                var currentRows = bodyRows();
                currentRows.sort(function (a, b) {
                    var aText = a.children[colIndex].textContent.trim();
                    var bText = b.children[colIndex].textContent.trim();
                    var aNum = parseNumeric(aText);
                    var bNum = parseNumeric(bText);
                    var cmp;
                    if (aNum !== null && bNum !== null) {
                        cmp = aNum - bNum;
                    } else {
                        cmp = normalize(aText).localeCompare(normalize(bText), 'es');
                    }
                    return cmp * dir;
                });
                currentRows.forEach(function (tr) { tbody.appendChild(tr); });
            });
        });

        applyFilters();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-table]').forEach(enhanceTable);
    });
})();
