/*
  Requirement: Populate the single topic page and manage replies.
  Instructions:
  1. Link this file to `topic.html` using:
     <script src="topic.js" defer></script>

  2. In `topic.html`, add the following IDs:
     - To the <h1>: `id="topic-subject"`
     - To the <article id="original-post">:
       - Add a <p> with `id="op-message"` for the message text.
       - Add a <footer> with `id="op-footer"` for the metadata.
     - To the <div> for the list of replies: `id="reply-list-container"`
     - To the "Post a Reply" <form>: `id="reply-form"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
let currentTopicId = null;
let currentReplies = []; // Will hold replies for *this* topic

// --- Element Selections ---
const topicSubject = document.getElementById('topic-subject');
const opMessage = document.getElementById('op-message');
const opFooter = document.getElementById('op-footer');
const replyListContainer = document.getElementById('reply-list-container');
const replyForm = document.getElementById('reply-form');
const newReplyText = document.getElementById('new-reply');

// --- Functions ---

/**
 * Implement the getTopicIdFromURL function.
 */
function getTopicIdFromURL() {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    return urlParams.get('id');
}

/**
 * Implement the renderOriginalPost function.
 */
function renderOriginalPost(topic) {
    topicSubject.textContent = topic.subject;
    opMessage.textContent = topic.message;
    opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;
    
    // Optional: Add Delete button
    const deleteButton = document.createElement('button');
    deleteButton.textContent = 'Delete';
    deleteButton.dataset.id = topic.id;
    opFooter.appendChild(deleteButton);
}

/**
 * Implement the createReplyArticle function.
 */
function createReplyArticle(reply) {
    const replyArticle = document.createElement('article');
    
    const replyText = document.createElement('p');
    replyText.textContent = reply.text;
    replyArticle.appendChild(replyText);

    const replyFooter = document.createElement('footer');
    replyFooter.textContent = `Posted by: ${reply.author} on ${reply.date}`;
    replyArticle.appendChild(replyFooter);

    const deleteReplyButton = document.createElement('button');
    deleteReplyButton.classList.add('delete-reply-btn');
    deleteReplyButton.dataset.id = reply.id;
    deleteReplyButton.textContent = 'Delete';
    replyArticle.appendChild(deleteReplyButton);

    return replyArticle;
}

/**
 * Implement the renderReplies function.
 */
function renderReplies() {
    replyListContainer.innerHTML = ''; // Clear previous replies

    // Loop through currentReplies array
    currentReplies.forEach(reply => {
        const replyArticle = createReplyArticle(reply);
        replyListContainer.appendChild(replyArticle);
    });
}

/**
 * Implement the handleAddReply function.
 */
function handleAddReply(event) {
    event.preventDefault(); // Prevent the default form submission

    const text = newReplyText.value.trim();
    if (!text) return; // Return if the input is empty

    const newReply = {
        id: `reply_${Date.now()}`,
        author: 'Student', // Hardcoded for exercise
        date: new Date().toISOString().split('T')[0],
        text: text
    };

    // Add the new reply to currentReplies array
    currentReplies.push(newReply);
    
    // Refresh the replies list
    renderReplies();

    // Clear the textarea
    newReplyText.value = '';
}

/**
 * Implement the handleReplyListClick function.
 */
function handleReplyListClick(event) {
    if (event.target.classList.contains('delete-reply-btn')) {
        const replyId = event.target.dataset.id; // Get the reply ID
        
        // Update the currentReplies array
        currentReplies = currentReplies.filter(reply => reply.id !== replyId);
        
        // Refresh the replies list
        renderReplies();
    }
}

/**
 * Implement the initializePage function.
 */
async function initializePage() {
    // Step 1: Get the `currentTopicId` by calling `getTopicIdFromURL()`.
    currentTopicId = getTopicIdFromURL();

    // Step 2: If no ID is found, set `topicSubject.textContent = "Topic not found."` and stop.
    if (!currentTopicId) {
        topicSubject.textContent = "Topic not found.";
        return; // Stop further execution
    }

    try {
        // Step 3: `fetch` both 'topics.json' and 'replies.json' (use `Promise.all`).
        const [topicsResponse, repliesResponse] = await Promise.all([
            fetch('topics.json'),
            fetch('replies.json')
        ]);

        // Step 4: Parse both JSON responses.
        const topics = await topicsResponse.json();
        const replies = await repliesResponse.json();

        // Step 5: Find the correct topic from the topics array using the `currentTopicId`.
        const topic = topics.find(t => t.id === currentTopicId);

        // Step 6: Get the correct replies array from the replies object using the `currentTopicId`.
        currentReplies = replies[currentTopicId] || []; // Store in the global `currentReplies` variable

        // Step 7: If the topic is found:
        if (topic) {
            renderOriginalPost(topic); // Call `renderOriginalPost()` with the topic object.
            renderReplies(); // Call `renderReplies()` to show the initial replies.

            // Add event listeners
            replyForm.addEventListener('submit', handleAddReply); // Submit event listener for replyForm
            replyListContainer.addEventListener('click', handleReplyListClick); // Click event listener for replyListContainer
        } else {
            // Step 8: If the topic is not found, display an error in `topicSubject`.
            topicSubject.textContent = "Topic not found.";
        }
    } catch (error) {
        console.error("Error loading data:", error);
        topicSubject.textContent = "Error loading topic."; // Display error message
    }
}

// --- Initial Page Load ---
initializePage();
