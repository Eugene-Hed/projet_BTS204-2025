<?php
$prenom = $_SESSION['user']['Prenom'];
?>
<section class="hero-section">
    <div class="container">
        <div class="hero-text">
            <h1>Bienvenue <?php echo htmlspecialchars($prenom); ?></h1>
        </div>
        <p class="hero-description">Découvrez nos solutions rapides et efficaces pour gérer vos demandes.</p>
    </div>
</section>

<section class="apple-carousel">
    <h2 class="text-center mb-5">Quelques fonctionnalités de la plateforme</h2>
    <div class="carousel-container">
        <div class="carousel-card" style="--bg-image: url('../../Assets/images/demande-cni.jpg');">
            <div class="carousel-card-content">
                <i class="bi bi-person-badge"></i>
                <h5>Demande de CNI</h5>
                <p>Soumettez votre demande en ligne et suivez son évolution.</p>
            </div>
        </div>
        <div class="carousel-card" style="--bg-image: url('../../Assets/images/demande-cn.jpg');">
            <div class="carousel-card-content">
                <i class="bi bi-file-earmark-text"></i>
                <h5>Demande de Certificat de Nationalité</h5>
                <p>Demandez et téléchargez facilement votre certificat de nationalité.</p>
            </div>
        </div>
        <div class="carousel-card" style="--bg-image: url('../../Assets/images/securite2.jpg');">
            <div class="carousel-card-content">
                <i class="bi bi-shield-lock"></i>
                <h5>Sécurité Renforcée</h5>
                <p>Toutes vos informations sont protégées grâce à une sécurité avancée.</p>
            </div>
        </div>
        <div class="carousel-card" style="--bg-image: url('../../Assets/images/config.jpg');">
            <div class="carousel-card-content">
                <i class="bi bi-gear"></i>
                <h5>Paramètres Avancés</h5>
                <p>Configurez vos préférences et paramètres utilisateur.</p>
            </div>
        </div>
        <div class="carousel-card" style="--bg-image: url('../../Assets/images/service-client.jpg');">
            <div class="carousel-card-content">
                <i class="bi bi-chat-dots"></i>
                <h5>Support Client</h5>
                <p>Obtenez de l'aide instantanée avec notre support client.</p>
            </div>
        </div>
        <div class="carousel-card" style="--bg-image: url('../../Assets/images/paiement.jpg');">
            <div class="carousel-card-content">
                <i class="bi bi-wallet2"></i>
                <h5>Paiement Sécurisé</h5>
                <p>Payez en toute sécurité grâce à nos options de paiement sécurisées.</p>
            </div>
        </div>
    </div>
    <button class="carousel-button prev">&lt;</button>
    <button class="carousel-button next">&gt;</button>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.querySelector('.carousel-container');
        const cards = Array.from(carousel.querySelectorAll('.carousel-card'));
        const prevButton = document.querySelector('.carousel-button.prev');
        const nextButton = document.querySelector('.carousel-button.next');
        let currentIndex = 0;

        function updateCarousel() {
            cards.forEach((card, index) => {
                card.classList.remove('active', 'prev', 'next');
                if (index === currentIndex) {
                    card.classList.add('active');
                } else if (index === (currentIndex - 1 + cards.length) % cards.length) {
                    card.classList.add('prev');
                } else if (index === (currentIndex + 1) % cards.length) {
                    card.classList.add('next');
                }
            });
        }

        function moveToNextCard() {
            currentIndex = (currentIndex + 1) % cards.length;
            updateCarousel();
        }

        function moveToPrevCard() {
            currentIndex = (currentIndex - 1 + cards.length) % cards.length;
            updateCarousel();
        }

        nextButton.addEventListener('click', moveToNextCard);
        prevButton.addEventListener('click', moveToPrevCard);

        // Initialize the carousel
        updateCarousel();

        // Optional: Auto-play functionality
        setInterval(moveToNextCard, 5000);
    });
</script>