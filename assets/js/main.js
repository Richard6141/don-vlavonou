// Main JavaScript for Donation Platform

document.addEventListener('DOMContentLoaded', function() {
    
    // Get CSRF token on page load
    fetchCSRFToken();
    
    // Amount buttons functionality
    const amountButtons = document.querySelectorAll('.amount-btn');
    const customAmountInput = document.getElementById('customAmount');
    
    amountButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all buttons
            amountButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Set the amount in the input
            const amount = this.getAttribute('data-amount');
            customAmountInput.value = amount;
        });
    });
    
    // Clear amount button selection when typing custom amount
    customAmountInput.addEventListener('input', function() {
        amountButtons.forEach(btn => btn.classList.remove('active'));
    });
    
    // Payment method selection
    const fedapayBtn = document.getElementById('fedapayBtn');
    const kkiapayBtn = document.getElementById('kkiapayBtn');
    const paymentMethodInput = document.getElementById('paymentMethod');
    const paymentMethodBtns = document.querySelectorAll('.payment-method-btn');
    
    fedapayBtn.addEventListener('click', function() {
        selectPaymentMethod('fedapay', this);
    });
    
    kkiapayBtn.addEventListener('click', function() {
        selectPaymentMethod('kkiapay', this);
    });
    
    function selectPaymentMethod(method, button) {
        // Remove active class from all payment buttons
        paymentMethodBtns.forEach(btn => btn.classList.remove('active'));
        
        // Add active class to selected button
        button.classList.add('active');
        
        // Set payment method value
        paymentMethodInput.value = method;
    }
    
    // Form submission
    const donationForm = document.getElementById('donationForm');
    
    donationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Get form data
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        // Show loading state
        toggleLoadingState(true);
        
        // Send data to backend
        processDonation(data);
    });
    
    // Validate form
    function validateForm() {
        const amount = document.getElementById('customAmount').value;
        const firstName = document.getElementById('firstName').value;
        const lastName = document.getElementById('lastName').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const paymentMethod = document.getElementById('paymentMethod').value;
        
        if (!amount || amount < 500) {
            showError('Le montant minimum est de 500 FCFA');
            return false;
        }
        
        if (!firstName.trim() || !lastName.trim()) {
            showError('Veuillez entrer votre nom complet');
            return false;
        }
        
        if (!validateEmail(email)) {
            showError('Veuillez entrer une adresse email valide');
            return false;
        }
        
        if (!phone.trim()) {
            showError('Veuillez entrer votre numéro de téléphone');
            return false;
        }
        
        if (!paymentMethod) {
            showError('Veuillez choisir un mode de paiement');
            return false;
        }
        
        return true;
    }
    
    // Email validation
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Process donation
    async function processDonation(data) {
        try {
            const response = await fetch('../backend/process_donation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Redirect to payment URL if available (server-side integration)
                if (result.payment_url) {
                    window.location.href = result.payment_url;
                } else if (data.payment_method === 'kkiapay') {
                    // Use client-side widget for Kkiapay
                    initializeKkiaPay(result.transaction_id, data);
                } else {
                    showError('Erreur de configuration du paiement.');
                    toggleLoadingState(false);
                }
            } else {
                showError(result.message || 'Une erreur est survenue. Veuillez réessayer.');
                toggleLoadingState(false);
            }
            
        } catch (error) {
            console.error('Fetch Error:', error);
            console.error('Error message:', error.message);
            showError('Erreur: ' + error.message);
            toggleLoadingState(false);
        }
    }
    
    // Initialize FedaPay payment
    function initializeFedaPay(transactionId, data) {
        if (typeof FedaPay === 'undefined') {
            showError('Erreur de chargement FedaPay. Rechargez la page.');
            toggleLoadingState(false);
            return;
        }

        let widget = FedaPay.init({
            public_key: window.PAYMENT_CONFIG.fedapay_public_key,
            environment: window.PAYMENT_CONFIG.fedapay_sandbox ? 'sandbox' : 'live',
            locale: 'fr',
            transaction: {
                amount: parseInt(data.amount),
                description: `Don pour Vlavonou - ${data.first_name} ${data.last_name}`,
                custom_metadata: {
                    transaction_id: transactionId
                }
            },
            customer: {
                firstname: data.first_name,
                lastname: data.last_name,
                email: data.email,
                phone_number: {
                    number: data.phone,
                    country: 'BJ'
                }
            },
            onComplete: function(reason, transaction) {
                if (reason === FedaPay.CHECKOUT_COMPLETED) {
                    handlePaymentSuccess(transactionId);
                } else if (reason === FedaPay.DIALOG_DISMISSED) {
                    toggleLoadingState(false);
                    showError('Paiement annulé');
                } else {
                    toggleLoadingState(false);
                    showError('Le paiement a échoué.');
                }
            }
        });

        widget.open();
    }
    
    // Initialize KkiaPay payment
    function initializeKkiaPay(transactionId, data) {
        if (typeof openKkiapayWidget === 'undefined') {
            showError('Erreur de chargement Kkiapay. Rechargez la page.');
            toggleLoadingState(false);
            return;
        }

        openKkiapayWidget({
            amount: parseInt(data.amount),
            key: window.PAYMENT_CONFIG.kkiapay_public_key,
            sandbox: window.PAYMENT_CONFIG.kkiapay_sandbox,
            data: transactionId,
            theme: '#16a34a',
            position: 'center',
            name: data.first_name + ' ' + data.last_name,
            email: data.email,
            phone: data.phone
        });

        // Stop loading state since widget handles its own UI
        toggleLoadingState(false);

        addSuccessListener(function(response) {
            console.log('Kkiapay success:', response);
            // Wait for Kkiapay widget to close before showing our notification
            setTimeout(function() {
                toggleLoadingState(true);
                handlePaymentSuccess(transactionId, 'KKIAPAY-' + response.transactionId);
            }, 1300);
        });

        addFailedListener(function(error) {
            console.log('Kkiapay failed:', error);
            showError('Le paiement a échoué. Veuillez réessayer.');
        });
    }
    
    // Handle successful payment
    async function handlePaymentSuccess(transactionId, paymentReference = null) {
        try {
            const response = await fetch('../backend/confirm_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    transaction_id: transactionId,
                    payment_reference: paymentReference
                })
            });
            
            const result = await response.json();
            console.log('Confirm payment response:', result);

            if (result.success) {
                console.log('Showing success notification...');
                showSuccess('Merci pour votre don ! Un reçu vous a été envoyé par email.');
                console.log('Success notification called');

                // Reset form after 3 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                console.error('Confirm payment error:', result.message);
                showError(result.message || 'Erreur lors de la confirmation. Veuillez contacter le support.');
            }
            
        } catch (error) {
            console.error('Confirm payment catch error:', error);
            console.error('Error details:', error.message);
            showError('Erreur de confirmation: ' + error.message);
        }
        
        toggleLoadingState(false);
    }
    
    // Fetch CSRF token
    async function fetchCSRFToken() {
        try {
            const response = await fetch('../backend/get_csrf_token.php');
            const data = await response.json();
            
            if (data.token) {
                document.getElementById('csrfToken').value = data.token;
            }
        } catch (error) {
            console.error('Error fetching CSRF token:', error);
        }
    }
    
    // Toggle loading state
    function toggleLoadingState(isLoading) {
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnLoader = document.getElementById('btnLoader');
        
        if (isLoading) {
            submitBtn.disabled = true;
            btnText.classList.add('hidden');
            btnLoader.classList.remove('hidden');
        } else {
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoader.classList.add('hidden');
        }
    }
    
    // Show notification modal
    function showNotification(type, title, message) {
        console.log('showNotification called:', type, title, message);
        const overlay = document.createElement('div');
        overlay.className = 'notification-overlay';
        console.log('Overlay created:', overlay);
        overlay.innerHTML = `
            <div class="notification-modal ${type}">
                <div class="notification-icon">
                    ${type === 'success'
                        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
                        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
                    }
                </div>
                <h3 class="notification-title">${title}</h3>
                <p class="notification-message">${message}</p>
                <button class="notification-button ${type}" onclick="this.closest('.notification-overlay').remove()">
                    Fermer
                </button>
            </div>
        `;
        document.body.appendChild(overlay);
        console.log('Overlay appended to body, checking DOM:', document.querySelectorAll('.notification-overlay').length);

        setTimeout(() => {
            if (overlay.parentNode) overlay.remove();
        }, 10000);
    }

    // Show success message
    function showSuccess(message) {
        showNotification('success', 'Succès', message);
    }

    // Show error message
    function showError(message) {
        showNotification('error', 'Erreur', message);
    }
    
});
