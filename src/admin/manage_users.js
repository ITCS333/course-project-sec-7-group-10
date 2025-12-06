let students = [];

const studentTableBody = document.querySelector('#student-table tbody');
const addStudentForm = document.getElementById('add-student-form');
const changePasswordForm = document.getElementById('password-form');
const searchInput = document.getElementById('search-input');
const tableHeaders = document.querySelectorAll('#student-table thead th');

function createStudentRow(student) {
  const tr = document.createElement('tr');

  const nameTd = document.createElement('td');
  nameTd.textContent = student.name;
  tr.appendChild(nameTd);

  const idTd = document.createElement('td');
  idTd.textContent = student.id;
  tr.appendChild(idTd);

  const emailTd = document.createElement('td');
  emailTd.textContent = student.email;
  tr.appendChild(emailTd);

  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.type = 'button';
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');
  editBtn.dataset.id = student.id;
  actionsTd.appendChild(editBtn);

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = student.id;
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

  return tr;
}

function renderTable(studentArray) {
  if (!studentTableBody) return;
  studentTableBody.innerHTML = '';
  studentArray.forEach(student => {
    const row = createStudentRow(student);
    studentTableBody.appendChild(row);
  });
}

function handleChangePassword(event) {
  event.preventDefault();

  const currentPasswordInput = document.getElementById('current-password');
  const newPasswordInput = document.getElementById('new-password');
  const confirmPasswordInput = document.getElementById('confirm-password');

  const currentPassword = currentPasswordInput ? currentPasswordInput.value : '';
  const newPassword = newPasswordInput ? newPasswordInput.value : '';
  const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';

  if (newPassword !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }

  if (newPassword.length < 8) {
    alert('Password must be at least 8 characters.');
    return;
  }

  alert('Password updated successfully!');

  if (currentPasswordInput) currentPasswordInput.value = '';
  if (newPasswordInput) newPasswordInput.value = '';
  if (confirmPasswordInput) confirmPasswordInput.value = '';
}

function handleAddStudent(event) {
  event.preventDefault();

  const nameInput = document.getElementById('student-name');
  const idInput = document.getElementById('student-id');
  const emailInput = document.getElementById('student-email');
  const defaultPasswordInput = document.getElementById('default-password');

  const name = nameInput ? nameInput.value.trim() : '';
  const id = idInput ? idInput.value.trim() : '';
  const email = emailInput ? emailInput.value.trim() : '';

  if (!name || !id || !email) {
    alert('Please fill out all required fields.');
    return;
  }

  const duplicate = students.some(student => student.id === id);
  if (duplicate) {
    alert('A student with this ID already exists.');
    return;
  }

  const newStudent = { name, id, email };
  students.push(newStudent);
  renderTable(students);

  if (nameInput) nameInput.value = '';
  if (idInput) idInput.value = '';
  if (emailInput) emailInput.value = '';
  if (defaultPasswordInput) defaultPasswordInput.value = '';
}

function handleTableClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  if (target.classList.contains('delete-btn')) {
    const id = target.dataset.id;
    if (!id) return;
    students = students.filter(student => student.id !== id);
    renderTable(students);
  } else if (target.classList.contains('edit-btn')) {
    const id = target.dataset.id;
    if (!id) return;
    const student = students.find(s => s.id === id);
    if (!student) return;
    const newName = prompt('Edit name:', student.name);
    const newEmail = prompt('Edit email:', student.email);
    if (newName && newEmail) {
      student.name = newName.trim();
      student.email = newEmail.trim();
      renderTable(students);
    }
  }
}

function handleSearch(event) {
  const term = event.target.value.toLowerCase().trim();
  if (!term) {
    renderTable(students);
    return;
  }

  const filtered = students.filter(student =>
    student.name.toLowerCase().includes(term)
  );
  renderTable(filtered);
}

function handleSort(event) {
  const th = event.currentTarget;
  if (!(th instanceof HTMLElement)) return;

  const index = th.cellIndex;
  let key = null;

  if (index === 0) key = 'name';
  if (index === 1) key = 'id';
  if (index === 2) key = 'email';
  if (!key) return;

  const currentDir = th.dataset.sortDir === 'asc' ? 'asc' : 'desc';
  const newDir = currentDir === 'asc' ? 'desc' : 'asc';
  th.dataset.sortDir = newDir;

  tableHeaders.forEach(header => {
    if (header !== th) {
      header.removeAttribute('data-sort-dir');
    }
  });

  students.sort((a, b) => {
    let result = 0;

    if (key === 'id') {
      const aNum = Number(a.id);
      const bNum = Number(b.id);
      if (!isNaN(aNum) && !isNaN(bNum)) {
        result = aNum - bNum;
      } else {
        result = String(a.id).localeCompare(String(b.id));
      }
    } else {
      result = String(a[key]).localeCompare(String(b[key]));
    }

    return newDir === 'asc' ? result : -result;
  });

  renderTable(students);
}

async function loadStudentsAndInitialize() {
  try {
    const response = await fetch('students.json');
    if (!response.ok) {
      console.error('Failed to fetch students.json:', response.status, response.statusText);
    } else {
      const data = await response.json();
      if (Array.isArray(data)) {
        students = data;
      } else {
        students = [];
      }
    }
  } catch (error) {
    console.error('Error loading students:', error);
    students = [];
  }

  renderTable(students);

  if (changePasswordForm) {
    changePasswordForm.addEventListener('submit', handleChangePassword);
  }
  if (addStudentForm) {
    addStudentForm.addEventListener('submit', handleAddStudent);
  }
  if (studentTableBody) {
    studentTableBody.addEventListener('click', handleTableClick);
  }
  if (searchInput) {
    searchInput.addEventListener('input', handleSearch);
  }
  tableHeaders.forEach(th => {
    th.addEventListener('click', handleSort);
  });
}

loadStudentsAndInitialize();
