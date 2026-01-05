/**
 * Tippy.js Loader - Ensures Popper.js and Tippy.js load in the correct order
 * This file loads both libraries sequentially to avoid race conditions
 */
define([], function() {
    'use strict';
    
    return new Promise(function(resolve, reject) {
        // Load Popper.js first
        var popperScript = document.createElement('script');
        popperScript.src = g_gamethemeurl + 'modules/js/vendor/popper.js';
        popperScript.onload = function() {
            // After Popper.js loads, load Tippy.js
            var tippyScript = document.createElement('script');
            tippyScript.src = g_gamethemeurl + 'modules/js/vendor/tippy.js';
            tippyScript.onload = function() {
                // Both loaded successfully
                if (typeof window.tippy === 'function' && typeof window.Popper !== 'undefined') {
                    resolve(window.tippy);
                } else {
                    reject(new Error('Tippy.js or Popper.js failed to initialize'));
                }
            };
            tippyScript.onerror = function() {
                reject(new Error('Failed to load Tippy.js'));
            };
            document.head.appendChild(tippyScript);
        };
        popperScript.onerror = function() {
            reject(new Error('Failed to load Popper.js'));
        };
        document.head.appendChild(popperScript);
    });
});

