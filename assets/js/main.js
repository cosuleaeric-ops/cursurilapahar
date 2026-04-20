/* ═══════════════════════════════════════════
   Cursuri la Pahar – main.js
═══════════════════════════════════════════ */

// ── Restore scroll position on back navigation ──
if (history.scrollRestoration) history.scrollRestoration = 'manual';
(function() {
  var key = 'clp_scroll_' + location.pathname;
  var saved = sessionStorage.getItem(key);
  if (saved && performance.getEntriesByType('navigation')[0]?.type === 'back_forward') {
    window.scrollTo(0, parseInt(saved));
  }
  window.addEventListener('beforeunload', function() {
    sessionStorage.setItem(key, window.scrollY);
  });
})();

// ── Hero slideshow ───────────────────────
(function initSlideshow() {
  const slides = document.querySelectorAll('.hero-slide');
  if (!slides.length) return;
  let current = 0;

  function loadSlide(slide) {
    if (slide.dataset.bg && !slide.style.backgroundImage)
      slide.style.backgroundImage = "url('" + slide.dataset.bg + "')";
  }

  function next() {
    slides[current].classList.remove('active');
    current = (current + 1) % slides.length;
    loadSlide(slides[current]);
    slides[current].classList.add('active');
    // Preload next
    loadSlide(slides[(current + 1) % slides.length]);
  }

  // Preload slide #1 right away (hidden but ready)
  if (slides[1]) loadSlide(slides[1]);
  setInterval(next, 4500);
})();

// ── FAQ accordion ────────────────────────
(function initFAQ() {
  // Pre-wrap all answer contents so grid animation works immediately
  document.querySelectorAll('.faq-answer').forEach(answer => {
    if (!answer.children.length || answer.children[0].tagName !== 'DIV') {
      const inner = document.createElement('div');
      inner.innerHTML = answer.innerHTML;
      answer.innerHTML = '';
      answer.appendChild(inner);
    }
  });

  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
      const item    = btn.closest('.faq-item');
      const answer  = item.querySelector('.faq-answer');
      const isOpen  = btn.getAttribute('aria-expanded') === 'true';

      if (isOpen) {
        btn.setAttribute('aria-expanded', 'false');
        answer.classList.remove('open');
      } else {
        // Close others
        document.querySelectorAll('.faq-question[aria-expanded="true"]').forEach(other => {
          other.setAttribute('aria-expanded', 'false');
          other.closest('.faq-item').querySelector('.faq-answer').classList.remove('open');
        });
        btn.setAttribute('aria-expanded', 'true');
        answer.classList.add('open');
      }
    });
  });
})();

// ── Scroll reveal ────────────────────────
(function initReveal() {
  const targets = document.querySelectorAll(
    '.event-card, .step, .collab-card, .faq-item, .section-title, .section-subtitle, .newsletter-form, .contact-form'
  );
  if (!targets.length) return;

  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.08 });

  targets.forEach(el => {
    el.classList.add('reveal');
    observer.observe(el);
  });
})();

// ── Smooth scroll for anchor links ───────
document.querySelectorAll('a[href^="#"]').forEach(link => {
  link.addEventListener('click', e => {
    const target = document.querySelector(link.getAttribute('href'));
    if (!target) return;
    e.preventDefault();
    const offset = 0;
    const top = target.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top, behavior: 'smooth' });
  });
});

// ── Hamburger / mobile drawer ─────────────
(function initHamburger() {
  const hamburger = document.getElementById('hamburger');
  const drawer    = document.getElementById('navDrawer');
  if (!hamburger || !drawer) return;

  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    drawer.classList.toggle('open');
  });
  drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
    hamburger.classList.remove('open');
    drawer.classList.remove('open');
  }));
})();

// ── Email validator ───────────────────────
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
}

