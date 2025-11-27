/*
  Requirement: Make the "Discussion Board" page interactive.
  Instructions:
  1. Link this file to `board.html` (or `board.html`) using:
     <script src="board.js" defer></script>
  
  2. In `board.html`, add an `id="topic-list-container"` to the 'div'
     that holds the list of topic articles.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the topics loaded from the JSON file.
let topics = [];

// --- Element Selections ---
// Select the new topic form ('#new-topic-form').
const newTopicForm = document.getElementById('new-topic-form');

// Select the topic list container ('#topic-list-container').
const topicListContainer = document.getElementById('topic-list-container');

// --- Functions ---

/**
 * Implement the createTopicArticle function.
 * It takes one topic object {id, subject, author, date}.
 * It should return an <article> element matching the structure in `board.html`.
 * - The main link's `href` MUST be `topic.html?id=${id}`.
 * - The footer should contain the author and date.
 * - The actions div should contain an "Edit" button and a "Delete" button.
 * - The "Delete" button should have a class "delete-btn" and `data-id="${id}"`.
 */
function createTopicArticle(topic) {
    const article = document.createElement('article');

    // Create the heading and link
    const heading = document.createElement('h3');
    const link = document.createElement('a');
    link.href = `topic.html?id=${topic.id}`;
    link.textContent = topic.subject;
    heading.appendChild(link);
    article.appendChild(heading);

    // Create the footer
    const footer = document.createElement('footer');
    footer.innerHTML = `<p>Posted by: ${topic.author} on ${topic.date}</p>`;
    article.appendChild(footer);

    // Create actions div
    const actions = document.createElement('div');
    actions.classList.add('action-buttons');
    actions.innerHTML = `
        <a href="edit-topic.html">Edit</a>
        <button class="delete-btn" data-id="${topic.id}">Delete</button>
    `;
    article.appendChild(actions);

    return article;
}

/**
 * Implement the renderTopics function.
 * It should:
 * 1. Clear the `topicListContainer`.
 * 2. Loop through the global `topics` array.
 * 3. For each topic, call `createTopicArticle()`, and
 * append the resulting <article> to `topicListContainer`.
 */
function renderTopics() {
    // Clear the topic list container
    topicListContainer.innerHTML = '';

    // Loop through the global topics array
    topics.forEach(topic => {
        const topicArticle = createTopicArticle(topic);
        topicListContainer.appendChild(topicArticle);
    });
}

/**
 * Implement the handleCreateTopic function.
 * This is the event handler for the form's 'submit' event.
 */
function handleCreateTopic(event) {
    event.preventDefault(); // Prevent the default form submission

    // Get the values from the '#topic-subject' and '#topic-message' inputs
    const subject = document.getElementById('topic-subject').value;
    const message = document.getElementById('topic-message').value;

    // Create a new topic object
    const newTopic = {
        id: `topic_${Date.now()}`,
        subject: subject,
        message: message,
        author: 'Student',
        date: new Date().toISOString().split('T')[0] // Today's date YYYY-MM-DD
    };

    // Add this new topic object to the global topics array
    topics.push(newTopic);

    // Call renderTopics() to refresh the list
    renderTopics();

    // Reset the form
    newTopicForm.reset();
}

/**
 * Implement the handleTopicListClick function.
 * This is an event listener on the `topicListContainer` (for delegation).
 */
function handleTopicListClick(event) {
    if (event.target.classList.contains('delete-btn')) {
        const topicId = event.target.dataset.id; // Get the data-id attribute

        // Update the global topics array by filtering out the topic
        topics = topics.filter(topic => topic.id !== topicId);
        
        // Call renderTopics() to refresh the list
        renderTopics();
    }
}

/**
 * Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 */
async function loadAndInitialize() {
    try {
        // Use fetch() to get data from 'topics.json'.
        const response = await fetch('topics.json');
        const data = await response.json();
        topics = data; // Store the result in the global topics array

        // Call renderTopics() to populate the list for the first time
        renderTopics();

        // Add event listeners
        newTopicForm.addEventListener('submit', handleCreateTopic);
        topicListContainer.addEventListener('click', handleTopicListClick);
    } catch (error) {
        console.error("Error loading topics:", error);
    }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
