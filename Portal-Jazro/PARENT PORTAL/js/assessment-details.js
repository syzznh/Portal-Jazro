document.addEventListener('DOMContentLoaded', async function () {
  const studentId = sessionStorage.getItem('jazroSelectedStudentId');
  const assessmentId = sessionStorage.getItem('jazroAssessmentId') || '12';
  const placeholder = document.querySelector('.question-placeholder');
  const oldStartButton = document.querySelector('.assessment-actions button[disabled]');
  const nameEl = document.getElementById('detailStudentName');
  const classEl = document.getElementById('detailStudentClass');
  const titleEl = document.getElementById('detailTitle');
  const actions = document.querySelector('.assessment-actions');

  if (oldStartButton) oldStartButton.style.display = 'none';
  if (!studentId) { showError(placeholder, 'Please return to My Children and select a child first.'); return; }

  try {
    const response = await fetch(`../get-assessment.php?assessment_id=${encodeURIComponent(assessmentId)}&student_id=${encodeURIComponent(studentId)}`, { credentials: 'same-origin' });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to load assessment.');

    titleEl.textContent = data.assessment.title;
    if (nameEl) nameEl.textContent = data.student.student_name;
    if (classEl) classEl.textContent = 'Mbot Junior';

    if (data.latest_attempt && data.latest_attempt.passed) {
      showError(placeholder, `This student has already passed with ${data.latest_attempt.score}/${data.assessment.total_marks}. No further attempt is needed.`);
      return;
    }
    if (data.latest_attempt) {
      const note = document.createElement('p');
      note.className = 'assessment-note';
      note.textContent = `Previous attempt: ${data.latest_attempt.score}/${data.assessment.total_marks}. You can try again.`;
      placeholder.insertAdjacentElement('beforebegin', note);
    }
    renderQuiz(placeholder, actions, data);
  } catch (error) { showError(placeholder, error.message); }
  setupNavigation();
});

function showError(container, message) {
  if (!container) return;
  container.innerHTML = '';
  const heading = document.createElement('h3'); heading.textContent = 'Assessment unavailable';
  const p = document.createElement('p'); p.textContent = message;
  container.append(heading, p);
}

function renderQuiz(container, oldActions, data) {
  container.innerHTML = '';
  container.classList.add('assessment-quiz');
  const questions = data.questions;
  const answers = {};
  let current = 0;
  const progress = document.createElement('div'); progress.className = 'quiz-progress';
  const progressText = document.createElement('strong');
  const progressTrack = document.createElement('div'); progressTrack.className = 'quiz-progress-track';
  const progressFill = document.createElement('div'); progressFill.className = 'quiz-progress-fill'; progressTrack.append(progressFill);
  const questionArea = document.createElement('div'); questionArea.className = 'quiz-question-area';
  const controls = document.createElement('div'); controls.className = 'quiz-controls';
  const previous = document.createElement('button'); previous.type = 'button'; previous.className = 'assessment-secondary-btn'; previous.textContent = '← Previous';
  const next = document.createElement('button'); next.type = 'button'; next.className = 'assessment-primary-btn'; next.textContent = 'Next →';
  const submit = document.createElement('button'); submit.type = 'button'; submit.className = 'assessment-primary-btn'; submit.textContent = 'Submit Assessment'; submit.style.display = 'none';
  const feedback = document.createElement('p'); feedback.className = 'quiz-feedback'; feedback.setAttribute('aria-live', 'polite');
  progress.append(progressText, progressTrack);
  controls.append(previous, next, submit);
  container.append(progress, questionArea, feedback, controls);
  if (oldActions) oldActions.style.display = 'none';

  function draw() {
    const q = questions[current];
    progressText.textContent = `Question ${current + 1} of ${questions.length} · ${q.marks} marks`;
    progressFill.style.width = `${((current + 1) / questions.length) * 100}%`;
    questionArea.innerHTML = '';
    const heading = document.createElement('h3'); heading.textContent = q.question_text; questionArea.append(heading);
    if (q.image_path) {
      const img = document.createElement('img'); img.className = 'quiz-question-image'; img.src = `../${q.image_path}`; img.alt = q.image_alt || 'Question image';
      img.onerror = () => { img.alt = 'Question image could not be loaded'; img.style.display = 'none'; };
      questionArea.append(img);
    }
    const options = document.createElement('div'); options.className = 'quiz-options';
    q.options.forEach(option => {
      const label = document.createElement('label'); label.className = 'quiz-option';
      const radio = document.createElement('input'); radio.type = 'radio'; radio.name = `question_${q.question_id}`; radio.value = option.option_id; radio.checked = String(answers[q.question_id] || '') === String(option.option_id);
      radio.addEventListener('change', () => { answers[q.question_id] = option.option_id; feedback.textContent = ''; });
      const letter = document.createElement('span'); letter.className = 'quiz-option-letter'; letter.textContent = option.option_label;
      const text = document.createElement('span'); text.textContent = option.option_text;
      label.append(radio, letter, text); options.append(label);
    });
    questionArea.append(options);
    previous.disabled = current === 0;
    next.style.display = current === questions.length - 1 ? 'none' : 'inline-flex';
    submit.style.display = current === questions.length - 1 ? 'inline-flex' : 'none';
  }
  previous.addEventListener('click', () => { if (current > 0) { current--; draw(); } });
  next.addEventListener('click', () => {
    if (!answers[questions[current].question_id]) { feedback.textContent = 'Please select an answer before continuing.'; return; }
    current++; draw();
  });
  submit.addEventListener('click', async () => {
    const unanswered = questions.find(q => !answers[q.question_id]);
    if (unanswered) { current = questions.indexOf(unanswered); draw(); feedback.textContent = 'Please answer every question before submitting.'; return; }
    if (!confirm('Are you sure you want to submit your assessment? You cannot change your answers afterwards.')) return;
    submit.disabled = true; submit.textContent = 'Submitting...'; previous.disabled = true; next.disabled = true;
    try {
      const response = await fetch('../submit-assessment.php', {
        method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: Number(sessionStorage.getItem('jazroSelectedStudentId')), assessment_id: Number(data.assessment.assessment_id), answers })
      });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'Unable to submit assessment.');
      renderResult(container, result.result, data.assessment);
    } catch (error) {
      feedback.textContent = error.message; submit.disabled = false; submit.textContent = 'Submit Assessment'; previous.disabled = current === 0; next.disabled = false;
    }
  });
  draw();
}

