let assignments = [];

const assignmentForm = document.querySelector('#assignment-form');
const assignmentsTableBody = document.querySelector('#assignments-tbody');

function createAssignmentRow(assignment) {
  const tr = document.createElement('tr');

  const titleTd = document.createElement('td');
  titleTd.textContent = assignment.title;
  tr.appendChild(titleTd);

  const dueDateTd = document.createElement('td');
  dueDateTd.textContent = assignment.dueDate;
  tr.appendChild(dueDateTd);

  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.type = 'button';
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');
  editBtn.dataset.id = assignment.id;
  actionsTd.appendChild(editBtn);

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = assignment.id;
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

  return tr;
}

function renderTable() {
  if (!assignmentsTableBody) return;
  assignmentsTableBody.innerHTML = '';
  assignments.forEach(assignment => {
    const row = createAssignmentRow(assignment);
    assignmentsTableBody.appendChild(row);
  });
}

function handleAddAssignment(event) {
  event.preventDefault();

  const titleInput = document.getElementById('assignment-title');
  const descriptionInput = document.getElementById('assignment-description');
  const dueDateInput = document.getElementById('assignment-due-date');
  const filesInput = document.getElementById('assignment-files');

  const title = titleInput ? titleInput.value.trim() : '';
  const description = descriptionInput ? descriptionInput.value.trim() : '';
  const dueDate = dueDateInput ? dueDateInput.value : '';
  const filesRaw = filesInput ? filesInput.value.trim() : '';

  if (!title || !description || !dueDate) {
    alert('Please fill out all required fields.');
    return;
  }

  const newAssignment = {
    id: `asg_${Date.now()}`,
    title,
    description,
    dueDate,
    files: filesRaw
  };

  assignments.push(newAssignment);
  renderTable();

  if (assignmentForm) {
    assignmentForm.reset();
  }
}

function handleTableClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  if (target.classList.contains('delete-btn')) {
    const id = target.dataset.id;
    if (!id) return;
    assignments = assignments.filter(assignment => assignment.id !== id);
    renderTable();
  }
}

async function loadAndInitialize() {
  try {
    const response = await fetch('assignments.json');
    if (response.ok) {
      const data = await response.json();
      if (Array.isArray(data)) {
        assignments = data;
      }
    } else {
      console.error('Failed to load assignments.json:', response.status, response.statusText);
    }
  } catch (error) {
    console.error('Error fetching assignments:', error);
  }

  renderTable();

  if (assignmentForm) {
    assignmentForm.addEventListener('submit', handleAddAssignment);
  }

  if (assignmentsTableBody) {
    assignmentsTableBody.addEventListener('click', handleTableClick);
  }
}

loadAndInitialize();
