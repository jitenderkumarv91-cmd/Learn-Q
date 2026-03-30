const navToggle = document.querySelector('[data-nav-toggle]');
const navMenu = document.querySelector('[data-nav-menu]');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const appUrl = document.querySelector('meta[name="app-url"]')?.content || '/';
const courseLogoMap = {
    html: {
        src: 'https://cdn.simpleicons.org/html5/E34F26',
        alt: 'HTML logo',
    },
    css: {
        src: 'https://cdn.simpleicons.org/css/F4A261',
        alt: 'CSS logo',
    },
    javascript: {
        src: 'https://cdn.simpleicons.org/javascript/F7DF1E',
        alt: 'JavaScript logo',
    },
    python: {
        src: 'https://cdn.simpleicons.org/python/3776AB',
        alt: 'Python logo',
    },
    'machine-learning': {
        src: 'https://cdn.simpleicons.org/scikitlearn/F7931E',
        alt: 'Machine Learning logo',
    },
    dsa: {
        src: 'https://cdn.simpleicons.org/leetcode/FFA116',
        alt: 'DSA logo',
    },
    c: {
        src: 'https://cdn.simpleicons.org/c/00599C',
        alt: 'Programming C logo',
    },
    cpp: {
        src: 'https://cdn.simpleicons.org/cplusplus/649AD2',
        alt: 'Programming C++ logo',
    },
    'ethical-hacking': {
        src: 'https://cdn.simpleicons.org/hackthebox/9FEF00',
        alt: 'Ethical Hacking logo',
    },
    linux: {
        src: 'https://cdn.simpleicons.org/linux/F4A261',
        alt: 'Linux logo',
    },
    django: {
        src: 'https://cdn.simpleicons.org/django/44B78B',
        alt: 'Django logo',
    },
    mysql: {
        src: 'https://cdn.simpleicons.org/mysql/4479A1',
        alt: 'MySQL logo',
    },
    mongodb: {
        src: 'https://cdn.simpleicons.org/mongodb/47A248',
        alt: 'MongoDB logo',
    },
};

const buildUrl = (path) => {
    const base = appUrl.endsWith('/') ? appUrl.slice(0, -1) : appUrl;
    return `${base}/${path.replace(/^\//, '')}`;
};

if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
        navMenu.classList.toggle('is-open');
    });
}

document.querySelectorAll('[data-course-logo-target]').forEach((target) => {
    const slug = target.dataset.courseSlug || '';
    const logo = courseLogoMap[slug];

    if (!logo || target.querySelector('.course-logo, .course-logo-stack')) {
        return;
    }

    target.classList.add('has-course-logo');
    if (['css', 'c', 'cpp'].includes(slug)) {
        target.classList.add('has-course-logo-emphasis');
    }

    if (slug === 'python') {
        const stack = document.createElement('span');
        stack.className = 'course-logo-stack';
        stack.setAttribute('aria-hidden', 'true');

        const topImage = document.createElement('img');
        topImage.className = 'course-logo course-logo-layer is-top';
        topImage.src = logo.src;
        topImage.alt = '';
        topImage.loading = 'lazy';
        topImage.decoding = 'async';

        const bottomImage = document.createElement('img');
        bottomImage.className = 'course-logo course-logo-layer is-bottom';
        bottomImage.src = logo.src;
        bottomImage.alt = '';
        bottomImage.loading = 'lazy';
        bottomImage.decoding = 'async';

        stack.append(topImage, bottomImage);
        target.prepend(stack);
        return;
    }

    const image = document.createElement('img');
    image.className = 'course-logo';
    image.src = logo.src;
    image.alt = logo.alt;
    image.loading = 'lazy';
    image.decoding = 'async';

    target.prepend(image);
});

document.querySelectorAll('.flash').forEach((flash) => {
    window.setTimeout(() => {
        flash.style.opacity = '0';
        flash.style.transform = 'translateY(-8px)';
        flash.style.transition = '0.25s ease';
    }, 4200);
});

window.ScholarGrid = {
    csrfToken,
    buildUrl,
    trackPageDuration(page, details = '') {
        const startedAt = Date.now();
        const sendDuration = () => {
            const seconds = Math.round((Date.now() - startedAt) / 1000);
            if (seconds <= 0 || !navigator.sendBeacon) {
                return;
            }

            const payload = new FormData();
            payload.append('csrf_token', csrfToken);
            payload.append('page', page);
            payload.append('seconds', String(seconds));
            payload.append('details', details);
            navigator.sendBeacon(buildUrl('ajax/log_duration.php'), payload);
        };

        window.addEventListener('pagehide', sendDuration, { once: true });
    },
};
