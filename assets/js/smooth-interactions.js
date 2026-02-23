/**
 * iOS-Smooth Interactions
 * Handles entrance animations, spring-loaded interactions, and smooth scrolling.
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Staggered Entrance Animations for Cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';

        setTimeout(() => {
            card.style.transition = 'all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 + (index * 100)); // Staggered by 100ms
    });

    // 2. Button Press Feedback (Haptic-like Scale)
    const btns = document.querySelectorAll('.btn, .list-group-item, .category-chip');
    btns.forEach(btn => {
        btn.addEventListener('mousedown', () => {
            btn.style.transform = 'scale(0.96)';
        });
        btn.addEventListener('mouseup', () => {
            btn.style.transform = '';
        });
        btn.addEventListener('mouseleave', () => {
            btn.style.transform = '';
        });
    });

    // 3. Smooth Menu Toggle logic enhancement
    const menuToggle = document.getElementById('menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');

            // Toggle Icon Rotation or state if needed
            const icon = menuToggle.querySelector('i');
            if (icon) {
                icon.style.transition = 'transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
                icon.style.transform = document.body.classList.contains('sb-sidenav-toggled') ? 'rotate(90deg)' : 'rotate(0deg)';
            }
        });
    }

    // 4. Smooth Scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            if (this.getAttribute('href') === '#') return;
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // 5. Add 'glass' effect to navbar on scroll
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 10) {
                navbar.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.05)';
                navbar.style.borderBottomColor = 'rgba(60, 60, 67, 0.1)';
            } else {
                navbar.style.boxShadow = 'none';
                navbar.style.borderBottomColor = 'var(--border-dim)';
            }
        });
    }
});
