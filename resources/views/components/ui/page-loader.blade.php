{{-- Global page-loading indicator: top progress bar + delayed spinner pill. --}}
<div id="page-loader" class="page-loader" aria-hidden="true">
    <div class="page-loader__track">
        <div class="page-loader__bar"></div>
    </div>

    <div class="page-loader__pill">
        <span class="page-loader__spinner"></span>
        <span>Loading…</span>
    </div>
</div>

@once
    @verbatim
        <style>
            .page-loader {
                position: fixed;
                inset: 0;
                z-index: 9999;
                pointer-events: none;
            }

            .page-loader__track {
                height: 3px;
                width: 100%;
                overflow: hidden;
                background: rgb(99 102 241 / 0.12);
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .page-loader.is-loading .page-loader__track {
                opacity: 1;
            }

            .page-loader__bar {
                position: relative;
                height: 100%;
                width: 100%;
                transform: scaleX(0);
                transform-origin: left center;
                background: linear-gradient(90deg, #6366f1, #a5b4fc, #4f46e5);
                box-shadow: 0 0 12px rgb(99 102 241 / 0.7);
                transition: transform 0.25s ease;
            }

            .page-loader__bar::after {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, transparent, rgb(255 255 255 / 0.55), transparent);
                transform: translateX(-100%);
            }

            .page-loader.is-loading .page-loader__bar::after {
                animation: page-loader-shimmer 1.1s ease-in-out infinite;
            }

            .page-loader__pill {
                position: absolute;
                bottom: 24px;
                left: 50%;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 14px;
                border-radius: 9999px;
                background: rgb(15 23 42 / 0.92);
                color: #fff;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: 0.01em;
                white-space: nowrap;
                box-shadow: 0 10px 30px -10px rgb(15 23 42 / 0.5);
                opacity: 0;
                transform: translate(-50%, 6px);
                transition: opacity 0.2s ease, transform 0.2s ease;
            }

            .page-loader.is-slow .page-loader__pill {
                opacity: 1;
                transform: translate(-50%, 0);
            }

            .page-loader__spinner {
                width: 14px;
                height: 14px;
                border-radius: 9999px;
                border: 2px solid rgb(255 255 255 / 0.3);
                border-top-color: #fff;
                animation: page-loader-spin 0.7s linear infinite;
            }

            @keyframes page-loader-spin {
                to {
                    transform: rotate(360deg);
                }
            }

            @keyframes page-loader-shimmer {
                0% {
                    transform: translateX(-100%);
                }

                60%,
                100% {
                    transform: translateX(100%);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .page-loader__track,
                .page-loader__bar,
                .page-loader__pill {
                    transition: none !important;
                }

                .page-loader__bar::after,
                .page-loader__spinner {
                    animation: none !important;
                }
            }
        </style>

        <script>
            (() => {
                const root = document.getElementById('page-loader');

                if (!root) {
                    return;
                }

                const bar = root.querySelector('.page-loader__bar');
                const track = root.querySelector('.page-loader__track');
                let progress = 0;
                let progressTimer = null;
                let slowTimer = null;
                let finishTimer = null;
                let livewireHooked = false;

                const setProgress = (value) => {
                    progress = Math.max(0, Math.min(1, value));
                    bar.style.transform = `scaleX(${progress})`;
                };

                const start = () => {
                    clearTimeout(finishTimer);
                    clearInterval(progressTimer);
                    clearTimeout(slowTimer);

                    root.classList.remove('is-complete', 'is-slow');
                    root.classList.add('is-loading');
                    track.style.opacity = '1';
                    setProgress(0.08);

                    progressTimer = setInterval(() => {
                        if (progress < 0.9) {
                            setProgress(progress + (0.9 - progress) * 0.12 + 0.005);
                        }
                    }, 180);

                    slowTimer = setTimeout(() => {
                        if (root.classList.contains('is-loading')) {
                            root.classList.add('is-slow');
                        }
                    }, 300);
                };

                const finish = () => {
                    clearInterval(progressTimer);
                    clearTimeout(slowTimer);
                    setProgress(1);
                    root.classList.add('is-complete');
                    root.classList.remove('is-slow');

                    finishTimer = setTimeout(() => {
                        root.classList.remove('is-loading', 'is-complete');
                        track.style.opacity = '0';
                        setProgress(0);
                    }, 260);
                };

                const shouldIgnoreLink = (link, event) => {
                    if (event.defaultPrevented) {
                        return true;
                    }

                    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return true;
                    }

                    if (link.target && link.target !== '_self') {
                        return true;
                    }

                    if (link.hasAttribute('download')) {
                        return true;
                    }

                    const href = link.getAttribute('href');

                    if (
                        !href ||
                        href.startsWith('#') ||
                        href.startsWith('mailto:') ||
                        href.startsWith('tel:') ||
                        href.startsWith('javascript:')
                    ) {
                        return true;
                    }

                    let url;

                    try {
                        url = new URL(link.href, window.location.href);
                    } catch {
                        return true;
                    }

                    if (url.origin !== window.location.origin) {
                        return true;
                    }

                    return url.pathname === window.location.pathname &&
                        url.search === window.location.search &&
                        Boolean(url.hash);
                };

                document.addEventListener('click', (event) => {
                    const link = event.target.closest('a');

                    if (!link || shouldIgnoreLink(link, event)) {
                        return;
                    }

                    start();
                }, true);

                document.addEventListener('submit', (event) => {
                    if (event.defaultPrevented) {
                        return;
                    }

                    if (event.target.closest('[wire\\:submit]')) {
                        return;
                    }

                    if (event.target.dataset.noLoader !== undefined) {
                        return;
                    }

                    start();
                }, true);

                const hookLivewire = () => {
                    if (livewireHooked || !window.Livewire?.hook) {
                        return;
                    }

                    livewireHooked = true;

                    window.Livewire.hook('request', ({ succeed, fail }) => {
                        start();
                        succeed(() => finish());
                        fail(() => finish());
                    });
                };

                document.addEventListener('livewire:init', hookLivewire);
                hookLivewire();

                window.addEventListener('load', finish);
                window.addEventListener('pageshow', (event) => {
                    if (event.persisted) {
                        finish();
                    }
                });

                if (document.readyState === 'complete') {
                    finish();
                } else {
                    start();
                }
            })();
        </script>
    @endverbatim
@endonce
