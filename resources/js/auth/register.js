const form = document.querySelector("form");
const fullName = document.getElementById("fullname");
const userName = document.getElementById("username");
const userPassword = document.getElementById("password");
const confirmPassword = document.getElementById("confirm_password");
const inputMessages = document.querySelectorAll(".input_message");
const messages = {
  fullname: "Full name must be 3+ characters.",
  username: "Username must be 3-20 characters (letters, numbers only).",
  password: "Password must be 6+ characters.",
  confirm: "Passwords must match.",
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

function validateFullname() {
  const value = fullName.value.trim();
  return value.length >= 3;
}

function validateUsername() {
  const value = userName.value.trim();
  const regex = /^[a-zA-Z0-9]{3,20}$/;
  return regex.test(value);
}

function validatePassword() {
  const value = userPassword.value.trim();
  return value.length >= 6;
}

function validateConfirm() {
  return userPassword.value === confirmPassword.value;
}

function validateForm() {
  validateFullname()
    ? removeError(fullName, inputMessages[0])
    : addError(fullName, inputMessages[0], messages.fullname);
  validateUsername()
    ? removeError(userName, inputMessages[1])
    : addError(userName, inputMessages[1], messages.username);
  validatePassword()
    ? removeError(userPassword, inputMessages[2])
    : addError(userPassword, inputMessages[2], messages.password);
  validateConfirm()
    ? removeError(confirmPassword, inputMessages[3])
    : addError(confirmPassword, inputMessages[3], messages.confirm);

  return (
    validateFullname() &&
    validateUsername() &&
    validatePassword() &&
    validateConfirm()
  );
}

// Real-time validation
[fullName, userName, userPassword, confirmPassword].forEach((input, index) => {
  input.addEventListener("input", () => {
    const value = input.value.trim();
    if (value) {
      const validators = [
        validateFullname,
        validateUsername,
        validatePassword,
        validateConfirm,
      ];
      validators[index]()
        ? removeError(input, inputMessages[index])
        : addError(
            input,
            inputMessages[index],
            messages[Object.keys(messages)[index]],
          );
    } else {
      removeError(input, inputMessages[index]);
    }
  });
});

confirmPassword.addEventListener("input", () => {
  const passwordValue = userPassword.value.trim();
  const confirmValue = confirmPassword.value.trim();
  if (confirmValue || passwordValue) {
    validateConfirm()
      ? removeError(confirmPassword, inputMessages[3])
      : addError(confirmPassword, inputMessages[3], messages.confirm);
  } else {
    removeError(confirmPassword, inputMessages[3]);
  }
});

// Blur events
[fullName, userName, userPassword].forEach((input, index) => {
  input.addEventListener("blur", () => {
    const value = input.value.trim();
    if (value) {
      const validators = [validateFullname, validateUsername, validatePassword];
      if (!validators[index]()) {
        addError(
          input,
          inputMessages[index],
          messages[Object.keys(messages)[index]],
        );
      }
    }
  });
});

// Form submission
form.addEventListener("submit", (e) => {
  if (!validateForm()) {
    e.preventDefault();
    form.reportValidity();
  }
});
