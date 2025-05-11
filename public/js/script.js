// File: public/js/script.js

// Fungsi Global untuk Inisialisasi Tema (Dipanggil segera)
(function() {
    const bodyElement = document.body;
    const htmlElement = document.documentElement; // Target elemen <html> juga

    function applyInitialTheme(theme) {
        if (theme === 'dark') {
            bodyElement.classList.add('dark-mode');
            htmlElement.classList.add('dark-mode');
        } else {
            bodyElement.classList.remove('dark-mode');
            htmlElement.classList.remove('dark-mode');
        }
    }

    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const manualOverride = localStorage.getItem('theme-manual-override');

    if (manualOverride && savedTheme) { // Jika ada pilihan manual, gunakan itu
        applyInitialTheme(savedTheme);
    } else if (prefersDark) { // Jika tidak ada pilihan manual, coba preferensi sistem
        applyInitialTheme('dark');
    } else { // Default ke terang jika tidak ada keduanya
        applyInitialTheme('light');
    }
})();


document.addEventListener('DOMContentLoaded', function() {

    // 1. Smooth scrolling untuk link anchor
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            let targetId = this.getAttribute('href');
            if (targetId && targetId.length > 1) {
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    try {
                        targetElement.scrollIntoView({
                            behavior: 'smooth'
                        });
                    } catch (error) {
                        console.warn("Error scrolling to target:", targetId, error);
                    }
                } else {
                    // console.warn("Target element not found for scroll:", targetId);
                }
            }
        });
    });

    // 2. Tombol "Back to Top"
    let backToTopButton = document.querySelector('.back-to-top-btn');
    if (!backToTopButton) {
        backToTopButton = document.createElement('button');
        backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>'; 
        backToTopButton.className = 'back-to-top-btn'; 
        backToTopButton.setAttribute('title', 'Kembali ke atas');
        document.body.appendChild(backToTopButton);
    }
    
    if (backToTopButton) { // Pastikan tombol ada sebelum menambah event listener
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
    }


    // 3. Navbar menjadi solid saat di-scroll (untuk .navbar-public)
    const publicNavbar = document.querySelector('.navbar-public');
    if (publicNavbar) {
        const isTransparentOnTop = publicNavbar.classList.contains('navbar-transparent-on-top');
        const heroElementPublic = document.querySelector('.hero-video-background') || document.querySelector('.hero');
        const heroOffset = heroElementPublic ? heroElementPublic.offsetHeight / 2.5 : 50; 

        window.addEventListener('scroll', () => {
            if (isTransparentOnTop) {
                if (window.scrollY > heroOffset) {
                    publicNavbar.classList.remove('navbar-transparent-on-top');
                    publicNavbar.classList.add('navbar-scrolled-solid');
                } else {
                    publicNavbar.classList.add('navbar-transparent-on-top');
                    publicNavbar.classList.remove('navbar-scrolled-solid');
                }
            } else { 
                if (window.scrollY > 50) { 
                    publicNavbar.classList.add('scrolled'); 
                } else {
                    publicNavbar.classList.remove('scrolled');
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
                    const delay = entry.target.dataset.animationDelay || '0ms'; // Default delay 0ms jika ingin langsung
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

    // 5. Client-side form validation example (jika ada form kontak publik dengan ID ini)
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

    // 6. Bootstrap validation (untuk form yang menggunakan kelas .needs-validation)
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

    // --- Logika Tombol Pengalih Tema (Setelah DOM siap) ---
    const themeToggleButton = document.getElementById('theme-toggle-btn');
    const bodyElement = document.body;
    const htmlElement = document.documentElement;
    let themeIcon = null;

    if (themeToggleButton) {
        themeIcon = themeToggleButton.querySelector('i');
        // Set ikon awal berdasarkan tema yang sudah diterapkan saat load
        if (bodyElement.classList.contains('dark-mode')) {
            if(themeIcon) {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }
        } else {
             if(themeIcon) {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        }

        themeToggleButton.addEventListener('click', () => {
            let newTheme;
            if (bodyElement.classList.contains('dark-mode')) {
                newTheme = 'light';
                bodyElement.classList.remove('dark-mode');
                htmlElement.classList.remove('dark-mode');
                if (themeIcon) {
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                    themeIcon.style.transform = 'rotate(0deg)';
                }
            } else {
                newTheme = 'dark';
                bodyElement.classList.add('dark-mode');
                htmlElement.classList.add('dark-mode');
                if (themeIcon) {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                    themeIcon.style.transform = 'rotate(360deg)';
                }
            }
            localStorage.setItem('theme', newTheme);
            localStorage.setItem('theme-manual-override', 'true'); 
        });
    }

    // Dengar perubahan preferensi sistem (jika pengguna belum override manual)
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme-manual-override')) { 
                const newSystemTheme = e.matches ? 'dark' : 'light';
                // Panggil fungsi yang sama seperti di atas untuk menerapkan dan mengganti ikon
                if (newSystemTheme === 'dark') {
                    bodyElement.classList.add('dark-mode');
                    htmlElement.classList.add('dark-mode');
                    if (themeIcon) {
                        themeIcon.classList.remove('fa-moon');
                        themeIcon.classList.add('fa-sun');
                    }
                } else {
                    bodyElement.classList.remove('dark-mode');
                    htmlElement.classList.remove('dark-mode');
                     if (themeIcon) {
                        themeIcon.classList.remove('fa-sun');
                        themeIcon.classList.add('fa-moon');
                    }
                }
                localStorage.setItem('theme', newSystemTheme); // Simpan juga perubahan dari sistem
            }
        });
    }

}); // Akhir dari DOMContentLoaded


// --- PRELOADER LOGIC (GLOBAL) ---
window.onload = function() {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        setTimeout(function() {
            preloader.classList.add('loaded');
        }, 300); // Jeda bisa dikurangi jika aset sudah sangat optimal
    }
};