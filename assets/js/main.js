/* ═══════════════════════════════════════════
   Cursuri la Pahar – main.js
═══════════════════════════════════════════ */

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

// ── Newsletter form ───────────────────────
const nlForm = document.getElementById('newsletterForm');
if (nlForm) {
  nlForm.addEventListener('submit', async e => {
    e.preventDefault();
    const email = nlForm.querySelector('#nlEmail').value.trim();
    const msg   = document.getElementById('nlMessage');
    const btn   = nlForm.querySelector('button[type="submit"]');

    if (!email) return;

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

// ── Collaboration / inner page forms ─────
const innerForms = document.querySelectorAll('.inner-page-form');
innerForms.forEach(form => {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const msg  = form.querySelector('.form-message');
    const btn  = form.querySelector('button[type="submit"]');
    const type = form.dataset.formType || 'contact';

    const formData = new FormData(form);
    const payload  = Object.fromEntries(formData.entries());
    // Include checkboxes as array
    form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      if (!payload[cb.name]) payload[cb.name] = [];
      if (cb.checked) {
        if (!Array.isArray(payload[cb.name])) payload[cb.name] = [];
        payload[cb.name].push(cb.value);
      }
    });
    payload.form_type = type;

    btn.disabled = true;
    btn.textContent = 'Se trimite…';
    if (msg) { msg.className = 'form-message'; }

    try {
      const res  = await fetch('/api/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.success) {
        if (msg) {
          msg.className = 'form-message success';
          msg.textContent = 'Mulțumim! Te vom contacta în cel mai scurt timp.';
        }
        form.reset();
      } else {
        throw new Error(data.message || 'Eroare');
      }
    } catch {
      if (msg) {
        msg.className = 'form-message error';
        msg.textContent = 'Ceva n-a mers bine. Scrie-ne direct la contact@cursurilapahar.ro';
      }
    } finally {
      btn.disabled = false;
      btn.textContent = 'Trimite';
    }
  });
});
