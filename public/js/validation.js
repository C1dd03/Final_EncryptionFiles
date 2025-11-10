document.getElementById("birthdate").addEventListener("change", function () {
  const birthDate = new Date(this.value);
  if (!this.value) return; // if empty, skip

  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();

  // Adjust if birthday hasn't occurred yet this year
  if (
    monthDiff < 0 ||
    (monthDiff === 0 && today.getDate() < birthDate.getDate())
  ) {
    age--;
  }

  // Display age in the input field
  document.getElementById("age").value = age;
});
