document.addEventListener('DOMContentLoaded', async function () {
  const studentId = sessionStorage.getItem('jazroSelectedStudentId');
  const startButton = document.getElementById('startAssessmentBtn');
  const titleEl = document.getElementById('assessmentTitle');
  const descEl = document.getElementById('assessmentDescription');
  const nameEl = document.getElementById('assessmentStudentName');
  const classEl = document.getElementById('assessmentStudentClass');
  const typeEl = document.getElementById('assessmentClassType');
  const meta = document.querySelectorAll('.assessment-meta span');

  if (!studentId) {
    showAssessmentMessage('Please go back to My Children and select a child first.');
    if (startButton) startButton.disabled = true;
    return;
  }

  try {
    const response = await fetch(`../get-assessment.php?assessment_id=12&student_id=${encodeURIComponent(studentId)}`, { credentials: 'same-origin' });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to load assessment.');

    const a = data.assessment;
    if (titleEl) titleEl.textContent = a.title;
    if (descEl) descEl.textContent = a.description || 'Complete this assessment to check your learning progress.';
    if (nameEl) nameEl.textContent = data.student.student_name;
    if (classEl) classEl.textContent = 'Junior Assessment';
    if (typeEl) typeEl.textContent = 'Assessment';
    if (meta[0]) meta[0].textContent = `Total Marks: ${a.total_marks}`;
    if (meta[1]) meta[1].textContent = `Questions: ${data.questions.length}`;
    if (meta[2]) meta[2].textContent = 'Mbot Junior';

    if (data.latest_attempt) {
      showAssessmentMessage(`Latest attempt: ${data.latest_attempt.score}/${a.total_marks} (${data.latest_attempt.percentage}%). ${data.latest_attempt.passed ? 'You have passed this assessment.' : 'You may try again.'}`);
    }
    if (!data.can_attempt) {
      startButton.disabled = true;
      startButton.textContent = 'Assessment Passed';
    } else {
      startButton.disabled = false;
      startButton.addEventListener('click', function () {
        sessionStorage.setItem('jazroAssessmentId', String(a.assessment_id));
        window.location.href = 'assessment-details.html';
      });
    }
  } catch (error) {
    showAssessmentMessage(error.message);
    if (startButton) startButton.disabled = true;
  }

  setYears(); setupNavigation();
});

function showAssessmentMessage(message) {
  let box = document.getElementById('assessmentDynamicMessage');
  if (!box) {
    box = document.createElement('div');
    box.id = 'assessmentDynamicMessage';
    box.className = 'assessment-note';
    const card = document.querySelector('.assessment-card');
    if (card) card.insertAdjacentElement('afterend', box);
    else document.querySelector('.assessment-content')?.prepend(box);
  }
  box.textContent = message;
}
function setYears() {
  const y = new Date().getFullYear();
  const a = document.getElementById('currentYear'); if (a) a.textContent = y;
  const b = document.getElementById('sidebarYear'); if (b) b.textContent = y;
}
function setupNavigation() {
  const sidebar = document.getElementById('sidebar'), toggle = document.getElementById('menuToggle'), backdrop = document.getElementById('sidebarBackdrop');
  if (toggle && sidebar && backdrop) {
    toggle.addEventListener('click', () => { const open = sidebar.classList.toggle('open'); backdrop.hidden = !open; toggle.setAttribute('aria-expanded', String(open)); });
    backdrop.addEventListener('click', () => { sidebar.classList.remove('open'); backdrop.hidden = true; toggle.setAttribute('aria-expanded', 'false'); });
  }
  const logout = document.getElementById('sidebarLogout');
  if (logout) logout.addEventListener('click', async () => {
    try { await fetch('../logout.php', { method: 'POST', credentials: 'same-origin' }); } catch (e) {}
    sessionStorage.removeItem('jazroSelectedStudentId'); sessionStorage.removeItem('jazroAssessmentId');
    window.location.replace('../index.html');
  });
}
