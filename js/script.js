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