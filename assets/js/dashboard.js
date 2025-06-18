
        setInterval(function() {
            location.reload();
        }, 30000);

        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            console.log('Dashboard loaded at:', now.toLocaleString());

            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';

                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    