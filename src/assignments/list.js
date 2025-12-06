const listSection = document.querySelector('#assignment-list-section');

function createAssignmentArticle(assignment) {
  const article = document.createElement('article');

  const h2 = document.createElement('h2');
  h2.textContent = assignment.title;
  article.appendChild(h2);

  const dueP = document.createElement('p');
  dueP.textContent = `Due: ${assignment.dueDate}`;
  article.appendChild(dueP);

  const descP = document.createElement('p');
  descP.textContent = assignment.description;
  article.appendChild(descP);

  const link = document.createElement('a');
  link.href = `details.html?id=${assignment.id}`;
  link.textContent = 'View Details & Discussion';
  article.appendChild(link);

  return article;
}

async function loadAssignments() {
  try {
    const response = await fetch('assignments.json');
    if (!response.ok) {
      console.error('Failed to load assignments.json:', response.status, response.statusText);
      return;
    }

    const assignments = await response.json();

    if (!listSection) return;

    listSection.innerHTML = '';

    assignments.forEach(assignment => {
      const article = createAssignmentArticle(assignment);
      listSection.appendChild(article);
    });

  } catch (error) {
    console.error('Error loading assignments:', error);
  }
}

loadAssignments();
