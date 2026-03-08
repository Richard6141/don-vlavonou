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
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Initialize payment gateway
                if (data.payment_method === 'fedapay') {
                    initializeFedaPay(result.transaction_id, data);
                } else if (data.payment_method === 'kkiapay') {
                    initializeKkiaPay(result.transaction_id, data);
                }
            } else {
                showError(result.message || 'Une erreur est survenue. Veuillez réessayer.');
                toggleLoadingState(false);
            }
            
        } catch (error) {
            console.error('Error:', error);
            showError('Erreur de connexion. Veuillez vérifier votre connexion internet.');
            toggleLoadingState(false);
        }
    }
    
    // Initialize FedaPay payment
    function initializeFedaPay(transactionId, data) {
        FedaPay.init({
            public_key: 'YOUR_FEDAPAY_PUBLIC_KEY', // À remplacer par votre clé publique
            transaction: {
                amount: parseInt(data.amount),
                description: `Don pour Vlavonou - ${data.first_name} ${data.last_name}`,
                callback_url: window.location.origin + '/backend/payment_callback.php',
                custom_metadata: {
                    transaction_id: transactionId,
                    donor_email: data.email
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
            onComplete: function(response) {
                if (response.reason === FedaPay.DIALOG_DISMISSED) {
                    toggleLoadingState(false);
                    showError('Paiement annulé');
                } else {
                    handlePaymentSuccess(transactionId);
                }
            }
        });
        
        FedaPay.open();
    }
    
    // Initialize KkiaPay payment
    function initializeKkiaPay(transactionId, data) {
        openKkiapayWidget({
            amount: parseInt(data.amount),
            api_key: 'YOUR_KKIAPAY_PUBLIC_KEY', // À remplacer par votre clé publique
            sandbox: true, // Mettre à false en production
            phone: data.phone,
            name: `${data.first_name} ${data.last_name}`,
            email: data.email,
            data: transactionId,
            callback: window.location.origin + '/backend/payment_callback.php',
            theme: '#16a34a'
        });
        
        addKkiapayListener('success', function(response) {
            handlePaymentSuccess(transactionId);
        });
        
        addKkiapayListener('failed', function(response) {
            toggleLoadingState(false);
            showError('Le paiement a échoué. Veuillez réessayer.');
        });
    }
    
    // Handle successful payment
    async function handlePaymentSuccess(transactionId) {
        try {
            const response = await fetch('../backend/confirm_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ transaction_id: transactionId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccess('Merci pour votre don ! Un reçu vous a été envoyé par email.');
                
                // Reset form after 2 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                showError('Erreur lors de la confirmation. Veuillez contacter le support.');
            }
            
        } catch (error) {
            console.error('Error:', error);
            showError('Erreur de confirmation. Votre paiement sera vérifié.');
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
    
    // Show success message
    function showSuccess(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(successDiv);
        
        setTimeout(() => {
            successDiv.remove();
        }, 5000);
    }
    
    // Show error message
    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(errorDiv);
        
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
    
});
