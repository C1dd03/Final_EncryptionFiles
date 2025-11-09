document.addEventListener("DOMContentLoaded", () => {
  const usernameInput = document.querySelector("input[name='username']");
  const form = document.querySelector(".register-form");

  // Create or get the inline error span
  let usernameError = document.getElementById("username-error");
  if (!usernameError) {
    usernameError = document.createElement("span");
    usernameError.id = "username-error";
    usernameError.style.color = "red";
    usernameError.style.fontSize = "12px";
    usernameInput.parentNode.appendChild(usernameError);
  }

  // Check username on blur
  usernameInput.addEventListener("blur", () => {
    const username = usernameInput.value.trim();
    if (username === "") {
      usernameInput.dataset.taken = "false";
      usernameError.textContent = "";
      return;
    }



    fetch("index.php?action=checkUsername", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "username=" + encodeURIComponent(username)
    })
    .then(response => response.text())
    .then(result => {
      if (result === "taken") {
        usernameInput.dataset.taken = "true";
        usernameError.textContent = "Username is already taken. Please choose another.";
        usernameInput.focus();
      } else {
        usernameInput.dataset.taken = "false";
        usernameError.textContent = "";
      }
    })
    .catch(error => {
      console.error("AJAX Error:", error);
      usernameInput.dataset.taken = "false";
    });
  });

  // Prevent form submission if username is taken
  form.addEventListener("submit", (e) => {
    if (usernameInput.dataset.taken === "true") {
      e.preventDefault();
      usernameError.textContent = "⚠️ Please choose a different username before submitting.";
      usernameInput.focus();
    }
  });
});
