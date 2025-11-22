<?php
require_once '../config/config.php';

// Handle payment return from FedaPay
if (isset($_GET['payment']) && $_GET['payment'] === 'complete') {
    require_once '../vendor/autoload.php';

    $ref = $_GET['ref'] ?? '';
    $fedapayId = $_GET['id'] ?? '';
    $status = $_GET['status'] ?? '';

    if (!empty($ref) && $status === 'approved') {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                UPDATE donations
                SET status = 'completed',
                    payment_reference = :payment_ref,
                    updated_at = NOW()
                WHERE transaction_id = :transaction_id AND status = 'pending'
            ");
            $stmt->execute([
                ':payment_ref' => 'FEDAPAY-' . $fedapayId,
                ':transaction_id' => $ref
            ]);
        } catch (Exception $e) {
            error_log("Payment update error: " . $e->getMessage());
        }
    }
}

// Fetch today's statistics and donations
$todayStats = ['total_donations' => 0, 'total_amount' => 0, 'avg_amount' => 0];
$todayDonations = [];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 5;
$totalPages = 1;

try {
    $pdo = getDBConnection();

    // Get today's statistics
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total_donations,
            COALESCE(SUM(amount), 0) as total_amount,
            COALESCE(AVG(amount), 0) as avg_amount
        FROM donations
        WHERE status = 'completed'
        AND DATE(created_at) = CURDATE()
    ");
    $todayStats = $stmt->fetch();

    // Get total count for pagination
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM donations
        WHERE status = 'completed'
        AND DATE(created_at) = CURDATE()
    ");
    $totalCount = $stmt->fetch()['total'];
    $totalPages = max(1, ceil($totalCount / $perPage));
    $offset = ($page - 1) * $perPage;

    // Get today's donations with pagination
    $stmt = $pdo->prepare("
        SELECT first_name, last_name, amount, payment_method, created_at
        FROM donations
        WHERE status = 'completed'
        AND DATE(created_at) = CURDATE()
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $todayDonations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Stats error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Soutenez Vlavonou - Ma contribution">
    <title>Don pour Vlavonou - Union Progressiste le Renouveau</title>

    <!-- TailwindCSS -->
    <link rel="stylesheet" href="../assets/css/tailwind.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Favicon (à ajouter) -->
    <!-- <link rel="icon" type="image/png" href="../assets/images/favicon.png"> -->
</head>

<body class="bg-gradient-to-br from-green-50 to-yellow-50 min-h-screen">

    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-center">
                <img src="../assets/images/logo.png" alt="Union Progressiste le Renouveau" class="h-20 md:h-24">
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 md:py-12">

        <!-- Message de Vlavonou -->
        <section class="bg-white rounded-2xl shadow-xl p-6 md:p-10 mb-8 max-w-4xl mx-auto">
            <div class="flex flex-col md:flex-row items-center gap-6 mb-6">
                <img src="../assets/images/vlavonou.jpg" alt="Vlavonou"
                    class="w-32 h-32 md:w-40 md:h-40 rounded-full object-cover border-4 border-green-600 shadow-lg">
                <div class="text-center md:text-left">
                    <h1 class="text-3xl md:text-4xl font-bold text-green-800 mb-2">Soutenez Notre Vision</h1>
                    <p class="text-xl text-gray-700">Avec Vlavonou pour un Renouveau</p>
                </div>
            </div>

            <div class="prose prose-lg max-w-none">
                <p class="text-gray-700 leading-relaxed mb-4">
                    Chers compatriotes, chers amis,
                </p>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Ensemble, nous construisons un avenir meilleur pour notre nation. Votre soutien est essentiel
                    pour mener à bien nos projets et nos engagements envers le peuple.
                </p>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Chaque contribution, quelle que soit sa taille, est un acte de foi en notre vision commune
                    d'un pays prospère, juste et solidaire.
                </p>
                <p class="text-gray-700 leading-relaxed font-semibold text-green-800">
                    Merci pour votre confiance et votre générosité.
                </p>
            </div>
        </section>

        <!-- Formulaire de Don -->
        <section class="bg-white rounded-2xl shadow-xl p-4 md:p-6 max-w-2xl mx-auto">
            <h2 class="text-2xl md:text-3xl font-bold text-center text-green-800 mb-4">
                Ma contribution
            </h2>

            <form id="donationForm" class="space-y-4">

                <!-- Montant prédéfini -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Choisissez un montant</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <button type="button"
                            class="amount-btn py-2 px-3 border-2 border-green-600 rounded-lg hover:bg-green-600 hover:text-white transition font-semibold text-sm"
                            data-amount="5000">5 000 FCFA</button>
                        <button type="button"
                            class="amount-btn py-2 px-3 border-2 border-green-600 rounded-lg hover:bg-green-600 hover:text-white transition font-semibold text-sm"
                            data-amount="10000">10 000 FCFA</button>
                        <button type="button"
                            class="amount-btn py-2 px-3 border-2 border-green-600 rounded-lg hover:bg-green-600 hover:text-white transition font-semibold text-sm"
                            data-amount="25000">25 000 FCFA</button>
                        <button type="button"
                            class="amount-btn py-2 px-3 border-2 border-green-600 rounded-lg hover:bg-green-600 hover:text-white transition font-semibold text-sm"
                            data-amount="50000">50 000 FCFA</button>
                    </div>
                </div>

                <!-- Montant personnalisé -->
                <div>
                    <label for="customAmount" class="block text-gray-700 font-semibold mb-1">Ou entrez un montant
                        (FCFA)</label>
                    <input type="number" id="customAmount" name="amount" min="500" step="100" required
                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-600 focus:outline-none"
                        placeholder="Montant (min. 500)">
                </div>

                <!-- Informations du donateur -->
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label for="firstName" class="block text-gray-700 font-semibold mb-1">Prénom *</label>
                        <input type="text" id="firstName" name="first_name" required
                            class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-600 focus:outline-none"
                            placeholder="Votre prénom">
                    </div>
                    <div>
                        <label for="lastName" class="block text-gray-700 font-semibold mb-1">Nom *</label>
                        <input type="text" id="lastName" name="last_name" required
                            class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-600 focus:outline-none"
                            placeholder="Votre nom">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label for="email" class="block text-gray-700 font-semibold mb-1">Email *</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-600 focus:outline-none"
                            placeholder="votre@email.com">
                    </div>
                    <div>
                        <label for="phone" class="block text-gray-700 font-semibold mb-1">Téléphone *</label>
                        <input type="tel" id="phone" name="phone" required
                            class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-600 focus:outline-none"
                            placeholder="+229 XX XX XX XX">
                    </div>
                </div>

                <div>
                    <label for="message" class="block text-gray-700 font-semibold mb-1">Message (optionnel)</label>
                    <textarea id="message" name="message" rows="2"
                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-600 focus:outline-none text-sm"
                        placeholder="Laissez un message de soutien..."></textarea>
                </div>

                <!-- Choix du mode de paiement -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Mode de paiement</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" id="fedapayBtn"
                            class="payment-method-btn flex items-center justify-center gap-2 py-3 px-4 border-2 border-gray-300 rounded-lg hover:border-green-600 hover:bg-green-50 transition">
                            <img src="../assets/images/fedapay-logo.png" alt="FedaPay" class="h-6">
                            <span class="font-semibold text-sm">FedaPay</span>
                        </button>
                        <button type="button" id="kkiapayBtn"
                            class="payment-method-btn flex items-center justify-center gap-2 py-3 px-4 border-2 border-gray-300 rounded-lg hover:border-green-600 hover:bg-green-50 transition">
                            <img src="../assets/images/kkiapay-logo.png" alt="KkiaPay" class="h-6">
                            <span class="font-semibold text-sm">KkiaPay</span>
                        </button>
                    </div>
                    <input type="hidden" id="paymentMethod" name="payment_method" required>
                </div>

                <!-- CSRF Token (généré dynamiquement) -->
                <input type="hidden" name="csrf_token" id="csrfToken" value="">

                <!-- Bouton de soumission -->
                <button type="submit" id="submitBtn"
                    class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-bold hover:bg-green-700 transition shadow-lg hover:shadow-xl disabled:bg-gray-400 disabled:cursor-not-allowed">
                    <span id="btnText">Procéder au paiement</span>
                    <span id="btnLoader" class="hidden">
                        <svg class="animate-spin h-5 w-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Traitement...
                    </span>
                </button>

                <p class="text-center text-xs text-gray-500">
                    🔒 Paiement 100% sécurisé
                </p>
            </form>
        </section>

        <!-- Informations de confiance -->
        <!-- <section class="max-w-4xl mx-auto mt-8 grid md:grid-cols-3 gap-6 text-center">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-green-600 text-4xl mb-2">🔒</div>
                <h3 class="font-bold text-gray-800 mb-2">Sécurisé</h3>
                <p class="text-sm text-gray-600">Paiement crypté et sécurisé</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-green-600 text-4xl mb-2">⚡</div>
                <h3 class="font-bold text-gray-800 mb-2">Rapide</h3>
                <p class="text-sm text-gray-600">Transaction en quelques secondes</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-green-600 text-4xl mb-2">📧</div>
                <h3 class="font-bold text-gray-800 mb-2">Reçu automatique</h3>
                <p class="text-sm text-gray-600">Confirmation par email instantanée</p>
            </div>
        </section> -->

        <!-- Statistiques du jour -->
        <section class="max-w-4xl mx-auto mt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">Contributions du jour</h2>

            <!-- Cartes statistiques -->
            <div class="grid md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow text-center">
                    <p class="text-3xl font-bold text-green-600"><?php echo $todayStats['total_donations']; ?></p>
                    <p class="text-sm text-gray-600">Dons aujourd'hui</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow text-center">
                    <p class="text-3xl font-bold text-green-600">
                        <?php echo number_format($todayStats['total_amount'], 0, ',', ' '); ?></p>
                    <p class="text-sm text-gray-600">FCFA collectés</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow text-center">
                    <p class="text-3xl font-bold text-green-600">
                        <?php echo number_format($todayStats['avg_amount'], 0, ',', ' '); ?></p>
                    <p class="text-sm text-gray-600">FCFA en moyenne</p>
                </div>
            </div>

            <!-- Liste des dons du jour -->
            <?php if (!empty($todayDonations)): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-green-600 text-white">
                            <tr>
                                <th class="py-3 px-4 text-left">Donateur</th>
                                <th class="py-3 px-4 text-right">Montant</th>
                                <th class="py-3 px-4 text-center hidden md:table-cell">Méthode</th>
                                <th class="py-3 px-4 text-right hidden md:table-cell">Heure</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayDonations as $donation): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <?php echo htmlspecialchars($donation['first_name'] . ' ' . substr($donation['last_name'], 0, 1) . '.'); ?>
                                    </td>
                                    <td class="py-3 px-4 text-right font-semibold text-green-600">
                                        <?php echo number_format($donation['amount'], 0, ',', ' '); ?> F
                                    </td>
                                    <td class="py-3 px-4 text-center hidden md:table-cell">
                                        <span
                                            class="px-2 py-1 text-xs rounded-full <?php echo $donation['payment_method'] === 'fedapay' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                            <?php echo ucfirst($donation['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-500 text-sm hidden md:table-cell">
                                        <?php echo date('H:i', strtotime($donation['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="flex justify-center items-center gap-2 py-4 bg-gray-50">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>"
                                    class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">←</a>
                            <?php endif; ?>

                            <span class="px-3 py-1 text-gray-600">
                                Page <?php echo $page; ?> / <?php echo $totalPages; ?>
                            </span>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>"
                                    class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">→</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                    <p>Aucun don enregistré aujourd'hui. Soyez le premier à contribuer !</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-green-800 text-white py-6 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2024 Union Progressiste le Renouveau - Tous droits réservés</p>
            <p class="text-sm mt-2">Contact : <a href="mailto:contact@upr-benin.org">contact@upr-benin.org</a></p>
        </div>
    </footer>

    <!-- Payment SDKs -->
    <script src="https://cdn.fedapay.com/checkout.js?v=1.1.7"></script>
    <script src="https://cdn.kkiapay.me/k.js"></script>

    <!-- Payment Configuration -->
    <?php require_once '../config/config.php'; ?>
    <script>
        window.PAYMENT_CONFIG = {
            fedapay_public_key: '<?php echo FEDAPAY_PUBLIC_KEY; ?>',
            fedapay_sandbox: <?php echo FEDAPAY_MODE === 'sandbox' ? 'true' : 'false'; ?>,
            kkiapay_public_key: '<?php echo KKIAPAY_PUBLIC_KEY; ?>',
            kkiapay_sandbox: <?php echo KKIAPAY_MODE === 'sandbox' ? 'true' : 'false'; ?>
        };
    </script>

    <!-- JavaScript -->
    <script src="../assets/js/main.js"></script>

    <!-- Handle payment return -->
    <script>
        (function() {
            function showNotification(type, title, message) {
                const overlay = document.createElement('div');
                overlay.className = 'notification-overlay';
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

                // Auto-close after 10 seconds
                setTimeout(() => {
                    if (overlay.parentNode) overlay.remove();
                }, 10000);
            }

            const params = new URLSearchParams(window.location.search);
            if (params.get('payment') === 'complete') {
                const status = params.get('status');
                if (status === 'approved') {
                    showNotification('success', 'Paiement réussi !',
                        'Merci pour votre don ! Votre paiement a été effectué avec succès. Un reçu vous sera envoyé par email.'
                    );
                } else {
                    // Tout autre statut (cancelled, declined, pending, etc.) = échec
                    showNotification('error', 'Paiement non complété',
                        'Le paiement a été annulé ou n\'a pas abouti. Veuillez réessayer.');
                }
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        })();
    </script>

    <style>
        .notification-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: rgba(0, 0, 0, 0.5) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 999999 !important;
            animation: fadeIn 0.3s ease;
        }

        .notification-modal {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        .notification-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .notification-icon svg {
            width: 32px;
            height: 32px;
        }

        .notification-modal.success .notification-icon {
            background: #dcfce7;
            color: #16a34a;
        }

        .notification-modal.error .notification-icon {
            background: #fee2e2;
            color: #dc2626;
        }

        .notification-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        .notification-message {
            color: #6b7280;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .notification-button {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .notification-button.success {
            background: #16a34a;
            color: white;
        }

        .notification-button.success:hover {
            background: #15803d;
        }

        .notification-button.error {
            background: #dc2626;
            color: white;
        }

        .notification-button.error:hover {
            background: #b91c1c;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</body>

</html>