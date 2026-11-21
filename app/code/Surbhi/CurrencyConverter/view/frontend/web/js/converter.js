define(['jquery'], function ($) {
    'use strict';

    return function () {
        var root = document.getElementById('surbhi-currency-converter');
        if (!root) { return; }
        var urls = JSON.parse(root.getAttribute('data-config'));
        var from = document.getElementById('cc-from');
        var to = document.getElementById('cc-to');
        var amount = document.getElementById('cc-amount');
        var period = document.getElementById('cc-period');
        var error = document.getElementById('cc-error');
        var rateValue = null;
        var requestId = 0;

        function api(url) {
            return fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok || !payload.success) {
                            throw new Error(payload.message || 'Unable to load exchange-rate data.');
                        }
                        return payload.data;
                    });
                });
        }

        function showError(message) {
            error.textContent = message;
            error.hidden = false;
        }

        function clearError() {
            error.hidden = true; error.textContent = '';
        }

        function setBusy(busy) {
            root.classList.toggle('is-loading', busy);
        }

        function format(value, max) {
            return new Intl.NumberFormat(undefined, { maximumFractionDigits: max || 4 }).format(value);
        }

        function updateResult(data) {
            rateValue = Number(data.rate);
            var numericAmount = Math.max(0, Number(amount.value) || 0);

            document.getElementById('cc-result-label').textContent = format(numericAmount, 8) + ' ' + from.value + ' equals';
            document.getElementById('cc-result-value').textContent = format(numericAmount * rateValue, 2) + ' ' + to.value;
            document.getElementById('cc-rate').textContent = '1 ' + from.value + ' = ' + format(rateValue, 8) + ' ' + to.value;
            document.getElementById('cc-date').textContent = 'Rate date: ' + (data.date || '—');
        }

        function drawChart(rows) {
            var svg = document.getElementById('cc-chart');
            while (svg.firstChild) { 
                svg.removeChild(svg.firstChild); 
            }
            var points = (rows || []).filter(function (row) { return Number.isFinite(Number(row.rate)); });
            if (!points.length) {
                var empty = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                empty.setAttribute('x', '450'); empty.setAttribute('y', '150'); empty.setAttribute('text-anchor', 'middle');
                empty.textContent = 'No historical data available for this period.'; svg.appendChild(empty); return;
            }
            points.sort(function (a, b) { return a.date.localeCompare(b.date); });

            var values = points.map(function (p) { return Number(p.rate); });
            var min = Math.min.apply(null, values), max = Math.max.apply(null, values);
            var spread = max - min || Math.max(max * 0.02, 0.0001);
            var low = min - spread * 0.12, high = max + spread * 0.12;
            var left = 65, right = 875, top = 24, bottom = 238;

            function node(name, attrs, content) {
                var el = document.createElementNS('http://www.w3.org/2000/svg', name);
                Object.keys(attrs || {}).forEach(function (key) { el.setAttribute(key, attrs[key]); });
                if (content !== undefined) { el.textContent = content; }
                svg.appendChild(el); return el;
            }

            for (var i = 0; i < 5; i++) {
                var y = top + (bottom - top) * i / 4;
                var v = high - (high - low) * i / 4;
                node('line', { x1: left, y1: y, x2: right, y2: y, stroke: '#e6eaf0', 'stroke-width': 1 });
                node('text', { x: left - 10, y: y + 4, 'text-anchor': 'end', fill: '#667085', 'font-size': 12 }, format(v, 5));
            }

            var coords = points.map(function (p, index) {
                var x = left + (points.length === 1 ? 0 : (right - left) * index / (points.length - 1));
                var y = bottom - (Number(p.rate) - low) / (high - low) * (bottom - top);
                return [x, y];
            });

            var path = coords.map(function (p, index) { return (index ? 'L' : 'M') + p[0] + ',' + p[1]; }).join(' ');
            node('path', { d: path, fill: 'none', stroke: '#2563eb', 'stroke-width': 3, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' });

            var first = points[0].date, last = points[points.length - 1].date;
            node('text', { x: left, y: 270, fill: '#667085', 'font-size': 12 }, first);
            node('text', { x: right, y: 270, 'text-anchor': 'end', fill: '#667085', 'font-size': 12 }, last);
            
            var lastPoint = coords[coords.length - 1];
            node('circle', { cx: lastPoint[0], cy: lastPoint[1], r: 5, fill: '#2563eb' });
        }

        function refresh() {
            var id = ++requestId;
            clearError(); setBusy(true);
            var pair = '?base=' + encodeURIComponent(from.value) + '&quote=' + encodeURIComponent(to.value);
            Promise.all([api(urls.rate + pair), api(urls.history + pair + '&days=' + encodeURIComponent(period.value))])
                .then(function (results) {
                    if (id !== requestId) { return; }
                    updateResult(results[0]); drawChart(results[1]);
                    document.getElementById('cc-chart-caption').textContent = from.value + ' to ' + to.value;
                })
                .catch(function (err) { if (id === requestId) { showError(err.message); } })
                .finally(function () { if (id === requestId) { setBusy(false); } });
        }

        api(urls.currencies).then(function (items) {
            var list = Array.isArray(items) ? items : (items.currencies || []);
            var options = list.map(function (item) {
                if (typeof item === 'string') { return { code: item, name: item }; }
                return {
                    code: item.iso_code || item.currency || item.code,
                    name: item.name || item.iso_code || item.currency || item.code
                };
            }).filter(function (item) { return item.code; }).sort(function (a, b) { return a.code.localeCompare(b.code); });
            [from, to].forEach(function (select) {
                select.innerHTML = '';
                options.forEach(function (item) {
                    var option = document.createElement('option');
                    option.value = item.code; option.textContent = item.code + ' — ' + item.name; select.appendChild(option);
                });
            });
            from.value = options.some(function (x) {
                return x.code === 'USD'; 
            }) ? 'USD' : options[0].code;
            to.value = options.some(function (x) {
                return x.code === 'EUR'; 
            }) ? 'EUR' : options[Math.min(1, options.length - 1)].code;
            refresh();
        }).catch(function (err) { showError(err.message); });
        document.getElementById('cc-swap').addEventListener('click', function () {
            var previous = from.value; from.value = to.value; to.value = previous; refresh();
        });

        from.addEventListener('change', refresh);
        to.addEventListener('change', refresh);
        period.addEventListener('change', refresh);
        amount.addEventListener('input', function () {
            if (rateValue !== null) {
                updateResult({ rate: rateValue, date: document.getElementById('cc-date').textContent.replace('Rate date: ', '') }); 
            } 
        });
    };
});
