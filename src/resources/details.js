/*
  Requirement: Populate the resource detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="resource-title"`
     - To the description <p>: `id="resource-description"`
     - To the "Access Resource Material" <a> tag: `id="resource-link"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Leave a Comment" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
let currentResourceId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
const resourceTitle = document.getElementById("resource-title");
const resourceDescription = document.getElementById("resource-description");
const resourceLink = document.getElementById("resource-link");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newComment = document.getElementById("new-comment");

// --- Functions ---

function getResourceIdFromURL() {
  // TODO IMPLEMENTATION
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

function renderResourceDetails(resource) {
  // TODO IMPLEMENTATION
  resourceTitle.textContent = resource.title;
  resourceDescription.textContent = resource.description;
  resourceLink.href = resource.link;
}

function createCommentArticle(comment) {
  // TODO IMPLEMENTATION
  const article = document.createElement("article");

  const p = document.createElement("p");
  p.textContent = comment.text;

  const footer = document.createElement("footer");
  footer.textContent = `Posted by: ${comment.author}`;

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

function renderComments() {
  // TODO IMPLEMENTATION
  commentList.innerHTML = "";

  currentComments.forEach((comment) => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

function handleAddComment(event) {
  // TODO IMPLEMENTATION
  event.preventDefault();

  const commentText = newComment.value.trim();
  if (!commentText) return;

  const newObj = {
    author: "Student",
    text: commentText,
  };

  currentComments.push(newObj);
  renderComments();
  newComment.value = "";
}

async function initializePage() {
  // TODO IMPLEMENTATION
  currentResourceId = getResourceIdFromURL();

  if (!currentResourceId) {
    resourceTitle.textContent = "Resource not found.";
    return;
  }

  try {
    const [resourcesRes, commentsRes] = await Promise.all([
      fetch("resources.json"),
      fetch("resource-comments.json")
    ]);

    const resources = await resourcesRes.json();
    const commentsData = await commentsRes.json();

    const resource = resources.find(r => r.id == currentResourceId);

    currentComments = commentsData[currentResourceId] || [];

    if (!resource) {
      resourceTitle.textContent = "Resource not found.";
      return;
    }

    renderResourceDetails(resource);
    renderComments();
    commentForm.addEventListener("submit", handleAddComment);

  } catch (error) {
    console.error("Error loading page:", error);
    resourceTitle.textContent = "Error loading resource.";
  }
}

// --- Initial Page Load ---
initializePage();
