document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("personalDetailsForm");
  if (!form) return;
  const message = document.getElementById("personalDetailsMessage");
  let loaded = null;

  const setMessage = (text = "", success = false) => {
    message.textContent = text;
    message.classList.toggle("success", success);
  };

  const validator = window.SharedValidator
    ? window.SharedValidator.attachRealtimeValidation(form, {
        getInitialData: () => loaded || {},
        ajaxCheckUrl: "../auth/index.php",
      })
    : null;

  const setValues = (data) => {
    ["id_number","username","email","role","status","first_name","middle_name","last_name","extension","birthdate","age","gender","street","barangay","city","province","country","zip"].forEach((name) => {
      if (form.elements[name]) form.elements[name].value = data[name] || "";
    });
    for (let index = 1; index <= 3; index += 1) {
      const question = (data.security_questions || [])[index - 1];
      form.elements[`security_question_${index}`].value = question ? String(question.question_id) : "";
      form.elements[`security_answer_${index}`].value = "";
    }
    if (validator) validator.clearAll();
  };

  const load = async () => {
    setMessage("Loading personal details...");
    try {
      const response = await fetch("../auth/index.php?action=getPersonalDetails", {credentials:"same-origin"});
      const data = await response.json();
      if (!data.success) throw new Error(data.message || "Unable to load details.");
      loaded = data.data;
      setValues(loaded);
      setMessage();
    } catch (error) {
      setMessage(error.message);
    }
  };

  form.addEventListener("reset", (event) => {
    event.preventDefault();
    if (validator) validator.clearAll();
    if (loaded) setValues(loaded);
    setMessage();
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    setMessage();
    if (validator && !validator.validateAll()) {
      setMessage("Please correct the highlighted fields.");
      return;
    }
    if (!window.confirm("Are you sure you want to edit your Personal Details?")) return;
    const submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      const response = await fetch("../auth/index.php?action=updatePersonalDetails", {
        method: "POST",
        credentials: "same-origin",
        body: new FormData(form),
      });
      const data = await response.json();
      if (!data.success) {
        if (data.fieldErrors && typeof data.fieldErrors === "object") {
          const errorList = Object.values(data.fieldErrors);
          setMessage(errorList[0] || data.message || "Please correct the highlighted fields.");
          Object.entries(data.fieldErrors).forEach(([field, msg]) => {
            if (validator) {
              validator.setFieldError(field, msg);
            } else {
              const el = form.elements[field];
              if (el) el.style.borderColor = "#ef4444";
            }
          });
        } else {
          setMessage(data.message || "Unable to save details.");
        }
        return;
      }
      await load();
      setMessage(data.message, true);
    } catch (error) {
      setMessage("Unable to connect. Please try again.");
    } finally {
      submit.disabled = false;
    }
  });

  load();
});