// ── Newsletter form ───────────────────────
const nlForm = document.getElementById('newsletterForm');
if (nlForm) {
  nlForm.addEventListener('submit', async e => {
    e.preventDefault();
    const email = nlForm.querySelector('#nlEmail').value.trim();
    const msg   = document.getElementById('nlMessage');
    const btn   = nlForm.querySelector('button[type="submit"]');

    if (!email) return;
    if (!isValidEmail(email)) {
      msg.className = 'form-message error';
      msg.textContent = 'Adresa de email nu este validă. Verifică formatul (ex: nume@exemplu.ro).';
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Se trimite…';
    msg.className = 'form-message';

    try {
      const res  = await fetch('/api/subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });
      const text = await res.text();
      console.log('[subscribe] raw response:', text);
      const data = JSON.parse(text);

      if (data.success) {
        msg.className = 'form-message success';
        msg.textContent = 'Mulțumim! Te vom anunța cu 2 săptămâni înainte de fiecare eveniment.';
        nlForm.querySelector('#nlEmail').value = '';
      } else {
        throw new Error(data.message || 'Eroare necunoscută');
      }
    } catch (err) {
      msg.className = 'form-message error';
      msg.textContent = err.message && err.message !== 'Eroare necunoscută'
        ? err.message
        : 'Ceva n-a mers bine. Încearcă din nou sau scrie-ne la contact@cursurilapahar.ro';
    } finally {
      btn.disabled = false;
      btn.textContent = 'Anunță-mă';
    }
  });
}

// ── Contact form ─────────────────────────
const contactForm = document.getElementById('contactForm');
if (contactForm) {
  contactForm.addEventListener('submit', async e => {
    e.preventDefault();
    const msg = document.getElementById('contactMessage');
    const btn = contactForm.querySelector('button[type="submit"]');

    const payload = {
      form_type: 'contact',
      name:      contactForm.querySelector('#contactName').value.trim(),
      email:     contactForm.querySelector('#contactEmail').value.trim(),
      message:   contactForm.querySelector('#contactMsg').value.trim()
    };

    if (!payload.name || !payload.email || !payload.message) {
      msg.className = 'form-message error';
      msg.textContent = 'Te rugăm completează toate câmpurile.';
      return;
    }
    if (!isValidEmail(payload.email)) {
      msg.className = 'form-message error';
      msg.textContent = 'Adresa de email nu este validă. Verifică formatul (ex: nume@exemplu.ro).';
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Se trimite…';
    msg.className = 'form-message';

    try {
      const res  = await fetch('/api/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.success) {
        msg.className = 'form-message success';
        msg.textContent = 'Mesaj trimis! Îți răspundem în cel mai scurt timp.';
        contactForm.reset();
      } else {
        throw new Error(data.message || 'Eroare');
      }
    } catch {
      msg.className = 'form-message error';
      msg.textContent = 'Ceva n-a mers bine. Scrie-ne direct la contact@cursurilapahar.ro';
    } finally {
      btn.disabled = false;
      btn.textContent = 'Trimite mesajul';
    }
  });
}

// ── Gallery slider arrows (infinite/circular, smooth) ─────
const gallerySlider = document.querySelector('.gallery-slider');
if (gallerySlider) {
  // Clone items: [clones_end][originals][clones_start] for seamless wrap
  const originals = Array.from(gallerySlider.children);
  originals.forEach(el => {
    const c = el.cloneNode(true);
    c.classList.add('gallery-clone');
    c.setAttribute('aria-hidden', 'true');
    gallerySlider.appendChild(c);
  });
  originals.forEach(el => {
    const c = el.cloneNode(true);
    c.classList.add('gallery-clone');
    c.setAttribute('aria-hidden', 'true');
    gallerySlider.prepend(c);
  });

  // Disable browser smooth scroll so rAF animation has full control
  gallerySlider.style.scrollBehavior = 'auto';

  // Start at the original section (middle third)
  const origWidth = () => gallerySlider.scrollWidth / 3;
  gallerySlider.scrollLeft = origWidth();

  const step = () => gallerySlider.clientWidth * 0.75;
  let busy = false;

  function ease(t) { return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t; }

  function animateScroll(delta) {
    if (busy) return;
    busy = true;
    const start = gallerySlider.scrollLeft;
    const duration = 380;
    const t0 = performance.now();

    function frame(now) {
      const t = Math.min((now - t0) / duration, 1);
      gallerySlider.scrollLeft = start + delta * ease(t);
      if (t < 1) {
        requestAnimationFrame(frame);
      } else {
        // Silent reset: jump back to equivalent position in originals
        const ow = origWidth();
        if (gallerySlider.scrollLeft >= ow * 2) gallerySlider.scrollLeft -= ow;
        else if (gallerySlider.scrollLeft < ow)  gallerySlider.scrollLeft += ow;
        busy = false;
      }
    }
    requestAnimationFrame(frame);
  }

  document.querySelector('.gslider-prev')?.addEventListener('click', () => animateScroll(-step()));
  document.querySelector('.gslider-next')?.addEventListener('click', () => animateScroll(step()));
}

// ── Gallery lightbox ────────────────────
const galleryLightbox = document.getElementById('galleryLightbox');
if (galleryLightbox) {
  const lbImg  = document.getElementById('lightboxImg');
  const items  = document.querySelectorAll('.gallery-item:not(.gallery-clone)');
  const srcs   = Array.from(items).map(el => el.querySelector('img').src);
  let cur = 0;

  function lbOpen(i) {
    cur = i;
    lbImg.src = srcs[cur];
    galleryLightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function lbClose() {
    galleryLightbox.classList.remove('active');
    document.body.style.overflow = '';
  }
  function lbNav(dir) {
    cur = (cur + dir + srcs.length) % srcs.length;
    lbImg.src = srcs[cur];
  }

  items.forEach((el, i) => el.addEventListener('click', () => lbOpen(i)));
  galleryLightbox.querySelector('.lightbox-close').addEventListener('click', lbClose);
  galleryLightbox.querySelector('.lightbox-prev').addEventListener('click', () => lbNav(-1));
  galleryLightbox.querySelector('.lightbox-next').addEventListener('click', () => lbNav(1));
  galleryLightbox.addEventListener('click', e => { if (e.target === galleryLightbox) lbClose(); });
  document.addEventListener('keydown', e => {
    if (!galleryLightbox.classList.contains('active')) return;
    if (e.key === 'Escape') lbClose();
    if (e.key === 'ArrowLeft') lbNav(-1);
    if (e.key === 'ArrowRight') lbNav(1);
  });
}

// ── Collaboration / inner page forms ─────
const innerForms = document.querySelectorAll('.inner-page-form');
innerForms.forEach(form => {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const msg  = form.querySelector('.form-message');
    const btn  = form.querySelector('button[type="submit"]');
    const type = form.dataset.formType || 'contact';

    const formData = new FormData(form);
    const payload  = {};
    formData.forEach((value, key) => {
      if (key.endsWith('[]')) {
        if (!payload[key]) payload[key] = [];
        payload[key].push(value);
      } else {
        payload[key] = value;
      }
    });
    // Include unchecked checkbox groups as empty arrays
    form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      if (!payload[cb.name]) payload[cb.name] = [];
    });
    payload.form_type = type;

    // Validate email if present
    const emailField = form.querySelector('input[type="email"]');
    if (emailField) {
      const emailVal = emailField.value.trim();
      if (!emailVal || !isValidEmail(emailVal)) {
        if (msg) {
          msg.className = 'form-message error';
          msg.textContent = 'Adresa de email nu este validă. Verifică formatul (ex: nume@exemplu.ro).';
        }
        return;
      }
    }

    btn.disabled = true;
    btn.textContent = 'Se trimite…';
    if (msg) { msg.className = 'form-message'; }

    try {
      const res  = await fetch('/api/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const text = await res.text();
      let data;
      try { data = JSON.parse(text); } catch { throw new Error('Răspuns invalid de la server: ' + text.substring(0, 200)); }

      if (data.success) {
        if (msg) {
          msg.className = 'form-message success';
          msg.textContent = 'Mulțumim! Te vom contacta în cel mai scurt timp.';
        }
        form.reset();
      } else {
        throw new Error(data.message || 'Eroare');
      }
    } catch (err) {
      if (msg) {
        msg.className = 'form-message error';
        msg.textContent = err.message || 'Ceva n-a mers bine. Scrie-ne direct la contact@cursurilapahar.ro';
      }
    } finally {
      btn.disabled = false;
      btn.textContent = 'Trimite';
    }
  });
});
