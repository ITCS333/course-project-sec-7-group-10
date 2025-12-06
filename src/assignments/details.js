let currentAssignmentId = null;
let currentComments = [];

const assignmentTitle = document.getElementById('assignment-title');
const assignmentDueDate = document.getElementById('assignment-due-date');
const assignmentDescription = document.getElementById('assignment-description');
const assignmentFilesList = document.getElementById('assignment-files-list');
const commentList = document.getElementById('comment-list');
const commentForm = document.getElementById('comment-form');
const newCommentText = document.getElementById('new-comment-text');

function getAssignmentIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

function renderAssignmentDetails(assignment) {
  if (assignmentTitle) {
    assignmentTitle.textContent = assignment.title || 'Assignment Details';
  }
  if (assignmentDueDate) {
    assignmentDueDate.textContent = `Due: ${assignment.dueDate || ''}`;
  }
  if (assignmentDescription) {
    assignmentDescription.textContent = assignment.description || '';
  }
  if (assignmentFilesList) {
    assignmentFilesList.innerHTML = '';
    const files = Array.isArray(assignment.files) ? assignment.files : [];
    files.forEach(fileName => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = '#';
      a.textContent = fileName;
      li.appendChild(a);
      assignmentFilesList.appendChild(li);
    });
  }
}

function createCommentArticle(comment) {
  const article = document.createElement('article');
  const p = document.createElement('p');
  p.textContent = comment.text;
  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${comment.author}`;
  article.appendChild(p);
  article.appendChild(footer);
  return article;
}

function renderComments() {
  if (!commentList) return;
  commentList.innerHTML = '';
  currentComments.forEach(comment => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

function handleAddComment(event) {
  event.preventDefault();
  if (!newCommentText) return;

  const text = newCommentText.value.trim();
  if (!text) return;

  const newComment = {
    author: 'Student',
    text
  };

  currentComments.push(newComment);
  renderComments();
  newCommentText.value = '';
}

async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();

  if (!currentAssignmentId) {
    if (assignmentTitle) {
      assignmentTitle.textContent = 'Assignment not found';
    }
    alert('No assignment ID provided in the URL.');
    return;
  }

  try {
    const [assignmentsRes, commentsRes] = await Promise.all([
      fetch('assignments.json'),
      fetch('comments.json')
    ]);

    if (!assignmentsRes.ok || !commentsRes.ok) {
      throw new Error('Failed to load data.');
    }

    const assignmentsData = await assignmentsRes.json();
    const commentsData = await commentsRes.json();

    const assignment = Array.isArray(assignmentsData)
      ? assignmentsData.find(a => String(a.id) === String(currentAssignmentId))
      : null;

    currentComments = commentsData && Array.isArray(commentsData[currentAssignmentId])
      ? commentsData[currentAssignmentId]
      : [];

    if (!assignment) {
      if (assignmentTitle) {
        assignmentTitle.textContent = 'Assignment not found';
      }
      alert('Assignment not found.');
      return;
    }

    renderAssignmentDetails(assignment);
    renderComments();

    if (commentForm) {
      commentForm.addEventListener('submit', handleAddComment);
    }
  } catch (error) {
    console.error(error);
    alert('An error occurred while loading assignment details.');
  }
}

initializePage();
