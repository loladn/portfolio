// Navbar scroll effect
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Read More functionality for both sections
document.addEventListener('DOMContentLoaded', () => {
    // Pour la section Bilan
    const bilanContent = document.querySelector('.personal-review-content');
    const bilanReadMoreBtn = document.querySelector('.personal-review .read-more-btn');

    if (bilanReadMoreBtn && bilanContent) {
        bilanReadMoreBtn.addEventListener('click', () => {
            bilanContent.classList.toggle('collapsed');
            bilanContent.classList.toggle('expanded');

            if (bilanContent.classList.contains('expanded')) {
                bilanReadMoreBtn.innerHTML = 'Voir moins <i class="fas fa-chevron-up"></i>';
            } else {
                bilanReadMoreBtn.innerHTML = 'Lire plus <i class="fas fa-chevron-down"></i>';
            }
        });
    }

    // Pour la section À propos
    const aboutText = document.querySelector('.about-text');
    const aboutReadMoreBtn = aboutText?.querySelector('.read-more-btn');

    if (aboutText && aboutReadMoreBtn) {
        aboutReadMoreBtn.addEventListener('click', () => {
            aboutText.classList.toggle('collapsed');
            aboutText.classList.toggle('expanded');

            if (aboutText.classList.contains('expanded')) {
                aboutReadMoreBtn.innerHTML = 'Réduire <i class="fas fa-chevron-up"></i>';
            } else {
                aboutReadMoreBtn.innerHTML = 'Lire plus <i class="fas fa-chevron-down"></i>';
            }
        });
    }
});

// Projects filtering
document.addEventListener('DOMContentLoaded', () => {
    const yearFilters = document.querySelectorAll('.year-filters .filter-btn');
    const domainFilters = document.querySelectorAll('.domain-filters .filter-btn');
    const projects = document.querySelectorAll('.project-card');

    let currentYearFilter = 'all';
    let currentDomainFilter = 'all';

    function updateFilters() {
        projects.forEach(project => {
            const projectYear = project.dataset.year;
            const projectDomains = project.dataset.domain.split(',');

            const yearMatch = currentYearFilter === 'all' || projectYear === currentYearFilter;
            const domainMatch = currentDomainFilter === 'all' || projectDomains.includes(currentDomainFilter);

            if (yearMatch && domainMatch) {
                project.style.display = '';
                project.style.animation = 'fadeIn 0.5s ease forwards';
            } else {
                project.style.display = 'none';
            }
        });
    }

    yearFilters.forEach(btn => {
        btn.addEventListener('click', () => {
            yearFilters.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentYearFilter = btn.dataset.year;
            updateFilters();
        });
    });

    domainFilters.forEach(btn => {
        btn.addEventListener('click', () => {
            domainFilters.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentDomainFilter = btn.dataset.domain;
            updateFilters();
        });
    });
});

// Modal handling
document.addEventListener('DOMContentLoaded', () => {
    // Get all buttons that open a modal
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.modal-close');

    // Open modal
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = trigger.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            modal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        });
    });

    // Close modal with close button
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            modal.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        });
    });

    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = ''; // Restore scrolling
            }
        });
    });

    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                modal.classList.remove('active');
                document.body.style.overflow = ''; // Restore scrolling
            });
        }
    });
});

// Merged functionality from main.js
document.addEventListener('DOMContentLoaded', () => {
    // Menu mobile
    const burgerMenu = document.querySelector('.burger-menu');
    const navLinks = document.querySelector('.nav-links');

    if (burgerMenu) {
        burgerMenu.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            burgerMenu.classList.toggle('active');
        });
    }

    // Animation au défilement
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, observerOptions);

    // Observer les sections
    document.querySelectorAll('section').forEach(section => {
        observer.observe(section);
    });

    // Smooth scroll pour les liens d'ancrage
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                // Fermer le menu mobile si ouvert
                if (window.innerWidth <= 768 && navLinks && burgerMenu) {
                    navLinks.classList.remove('active');
                    burgerMenu.classList.remove('active');
                }
            }
        });
    });

    // Animation du menu au scroll (Enhanced version)
    let lastScroll = 0;
    const navbar = document.querySelector('.navbar');

    if (navbar) {
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            // Keep the original simple check for transparency
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            // Enhanced scroll behavior (hide/show)
            if (currentScroll <= 0) {
                navbar.classList.remove('scroll-up');
                return;
            }

            if (currentScroll > lastScroll && !navbar.classList.contains('scroll-down')) {
                navbar.classList.remove('scroll-up');
                navbar.classList.add('scroll-down');
            } else if (currentScroll < lastScroll && navbar.classList.contains('scroll-down')) {
                navbar.classList.remove('scroll-down');
                navbar.classList.add('scroll-up');
            }
            lastScroll = currentScroll;
        });
    }
});