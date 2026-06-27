    <style>
        :root {
            --primary: #059669;           /* emerald-600 - warna utama */
            --primary-dark: #047857;
            --primary-light: #34d399;
            --teal: #0d9488;
            --teal-dark: #0c7a6e;
            --cyan: #0891b2;
            --cyan-dark: #077c9c;
            --soft-bg: #f0fdfa;
            --card-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(5, 150, 105, 0.18);
            --shadow: 0 12px 36px -12px rgba(5, 150, 105, 0.15);
            --shadow-hover: 0 24px 48px -12px rgba(5, 150, 105, 0.3);
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            --gold: #f59e0b;
            --ink: #0f172a;
            --muted: #64748b;
            --surface: rgba(255, 255, 255, 0.78);
            --surface-strong: rgba(255, 255, 255, 0.94);
        }

        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-12px) scale(1.03); }
        }
        @keyframes float-reverse {
            0%, 100% { transform: translateY(0px) scale(1.03); }
            50% { transform: translateY(12px) scale(1); }
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.25; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.15); }
        }
        @keyframes border-glow {
            0%, 100% { border-color: rgba(5, 150, 105, 0.25); }
            50% { border-color: rgba(16, 185, 129, 0.6); }
        }

        .animate-float-slow {
            animation: float-slow 8s ease-in-out infinite;
        }
        .animate-float-reverse {
            animation: float-reverse 9s ease-in-out infinite;
        }
        .animate-pulse-slow {
            animation: pulse-slow 10s ease-in-out infinite;
        }
        .animate-border-glow {
            animation: border-glow 3s infinite;
        }

        .bg-pattern-islamic {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cpath d='M40 0 L80 40 L40 80 L0 40 Z' fill='none' stroke='%23059669' stroke-width='0.5' stroke-opacity='0.035'/%3E%3Cpath d='M40 15 L65 40 L40 65 L15 40 Z' fill='none' stroke='%230d9488' stroke-width='0.5' stroke-opacity='0.02'/%3E%3Ccircle cx='40' cy='40' r='6' fill='none' stroke='%2310b981' stroke-width='0.5' stroke-opacity='0.04'/%3E%3Cpath d='M0 0 L80 80 M80 0 L0 80' fill='none' stroke='%23059669' stroke-width='0.25' stroke-opacity='0.01'/%3E%3C/svg%3E");
        }

        body {
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 50%, #e0f2fe 100%);
            color: #0f172a;
            font-feature-settings: "cv03", "cv04", "ss01";
        }


        .home-page-shell {
            position: relative;
            isolation: isolate;
            background:
                radial-gradient(circle at 10% 8%, rgba(16, 185, 129, 0.22), transparent 32rem),
                radial-gradient(circle at 88% 16%, rgba(14, 165, 233, 0.18), transparent 34rem),
                radial-gradient(circle at 50% 72%, rgba(245, 158, 11, 0.10), transparent 30rem),
                linear-gradient(135deg, #ecfdf5 0%, #f8fafc 44%, #e0f2fe 100%);
        }

        .home-page-shell::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -2;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(5, 150, 105, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(5, 150, 105, 0.035) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: linear-gradient(to bottom, rgba(0,0,0,0.82), rgba(0,0,0,0.15));
        }

        .home-page-shell::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background: linear-gradient(180deg, rgba(255,255,255,0.16), rgba(255,255,255,0.72) 42%, rgba(236,253,245,0.82));
        }

        .home-page-shell > section {
            position: relative;
            scroll-margin-top: 96px;
        }

        .home-page-shell .container {
            position: relative;
            z-index: 1;
        }

        .home-hero {
            min-height: min(860px, calc(100vh - 5rem));
            display: flex;
            align-items: center;
        }

        .home-hero h1 {
            letter-spacing: 0;
            text-wrap: balance;
        }

        .home-hero p {
            text-wrap: pretty;
        }

        .hero-eyebrow {
            box-shadow: 0 18px 44px -24px rgba(5, 150, 105, 0.75);
        }

        .hero-primary-cta,
        .hero-secondary-cta {
            min-height: 3.5rem;
        }
        .container {
            max-width: 1400px;
        }

        h1, h2, h3 {
            font-feature-settings: "cv01", "ss01";
            color: #065f46; /* emerald-900 lebih hidup */
        }

        .banner-track {
            scroll-snap-type: x mandatory;
        }
        .banner-page {
            scroll-snap-align: start;
        }
        /* ==================== JADWAL SHOLAT - Scoped Card ==================== */
        .prayer-panel {
            background:
                linear-gradient(145deg, rgba(255,255,255,0.96), rgba(236,253,245,0.88)),
                radial-gradient(circle at 18% 0%, rgba(16,185,129,0.22), transparent 16rem);
            backdrop-filter: blur(22px);
            border: 1px solid rgba(255,255,255,0.72);
            border-radius: 1.75rem;
            box-shadow: 0 28px 80px -34px rgba(15, 118, 110, 0.62);
            transition: var(--transition);
        }

        .prayer-panel:hover {
            transform: translateY(-4px);
            box-shadow: 0 34px 90px -36px rgba(15, 118, 110, 0.72);
        }

        .prayer-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .prayer-time-card {
            border: 1px solid rgba(5,150,105,0.16);
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(240,253,250,0.86));
            box-shadow: 0 14px 34px -24px rgba(15, 118, 110, 0.65);
        }

        .prayer-time-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(16,185,129,0.52);
            box-shadow: 0 24px 48px -24px rgba(15, 118, 110, 0.72);
        }

        .prayer-time-card .text-lg,
        .prayer-time-card .text-xl {
            letter-spacing: 0;
        }

        @media (min-width: 768px) {
            .prayer-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 1.15rem;
            }
        }

        @media (max-width: 767px) {
            .prayer-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 480px) {
            .prayer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        /* ==================== STYLE UMUM LAINNYA ==================== */
        .home-page-shell .btn {
            border-radius: 9999px;
            font-weight: 700;
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s ease, filter 0.35s ease;
        }

        .home-page-shell .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 42px -22px rgba(5,150,105,0.72);
            filter: saturate(1.06);
        }

        .home-page-shell :is(.rounded-2xl, .rounded-3xl) {
            transition: transform 0.38s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.38s ease, border-color 0.38s ease, background-color 0.38s ease;
        }

        .home-page-shell :is(.rounded-2xl, .rounded-3xl):hover {
            box-shadow: var(--shadow-hover);
        }

        .home-page-shell :is(section[id], section.py-16, section.py-12, section.py-10) > .container {
            max-width: 1320px;
        }

        .home-page-shell :is(.service-card, [data-galeri-item], article, .banner-page .relative.rounded-3xl) {
            will-change: transform;
        }

        #backToTop {
            backdrop-filter: blur(12px);
            box-shadow: 0 18px 40px -18px rgba(4, 120, 87, 0.72);
        }

        /* Loader */
        #page-loader {
            background: linear-gradient(to bottom, #f0f9ff, #ecfeff, #f0fdfa);
        }

        /* Scrollbar custom */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(5,150,105,0.05);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(5,150,105,0.4);
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(5,150,105,0.6);
        }

        /* Agenda - Compact & Lebih Berwarna */
        #acara .bg-white.rounded-xl {
            background: linear-gradient(to bottom, white, #ecfdf5);
            border: 1px solid rgba(5,150,105,0.25);
        }

        #acara .bg-white.rounded-xl:hover {
            box-shadow: var(--shadow-hover);
            border-color: #10b981;
        }

        /* Quote text – area yang bisa discroll jika terlalu panjang */
        .quote-text {
            max-height: 11rem;           /* ~10-12 baris di mobile – sesuaikan selera */
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;   /* smooth scroll di iOS */
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.5) transparent;
            padding-right: 0.25rem;      /* ruang untuk scrollbar */
        }

        /* Hilangkan scrollbar default di WebKit (Chrome, Safari) agar lebih clean */
        .quote-text::-webkit-scrollbar {
            width: 5px;
        }
        .quote-text::-webkit-scrollbar-track {
            background: transparent;
        }
        .quote-text::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.5);
            border-radius: 10px;
        }
        .quote-text::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.8);
        }

        /* Di desktop – biarkan teks mengembang tanpa batas tinggi */
        @media (min-width: 640px) {
            .quote-text {
                max-height: none;
                overflow-y: visible;
            }
        }

        /* Animasi pergantian lebih mulus – container ikut menyesuaikan tinggi */
        #quote-container {
            transition: min-height 0.6s ease-out;
        }
        #quote-container p {
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 640px) {
            #quote-container {
                min-height: 160px !important;
            }
            #quote-container p {
                max-height: 9rem;
                overflow-y: auto;
            }
        }

        .service-card {
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          }
          .service-card:hover {
            transform: translateY(-12px) scale(1.02);
          }
          .service-icon-bg {
            transition: all 0.5s ease;
          }
          .service-card:hover .service-icon-bg {
            background: linear-gradient(135deg, #059669, #0d9488);
            color: white;
          }

        #rekeningNum {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #10b981 transparent;
        }

        #rekeningNum::-webkit-scrollbar {
            height: 4px;
        }

        #rekeningNum::-webkit-scrollbar-thumb {
            background: #10b981;
            border-radius: 10px;
        }

        /* Tampilkan indikator scroll hanya jika benar-benar overflow */
        @media (max-width: 400px) {
            #rekeningNum.overflow-scroll {
                position: relative;
            }
            #rekeningNum.overflow-scroll::after {
                content: "→ geser";
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 10px;
                color: rgba(255,255,255,0.7);
                pointer-events: none;
            }
        }
        /* Kontak - Form Lebih Berwarna */
        #layanan_jamaah input,
        #layanan_jamaah textarea {
            border-color: #cbd5e1;
            color: #0f172a;
            background: white;
        }

        #layanan_jamaah input:focus,
        #layanan_jamaah textarea:focus {
            border-color: #059669;
            box-shadow: 0 0 0 4px rgba(5,150,105,0.18);
        }

        #layanan_jamaah form {
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .infaq-track > div {
            padding: 0 1rem; /* jarak antar slide lebih jelas */
        }

        .infaq-track {
            scroll-snap-type: x mandatory;
        }

        .infaq-dot.w-4, .infaq-dot[data-active="true"] {
            width: 1.25rem;
            height: 1.25rem;
            background-color: #10b981 !important;
        }

        @media (max-width: 640px) {
            #infaqCarousel .min-h-[420px] {
                min-height: 420px !important;
            }
            #infaqCarousel p {
                font-size: 1.125rem; /* text-lg lebih kecil di mobile */
            }
            #infaqCarousel h3 {
                font-size: 1.5rem; /* text-2xl lebih kecil */
            }
        }

        /* Galeri - Hover Lebih Hidup */
        [data-galeri-item] img {
            transition: transform 0.5s ease;
        }

        [data-galeri-item]:hover img {
            transform: scale(1.15);
        }

        /* ==================== SCROLL EXPERIENCE ==================== */
        @media (prefers-reduced-motion: no-preference) {
            html {
                scroll-behavior: smooth;
            }
        }

        .home-reveal {
            opacity: 0;
            transform: translate3d(0, 38px, 0) scale(0.985);
            transition:
                opacity 0.85s ease,
                transform 0.9s cubic-bezier(0.16, 1, 0.3, 1);
            transition-delay: var(--reveal-delay, 0ms);
            will-change: opacity, transform;
        }

        .home-reveal.is-visible {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1);
        }

        .home-stagger-item {
            opacity: 0;
            transform: translateY(18px) scale(0.985);
            transition:
                opacity 0.65s ease,
                transform 0.72s cubic-bezier(0.16, 1, 0.3, 1);
            transition-delay: var(--item-delay, 0ms);
        }

        .home-reveal.is-visible .home-stagger-item {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .home-page-shell a[href^="#"] {
            scroll-margin-top: 96px;
        }

        .home-page-shell img {
            content-visibility: auto;
        }


        @media (max-width: 640px) {
            main > .fixed.bottom-6.right-6.z-50 {
                right: 1rem !important;
                bottom: 1rem !important;
                gap: 0.75rem !important;
            }

            main > .fixed.bottom-6.right-6.z-50 .btn-circle.btn-lg {
                width: 3.25rem;
                height: 3.25rem;
                min-height: 3.25rem;
                max-width: 3.25rem !important;
                max-height: 3.25rem !important;
                padding: 0 !important;
                box-shadow: 0 18px 34px -20px rgba(15, 23, 42, 0.72);
            }

            #backToTop {
                bottom: 5.25rem;
                left: 1rem !important;
            }

            .home-hero {
                min-height: auto;
                padding-top: 4.5rem;
                padding-bottom: 3.25rem;
            }

            .home-hero h1 {
                font-size: clamp(2.75rem, 11.5vw, 3.45rem);
                line-height: 1.08;
                max-width: 21rem;
                margin-left: auto;
                margin-right: auto;
            }

            .hero-primary-cta,
            .hero-secondary-cta {
                width: min(100%, 22rem);
                justify-content: center;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto !important;
            }

            .home-reveal,
            .home-stagger-item {
                opacity: 1 !important;
                transform: none !important;
                transition: none !important;
            }

            .animate-float-slow,
            .animate-float-reverse,
            .animate-pulse-slow,
            .animate-border-glow {
                animation: none !important;
            }
        }
    </style>
