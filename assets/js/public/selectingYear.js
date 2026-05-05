let select_year = document.getElementById('select_year');

if (select_year) {
    select_year.addEventListener('change', function () {
        const year = this.value;

        // Tomamos la URL actual
        const url = new URL(window.location.href);

        // Seteamos / reemplazamos el parámetro period
        url.searchParams.set('period', year);

        // Redirigimos a la nueva URL
        window.location.href = url.toString();
    });
}