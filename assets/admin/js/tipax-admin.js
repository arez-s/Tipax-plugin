(function () {
    function showToast(message) {
        var toast = document.getElementById('tipax-admin-toast');
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(toast.dataset.timer);
        toast.dataset.timer = window.setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 2200);
    }

    function fallbackCopy(value) {
        var input = document.createElement('textarea');
        input.value = value;
        input.setAttribute('readonly', 'readonly');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
    }

    function stepNumberInput(input, direction) {
        if (!input) {
            return;
        }

        if (direction > 0) {
            input.stepUp();
        } else {
            input.stepDown();
        }

        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function enhanceNumberInputs() {
        document.querySelectorAll('.tipax-admin-card input[type="number"]:not([data-tipax-number-ready])').forEach(function (input) {
            if (input.closest('.tipax-number-control')) {
                return;
            }

            input.dataset.tipaxNumberReady = '1';

            var wrapper = document.createElement('div');
            wrapper.className = 'tipax-number-control';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            var controls = document.createElement('div');
            controls.className = 'tipax-number-buttons';
            controls.innerHTML = [
                '<button type="button" class="tipax-number-step tipax-number-up" data-step="1" aria-label="Increase value">',
                '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 10l4-4 4 4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                '</button>',
                '<button type="button" class="tipax-number-step tipax-number-down" data-step="-1" aria-label="Decrease value">',
                '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                '</button>'
            ].join('');
            wrapper.appendChild(controls);
        });
    }

    function normalizeTrackingToolbar() {
        var ordersPanel = document.querySelector('.tipax-orders-panel');
        if (ordersPanel && ordersPanel.classList.contains('is-other-orders')) {
            return;
        }

        if (ordersPanel) {
            var title = ordersPanel.querySelector('.tipax-action-content h2');
            if (title) {
                title.textContent = '\u0633\u0641\u0627\u0631\u0634\u0627\u062a \u062a\u06cc\u067e\u0627\u06a9\u0633';
            }

            var description = ordersPanel.querySelector('.tipax-action-content p');
            if (description) {
                description.textContent = '\u0633\u0641\u0627\u0631\u0634\u200c\u0647\u0627\u06cc \u06f1\u06f0 \u0631\u0648\u0632 \u0627\u062e\u06cc\u0631 \u06a9\u0647 \u0631\u0648\u0634 \u0627\u0631\u0633\u0627\u0644 \u062a\u06cc\u067e\u0627\u06a9\u0633 \u062f\u0627\u0631\u0646\u062f\u060c \u0628\u0631\u0627\u06cc \u062b\u0628\u062a \u0645\u062c\u062f\u062f \u06cc\u0627 \u0628\u0631\u0648\u0632\u0631\u0633\u0627\u0646\u06cc \u0648\u0636\u0639\u06cc\u062a \u0646\u0645\u0627\u06cc\u0634 \u062f\u0627\u062f\u0647 \u0645\u06cc\u200c\u0634\u0648\u0646\u062f.';
            }

            var stats = ordersPanel.querySelectorAll('.tipax-stats span');
            if (stats[0]) {
                var totalOrders = stats[0].querySelector('strong');
                stats[0].innerHTML = '<strong>' + (totalOrders ? totalOrders.textContent : '0') + '</strong> \u0633\u0641\u0627\u0631\u0634';
            }

            if (stats[1]) {
                var currentPage = stats[1].querySelector('strong');
                stats[1].innerHTML = '<strong>' + (currentPage ? currentPage.textContent : '1') + '</strong> \u0635\u0641\u062d\u0647 \u0641\u0639\u0644\u06cc';
            }
        }

        var pageBatchButton = document.querySelector('.tipax-batch-update-page');
        if (pageBatchButton) {
            pageBatchButton.textContent = '\u0628\u0631\u0648\u0632\u0631\u0633\u0627\u0646\u06cc \u062f\u0633\u062a\u0647\u200c\u0627\u06cc \u0627\u06cc\u0646 \u0635\u0641\u062d\u0647';
        }

        var dueBatchButton = document.querySelector('.tipax-run-due-batch');
        if (dueBatchButton) {
            var batchSize = dueBatchButton.getAttribute('data-limit') || '20';
            dueBatchButton.textContent = '\u0627\u062c\u0631\u0627\u06cc \u0628\u0686 \u062e\u0648\u062f\u06a9\u0627\u0631 ' + batchSize + ' \u062a\u0627\u06cc\u06cc';
        }

    }

    function normalizeTipaxNavigation() {
        document.querySelectorAll('.tipax-admin-nav a[href*="page=tipax-support"]').forEach(function (link) {
            var label = link.lastElementChild;
            if (label) {
                label.textContent = '\u067e\u0634\u062a\u06cc\u0628\u0627\u0646\u06cc';
            }
        });

        document.querySelectorAll('.tipax-admin-nav a[href*="page=tipax-financial"]').forEach(function (link) {
            var label = link.lastElementChild;
            if (label) {
                label.textContent = '\u06af\u0632\u0627\u0631\u0634 \u0645\u0627\u0644\u06cc';
            }
        });

        document.querySelectorAll('#toplevel_page_tipax-api-config .wp-submenu a[href*="page=tipax-support"]').forEach(function (link) {
            link.textContent = '\u067e\u0634\u062a\u06cc\u0628\u0627\u0646\u06cc';
        });

        document.querySelectorAll('#toplevel_page_tipax-api-config .wp-submenu a[href*="page=tipax-financial"]').forEach(function (link) {
            link.textContent = '\u06af\u0632\u0627\u0631\u0634 \u0645\u0627\u0644\u06cc';
        });
    }

    function enhanceDataTables(scope) {
        scope.querySelectorAll('.tipax-data-table').forEach(function (table) {
            if (table.closest('.tipax-table-scroll, .tipax-cities-table-wrap')) {
                return;
            }

            var wrapper = document.createElement('div');
            wrapper.className = 'tipax-table-scroll';
            wrapper.tabIndex = 0;
            wrapper.setAttribute('role', 'region');
            var heading = table.closest('.tipax-support-card, .tipax-admin-card').querySelector('h2, h3');
            wrapper.setAttribute('aria-label', heading ? heading.textContent.trim() : document.title);
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        enhanceNumberInputs();
        normalizeTrackingToolbar();
        normalizeTipaxNavigation();
        var card = document.querySelector('.tipax-admin-card');
        if (card) {
            enhanceDataTables(card);
            var tableFrame = null;
            new MutationObserver(function (mutations) {
                var hasAddedNodes = mutations.some(function (mutation) {
                    return mutation.addedNodes.length > 0;
                });
                if (!hasAddedNodes || tableFrame !== null) {
                    return;
                }
                tableFrame = window.requestAnimationFrame(function () {
                    tableFrame = null;
                    enhanceDataTables(card);
                });
            }).observe(card, { childList: true, subtree: true });
        }
        var activeTab = document.querySelector('.tipax-admin-nav a.is-active');
        if (activeTab) {
            activeTab.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
    });

    document.addEventListener('click', function (event) {
        var numberButton = event.target.closest('.tipax-number-step');
        if (numberButton) {
            var numberInput = numberButton.closest('.tipax-number-control').querySelector('input[type="number"]');
            stepNumberInput(numberInput, parseInt(numberButton.dataset.step, 10));
            return;
        }

        var passwordButton = event.target.closest('.tipax-password-toggle');
        if (passwordButton) {
            var passwordInput = document.getElementById(passwordButton.dataset.target);
            if (!passwordInput) {
                return;
            }

            var shouldShow = passwordInput.type === 'password';
            passwordInput.type = shouldShow ? 'text' : 'password';
            var label = passwordButton.querySelector('span');
            if (label) {
                label.textContent = shouldShow ? 'پنهان' : 'نمایش';
            }
            passwordButton.classList.toggle('is-active', shouldShow);
            return;
        }

        var copyButton = event.target.closest('.tipax-copy-key');
        if (copyButton) {
            var apiInput = document.getElementById(copyButton.dataset.target);
            if (!apiInput || !apiInput.value) {
                return;
            }

            var copiedLabel = copyButton.querySelector('span');
            var originalText = copiedLabel ? copiedLabel.textContent : '';
            var markAsCopied = function () {
                if (copiedLabel) {
                    copiedLabel.textContent = 'کپی شد';
                    window.setTimeout(function () {
                        copiedLabel.textContent = originalText;
                    }, 1800);
                }
                showToast('کلید API کپی شد');
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(apiInput.value).then(markAsCopied, function () {
                    fallbackCopy(apiInput.value);
                    markAsCopied();
                });
            } else {
                fallbackCopy(apiInput.value);
                markAsCopied();
            }
        }
    });
})();
