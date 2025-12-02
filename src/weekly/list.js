/*
  Requirement: Populate the "Weekly Course Breakdown" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="week-list-section"` to the
     <section> element that will contain the weekly articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the week list ('#week-list-section').
const listSection = document.querySelector('#week-list-section');

// --- Functions ---

/**
 * TODO: Implement the createWeekArticle function.
 */
function createWeekArticle(week) {
  const article = document.createElement('article');

  // Week title
  const h2 = document.createElement('h2');
  h2.textContent = week.title;
  article.appendChild(h2);

  // Start date
  const pDate = document.createElement('p');
  pDate.textContent = `Starts on: ${week.startDate}`;
  article.appendChild(pDate);

  // Description
  const pDesc = document.createElement('p');
  pDesc.textContent = week.description;
  article.appendChild(pDesc);

  // Link to details page
  const a = document.createElement('a');
  a.href = `details.html?week=${week.id}`;
  a.textContent = "View Details & Discussion";
  article.appendChild(a);

  return article;
}

/**
 * TODO: Implement the loadWeeks function.
 */
async function loadWeeks() {
  try {
    const response = await fetch('weeks.json');
    if (!response.ok) throw new Error('Failed to load weeks.json');

    const weeks = await response.json();

    // Clear existing content
    listSection.innerHTML = '';

    // Add each week
    weeks.forEach(week => {
      const article = createWeekArticle(week);
      listSection.appendChild(article);
    });
  } catch (error) {
    console.error('Error loading weeks:', error);
    listSection.textContent = "Failed to load weekly breakdown.";
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadWeeks();
