document.addEventListener('DOMContentLoaded', function() {
  const langEnBtn = document.getElementById('lang-en');
  const langBnBtn = document.getElementById('lang-bn');

  // Texts for both languages
  const texts = {
    en: {
      heroTitle: 'Maternal Health & Awareness',
      heroDesc: 'Empowering mothers with knowledge, support, and care – in Bangla & English.',
      register: 'Register',
      login: 'Login',
      featuresTitle: 'Platform Features',
      featuresDesc: 'Comprehensive care for mothers and babies',
      feature1: 'Health Tracking',
      feature1Desc: 'Monitor vitals, medical history, and receive personalized health tips.',
      feature2: 'Appointments',
      feature2Desc: 'Book, manage, and track appointments with healthcare professionals.',
      feature3: 'Baby Tracker',
      feature3Desc: 'Track baby’s growth, vaccinations, and developmental milestones.'
    },
    bn: {
      heroTitle: 'মাতৃস্বাস্থ্য ও সচেতনতা',
      heroDesc: 'বাংলা ও ইংরেজিতে মায়েদের জন্য জ্ঞান, সহায়তা ও যত্ন।',
      register: 'নিবন্ধন',
      login: 'লগইন',
      featuresTitle: 'প্ল্যাটফর্মের বৈশিষ্ট্য',
      featuresDesc: 'মা ও শিশুর জন্য পূর্ণাঙ্গ যত্ন',
      feature1: 'স্বাস্থ্য পর্যবেক্ষণ',
      feature1Desc: 'ভিটালস, চিকিৎসা ইতিহাস পর্যবেক্ষণ এবং ব্যক্তিগত স্বাস্থ্য টিপস পান।',
      feature2: 'অ্যাপয়েন্টমেন্ট',
      feature2Desc: 'ডাক্তারের সাথে অ্যাপয়েন্টমেন্ট বুক, ম্যানেজ ও ট্র্যাক করুন।',
      feature3: 'বেবি ট্র্যাকার',
      feature3Desc: 'শিশুর বৃদ্ধি, টিকা ও বিকাশ পর্যবেক্ষণ করুন।'
    }
  };

  function setLanguage(lang) {
    const t = texts[lang];
    // Hero
    document.querySelector('.hero-section h1').textContent = t.heroTitle;
    document.querySelector('.hero-section p').textContent = t.heroDesc;
    document.querySelector('.hero-section .btn-primary').textContent = t.register;
    document.querySelector('.hero-section .btn-outline-primary').textContent = t.login;
    // Features
    document.querySelector('.features-section h2').textContent = t.featuresTitle;
    document.querySelector('.features-section p.text-muted').textContent = t.featuresDesc;
    const cards = document.querySelectorAll('.feature-card');
    if (cards.length === 3) {
      cards[0].querySelector('.card-title').textContent = t.feature1;
      cards[0].querySelector('.card-text').textContent = t.feature1Desc;
      cards[1].querySelector('.card-title').textContent = t.feature2;
      cards[1].querySelector('.card-text').textContent = t.feature2Desc;
      cards[2].querySelector('.card-title').textContent = t.feature3;
      cards[2].querySelector('.card-text').textContent = t.feature3Desc;
    }
    // Animate text change
    document.querySelectorAll('.hero-section h1, .hero-section p, .features-section h2, .features-section p.text-muted, .feature-card .card-title, .feature-card .card-text').forEach(el => {
      el.style.transition = 'opacity 0.3s';
      el.style.opacity = 0.3;
      setTimeout(() => { el.style.opacity = 1; }, 200);
    });
  }

  langEnBtn.addEventListener('click', () => setLanguage('en'));
  langBnBtn.addEventListener('click', () => setLanguage('bn'));

  // Contact Us form success animation
  if (document.getElementById('contactForm')) {
    document.getElementById('contactForm').addEventListener('submit', function(e) {
      e.preventDefault();
      var successMsg = document.getElementById('contactSuccess');
      if (successMsg) {
        successMsg.classList.add('show');
        successMsg.style.display = 'block';
        setTimeout(function() {
          successMsg.classList.remove('show');
          successMsg.style.display = 'none';
        }, 3500);
      }
      // Clear form fields
      this.reset();
    });
  }
}); 