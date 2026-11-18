document.querySelectorAll("[data-password-toggle]").forEach((button) => {
    const input = document.getElementById(button.dataset.passwordToggle);

    if (!input) {
        return;
    }

    button.addEventListener("click", () => {
        const isPasswordVisible = input.type === "password";
        input.type = isPasswordVisible ? "text" : "password";
        button.textContent = isPasswordVisible ? "Sembunyikan" : "Tampilkan";
        button.setAttribute("aria-label", isPasswordVisible ? "Sembunyikan password" : "Tampilkan password");
        button.setAttribute("aria-pressed", String(isPasswordVisible));
    });
});
