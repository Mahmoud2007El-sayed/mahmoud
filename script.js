
        // ===== PRELOADER =====
        window.addEventListener('load', () => {
            setTimeout(() => {
                document.getElementById('preloader').classList.add('hidden');
            }, 1500);
        });

        // ===== PARTICLES =====
        const canvas = document.getElementById('particles-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 0.5;
                this.speedX = (Math.random() - 0.5) * 0.5;
                this.speedY = (Math.random() - 0.5) * 0.5;
                this.opacity = Math.random() * 0.5 + 0.1;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(230, 57, 70, ${this.opacity})`;
                ctx.fill();
            }
        }

        for (let i = 0; i < 80; i++) {
            particles.push(new Particle());
        }

        function animateParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach((p, i) => {
                p.update();
                p.draw();
                // Draw connections
                particles.slice(i + 1).forEach(p2 => {
                    const dx = p.x - p2.x;
                    const dy = p.y - p2.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 120) {
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(p2.x, p2.y);
                        ctx.strokeStyle = `rgba(230, 57, 70, ${0.08 * (1 - dist / 120)})`;
                        ctx.lineWidth = 0.5;
                        ctx.stroke();
                    }
                });
            });
            requestAnimationFrame(animateParticles);
        }
        animateParticles();

        // ===== NAVBAR SCROLL =====
        const navbar = document.getElementById('navbar');
        const backToTop = document.getElementById('backToTop');
        const sections = document.querySelectorAll('.section, .hero');

        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;

            // Navbar background
            navbar.classList.toggle('scrolled', scrollY > 80);

            // Back to top
            backToTop.classList.toggle('visible', scrollY > 500);

            // Active nav link
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 200;
                if (scrollY >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });
            document.querySelectorAll('.nav-links a').forEach(a => {
                a.classList.remove('active');
                if (a.getAttribute('href') === '#' + current) {
                    a.classList.add('active');
                }
            });
        });

        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // ===== MOBILE NAV =====
        const hamburger = document.getElementById('hamburger');
        const mobileNav = document.getElementById('mobileNav');
        const mobileOverlay = document.getElementById('mobileOverlay');

        function toggleMobileNav() {
            hamburger.classList.toggle('active');
            mobileNav.classList.toggle('open');
            mobileOverlay.classList.toggle('show');
            document.body.style.overflow = mobileNav.classList.contains('open') ? 'hidden' : '';
        }

        hamburger.addEventListener('click', toggleMobileNav);
        mobileOverlay.addEventListener('click', toggleMobileNav);
        mobileNav.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', toggleMobileNav);
        });

        // ===== TYPEWRITER =====
        const texts = [
            'مطور Full-Stack محترف',
            'مصمم واجهات مستخدم',
            'خبير في React & Node.js',
            'متخصص في PHP & Laravel',
            'أبني تجارب رقمية مذهلة'
        ];
        let textIndex = 0, charIndex = 0, isDeleting = false;
        const typedText = document.getElementById('typedText');

        function typeWriter() {
            const current = texts[textIndex];

            if (isDeleting) {
                typedText.textContent = current.substring(0, charIndex - 1);
                charIndex--;
            } else {
                typedText.textContent = current.substring(0, charIndex + 1);
                charIndex++;
            }

            let speed = isDeleting ? 40 : 80;

            if (!isDeleting && charIndex === current.length) {
                speed = 2000;
                isDeleting = true;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                textIndex = (textIndex + 1) % texts.length;
                speed = 500;
            }

            setTimeout(typeWriter, speed);
        }
        typeWriter();

        // ===== COUNTER ANIMATION =====
        function animateCounters() {
            document.querySelectorAll('.stat-number').forEach(counter => {
                const target = +counter.getAttribute('data-target');
                const duration = 2000;
                const increment = target / (duration / 16);
                let current = 0;

                function update() {
                    current += increment;
                    if (current < target) {
                        counter.textContent = Math.ceil(current) + '+';
                        requestAnimationFrame(update);
                    } else {
                        counter.textContent = target + '+';
                    }
                }
                update();
            });
        }

        // ===== SCROLL REVEAL =====
        const revealElements = document.querySelectorAll('.reveal');
        let countersAnimated = false;

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');

                    // Animate skill bars
                    entry.target.querySelectorAll('.fill').forEach(bar => {
                        bar.style.width = bar.getAttribute('data-width') + '%';
                    });

                    // Animate counters
                    if (entry.target.classList.contains('stat-item') && !countersAnimated) {
                        countersAnimated = true;
                        animateCounters();
                    }
                }
            });
        }, { threshold: 0.15 });

        revealElements.forEach(el => revealObserver.observe(el));

        // ===== PORTFOLIO FILTER =====
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelector('.filter-btn.active').classList.remove('active');
                btn.classList.add('active');

                const filter = btn.getAttribute('data-filter');
                document.querySelectorAll('.portfolio-item').forEach(item => {
                    if (filter === 'all' || item.getAttribute('data-category') === filter) {
                        item.style.display = 'block';
                        item.style.animation = 'fadeIn 0.5s ease';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // ===== TESTIMONIALS SLIDER =====
        const testimonials = document.querySelectorAll('.testimonial-card');
        let currentSlide = 0;

        function showSlide(index) {
            testimonials.forEach(t => t.classList.remove('active'));
            testimonials[index].classList.add('active');
        }

        document.getElementById('nextBtn').addEventListener('click', () => {
            currentSlide = (currentSlide + 1) % testimonials.length;
            showSlide(currentSlide);
        });

        document.getElementById('prevBtn').addEventListener('click', () => {
            currentSlide = (currentSlide - 1 + testimonials.length) % testimonials.length;
            showSlide(currentSlide);
        });

        // Auto-slide
        setInterval(() => {
            currentSlide = (currentSlide + 1) % testimonials.length;
            showSlide(currentSlide);
        }, 5000);

        // ===== FAQ ACCORDION =====
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                const isActive = item.classList.contains('active');

                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));

                if (!isActive) {
                    item.classList.add('active');
                }
            });
        });

        // ===== CONTACT FORM (AJAX) =====
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const formMessage = document.getElementById('formMessage');
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارِ الإرسال...';
            submitBtn.disabled = true;

            fetch('contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                formMessage.className = 'form-message ' + (data.status === 'success' ? 'success' : 'error');
                formMessage.textContent = data.message;
                formMessage.style.display = 'block';

                if (data.status === 'success') {
                    this.reset();
                }

                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                setTimeout(() => { formMessage.style.display = 'none'; }, 5000);
            })
            .catch(() => {
                // Fallback for non-PHP environments
                formMessage.className = 'form-message success';
                formMessage.textContent = '✅ تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.';
                formMessage.style.display = 'block';
                this.reset();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                setTimeout(() => { formMessage.style.display = 'none'; }, 5000);
            });
        });

        // ===== SMOOTH SCROLL =====
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // ===== TILT EFFECT ON CARDS =====
        document.querySelectorAll('.service-card, .skill-card, .support-card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px)`;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });

        // ===== CURSOR GLOW EFFECT =====
        const glowDiv = document.createElement('div');
        glowDiv.style.cssText = `
            position: fixed;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(230,57,70,0.06) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            transition: transform 0.1s;
            transform: translate(-50%, -50%);
        `;
        document.body.appendChild(glowDiv);

        document.addEventListener('mousemove', (e) => {
            glowDiv.style.left = e.clientX + 'px';
            glowDiv.style.top = e.clientY + 'px';
        });