function renderResult(container, result, assessment) {
  container.innerHTML = '';
  container.classList.remove('assessment-quiz');
  const panel = document.createElement('div'); panel.className = 'quiz-result';
  const icon = document.createElement('div'); icon.className = 'quiz-result-icon'; icon.textContent = result.passed ? '🎉' : '💪';
  const title = document.createElement('h2'); title.textContent = result.passed ? 'Congratulations! You Passed!' : 'Keep Practising!';
  const score = document.createElement('p'); score.className = 'quiz-result-score'; score.textContent = `${result.score} / ${result.total_marks}`;
  const percent = document.createElement('p'); percent.textContent = `Your result: ${result.percentage}%`;
  const status = document.createElement('strong'); status.className = result.passed ? 'quiz-pass' : 'quiz-fail'; status.textContent = result.passed ? 'PASS' : 'NOT YET PASSED';
  const attempt = document.createElement('p'); attempt.textContent = `Attempt ${result.attempt_number} · Passing mark: ${result.passing_marks}`;
  const message = document.createElement('p'); message.textContent = result.passed ? 'You have achieved the minimum passing mark of 50.' : 'You need at least 50 marks to pass. You may retake this assessment.';
  const back = document.createElement('a'); back.href = 'assessments.html'; back.className = 'assessment-primary-btn'; back.textContent = 'Back to Assessments';
  panel.append(icon, title, score, percent, status, attempt, message, back); container.append(panel);
}
function setupNavigation() {
  const sidebar = document.getElementById('sidebar'), toggle = document.getElementById('menuToggle'), backdrop = document.getElementById('sidebarBackdrop');
  if (toggle && sidebar && backdrop) {
    toggle.addEventListener('click', () => { const open = sidebar.classList.toggle('open'); backdrop.hidden = !open; toggle.setAttribute('aria-expanded', String(open)); });
    backdrop.addEventListener('click', () => { sidebar.classList.remove('open'); backdrop.hidden = true; toggle.setAttribute('aria-expanded', 'false'); });
  }
  const logout = document.getElementById('sidebarLogout');
  if (logout) logout.addEventListener('click', async () => { try { await fetch('../logout.php', { method: 'POST', credentials: 'same-origin' }); } catch (e) {} sessionStorage.clear(); window.location.replace('../index.html'); });
  const y = new Date().getFullYear(); const a = document.getElementById('currentYear'); const b = document.getElementById('sidebarYear'); if (a) a.textContent = y; if (b) b.textContent = y;
}
