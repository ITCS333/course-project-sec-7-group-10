
const weeksData = {
  1: {
    title: "Week 1: Introduction to HTML",
    description: "This week covers the basics of HTML structure.",
    notes: "Practice headings, paragraphs, and links.",
    resources: ["HTML Basics Exercise"]
  },
  2: {
    title: "Week 2: HTML Tags",
    description: "Learn about different HTML tags and their usage.",
    notes: "Practice lists, images, and tables.",
    resources: ["HTML Tags Practice"]
  },
  3: {
    title: "Week 3: CSS Basics",
    description: "Introduction to CSS styling for HTML pages.",
    notes: "Practice colors, fonts, and layout.",
    resources: ["CSS Basics Exercise"]
  }
};


const params = new URLSearchParams(window.location.search);
const weekNumber = params.get("week");


if (weeksData[weekNumber]) {
  const data = weeksData[weekNumber];
  document.getElementById("week-title").textContent = data.title;
  document.getElementById("week-description").textContent = data.description;
  document.getElementById("week-notes").textContent = data.notes;

  const resourcesList = document.getElementById("week-resources");
  resourcesList.innerHTML = "";
  data.resources.forEach(item => {
    const li = document.createElement("li");
    li.textContent = item;
    resourcesList.appendChild(li);
  });
}


const commentForm = document.getElementById("comment-form");
const commentsContainer = document.getElementById("comments-container");


let comments = JSON.parse(localStorage.getItem("comments_week_" + weekNumber)) || [];


comments.forEach(text => {
  const p = document.createElement("p");
  p.textContent = text;
  commentsContainer.appendChild(p);
});


commentForm.addEventListener("submit", (e) => {
  e.preventDefault();
  const textarea = commentForm.querySelector("textarea");
  const commentText = textarea.value.trim();
  if(commentText !== "") {
    const p = document.createElement("p");
    p.textContent = commentText;
    commentsContainer.appendChild(p);

    
    comments.push(commentText);
    localStorage.setItem("comments_week_" + weekNumber, JSON.stringify(comments));

    textarea.value = "";
  }
});
