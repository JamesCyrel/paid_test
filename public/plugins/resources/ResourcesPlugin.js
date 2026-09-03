/**
 * BigBlueButton Resources Plugin - Production Bundle
 * Adds a visible "Resources" button/option to the BigBlueButton classroom.
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define(['react', 'react-dom'], factory);
    } else if (typeof exports === 'object') {
        module.exports = factory(require('react'), require('react-dom'));
    } else {
        root.ResourcesPlugin = factory(root.React, root.ReactDOM);
    }
})(typeof self !== 'undefined' ? self : this, function (React, ReactDOM) {
    'use strict';

    var TARGET_URL = 'https://example.com';

    function initPlugin() {
        var currentScript = document.currentScript;
        var uuid =
            (currentScript && currentScript.getAttribute('uuid')) ||
            'bbb-resources-plugin';

        // 1. Attempt official BigBlueButton HTML5 Plugin SDK registration
        try {
            var sdk =
                window.BbbPluginSdk ||
                (window.parent && window.parent.BbbPluginSdk);
            if (sdk && typeof sdk.initialize === 'function') {
                sdk.initialize(uuid);
                var api = sdk.getPluginApi(uuid);

                if (api) {
                    // Options Dropdown Option
                    if (typeof api.setOptionsDropdownItems === 'function') {
                        api.setOptionsDropdownItems([
                            {
                                label: 'Resources',
                                icon: 'file',
                                onClick: function () {
                                    window.open(
                                        TARGET_URL,
                                        '_blank',
                                        'noopener,noreferrer',
                                    );
                                },
                            },
                        ]);
                    }

                    // Actions Bar Button
                    if (typeof api.setActionsBarItems === 'function') {
                        api.setActionsBarItems([
                            {
                                label: 'Resources',
                                tooltip: 'Resources',
                                icon: 'file',
                                position: 'right',
                                onClick: function () {
                                    window.open(
                                        TARGET_URL,
                                        '_blank',
                                        'noopener,noreferrer',
                                    );
                                },
                            },
                        ]);
                    }
                }
            }
        } catch (e) {
            console.warn('[BBB-Resources-Plugin] SDK hook notice:', e);
        }

        // 2. Resilient DOM Integration
        // Ensures the button is visibly rendered even in custom BBB layouts
        function ensureVisibleButton() {
            if (document.getElementById('bbb-resources-action-btn')) {
                return;
            }

            var actionsBar =
                document.querySelector('[class*="actionsBar"]') ||
                document.querySelector('[id*="actions-bar"]') ||
                document.querySelector('footer') ||
                document.body;

            if (!actionsBar) return;

            var btn = document.createElement('button');
            btn.id = 'bbb-resources-action-btn';
            btn.setAttribute('aria-label', 'Resources');
            btn.setAttribute('title', 'Resources');
            btn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: middle;">
          <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
          <path d="M6 6h10"/>
          <path d="M6 10h10"/>
        </svg>
        <span style="vertical-align: middle;">Resources</span>
      `;

            btn.style.cssText = [
                'display: inline-flex',
                'align-items: center',
                'justify-content: center',
                'padding: 8px 14px',
                'margin: 0 8px',
                'background-color: #0f70b7',
                'color: #ffffff',
                'font-family: inherit',
                'font-size: 13px',
                'font-weight: 600',
                'border: none',
                'border-radius: 4px',
                'cursor: pointer',
                'box-shadow: 0 2px 4px rgba(0,0,0,0.2)',
                'transition: background-color 0.2s, transform 0.1s',
                'z-index: 9999',
            ].join(';');

            btn.onmouseover = function () {
                btn.style.backgroundColor = '#0b5388';
            };
            btn.onmouseout = function () {
                btn.style.backgroundColor = '#0f70b7';
            };
            btn.onclick = function (e) {
                e.preventDefault();
                window.open(TARGET_URL, '_blank', 'noopener,noreferrer');
            };

            // Append to action bar or top right options
            if (actionsBar === document.body) {
                btn.style.position = 'fixed';
                btn.style.top = '12px';
                btn.style.right = '70px';
            }

            actionsBar.appendChild(btn);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', ensureVisibleButton);
        } else {
            ensureVisibleButton();
        }

        var observer = new MutationObserver(ensureVisibleButton);
        observer.observe(document.body, { childList: true, subtree: true });
    }

    initPlugin();

    return {
        version: '1.0.0',
        name: 'bbb-resources-plugin',
        targetUrl: TARGET_URL,
    };
});
