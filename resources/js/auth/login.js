const form = document.querySelector("form");
const userName = document.getElementById("username");
const userPassword = document.getElementById("password");
const inputMessages = document.querySelectorAll(".input_message");
const messages = {
  username: "Username must be 3-20 characters (letters, numbers only).",
  password: "Password must be 6+ characters.",
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

function validatePassword() {
  const value = userPassword.value.trim();
  const regex = /^.{6,}$/;
  return regex.test(value);
}

function validateForm() {
  const isUsernameValid = validateUsername();
  const isPasswordValid = validatePassword();

  if (!isUsernameValid) {
    addError(userName, inputMessages[0], messages.username);
  } else {
    removeError(userName, inputMessages[0]);
  }

  if (!isPasswordValid) {
    addError(userPassword, inputMessages[1], messages.password);
  } else {
    removeError(userPassword, inputMessages[1]);
  }

  return isUsernameValid && isPasswordValid;
}

// Real-time validation on input and blur
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

userPassword.addEventListener("input", () => {
  const value = userPassword.value.trim();
  if (value) {
    validatePassword()
      ? removeError(userPassword, inputMessages[1])
      : addError(userPassword, inputMessages[1], messages.password);
  } else {
    removeError(userPassword, inputMessages[1]);
  }
});

userPassword.addEventListener("blur", () => {
  const value = userPassword.value.trim();
  if (value && !validatePassword()) {
    addError(userPassword, inputMessages[1], messages.password);
  }
});

// Form submission
form.addEventListener("submit", (e) => {
  if (!validateForm()) {
    e.preventDefault();
    form.reportValidity();
  }
});
