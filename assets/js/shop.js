
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');

                fetch('add-to-cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'product_id=' + productId + '&quantity=1'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update cart badge
                            const cartBadge = document.querySelector('.cart-badge');
                            if (cartBadge) {
                                cartBadge.textContent = data.cart_count;
                            } else {
                                const cartLink = document.querySelector('a[href="cart.php"]');
                                cartLink.innerHTML += '<span class="cart-badge">' + data.cart_count + '</span>';
                            }

                            // Show success message
                            this.innerHTML = '<i class="fas fa-check"></i> Added';
                            this.classList.remove('btn-primary');
                            this.classList.add('btn-success');

                            setTimeout(() => {
                                this.innerHTML = '<i class="fas fa-cart-plus"></i> Add';
                                this.classList.remove('btn-success');
                                this.classList.add('btn-primary');
                            }, 2000);
                        } else {
                            alert('Error adding product to cart: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error adding product to cart');
                    });
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
