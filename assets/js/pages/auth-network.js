const authNetworkPanels = document.querySelectorAll('[data-auth-network]');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const createParticle = (width, height, speed) => ({
    x: Math.random() * width,
    y: Math.random() * height,
    velocityX: (Math.random() - 0.5) * speed,
    velocityY: (Math.random() - 0.5) * speed,
});

authNetworkPanels.forEach((panel) => {
    const canvas = panel.querySelector('[data-network-canvas]');

    if (!(canvas instanceof HTMLCanvasElement)) {
        return;
    }

    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    let width = 0;
    let height = 0;
    let particles = [];
    let animationFrameId = 0;
    let lineMaxDistance = 150;
    let mouseRadius = 200;
    const particleSpeed = 0.5;
    const particleRadius = 3;
    const mouse = { x: null, y: null };

    const setCanvasSize = () => {
        const rect = panel.getBoundingClientRect();
        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        width = Math.max(rect.width, 1);
        height = Math.max(rect.height, 1);

        canvas.width = Math.round(width * dpr);
        canvas.height = Math.round(height * dpr);
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        context.setTransform(dpr, 0, 0, dpr, 0, 0);

        lineMaxDistance = width < 768 ? 100 : 150;
        mouseRadius = width < 768 ? 130 : 200;

        const particleCount = width < 640 ? 40 : width < 900 ? 68 : 96;
        particles = Array.from({ length: particleCount }, () => createParticle(width, height, particleSpeed));
    };

    const drawMouseGlow = () => {
        if (mouse.x === null || mouse.y === null) {
            return;
        }

        const gradient = context.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, mouseRadius);
        gradient.addColorStop(0, 'rgba(56, 189, 248, 0.22)');
        gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

        context.fillStyle = gradient;
        context.beginPath();
        context.arc(mouse.x, mouse.y, mouseRadius, 0, Math.PI * 2);
        context.fill();
    };

    const updateParticle = (particle) => {
        if (particle.x + particle.velocityX > width || particle.x + particle.velocityX < 0) {
            particle.velocityX *= -1;
        }

        if (particle.y + particle.velocityY > height || particle.y + particle.velocityY < 0) {
            particle.velocityY *= -1;
        }

        particle.x += particle.velocityX;
        particle.y += particle.velocityY;

        if (mouse.x === null || mouse.y === null) {
            return;
        }

        const dx = mouse.x - particle.x;
        const dy = mouse.y - particle.y;
        const distance = Math.hypot(dx, dy);

        if (distance > 0 && distance < mouseRadius) {
            particle.x += dx * 0.01;
            particle.y += dy * 0.01;
        }
    };

    const drawParticles = () => {
        particles.forEach((particle) => {
            context.beginPath();
            context.arc(particle.x, particle.y, particleRadius, 0, Math.PI * 2);
            context.fillStyle = 'rgba(125, 211, 252, 0.72)';
            context.fill();
        });
    };

    const drawLines = () => {
        for (let i = 0; i < particles.length; i += 1) {
            for (let j = i + 1; j < particles.length; j += 1) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.hypot(dx, dy);

                if (distance >= lineMaxDistance) {
                    continue;
                }

                context.lineWidth = 1;
                context.strokeStyle = `rgba(56, 189, 248, ${0.18 - (distance / lineMaxDistance) * 0.12})`;
                context.beginPath();
                context.moveTo(particles[i].x, particles[i].y);
                context.lineTo(particles[j].x, particles[j].y);
                context.stroke();
            }
        }
    };

    const drawFrame = (shouldAnimate) => {
        context.clearRect(0, 0, width, height);
        drawMouseGlow();

        if (shouldAnimate) {
            particles.forEach(updateParticle);
        }

        drawLines();
        drawParticles();
    };

    const animate = () => {
        drawFrame(true);
        animationFrameId = window.requestAnimationFrame(animate);
    };

    const restart = () => {
        window.cancelAnimationFrame(animationFrameId);
        setCanvasSize();

        if (prefersReducedMotion.matches) {
            drawFrame(false);
            return;
        }

        animate();
    };

    panel.addEventListener('pointermove', (event) => {
        const rect = panel.getBoundingClientRect();
        mouse.x = event.clientX - rect.left;
        mouse.y = event.clientY - rect.top;
    });

    panel.addEventListener('pointerleave', () => {
        mouse.x = null;
        mouse.y = null;
    });

    const resizeObserver = typeof ResizeObserver === 'function'
        ? new ResizeObserver(restart)
        : null;

    if (resizeObserver) {
        resizeObserver.observe(panel);
    } else {
        window.addEventListener('resize', restart);
    }

    if (typeof prefersReducedMotion.addEventListener === 'function') {
        prefersReducedMotion.addEventListener('change', restart);
    }

    restart();
});
