<!-- Premium Glassmorphic Loading Overlay -->
<div id="saveLoadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); z-index:99999; flex-direction:column; align-items:center; justify-content:center; transition: opacity 0.3s ease;">
    <div style="background:#fff; padding:30px 40px; border-radius:16px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border:1px solid rgba(0,0,0,0.05); display:flex; flex-direction:column; align-items:center; gap:20px; max-width: 400px; text-align: center;">
        <div class="spinner-resilient" style="width:45px; height:45px; border:4px solid #e0eaf5; border-top:4px solid #0066cc; border-radius:50%; animation:spin-resilient 0.8s linear infinite;"></div>
        <div style="display:flex; flex-direction:column; gap:6px;">
            <h4 style="margin:0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size:16px; font-weight:700; color:#111;">Saving Record Safely</h4>
            <p style="margin:0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size:12px; color:#666; line-height:1.4;">Connecting to server and writing transaction ledger. Please do not close or refresh this page.</p>
        </div>
    </div>
</div>
<style>
@keyframes spin-resilient {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.spinner-resilient {
    box-sizing: border-box;
}
</style>
<script>
document.addEventListener('submit', function(e) {
    const form = e.target;
    
    // Ignore GET forms, target="blank", or forms marked to ignore
    if (form.tagName !== 'FORM' || 
        form.method.toUpperCase() === 'GET' || 
        form.getAttribute('target') === '_blank' || 
        form.getAttribute('data-ajax-ignore') === 'true' ||
        form.getAttribute('data-resilient') === 'false') {
        return;
    }

    // If another script already intercepted and prevented default, ignore
    if (e.defaultPrevented) {
        return;
    }

    e.preventDefault();

    const overlay = document.getElementById('saveLoadingOverlay');
    if (overlay) overlay.style.display = 'flex';

    // Construct FormData capturing submitter button if present
    const formData = new FormData(form);
    const submitter = e.submitter;
    if (submitter && submitter.name) {
        formData.append(submitter.name, submitter.value);
    }

    // Fail-safe inject CSRF token if not already in FormData
    const resilientCsrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    if (resilientCsrfToken && !formData.has('csrf_token')) {
        formData.append('csrf_token', resilientCsrfToken);
    }

    const targetUrl = form.getAttribute('action') || window.location.href;

    // Diagnostic logging
    console.log('--- Resilient Form Submission Payload ---');
    console.log('Target URL:', targetUrl);
    console.log('HTTP Method:', form.method || 'POST');
    console.log('Form Element ID:', form.id || 'No ID');
    for (let pair of formData.entries()) {
        console.log('  ' + pair[0] + ' =', pair[1]);
    }
    console.log('----------------------------------------');

    const fetchHeaders = {
        'X-Requested-With': 'XMLHttpRequest'
    };
    if (resilientCsrfToken) {
        fetchHeaders['X-CSRF-TOKEN'] = resilientCsrfToken;
    }

    fetch(targetUrl, {
        method: form.method || 'POST',
        body: formData,
        headers: fetchHeaders
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Server save error details:', text);
                throw new Error(text || 'Unknown Server Error');
            });
        }

        // Check if it's a file download
        const disposition = response.headers.get('content-disposition');
        const contentType = response.headers.get('content-type') || '';
        if ((disposition && disposition.indexOf('attachment') !== -1) || 
            contentType.indexOf('csv') !== -1 || 
            contentType.indexOf('octet-stream') !== -1 ||
            contentType.indexOf('excel') !== -1 ||
            contentType.indexOf('spreadsheet') !== -1) {
            
            return response.blob().then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                let filename = 'download';
                const match = disposition ? disposition.match(/filename="?([^"]+)"?/) : null;
                if (match && match[1]) filename = match[1];
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                if (overlay) overlay.style.display = 'none';
            });
        }

        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text().then(html => {
                document.open();
                document.write(html);
                document.close();
            });
        }
    })
    .catch(error => {
        if (overlay) overlay.style.display = 'none';
        console.error('Safe save failed:', error);
        
        const errMsg = error.message || '';
        const isSkuDuplicate = errMsg.toLowerCase().includes('item code (sku)') || errMsg.toLowerCase().includes('duplicate sku') || errMsg.toLowerCase().includes('item_code');
        
        if (isSkuDuplicate) {
            const skuInput = form.querySelector('#mainItemCode') || form.querySelector('input[name="item_code"]');
            if (skuInput) {
                skuInput.focus();
                skuInput.classList.remove('border-slate-200');
                skuInput.classList.add('border-rose-500', 'focus:ring-rose-500/20', 'focus:border-rose-500');
                
                const existingMsg = skuInput.parentNode.parentNode.querySelector('.sku-error-msg');
                if (existingMsg) {
                    existingMsg.remove();
                }
                
                const errorElement = document.createElement('p');
                errorElement.className = 'text-xs text-rose-500 font-bold mt-1.5 sku-error-msg';
                errorElement.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-1"></i> Duplicate SKU code. Please use a unique item code.';
                
                skuInput.parentNode.parentNode.appendChild(errorElement);
                skuInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                skuInput.addEventListener('input', function() {
                    skuInput.classList.remove('border-rose-500', 'focus:ring-rose-500/20', 'focus:border-rose-500');
                    skuInput.classList.add('border-slate-200');
                    const errorMsg = skuInput.parentNode.parentNode.querySelector('.sku-error-msg');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }, { once: true });
                
                alert('⚠ Save Failed: Duplicate SKU code.');
                return;
            }
        }
        
        alert('⚠ Save Failed: ' + errMsg);
    });
});

