/*
  Requirement: Make the "Manage Resources" page interactive.
*/

let resources = [];

const resourceForm = document.querySelector('#resource-form');
const resourcesTableBody = document.querySelector('#resources-tbody');

function createResourceRow(resource) {
  const tr = document.createElement('tr');

  tr.innerHTML = `
    <td>${resource.title}</td>
    <td>${resource.description}</td>
    <td>
      <button class="edit-btn" data-id="${resource.id}">Edit</button>
      <button class="delete-btn" data-id="${resource.id}">Delete</button>
    </td>
  `;
  return tr;
}

function renderTable() {
  resourcesTableBody.innerHTML = '';
  resources.forEach(resource => {
    resourcesTableBody.appendChild(createResourceRow(resource));
  });
}

function handleAddResource(event) {
  event.preventDefault();

  const title = document.querySelector('#resource-title').value;
  const description = document.querySelector('#resource-description').value;
  const link = document.querySelector('#resource-link').value;

  fetch('/src/resources/api/index.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ title, description, link })
  })
    .then(res => res.json())
    .then(data => {
      resources.push(data);
      renderTable();
      resourceForm.reset();
    });
}

resourceForm.addEventListener('submit', handleAddResource);

fetch('/src/resources/api/index.php')
  .then(res => res.json())
  .then(data => {
    resources = data;
    renderTable();
  });
