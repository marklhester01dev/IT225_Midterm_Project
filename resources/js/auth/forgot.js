const form = document.querySelector("form");
const userName = document.getElementById("username");
const inputMessages = document.querySelectorAll(".input_message");
const messages = {
  username: "Username must be 3-20 characters (letters, numbers only).",
};

function addError(input, messageEl, msg) {
  input.classList.add("error");
  input.setCustomValidity(msg);
  messageEl.textContent = msg;
  messageEl.classList.add("error");
}

function removeError(input, messageEl) {
  input.classList.remove("error");
  input.setCustomValidity("");
  messageEl.textContent = "";
  messageEl.classList.remove("error");
}

function validateUsername() {
  const value = userName.value.trim();
  const regex = /^[a-zA-Z0-9]{3,20}$/;
  return regex.test(value);
}

function validateForm() {
  const isUsernameValid = validateUsername();

  if (!isUsernameValid) {
    addError(userName, inputMessages[0], messages.username);
  } else {
    removeError(userName, inputMessages[0]);
  }

  return isUsernameValid;
}

userName.addEventListener("input", () => {
  const value = userName.value.trim();
  if (value) {
    validateUsername()
      ? removeError(userName, inputMessages[0])
      : addError(userName, inputMessages[0], messages.username);
  } else {
    removeError(userName, inputMessages[0]);
  }
});

userName.addEventListener("blur", () => {
  const value = userName.value.trim();
  if (value && !validateUsername()) {
    addError(userName, inputMessages[0], messages.username);
  }
});

form.addEventListener("submit", (e) => {
  if (!validateForm()) {
    e.preventDefault();
    form.reportValidity();
  }
});
