document.addEventListener("DOMContentLoaded", function () {
    const panelBox = document.querySelector(".mojo_panel-box");
    const buttons = panelBox.querySelectorAll(".mojo_panel-tabs button");

    buttons.forEach((button, index) => {
        button.addEventListener("click", () => {
            // Cambiar el atributo data-state
            panelBox.setAttribute("data-state", index + 1);
        });
    });
});