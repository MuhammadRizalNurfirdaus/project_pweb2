// File: public/js/script.js

document.addEventListener('DOMContentLoaded', function() {

    // 1. Smooth scrolling untuk link anchor (misalnya, #section-id)
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            let targetId = this.getAttribute('href');
            if (targetId && targetId.length > 1 && document.querySelector(targetId)) {
                try {
                    document.querySelector(targetId).scrollIntoView({
                        behavior: 'smooth'
                    });
                } catch (error) {
                    console.warn("Error scrolling to target:", targetId, error);
                }
            }
        });
    });

    // 2. Tombol "Back to Top"
    const backToTopButton = document.createElement('button');
    backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTopButton.className = 'back-to-top-btn';
    backToTopButton.setAttribute('title', 'Kembali ke atas');
    document.body.appendChild(backToTopButton);

    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.add('show');
        } else {
            backToTopButton.classList.remove('show');
        }
    });

    backToTopButton.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // 3. Navbar menjadi solid saat di-scroll
    const navbar = document.querySelector('.navbar-public');
    if (navbar) {
        const isTransparentOnTop = navbar.classList.contains('navbar-transparent-on-top');
        const heroElement = document.querySelector('.hero-video-background') || document.querySelector('.hero');
        const heroSectionHeight = heroElement ? heroElement.offsetHeight / 2 : 100; 

        window.addEventListener('scroll', () => {
            if (isTransparentOnTop) {
                if (window.scrollY > heroSectionHeight) {
                    navbar.classList.remove('navbar-transparent-on-top');
                    navbar.classList.add('navbar-scrolled-solid');
                } else {
                    navbar.classList.add('navbar-transparent-on-top');
                    navbar.classList.remove('navbar-scrolled-solid');
                }
            } else {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
        });
    }

    // 4. Animasi sederhana untuk elemen saat masuk viewport
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    if (animatedElements.length > 0 && "IntersectionObserver" in window) {
        const observerCallback = (entries, observerInstance) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const delay = entry.target.dataset.animationDelay || '100ms';
                    entry.target.style.transitionDelay = delay;
                    entry.target.classList.add('is-visible');
                    observerInstance.unobserve(entry.target);
                }
            });
        };
        
        const intersectionObserver = new IntersectionObserver(observerCallback, { threshold: 0.1 });

        animatedElements.forEach(el => {
            intersectionObserver.observe(el);
        });
    }

    // 5. Client-side form validation example
    // Ganti 'contactFormPublic' dengan ID form kontak Anda jika berbeda
    const contactFormPublic = document.getElementById('contactFormPublic'); 
    if (contactFormPublic) {
        contactFormPublic.addEventListener('submit', function(event) {
            const emailInput = contactFormPublic.querySelector('input[name="email"]');
            const messageInput = contactFormPublic.querySelector('textarea[name="pesan"]');
            let isValid = true;
            let errorMessages = [];

            const existingErrorDiv = contactFormPublic.querySelector('.form-error-message-container');
            if (existingErrorDiv) {
                existingErrorDiv.remove();
            }

            if (emailInput && emailInput.value.trim() === '') {
                errorMessages.push('Email wajib diisi.');
                isValid = false;
            } else if (emailInput && !/\S+@\S+\.\S+/.test(emailInput.value)) {
                errorMessages.push('Format email tidak valid.');
                isValid = false;
            }

            if (messageInput && messageInput.value.trim() === '') {
                errorMessages.push('Pesan wajib diisi.');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault(); 
                const errorDivContainer = document.createElement('div');
                errorDivContainer.className = 'alert alert-danger mt-3 form-error-message-container';
                errorDivContainer.setAttribute('role', 'alert');
                
                const errorList = document.createElement('ul');
                errorList.className = 'mb-0 ps-3';
                errorMessages.forEach(msg => {
                    const listItem = document.createElement('li');
                    listItem.textContent = msg;
                    errorList.appendChild(listItem);
                });
                errorDivContainer.appendChild(errorList);
                
                const submitButton = contactFormPublic.querySelector('button[type="submit"]');
                if (submitButton) {
                    contactFormPublic.insertBefore(errorDivContainer, submitButton);
                } else {
                    contactFormPublic.prepend(errorDivContainer);
                }
            }
        });
    }

    // 6. Bootstrap validation
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })

}); // Akhir dari DOMContentLoaded


// --- LOGIKA UNTUK MODE GELAP/TERANG ---
(function() {
    const themeToggleButton = document.getElementById('theme-toggle-btn');
    const bodyElement = document.body;
    const themeIcon = themeToggleButton ? themeToggleButton.querySelector('i') : null;

    function setTheme(theme) {
        if (theme === 'dark') {
            bodyElement.classList.add('dark-mode');
            if (themeIcon) {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
                themeIcon.style.transform = 'rotate(360deg)';
            }
            localStorage.setItem('theme', 'dark');
        } else {
            bodyElement.classList.remove('dark-mode');
            if (themeIcon) {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
                themeIcon.style.transform = 'rotate(0deg)';
            }
            localStorage.setItem('theme', 'light');
        }
    }

    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme) {
        setTheme(savedTheme);
    } else if (prefersDark) {
        setTheme('dark');
    } else {
        setTheme('light');
    }

    if (themeToggleButton) {
        themeToggleButton.addEventListener('click', () => {
            const newTheme = bodyElement.classList.contains('dark-mode') ? 'light' : 'dark';
            setTheme(newTheme);
            localStorage.setItem('theme-manual-override', 'true'); 
        });
    }

    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme-manual-override')) { 
                const newSystemTheme = e.matches ? 'dark' : 'light';
                setTheme(newSystemTheme);
            }
        });
    }
})();


// !!! SOLUSI UTAMA UNTUK PRELOADER YANG TIDAK HILANG !!!
window.onload = function() {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        setTimeout(function() {
            preloader.classList.add('loaded');
        }, 500); 
    }
};