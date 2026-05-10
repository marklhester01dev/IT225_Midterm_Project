const form = document.querySelector("form");
const userPassword = document.getElementById("new_password");
const confirmPassword = document.getElementById("confirm_password");
const inputMessages = document.querySelectorAll(".input_message");
const messages = {
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

function validatePassword() {
  const value = userPassword.value.trim();
  return value.length >= 6;
}

function validateConfirm() {
  return userPassword.value === confirmPassword.value;
}

function validateForm() {
  validatePassword()
    ? removeError(userPassword, inputMessages[0])
    : addError(userPassword, inputMessages[0], messages.password);
  validateConfirm()
    ? removeError(confirmPassword, inputMessages[1])
    : addError(confirmPassword, inputMessages[1], messages.confirm);

  return validatePassword() && validateConfirm();
}

userPassword.addEventListener("input", () => {
  const value = userPassword.value.trim();
  if (value) {
    validatePassword()
      ? removeError(userPassword, inputMessages[0])
      : addError(userPassword, inputMessages[0], messages.password);
  } else {
    removeError(userPassword, inputMessages[0]);
  }
});

confirmPassword.addEventListener("input", () => {
  validateConfirm()
    ? removeError(confirmPassword, inputMessages[1])
    : addError(confirmPassword, inputMessages[1], messages.confirm);
});

userPassword.addEventListener("blur", () => {
  const value = userPassword.value.trim();
  if (value && !validatePassword()) {
    addError(userPassword, inputMessages[0], messages.password);
  }
});

form.addEventListener("submit", (e) => {
  if (!validateForm()) {
    e.preventDefault();
    form.reportValidity();
  }
});
