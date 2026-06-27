    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Countdown Timer ke 27 Mei 2026
        function updateCountdown() {
            // Target: 27 Mei 2026, pukul 00:00:00
            const targetDate = new Date('May 27, 2026 00:00:00').getTime();
            const now = new Date().getTime();
            const diff = targetDate - now;
            
            const countdownEl = document.getElementById('qurbanCountdown');
            if (countdownEl) {
                if (diff > 0) {
                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (86400000)) / (3600000));
                    const minutes = Math.floor((diff % 3600000) / 60000);
                    const seconds = Math.floor((diff % 60000) / 1000);
                    
                    // Ubah warna berdasarkan kedekatan
                    if (days <= 7) {
                        countdownEl.style.color = '#f87171'; // merah kalau < 7 hari
                    } else if (days <= 30) {
                        countdownEl.style.color = '#fbbf24'; // kuning kalau < 30 hari
                    } else {
                        countdownEl.style.color = '#fcd34d'; // kuning terang
                    }
                    
                    countdownEl.innerHTML = `${days} hari ${hours} jam ${minutes} menit ${seconds} detik`;
                } else {
                    countdownEl.innerHTML = '🎉 Hari Raya Idul Adha Telah Tiba! 🎉';
                    countdownEl.style.color = '#34d399'; // hijau saat hari H
                }
            }
        }

        // Jalankan countdown setiap detik
        setInterval(updateCountdown, 1000);
        updateCountdown();
    </script>

    <script>
        $('#contactPesan').on('input', function() {
            const val = $(this).val().trim();
            if (val.length < 10 && val.length > 0) {
                $(this).addClass('border-yellow-500');
                $('#error-pesan').text('Pesan minimal 10 karakter').removeClass('hidden');
            } else {
                $(this).removeClass('border-yellow-500');
                $('#error-pesan').addClass('hidden');
            }
        });

        // Lazy loading images
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
        // Modern home scroll reveal + anchor smoothing
        document.addEventListener('DOMContentLoaded', () => {
            const shell = document.querySelector('.home-page-shell');
            if (!shell) return;

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const sections = Array.from(shell.querySelectorAll(':scope > section'));

            sections.forEach((section, index) => {
                section.classList.add('home-reveal');
                section.style.setProperty('--reveal-delay', `${Math.min(index * 55, 330)}ms`);

                const staggerItems = section.querySelectorAll('.service-card, article, [data-galeri-item], .banner-page .rounded-3xl, .prayer-time-card, .grid > .rounded-2xl, .grid > .rounded-3xl');
                staggerItems.forEach((item, itemIndex) => {
                    if (item.closest('dialog')) return;
                    item.classList.add('home-stagger-item');
                    item.style.setProperty('--item-delay', `${Math.min((itemIndex % 8) * 55, 385)}ms`);
                });
            });

            if (prefersReducedMotion) {
                sections.forEach(section => section.classList.add('is-visible'));
            } else {
                let revealTicking = false;

                function revealVisibleSections() {
                    revealTicking = false;
                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

                    sections.forEach((section) => {
                        if (section.classList.contains('is-visible')) return;

                        const rect = section.getBoundingClientRect();
                        const isInView = rect.top < viewportHeight * 0.88 && rect.bottom > viewportHeight * 0.08;

                        if (isInView) {
                            section.classList.add('is-visible');
                        }
                    });
                }

                function scheduleRevealCheck() {
                    if (revealTicking) return;
                    revealTicking = true;
                    requestAnimationFrame(revealVisibleSections);
                }

                const revealObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        entry.target.classList.add('is-visible');
                        revealObserver.unobserve(entry.target);
                    });
                }, {
                    threshold: 0.04,
                    rootMargin: '0px 0px -4% 0px',
                });

                sections.forEach(section => revealObserver.observe(section));
                requestAnimationFrame(() => {
                    sections.slice(0, 1).forEach(section => section.classList.add('is-visible'));
                    revealVisibleSections();
                });

                window.addEventListener('scroll', scheduleRevealCheck, { passive: true });
                window.addEventListener('resize', scheduleRevealCheck);
                window.addEventListener('hashchange', () => setTimeout(revealVisibleSections, 80));
            }

            shell.querySelectorAll('a[href^="#"]:not([href="#"])').forEach((anchor) => {
                anchor.addEventListener('click', (event) => {
                    const target = document.querySelector(anchor.getAttribute('href'));
                    if (!target) return;

                    event.preventDefault();
                    target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'start' });
                    history.pushState(null, '', anchor.getAttribute('href'));
                });
            });
        });

        // Back to Top
        const backToTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTop.classList.remove('opacity-0', 'invisible');
                backToTop.classList.add('opacity-100', 'visible');
            } else {
                backToTop.classList.add('opacity-0', 'invisible');
                backToTop.classList.remove('opacity-100', 'visible');
            }
        });
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('quote-container');
            if (!container) return;

            // Ambil semua quote aktif dari Blade - PASTIKAN INI DIATAS SEBELUM DIGUNAKAN
            const quotes = @json($quoteHarianList->map(function($q) {
                return ['title' => $q->title, 'text' => $q->text];
            })->toArray());

            // Jika tidak ada quote, gunakan default
            if (!quotes || quotes.length === 0) {
                quotes.push({
                    title: 'Pengingat Harian',
                    text: '"Sesungguhnya bersama kesulitan ada kemudahan." — QS. Al-Insyirah: 6'
                });
            }

            // Acak urutan quote setiap load halaman
            quotes.sort(() => Math.random() - 0.5);

            let currentIndex = 0;
            let timer = null;
            let isTransitioning = false;
            const dots = document.querySelectorAll('.dot');

            // Buat elemen quote baru
            function createQuoteElement(quote, index) {
                const div = document.createElement('div');
                div.className = 'quote-item absolute inset-0 opacity-0 translate-y-6 scale-95 transition-all duration-700 ease-in-out flex flex-col';
                div.dataset.index = index;
                div.innerHTML = `
                    <div class="quote-text mt-3 text-base sm:text-lg leading-relaxed flex-1 overflow-y-auto pr-2 scroll-smooth">
                        ${quote.text}
                    </div>
                `;
                return div;
            }

            // Update dots indicator
            function updateDots(index) {
                if (!dots || dots.length === 0) return;
                
                dots.forEach((dot, i) => {
                    if (i === index) {
                        dot.className = 'dot w-4 h-2 rounded-full bg-white transition-all duration-300';
                    } else {
                        dot.className = 'dot w-2 h-2 rounded-full bg-white/40 transition-all duration-300';
                    }
                });
                
                // Update counter
                const counter = document.getElementById('quote-counter');
                if (counter) {
                    counter.textContent = `${index + 1}/${quotes.length}`;
                }
            }

            // Ganti quote dengan animasi
            function rotateQuote() {
                if (isTransitioning) return;
                if (!quotes || quotes.length <= 1) return;
                
                isTransitioning = true;

                const oldQuote = container.querySelector('.quote-item.opacity-100');
                if (oldQuote) {
                    oldQuote.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
                    oldQuote.classList.add('opacity-0', '-translate-y-6', 'scale-95');
                }

                // Hitung index berikutnya
                const nextIndex = (currentIndex + 1) % quotes.length;
                currentIndex = nextIndex;

                // Buat quote baru
                const newQuote = createQuoteElement(quotes[currentIndex], currentIndex);
                container.appendChild(newQuote);

                // Update dots
                if (dots && dots.length > 0) {
                    updateDots(currentIndex);
                }

                // Trigger reflow untuk animasi
                void newQuote.offsetHeight;

                // Tampilkan quote baru dengan animasi
                setTimeout(() => {
                    newQuote.classList.remove('opacity-0', 'translate-y-6', 'scale-95');
                    newQuote.classList.add('opacity-100', 'translate-y-0', 'scale-100');

                    // Hapus quote lama setelah transisi selesai
                    setTimeout(() => {
                        if (oldQuote && oldQuote !== newQuote) {
                            oldQuote.remove();
                        }
                        isTransitioning = false;
                    }, 700);
                }, 50);
            }

            // Auto-scroll untuk teks panjang
            function autoScrollText() {
                const currentQuote = container.querySelector('.quote-item.opacity-100');
                if (!currentQuote) return;

                const textContainer = currentQuote.querySelector('.quote-text');
                if (!textContainer) return;

                // Reset scroll
                textContainer.scrollTop = 0;

                // Cek apakah teks overflow
                if (textContainer.scrollHeight > textContainer.clientHeight) {
                    let scrollPosition = 0;
                    const scrollStep = 0.8;
                    const scrollInterval = 30;
                    const pauseDuration = 3000;

                    let isScrolling = false;
                    let scrollTimer = null;

                    function startScroll() {
                        if (isScrolling) return;
                        isScrolling = true;

                        const maxScroll = textContainer.scrollHeight - textContainer.clientHeight;

                        scrollTimer = setInterval(() => {
                            scrollPosition += scrollStep;
                            textContainer.scrollTop = scrollPosition;

                            if (scrollPosition >= maxScroll) {
                                clearInterval(scrollTimer);
                                scrollTimer = null;
                                isScrolling = false;

                                // Pause sebelum ke quote berikutnya
                                setTimeout(() => {
                                    if (quotes.length > 1) {
                                        rotateQuote();
                                    }
                                }, pauseDuration);
                            }
                        }, scrollInterval);
                    }

                    // Mulai scroll saat quote aktif
                    startScroll();

                    // Pause scroll saat hover
                    const containerElement = document.getElementById('quote-container');
                    containerElement.addEventListener('mouseenter', function() {
                        if (scrollTimer) {
                            clearInterval(scrollTimer);
                            scrollTimer = null;
                            isScrolling = false;
                        }
                    });

                    containerElement.addEventListener('mouseleave', function() {
                        if (!scrollTimer && textContainer.scrollTop < textContainer.scrollHeight - textContainer.clientHeight) {
                            startScroll();
                        }
                    });

                    // Reset scroll saat quote berubah
                    const observer = new MutationObserver(function() {
                        if (scrollTimer) {
                            clearInterval(scrollTimer);
                            scrollTimer = null;
                            isScrolling = false;
                        }
                        scrollPosition = 0;
                        textContainer.scrollTop = 0;
                    });

                    observer.observe(container, {
                        childList: true,
                        subtree: false
                    });
                }
            }

            // Mulai rotasi quote
            function startRotation() {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }

                // Jika hanya 1 quote, tidak perlu rotasi
                if (quotes.length <= 1) {
                    // Tapi tetap jalankan auto-scroll
                    setTimeout(autoScrollText, 1000);
                    return;
                }

                // Mulai auto-scroll untuk quote pertama
                setTimeout(autoScrollText, 1000);

                // Rotasi quote setiap 10 detik
                timer = setInterval(function() {
                    // Hentikan scroll saat transisi
                    const currentQuote = container.querySelector('.quote-item.opacity-100');
                    if (currentQuote) {
                        const textContainer = currentQuote.querySelector('.quote-text');
                        if (textContainer) {
                            textContainer.scrollTop = 0;
                        }
                    }
                    rotateQuote();
                    // Mulai auto-scroll untuk quote baru
                    setTimeout(autoScrollText, 800);
                }, 10000);
            }

            // Pause saat hover
            container.addEventListener('mouseenter', function() {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            });

            container.addEventListener('mouseleave', function() {
                if (!timer && quotes.length > 1) {
                    startRotation();
                }
            });

            // Inisialisasi dots
            if (dots && dots.length > 0) {
                updateDots(0);
            }

            // Mulai rotasi setelah delay
            setTimeout(function() {
                if (quotes.length > 1) {
                    startRotation();
                } else {
                    // Jika hanya 1 quote, tetap jalankan auto-scroll
                    setTimeout(autoScrollText, 1000);
                }
            }, 3000);
        });

        document.addEventListener('DOMContentLoaded', function () {
            const carousel = document.getElementById('motivasiCarousel');
            if (!carousel) return;

            const track = carousel.querySelector('.motivasi-track');
            const dots = carousel.querySelectorAll('.motivasi-dot');
            let current = 0;
            const total = dots.length;
            let timer = null;

            function setDot(i) {
                dots.forEach((d, idx) => {
                    d.classList.toggle('bg-emerald-600', idx === i);
                    d.classList.toggle('bg-emerald-300', idx !== i);
                    d.classList.toggle('w-4', idx === i);
                    d.classList.toggle('h-4', idx === i);
                });
            }

            function go(index) {
                if (index < 0) index = total - 1;
                if (index >= total) index = 0;
                track.style.transform = `translateX(-${index * 100}%)`;
                setDot(index);
                current = index;

                // Tambah class active ke slide yang sedang dilihat (untuk overlay)
                document.querySelectorAll('.motivasi-track > div').forEach((slide, i) => {
                    slide.classList.toggle('active', i === index);
                });
            }

            function startAuto() {
                stopAuto();
                timer = setInterval(() => go(current + 1), 6000); // 6 detik per slide
            }

            function stopAuto() {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            }

            dots.forEach(dot => {
                dot.addEventListener('click', () => {
                    go(parseInt(dot.dataset.index));
                    startAuto();
                });
            });

            carousel.addEventListener('mouseenter', stopAuto);
            carousel.addEventListener('mouseleave', startAuto);

            // Mulai
            go(0);
            startAuto();
        });

        function copyToClipboard(text) {
            const el = document.getElementById('rekeningNum');
            if (!el) return;

            // Simpan original untuk feedback
            const originalColor = el.style.color || '';

            // Fungsi feedback sukses
            function showSuccess() {
                el.style.color = '#10b981';
                el.classList.add('font-medium');
                setTimeout(() => {
                    el.style.color = originalColor;
                    el.classList.remove('font-medium');
                }, 2500);
            }

            // Coba modern Clipboard API dulu
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(showSuccess)
                    .catch(err => {
                        console.warn('Clipboard API gagal:', err);
                        fallbackCopy();
                    });
            } else {
                // Langsung fallback jika API tidak ada
                fallbackCopy();
            }

            function fallbackCopy() {
                // Buat textarea sementara
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'absolute';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);

                textarea.select();
                textarea.setSelectionRange(0, 99999); // Untuk mobile

                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        showSuccess();
                    } else {
                        alert('Gagal menyalin otomatis. Tekan lama nomor rekening lalu pilih "Salin".');
                    }
                } catch (err) {
                    console.error('Fallback copy gagal:', err);
                    alert('Gagal menyalin. Tekan lama nomor rekening lalu pilih "Salin".');
                }

                document.body.removeChild(textarea);
            }
        }

        function openPengumumanPreview(btn) {
            const title = btn.getAttribute('data-pengumuman-judul') || '';
            const isi   = btn.getAttribute('data-pengumuman-isi') || '';
            const url   = btn.getAttribute('data-pengumuman-url') || '#';

            const modal = document.getElementById('pengumumanModal');
            const elTitle = document.getElementById('pengumumanModalTitle');
            const elBody  = document.getElementById('pengumumanModalBody');
            const elDate  = document.getElementById('pengumumanModalDate');
            const elDetail= document.getElementById('pengumumanModalDetail');

            elTitle.textContent = title;
            // jika isi mengandung HTML yang aman, gunakan innerHTML setelah sanitasi.
            // di sini kita menampilkan text saja supaya aman:
            elBody.textContent = isi;

            // tanggal: jika tersedia di tombol, gunakan; kalau tidak biarkan kosong
            const tanggalAttr = btn.getAttribute('data-pengumuman-tanggal');
            if (tanggalAttr) elDate.textContent = tanggalAttr;
            else {
                const parent = btn.closest('article');
                const dateEl = parent ? parent.querySelector('.text-amber-700, .text-amber-700') : null;
                elDate.textContent = dateEl ? dateEl.textContent.trim() : '';
            }

            elDetail.href = url;

            // focus & open
            try {
                if (typeof modal.showModal === 'function') {
                    modal.showModal();
                } else {
                    // polyfill fallback: add class to show
                    modal.classList.add('modal-open');
                    modal.style.display = 'block';
                }
                // move focus to title for accessibility
                elTitle.focus && elTitle.focus();
            } catch (e) {
                // fallback
                modal.classList.add('modal-open');
            }
        }

        function closePengumumanPreview() {
            const modal = document.getElementById('pengumumanModal');
            try {
                if (typeof modal.close === 'function') modal.close();
                else {
                    modal.classList.remove('modal-open');
                    modal.style.display = 'none';
                }
            } catch (e) {
                modal.classList.remove('modal-open');
                modal.style.display = 'none';
            }
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('pengumumanModal');
                if (!modal) return;
                // only close if modal is open
                if (modal.open || modal.classList.contains('modal-open')) closePengumumanPreview();
            }
        });

        (function () {
            let loaderDismissed = false;

            function dismissPageLoader() {
                if (loaderDismissed) return;
                loaderDismissed = true;

                const loader = document.getElementById('page-loader');
                const hash = window.location.hash;

                if (!loader) return;

                loader.classList.add('opacity-0', 'pointer-events-none');

                setTimeout(() => {
                    loader.remove();

                    if (hash) {
                        const target = document.querySelector(hash);
                        if (target) {
                            setTimeout(() => {
                                target.scrollIntoView({
                                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                                    block: 'start'
                                });
                            }, 150);
                        }
                    }
                }, 500);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => setTimeout(dismissPageLoader, 450), { once: true });
            } else {
                setTimeout(dismissPageLoader, 450);
            }

            window.addEventListener('load', dismissPageLoader, { once: true });
            setTimeout(dismissPageLoader, 2800);
        })();
        document.addEventListener('DOMContentLoaded',function(){
            const carousel=document.getElementById('bannerCarousel');
            if(!carousel)return;

            const track=carousel.querySelector('.banner-track');
            const pages=track.querySelectorAll('.banner-page');
            const dots=carousel.querySelectorAll('.banner-dot');

            let current=0,total=pages.length,timer=null;

            function setDot(i){
                dots.forEach((d,idx)=>{
                    d.classList.toggle('bg-emerald-500',idx===i);
                    d.classList.toggle('bg-emerald-200',idx!==i);
                    d.classList.toggle('w-4',idx===i);
                });
            }
            function go(i){
                if(i<0)i=total-1;
                if(i>=total)i=0;
                track.style.transform=`translateX(-${i*100}%)`;
                setDot(i);
                current=i;
            }
            function start(){
                stop();
                timer=setInterval(()=>go(current+1),6000);
            }
            function stop(){ if(timer){clearInterval(timer);timer=null;}}

            dots.forEach(d=>d.onclick=()=>{go(+d.dataset.index);start();});
            carousel.addEventListener('mouseenter',stop);
            carousel.addEventListener('mouseleave',start);

            go(0);start();


            // Galeri Modal
            const items=document.querySelectorAll('[data-galeri-item]');
            const modal=document.getElementById('galeriModal');
            const modalImg=document.getElementById('galeriModalImg');
            const modalTitle=document.getElementById('galeriModalTitle');

            items.forEach(btn=>{
                btn.onclick=()=>{
                    modalImg.src=btn.dataset.img;
                    modalTitle.textContent=btn.dataset.title;
                    modal.showModal();
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('galeriModal');
            const modalImg = document.getElementById('galeriModalImg');
            const modalTitle = document.getElementById('galeriModalTitle');
            const thumbsBox = document.getElementById('galeriThumbs'); // pastikan id ini ada di blade
            const prevBtn = document.getElementById('galeriPrev');
            const nextBtn = document.getElementById('galeriNext');
            const counter = document.getElementById('galeriCounter');
            const closeBtn = document.getElementById('closeGaleriModalBtn');

            let fotos = []; // array of {url, caption}
            let index = 0;

            function openModal() {
                try { if (typeof modal.showModal === 'function') modal.showModal(); else modal.classList.add('modal-open'); }
                catch (e) { modal.classList.add('modal-open'); }
            }
            function closeModal() {
                try { if (typeof modal.close === 'function') modal.close(); else modal.classList.remove('modal-open'); }
                catch (e) { modal.classList.remove('modal-open'); }
            }

            function isModalOpen() {
                if (!modal) return false;
                if (typeof modal.open !== 'undefined') return !!modal.open;
                return modal.classList.contains('modal-open');
            }

            // safe event bindings
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (modal) {
                modal.addEventListener('click', (ev) => {
                    // close when clicking backdrop (modal element itself)
                    if (ev.target === modal) closeModal();
                });
            }

            function updateCounter() {
                if (!counter) return;
                counter.textContent = fotos.length ? `${index + 1} / ${fotos.length}` : '';
            }

            function setImage(i) {
                if (!modalImg) return;
                if (!fotos.length) {
                    modalImg.src = '';
                    modalImg.alt = '';
                    updateCounter();
                    // remove highlight if thumbsBox exists
                    if (thumbsBox) Array.from(thumbsBox.children).forEach(ch => { ch.classList.remove('ring'); ch.classList.remove('ring-emerald-400'); });
                    return;
                }

                index = (Number(i) + fotos.length) % fotos.length;
                modalImg.src = fotos[index].url;
                modalImg.alt = fotos[index].caption || fotos[index].file_name || '';
                updateCounter();

                // highlight thumb (use add/remove to avoid multi-token errors)
                if (thumbsBox) {
                    Array.from(thumbsBox.children).forEach((child, idx) => {
                        if (idx === index) {
                            child.classList.add('ring');
                            child.classList.add('ring-emerald-400');
                        } else {
                            child.classList.remove('ring');
                            child.classList.remove('ring-emerald-400');
                        }
                    });
                    // ensure the active thumb is visible (scroll into view if overflow)
                    const active = thumbsBox.children[index];
                    if (active && typeof active.scrollIntoView === 'function') {
                        active.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                    }
                }
            }

            function renderThumbs() {
                if (!thumbsBox) return;
                thumbsBox.innerHTML = '';

                fotos.forEach((f, i) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'inline-block border rounded overflow-hidden';
                    btn.style.width = '90px';
                    btn.style.height = '64px';
                    btn.style.flex = '0 0 auto';
                    btn.style.padding = '0';
                    btn.style.margin = '0 6px 6px 0';
                    btn.setAttribute('aria-label', f.caption || `Foto ${i+1}`);
                    btn.innerHTML = `<img src="${f.url}" loading="lazy" class="w-full h-full object-cover" alt="${(f.caption||f.file_name||'foto')}">`;
                    btn.addEventListener('click', () => setImage(i));
                    thumbsBox.appendChild(btn);
                });
            }

            if (prevBtn) prevBtn.addEventListener('click', () => setImage(index - 1));
            if (nextBtn) nextBtn.addEventListener('click', () => setImage(index + 1));

            document.addEventListener('keydown', (e) => {
                if (!isModalOpen()) return;
                if (e.key === 'ArrowLeft') setImage(index - 1);
                if (e.key === 'ArrowRight') setImage(index + 1);
                if (e.key === 'Escape') closeModal();
            });

            // Delegate click on gallery items (buttons with data-galeri-item and data-id)
            document.body.addEventListener('click', (ev) => {
                const btn = ev.target.closest('[data-galeri-item]');
                if (!btn) return;

                const id = btn.dataset.id || null;
                const fallbackImg = btn.dataset.img || null;
                const title = btn.dataset.title || '';

                if (modalTitle) modalTitle.textContent = title || '';

                if (!id) {
                    // if no id, show single fallback image
                    fotos = fallbackImg ? [{ url: fallbackImg, caption: title }] : [];
                    renderThumbs();
                    setImage(0);
                    openModal();
                    return;
                }

                // Fetch full foto list
                $.ajax({
                    url: `/home/galeri/${encodeURIComponent(id)}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        try {
                            fotos = Array.isArray(data.fotos)
                                ? data.fotos.map(function(f) {
                                    return {
                                        url: f.url || (f.file_name ? `/storage/galeri/${f.file_name}` : ''),
                                        caption: f.caption || f.file_name || ''
                                    };
                                })
                                : [];

                            // fallback to single image if empty
                            if (!fotos.length && fallbackImg) {
                                fotos = [{ url: fallbackImg, caption: title }];
                            }

                            renderThumbs();
                            setImage(0);
                            openModal();
                        } catch (e) {
                            console.error('Galeri parse error', e);
                            // fallback
                            if (fallbackImg) {
                                fotos = [{ url: fallbackImg, caption: title }];
                                renderThumbs();
                                setImage(0);
                                openModal();
                            } else if (window.Swal) {
                                Swal.fire('Error', 'Gagal memproses data galeri.', 'error');
                            }
                        }
                    },
                    error: function(xhr, status, err) {
                        console.error('Galeri fetch error', status, err);
                        if (fallbackImg) {
                            fotos = [{ url: fallbackImg, caption: title }];
                            renderThumbs();
                            setImage(0);
                            openModal();
                        } else if (window.Swal) {
                            Swal.fire('Error', 'Gagal memuat foto galeri dari server.', 'error');
                        }
                    }
                });
            });
        });

        // pastikan functions setImage(index) dan index var ada di scope
        const prevBtn = document.getElementById('galeriPrev');
        const nextBtn = document.getElementById('galeriNext');
        const prevPill = document.getElementById('galeriPrevPill');
        const nextPill = document.getElementById('galeriNextPill');

        // fallback: jika fungsi setImage belum ada, gunakan dispatch click pada element lain
        function safePrev() {
            if (typeof setImage === 'function') setImage(index - 1);
            else document.dispatchEvent(new CustomEvent('galeriPrev'));
        }
        function safeNext() {
            if (typeof setImage === 'function') setImage(index + 1);
            else document.dispatchEvent(new CustomEvent('galeriNext'));
        }

        if (prevBtn) prevBtn.addEventListener('click', safePrev);
        if (nextBtn) nextBtn.addEventListener('click', safeNext);
        if (prevPill) prevPill.addEventListener('click', safePrev);
        if (nextPill) nextPill.addEventListener('click', safeNext);

        // keyboard hint: left/right also control
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') safePrev();
            if (e.key === 'ArrowRight') safeNext();
        });

        const $form   = $('#contactForm');
        const $btn    = $('#contactSubmitBtn');
        const $status = $('#contactStatus');

        // pastikan meta csrf ada
        const csrf = $('meta[name="csrf-token"]').attr('content');

        $form.on('submit', function(e) {
            e.preventDefault();

            // Reset UI sebelum kirim
            $('.error, .invalid-feedback').remove();
            $('input, textarea').removeClass('border-red-500');
            $status.html('').removeClass('text-green-600 text-red-600');
            $btn.prop('disabled', true).text('Mengirim...');

            // Generate reCAPTCHA v3 token (invisible)
            grecaptcha.ready(function() {
                grecaptcha.execute('{{ env('RECAPTCHA_SITE_KEY') }}', {action: 'submit_saran'})
                    .then(function(token) {
                        // Masukkan token ke hidden input
                        $('#recaptchaToken').val(token);

                        // Siapkan data form
                        const formData = $form.serialize();

                        $.ajax({
                            url: '{{ route("kontak.kirim") }}',
                            type: 'POST',
                            data: formData,
                            success: function(res) {
                                if (res.success) {
                                    $status.html('<span class="text-green-600 font-medium">' + (res.message || 'Pesan berhasil dikirim! Terima kasih.') + '</span>');
                                    $form[0].reset(); // reset hanya saat sukses
                                } else {
                                    $status.html('<span class="text-red-600">' + (res.message || 'Gagal mengirim.') + '</span>');
                                }
                            },
                            error: function(xhr) {
                                let errorMsg = 'Terjadi kesalahan. Coba lagi nanti.';
                                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                                    const errors = xhr.responseJSON.errors;
                                    let firstError = '';

                                    $.each(errors, function(field, messages) {
                                        const msg = messages[0];
                                        firstError = firstError || msg;

                                        // Tampil error di bawah field
                                        const $input = $form.find('[name="' + field + '"]');
                                        if ($input.length) {
                                            $input.addClass('border-red-500');
                                            $input.after('<div class="error text-red-600 text-xs mt-1">' + msg + '</div>');
                                        }
                                    });

                                    if (firstError) {
                                        $status.html('<span class="text-red-600">' + firstError + '</span>');
                                    }
                                } else {
                                    // Error lain (500, network, dll)
                                    try {
                                        const json = xhr.responseJSON || JSON.parse(xhr.responseText);
                                        errorMsg = json.message || errorMsg;
                                    } catch (e) {
                                        // ignore parse error
                                    }
                                    $status.html('<span class="text-red-600">' + errorMsg + '</span>');
                                }
                            },
                            complete: function() {
                                $btn.prop('disabled', false).text('Kirim Pesan');
                            }
                        });
                    })
                    .catch(function(error) {
                        $status.html('<span class="text-red-600">Gagal verifikasi reCAPTCHA. Coba lagi atau refresh halaman.</span>');
                        $btn.prop('disabled', false).text('Kirim Pesan');
                    });
            });
        });


        /* ===================== DETEKSI LOKASI USER ===================== */
        (async function () {

            const today = new Date().toISOString().slice(0,10);
            const lastCheck = localStorage.getItem('masjid_location_date');

            // sudah pernah cek hari ini → tidak minta GPS lagi
            if (lastCheck === today) return;

            // browser tidak support
            if (!navigator.geolocation) return;

            // jangan ganggu user saat loading awal
            setTimeout(() => {

                navigator.geolocation.getCurrentPosition(async (pos) => {

                    try {
                        const res = await fetch("{{ route('set.location') }}", {
                            method: "POST",
                                credentials: "same-origin", // 🔥 INI YANG HILANG
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                },
                                body: JSON.stringify({
                                lat: pos.coords.latitude,
                                lng: pos.coords.longitude
                                })
                            });


                        const data = await res.json();

                        if (data.success) {
                            localStorage.setItem('masjid_location_date', today);

                            console.log("Lokasi terdeteksi:", data.city);

                            // reload sekali agar jadwal ikut kota user
                            setTimeout(()=>location.reload(), 600);
                        }

                    } catch (e) {
                        console.log("Gagal kirim lokasi");
                    }

                }, () => {
                    console.log("User menolak izin lokasi");
                });

            }, 2500); // tunggu halaman selesai render dulu

        })();
    </script>