(function() {
    // Inject custom styles for searchable select dropdowns
    const style = document.createElement('style');
    style.innerHTML = `
        .searchable-select-wrapper {
            position: relative;
            width: 100%;
            display: inline-block;
        }
        .searchable-select-input {
            width: 100% !important;
            box-sizing: border-box;
            cursor: pointer;
            background: transparent;
            border: 1px solid var(--mac-border, #ccc);
            color: var(--text-main, #111);
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
        }
        /* Style variation for inside tables where elements need to be compact */
        td .searchable-select-input {
            padding: 4px 8px !important;
            font-size: 12px !important;
            border: 1px solid transparent !important;
        }
        td .searchable-select-input:focus, td .searchable-select-input:hover {
            border: 1px solid #0066cc !important;
            background: var(--mega-hover, #f9f9fb) !important;
        }
        .searchable-select-input:focus {
            border-color: #0066cc !important;
            outline: none;
            background: var(--mega-hover, #f9f9fb);
        }
        .searchable-select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 250px;
            overflow-y: auto;
            background: var(--mega-bg, #fff);
            border: 1px solid var(--mac-border, #ccc);
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            z-index: 999999;
            margin-top: 4px;
            box-sizing: border-box;
            display: none;
        }
        .searchable-select-group-header {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #0066cc;
            padding: 6px 10px;
            background: rgba(0, 102, 204, 0.05);
            pointer-events: none;
        }
        .searchable-select-item {
            padding: 8px 10px;
            cursor: pointer;
            font-size: 12px;
            color: var(--text-main, #111);
            border-bottom: 1px solid var(--mac-border, rgba(0,0,0,0.05));
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .searchable-select-item:hover, .searchable-select-item.highlighted {
            background-color: #0066cc !important;
            color: #fff !important;
        }
        .searchable-select-item.selected {
            font-weight: bold;
            background-color: rgba(0, 102, 204, 0.1);
        }
        .searchable-select-item-sub {
            font-size: 10px;
            color: #888;
            margin-left: 10px;
        }
        .searchable-select-item:hover .searchable-select-item-sub, .searchable-select-item.highlighted .searchable-select-item-sub {
            color: #eee;
        }
        .searchable-select-no-results {
            padding: 10px;
            color: #888;
            font-style: italic;
            text-align: center;
            font-size: 12px;
        }
    `;
    document.head.appendChild(style);

    // Main conversion logic
    function convertSelectToSearchable(select) {
        if (select.dataset.searchableConverted === 'true' || select.getAttribute('data-searchable') === 'false') {
            return;
        }
        // Exclude simple, small binary dropdowns (length <= 5) unless explicitly asked
        if (select.options.length <= 5 && !select.classList.contains('item-select') && !select.classList.contains('customer-select') && !select.id.includes('customer') && !select.id.includes('vendor') && !select.name.includes('vendor') && !select.name.includes('customer') && !select.name.includes('item_selection') && select.getAttribute('data-searchable') !== 'true') {
            return;
        }

        select.dataset.searchableConverted = 'true';
        select.style.setProperty('display', 'none', 'important');

        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select-wrapper';
        if (select.style.width) {
            wrapper.style.width = select.style.width;
        }

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'searchable-select-input';
        // Copy classes from original select to match style
        Array.from(select.classList).forEach(cls => {
            if (cls !== 'searchable-converted') input.classList.add(cls);
        });
        
        // Find default placeholder text
        let placeholder = select.getAttribute('placeholder') || 'Search...';
        if (select.options.length > 0 && select.options[0].value === '') {
            placeholder = select.options[0].textContent;
        }
        input.placeholder = placeholder;
        input.autocomplete = 'off';
        
        // Read disabled state
        input.disabled = select.disabled;

        // Transfer required attribute to the focusable input to prevent
        // "An invalid form control with name='...' is not focusable" console error
        if (select.required) {
            input.required = true;
            select.removeAttribute('required');
            select.dataset.required = 'true';
        }

        const dropdown = document.createElement('div');
        dropdown.className = 'searchable-select-dropdown';

        wrapper.appendChild(input);
        wrapper.appendChild(dropdown);
        select.parentNode.insertBefore(wrapper, select);

        let highlightedIndex = -1;
        let visibleItems = [];

        // Build list elements from options
        function buildOptionsList(ignoreSelectedText = false) {
            dropdown.innerHTML = '';
            visibleItems = [];
            highlightedIndex = -1;

            const selectedOpt = select.options[select.selectedIndex];
            const selectedText = selectedOpt ? selectedOpt.textContent.toLowerCase().trim() : '';
            const rawQuery = input.value.toLowerCase().trim();
            const query = (ignoreSelectedText || rawQuery === selectedText) ? '' : rawQuery;
            const children = Array.from(select.children);

            // Rebuild option structure, respecting optgroups
            children.forEach(child => {
                if (child.tagName.toUpperCase() === 'OPTGROUP') {
                    // Check if parent group is hidden
                    if (child.style.display === 'none') return;

                    const groupOptions = Array.from(child.children);
                    const matchingOptions = groupOptions.filter(opt => matchOption(opt, query));

                    if (matchingOptions.length > 0) {
                        const header = document.createElement('div');
                        header.className = 'searchable-select-group-header';
                        header.textContent = child.label;
                        dropdown.appendChild(header);

                        matchingOptions.forEach(opt => {
                            createItemElement(opt);
                        });
                    }
                } else if (child.tagName.toUpperCase() === 'OPTION') {
                    if (child.style.display === 'none') return;
                    if (matchOption(child, query)) {
                        createItemElement(child);
                    }
                }
            });

            if (visibleItems.length === 0) {
                const noResults = document.createElement('div');
                noResults.className = 'searchable-select-no-results';
                noResults.textContent = 'No results found';
                dropdown.appendChild(noResults);
            }
        }

        function matchOption(option, query) {
            if (!query) return true;
            const text = option.textContent.toLowerCase();
            const val = option.value.toLowerCase();
            const sku = (option.getAttribute('data-sku') || '').toLowerCase();
            const code = (option.getAttribute('data-code') || '').toLowerCase();
            const sampleCode = (option.getAttribute('data-sample-code') || '').toLowerCase();
            const name = (option.getAttribute('data-name') || '').toLowerCase();

            return text.includes(query) || 
                   val.includes(query) || 
                   sku.includes(query) || 
                   code.includes(query) || 
                   sampleCode.includes(query) ||
                   name.includes(query);
        }

        function createItemElement(option) {
            const item = document.createElement('div');
            item.className = 'searchable-select-item';
            if (option.selected) {
                item.classList.add('selected');
            }

            const labelSpan = document.createElement('span');
            labelSpan.textContent = option.textContent;
            item.appendChild(labelSpan);

            // Show SKU/Code/Sample Code as subtitle if available
            const sku = option.getAttribute('data-sku') || option.getAttribute('data-code') || '';
            const sampleCode = option.getAttribute('data-sample-code') || '';
            let subtitleText = '';
            if (sku) subtitleText += `SKU: ${sku}`;
            if (sampleCode) subtitleText += (subtitleText ? ' | ' : '') + `Code: ${sampleCode}`;

            if (subtitleText) {
                const subSpan = document.createElement('span');
                subSpan.className = 'searchable-select-item-sub';
                subSpan.textContent = subtitleText;
                item.appendChild(subSpan);
            }

            item.addEventListener('click', function(e) {
                e.stopPropagation();
                selectOption(option);
            });

            dropdown.appendChild(item);
            visibleItems.push({ element: item, option: option });
        }

        function selectOption(option) {
            select.value = option.value;
            // Set text input value
            input.value = option.value ? option.textContent : '';
            dropdown.style.display = 'none';
            highlightedIndex = -1;

            // Trigger original change event
            const event = new Event('change', { bubbles: true });
            select.dispatchEvent(event);
        }

        // Synchronize selected option from original select to input value
        function syncSelection() {
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                input.value = selectedOpt.textContent;
            } else {
                input.value = '';
            }
        }

        // Init initial value
        syncSelection();

        // Listen for programmatical value changes on original select
        select.addEventListener('change', function() {
            syncSelection();
        });

        // Search typing listener
        input.addEventListener('input', function() {
            dropdown.style.display = 'block';
            buildOptionsList();
        });

        let justFocused = false;

        // Click to open/close
        input.addEventListener('click', function(e) {
            e.stopPropagation();
            if (justFocused) {
                return;
            }
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
                syncSelection();
            } else {
                // Close other searchable select dropdowns first
                document.querySelectorAll('.searchable-select-dropdown').forEach(d => {
                    if (d !== dropdown) d.style.display = 'none';
                });
                buildOptionsList(true);
                dropdown.style.display = 'block';
                // Safe positioning checks
                const rect = wrapper.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                if (spaceBelow < 250 && rect.top > 250) {
                    dropdown.style.top = 'auto';
                    dropdown.style.bottom = '100%';
                    dropdown.style.marginBottom = '4px';
                } else {
                    dropdown.style.top = '100%';
                    dropdown.style.bottom = 'auto';
                    dropdown.style.marginTop = '4px';
                }
            }
        });

        // Focus listener to show all options when opened
        input.addEventListener('focus', function() {
            document.querySelectorAll('.searchable-select-dropdown').forEach(d => {
                if (d !== dropdown) d.style.display = 'none';
            });
            buildOptionsList(true);
            dropdown.style.display = 'block';
            justFocused = true;
            setTimeout(() => { justFocused = false; }, 200);
        });

        // Keydown support
        input.addEventListener('keydown', function(e) {
            if (dropdown.style.display !== 'block') {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
                    dropdown.style.display = 'block';
                    buildOptionsList(true);
                    e.preventDefault();
                }
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                highlightedIndex++;
                if (highlightedIndex >= visibleItems.length) highlightedIndex = 0;
                updateHighlight();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlightedIndex--;
                if (highlightedIndex < 0) highlightedIndex = visibleItems.length - 1;
                updateHighlight();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (highlightedIndex >= 0 && highlightedIndex < visibleItems.length) {
                    selectOption(visibleItems[highlightedIndex].option);
                } else if (visibleItems.length > 0) {
                    selectOption(visibleItems[0].option);
                }
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
                syncSelection();
            }
        });

        function updateHighlight() {
            visibleItems.forEach((item, idx) => {
                if (idx === highlightedIndex) {
                    item.element.classList.add('highlighted');
                    // Scroll into view
                    item.element.scrollIntoView({ block: 'nearest' });
                } else {
                    item.element.classList.remove('highlighted');
                }
            });
        }

        // Close on clicking outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                if (dropdown.style.display === 'block') {
                    dropdown.style.display = 'none';
                    syncSelection();
                }
            }
        });

        // Observe changes to options inside original select
        const optionObserver = new MutationObserver(function() {
            syncSelection();
            if (dropdown.style.display === 'block') {
                buildOptionsList();
            }
        });
        optionObserver.observe(select, { childList: true, subtree: true, attributes: true });
    }

    // Run conversion on DOMContentLoaded
    function initAll() {
        document.querySelectorAll('select').forEach(select => {
            convertSelectToSearchable(select);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // Set up MutationObserver to convert any new selects dynamically added to DOM
    const globalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.tagName.toUpperCase() === 'SELECT') {
                        convertSelectToSearchable(node);
                    } else {
                        node.querySelectorAll('select').forEach(select => {
                            convertSelectToSearchable(select);
                        });
                    }
                }
            });
        });
    });
    globalObserver.observe(document.body, { childList: true, subtree: true });
})();
</script>
