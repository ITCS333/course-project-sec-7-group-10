/*
  Requirement: Populate the weekly detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="week-title"`
     - To the start date <p>: `id="week-start-date"`
     - To the description <p>: `id="week-description"`
     - To the "Exercises & Resources" <ul>: `id="week-links-list"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Ask a Question" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment-text"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* specific week.
let currentWeekId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
const weekTitle = document.querySelector('#week-title');
const weekStartDate = document.querySelector('#week-start-date');
const weekDescription = document.querySelector('#week-description');
const weekLinksList = document.querySelector('#week-links-list');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newCommentText = document.querySelector('#new-comment-text');

// --- Functions ---

/**
 * TODO: Implement the getWeekIdFromURL function.
 */
function getWeekIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('week'); // or 'id' if your query string uses ?id=...
}

/**
 * TODO: Implement the renderWeekDetails function.
 */
function renderWeekDetails(week) {
  weekTitle.textContent = week.title;
  weekStartDate.textContent = `Starts on: ${week.startDate}`;
  weekDescription.textContent = week.description;

  // Clear previous links
  weekLinksList.innerHTML = '';

  if (week.links && week.links.length > 0) {
    week.links.forEach(link => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = link;
      a.textContent = link;
      a.target = "_blank"; // open in new tab
      li.appendChild(a);
      weekLinksList.appendChild(li);
    });
  }
}

/**
 * TODO: Implement the createCommentArticle function.
 */
function createCommentArticle(comment) {
  const article = document.createElement('article');

  const p = document.createElement('p');
  p.textContent = comment.text;
  article.appendChild(p);

  const footer = document.createElement('footer');
  footer.textContent = `— ${comment.author}`;
  article.appendChild(footer);

  return article;
}

/**
 * TODO: Implement the renderComments function.
 */
function renderComments() {
  // Clear existing comments
  commentList.innerHTML = '';

  currentComments.forEach(comment => {
    const commentArticle = createCommentArticle(comment);
    commentList.appendChild(commentArticle);
  });
}

/**
 * TODO: Implement the handleAddComment function.
 */
function handleAddComment(event) {
  event.preventDefault();

  const commentText = newCommentText.value.trim();
  if (!commentText) return;

  const newComment = {
    author: 'Student',
    text: commentText
  };

  currentComments.push(newComment);
  renderComments();

  newCommentText.value = '';
}

/**
 * TODO: Implement an `initializePage` function.
 */
async function initializePage() {
  currentWeekId = getWeekIdFromURL();

  if (!currentWeekId) {
    weekTitle.textContent = "Week not found.";
    return;
  }

  try {
    const [weeksResponse, commentsResponse] = await Promise.all([
      fetch('weeks.json'),
      fetch('week-comments.json')
    ]);

    const weeks = await weeksResponse.json();
    const commentsData = await commentsResponse.json();

    const week = weeks.find(w => w.id === currentWeekId);
    currentComments = commentsData[currentWeekId] || [];

    if (week) {
      renderWeekDetails(week);
      renderComments();
      commentForm.addEventListener('submit', handleAddComment);
    } else {
      weekTitle.textContent = "Week not found.";
    }

  } catch (error) {
    console.error('Error loading week or comments:', error);
    weekTitle.textContent = "Error loading week details.";
  }
}

// --- Initial Page Load ---
initializePage();
