document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initGlobalProductSearch();
});

function initSidebar() {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    if (!toggle || !sidebar) return;

    const storageKey = 'snackInventory.sidebarCollapsed';
    let collapsed = false;
    try {
        collapsed = localStorage.getItem(storageKey) === 'true';
    } catch (error) {
        // Keep the sidebar usable when browser storage is unavailable.
    }

    const setCollapsed = (value) => {
        document.body.classList.toggle('sidebar-collapsed', value);
        toggle.setAttribute('aria-expanded', String(!value));
        toggle.setAttribute('aria-label', value ? 'Perbesar sidebar' : 'Perkecil sidebar');
        toggle.setAttribute('title', value ? 'Perbesar sidebar' : 'Perkecil sidebar');
    };

    setCollapsed(collapsed);
    toggle.addEventListener('click', () => {
        collapsed = !document.body.classList.contains('sidebar-collapsed');
        setCollapsed(collapsed);
        try {
            localStorage.setItem(storageKey, String(collapsed));
        } catch (error) {
            // The current-page toggle still works without browser storage.
        }
    });
}

function initGlobalProductSearch() {
    const wrapper = document.getElementById('globalProductSearch');
    const input = document.getElementById('globalProductSearchInput');
    const results = document.getElementById('globalProductSearchResults');
    const modalElement = document.getElementById('globalProductDetailModal');
    if (!wrapper || !input || !results || !modalElement) return;

    const endpoint = wrapper.dataset.endpoint;
    let debounceTimer;
    let requestNumber = 0;
    let activeIndex = -1;

    const openDetailModal = () => {
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            return;
        }
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        modalElement.setAttribute('aria-hidden', 'false');
        modalElement.setAttribute('role', 'dialog');
        document.body.classList.add('modal-open');
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show product-detail-backdrop';
        backdrop.addEventListener('click', closeDetailModal, { once: true });
        document.body.append(backdrop);
    };

    const closeDetailModal = () => {
        if (window.bootstrap && window.bootstrap.Modal) {
            const instance = window.bootstrap.Modal.getInstance(modalElement);
            if (instance) {
                instance.hide();
                return;
            }
        }
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        document.querySelectorAll('.product-detail-backdrop').forEach((item) => item.remove());
    };

    modalElement.querySelectorAll('[data-bs-dismiss="modal"]').forEach((button) => {
        button.addEventListener('click', closeDetailModal);
    });

    const closeResults = () => {
        results.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    };

    const showMessage = (message) => {
        results.replaceChildren();
        const row = document.createElement('div');
        row.className = 'global-search-message';
        row.textContent = message;
        results.append(row);
        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    const showSuggestions = (items) => {
        results.replaceChildren();
        if (!items.length) {
            showMessage('Barang tidak ditemukan.');
            return;
        }

        items.forEach((item, index) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'global-search-option';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.dataset.id = item.id;

            const name = document.createElement('span');
            name.className = 'global-search-option-name';
            name.textContent = item.name;

            const code = document.createElement('span');
            code.className = 'global-search-option-code';
            code.textContent = item.code;

            const info = document.createElement('span');
            info.className = 'global-search-option-info';
            info.textContent = `${item.stock} ${item.unit} · ${item.status}`;

            const text = document.createElement('span');
            text.className = 'global-search-option-text';
            text.append(name, code);
            option.append(text, info);
            option.addEventListener('click', () => loadProduct(item.id));
            results.append(option);
        });

        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    const search = async (term) => {
        const currentRequest = ++requestNumber;
        try {
            const response = await fetch(`${endpoint}?q=${encodeURIComponent(term)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Pencarian tidak tersedia.');
            if (currentRequest !== requestNumber) return;
            showSuggestions(data.items || []);
        } catch (error) {
            if (currentRequest === requestNumber) showMessage('Pencarian gagal. Coba lagi.');
        }
    };

    const loadProduct = async (id) => {
        closeResults();
        document.getElementById('globalProductDetailTitle').textContent = 'Memuat detail barang...';
        document.getElementById('globalDetailMovements').textContent = 'Memuat riwayat stok...';
        openDetailModal();

        try {
            const response = await fetch(`${endpoint}?id=${encodeURIComponent(id)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            const product = await response.json();
            if (!response.ok) throw new Error(product.error || 'Detail barang tidak dapat dimuat.');
            document.getElementById('globalProductDetailTitle').textContent = product.name;
            document.getElementById('globalDetailCode').textContent = product.code;
            document.getElementById('globalDetailCategory').textContent = product.category_name || 'Tanpa kategori';
            document.getElementById('globalDetailStock').textContent = `${Number(product.stock).toLocaleString('id-ID')} ${product.unit}`;
            document.getElementById('globalDetailMinimum').textContent = `${Number(product.minimum_stock).toLocaleString('id-ID')} ${product.unit}`;
            document.getElementById('globalDetailUnit').textContent = product.unit;

            const status = document.getElementById('globalDetailStatus');
            status.textContent = product.status;
            status.className = `product-detail-status status-${product.status.toLowerCase()}`;
            renderMovements(product.movements || []);
        } catch (error) {
            document.getElementById('globalProductDetailTitle').textContent = 'Detail barang';
            document.getElementById('globalDetailMovements').textContent = error.message;
        }
    };

    const renderMovements = (movements) => {
        const container = document.getElementById('globalDetailMovements');
        container.replaceChildren();
        if (!movements.length) {
            const empty = document.createElement('p');
            empty.className = 'product-detail-empty';
            empty.textContent = 'Belum ada transaksi untuk barang ini.';
            container.append(empty);
            return;
        }

        const table = document.createElement('table');
        table.className = 'table product-detail-table';
        const head = document.createElement('thead');
        const headerRow = document.createElement('tr');
        ['Tanggal', 'Jenis', 'Jumlah', 'Keterangan', 'User'].forEach((label) => {
            const cell = document.createElement('th');
            cell.textContent = label;
            headerRow.append(cell);
        });
        head.append(headerRow);
        const body = document.createElement('tbody');

        movements.forEach((movement) => {
            const row = document.createElement('tr');
            const date = document.createElement('td');
            const parsedDate = new Date(String(movement.created_at).replace(' ', 'T'));
            date.textContent = Number.isNaN(parsedDate.getTime())
                ? movement.created_at
                : new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(parsedDate);
            const type = document.createElement('td');
            type.textContent = movement.type === 'IN' ? 'Masuk' : 'Keluar';
            type.className = movement.type === 'IN' ? 'movement-in' : 'movement-out';
            const quantity = document.createElement('td');
            quantity.textContent = `${movement.type === 'IN' ? '+' : '-'}${Number(movement.quantity).toLocaleString('id-ID')}`;
            const description = document.createElement('td');
            description.textContent = movement.description || '-';
            const user = document.createElement('td');
            user.textContent = movement.user_name || '-';
            row.append(date, type, quantity, description, user);
            body.append(row);
        });

        table.append(head, body);
        const responsive = document.createElement('div');
        responsive.className = 'table-responsive';
        responsive.append(table);
        container.append(responsive);
    };

    input.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        const term = input.value.trim();
        if (term.length < 1) {
            requestNumber++;
            closeResults();
            return;
        }
        showMessage('Mencari barang...');
        debounceTimer = window.setTimeout(() => search(term), 220);
    });

    input.addEventListener('keydown', (event) => {
        const options = [...results.querySelectorAll('.global-search-option')];
        if (event.key === 'Escape') closeResults();
        if (event.key === 'ArrowDown' && options.length) {
            event.preventDefault();
            activeIndex = (activeIndex + 1) % options.length;
        } else if (event.key === 'ArrowUp' && options.length) {
            event.preventDefault();
            activeIndex = (activeIndex - 1 + options.length) % options.length;
        } else if (event.key === 'Enter' && options.length) {
            event.preventDefault();
            options[activeIndex < 0 ? 0 : activeIndex].click();
        }
        options.forEach((option, index) => {
            const active = index === activeIndex;
            option.classList.toggle('active', active);
            option.setAttribute('aria-selected', String(active));
        });
    });

    document.addEventListener('click', (event) => {
        if (!wrapper.contains(event.target)) closeResults();
    });
}
