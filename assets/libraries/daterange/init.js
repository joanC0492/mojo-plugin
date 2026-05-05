$(function() {
    var today = new Date();
    var formattedDate = (today.getMonth() + 1) + '/' + today.getDate() + '/' + today.getFullYear();

    $('input[name="daterange"]').daterangepicker({
        "autoApply": true,
        "startDate": formattedDate,
        "opens": "center",
        "drops": "up"
    });
});