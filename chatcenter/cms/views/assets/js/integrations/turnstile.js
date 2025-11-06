/**
 * Cloudflare Turnstile Integration for ChatCenter
 * Handles token submission automatically for forms with Turnstile widget
 */

(function() {
    'use strict';

    // Initialize Turnstile on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Turnstile Integration: Initialized');
        initializeTurnstileHandlers();
    });

    function initializeTurnstileHandlers() {
        // Handle form submissions with Turnstile
        const forms = document.querySelectorAll('form');
        
        forms.forEach(function(form) {
            const turnstileWidget = form.querySelector('.turnstile-widget');
            
            if (turnstileWidget) {
                console.log('Turnstile Integration: Found form with Turnstile widget');
                setupFormHandling(form);
            }
        });

        // Handle iframe messages from Turnstile
        window.addEventListener('message', function(event) {
            if (event.origin !== 'https://challenges.cloudflare.com') return;
            
            const data = event.data;
            
            if (data && data.response) {
                handleTurnstileResponse(data.response);
            }
        });
    }

    function setupFormHandling(form) {
        // Intercept form submission
        form.addEventListener('submit', function(event) {
            const turnstileWidget = form.querySelector('.turnstile-widget');
            const hiddenInput = form.querySelector('input[name="g-recaptcha-response"]');
            
            if (turnstileWidget && hiddenInput && !hiddenInput.value) {
                event.preventDefault();
                console.log('Turnstile Integration: Waiting for Turnstile response...');
                
                // Show loading state
                showTurnstileLoading(form);
                
                return false;
            }
        });
    }

    function handleTurnstileResponse(response) {
        console.log('Turnstile Integration: Received response', response);
        
        // Find all forms with hidden inputs for this response
        const hiddenInputs = document.querySelectorAll('input[name="g-recaptcha-response"]');
        
        hiddenInputs.forEach(function(hiddenInput) {
            if (!hiddenInput.value) {
                hiddenInput.value = response;
                console.log('Turnstile Integration: Token set in form');
                
                // Try to submit the form if it's waiting
                const form = hiddenInput.closest('form');
                if (form) {
                    hideTurnstileLoading(form);
                    
                    // Small delay to ensure the form data is updated
                    setTimeout(function() {
                        form.submit();
                    }, 100);
                }
            }
        });
    }

    function showTurnstileLoading(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Verificando...';
        }
        
        // Add visual indicator
        const turnstileWidget = form.querySelector('.turnstile-widget');
        if (turnstileWidget) {
            turnstileWidget.style.opacity = '0.7';
            turnstileWidget.style.pointerEvents = 'none';
        }
    }

    function hideTurnstileLoading(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = submitButton.getAttribute('data-original-text') || submitButton.textContent || 'Enviar';
        }
        
        // Restore visual state
        const turnstileWidget = form.querySelector('.turnstile-widget');
        if (turnstileWidget) {
            turnstileWidget.style.opacity = '1';
            turnstileWidget.style.pointerEvents = 'auto';
        }
    }

    // Fallback: Manual token injection for API calls
    window.TurnstileIntegration = {
        setToken: function(token) {
            const hiddenInputs = document.querySelectorAll('input[name="g-recaptcha-response"]');
            hiddenInputs.forEach(function(input) {
                if (!input.value) {
                    input.value = token;
                }
            });
            console.log('Turnstile Integration: Manual token set');
        },
        
        verifyToken: function(token) {
            console.log('Turnstile Integration: Manual verification requested');
            // This would be called after manual verification
            return new Promise(function(resolve) {
                setTimeout(function() {
                    resolve({success: true, disabled: false});
                }, 1000);
            });
        }
    };

})();
