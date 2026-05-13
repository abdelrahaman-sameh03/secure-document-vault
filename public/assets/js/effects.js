(() => {
    const navToggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-nav]');
    navToggle?.addEventListener('click', () => nav?.classList.toggle('open'));

    document.querySelectorAll('[data-drop-zone]').forEach((zone) => {
        const input = zone.querySelector('input[type=file]');
        ['dragenter', 'dragover'].forEach(eventName => zone.addEventListener(eventName, (e) => {
            e.preventDefault();
            zone.classList.add('drag');
        }));
        ['dragleave', 'drop'].forEach(eventName => zone.addEventListener(eventName, (e) => {
            e.preventDefault();
            zone.classList.remove('drag');
        }));
        zone.addEventListener('drop', (e) => {
            if (input && e.dataTransfer.files.length) input.files = e.dataTransfer.files;
        });
    });

    const canvas = document.getElementById('particle-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let width = 0, height = 0, particles = [];
    const resize = () => {
        width = canvas.width = window.innerWidth * devicePixelRatio;
        height = canvas.height = window.innerHeight * devicePixelRatio;
        canvas.style.width = window.innerWidth + 'px';
        canvas.style.height = window.innerHeight + 'px';
        particles = Array.from({ length: Math.min(90, Math.floor(window.innerWidth / 18)) }, () => ({
            x: Math.random() * width,
            y: Math.random() * height,
            vx: (Math.random() - 0.5) * 0.35 * devicePixelRatio,
            vy: (Math.random() - 0.5) * 0.35 * devicePixelRatio,
            r: (Math.random() * 1.6 + 0.6) * devicePixelRatio,
        }));
    };
    window.addEventListener('resize', resize);
    resize();

    const draw = () => {
        ctx.clearRect(0, 0, width, height);
        for (const p of particles) {
            p.x += p.vx;
            p.y += p.vy;
            if (p.x < 0 || p.x > width) p.vx *= -1;
            if (p.y < 0 || p.y > height) p.vy *= -1;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(150, 225, 255, .55)';
            ctx.fill();
        }
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const a = particles[i], b = particles[j];
                const dx = a.x - b.x, dy = a.y - b.y;
                const dist = Math.hypot(dx, dy);
                if (dist < 130 * devicePixelRatio) {
                    ctx.strokeStyle = `rgba(105, 205, 255, ${0.13 * (1 - dist / (130 * devicePixelRatio))})`;
                    ctx.lineWidth = devicePixelRatio;
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.stroke();
                }
            }
        }
        requestAnimationFrame(draw);
    };
    draw();
})();
