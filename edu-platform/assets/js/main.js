const menuBtn = document.getElementById('menuBtn');
const navLinks = document.getElementById('navLinks');
if (menuBtn && navLinks) {
  menuBtn.addEventListener('click', () => navLinks.classList.toggle('show'));
}

const courses = [
  { title: 'HTML & CSS للمبتدئين', level: 'مبتدئ', hours: 12 },
  { title: 'JavaScript تفاعلي', level: 'متوسط', hours: 20 },
  { title: 'PHP وبناء أنظمة الويب', level: 'متقدم', hours: 24 },
  { title: 'بناء متجر إلكتروني', level: 'متوسط', hours: 18 },
  { title: 'أساسيات UI/UX', level: 'مبتدئ', hours: 10 },
  { title: 'مشروع تخرج متكامل', level: 'متقدم', hours: 30 }
];

function renderCourses(list) {
  const grid = document.getElementById('coursesGrid');
  if (!grid) return;
  grid.innerHTML = list.map((c, i) => `
    <article class="card">
      <h3>${c.title}</h3>
      <p>المستوى: ${c.level}</p>
      <p>الساعات: ${c.hours}</p>
      <button class="btn" data-course="${c.title}" onclick="enroll('${c.title}')">سجّل الآن</button>
    </article>
  `).join('');
}

window.enroll = async function(courseTitle) {
  const res = await fetch('api/enroll.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ course: courseTitle, student: 'طالب تجريبي' })
  });
  const data = await res.json();
  alert(data.message);
}

const searchInput = document.getElementById('courseSearch');
if (searchInput) {
  renderCourses(courses);
  searchInput.addEventListener('input', (e) => {
    const q = e.target.value.trim();
    const filtered = courses.filter(c => c.title.includes(q) || c.level.includes(q));
    renderCourses(filtered);
  });
}

const contactForm = document.getElementById('contactForm');
if (contactForm) {
  contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const status = document.getElementById('formStatus');
    const formData = new FormData(contactForm);

    const res = await fetch('api/contact.php', { method: 'POST', body: formData });
    const data = await res.json();
    status.textContent = data.message;
    if (data.status === 'success') contactForm.reset();
  });
}
