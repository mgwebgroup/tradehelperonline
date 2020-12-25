import $ from 'jquery';

import '../scss/study.scss';

window.onload = function()
{
    // AS List symbols are clickable
    $('.symbol').click(function() {
        var date = $(this).attr('date');
        var symbol = $(this).attr('symbol');

        fetchChartWindow(symbol, date);
    });

    var date = $('.studydate').attr('studydate');
    fetchChartWindow('SPY', date);


    // make sectors table sortable
    const getCellValue = (tr, idx) => tr.children[idx].innerText || tr.children[idx].textContent;
    const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
            v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
    )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

    document.querySelectorAll('.sectors .table th.sortable').forEach(th => th.addEventListener('click', (() => {
        const table = th.closest('table');
        const tbody = table.querySelector('tbody');
        Array.from(tbody.querySelectorAll('tr:nth-child(-n+11)')).sort(comparer(Array.from(th.parentNode.children).indexOf(th), this.asc = !this.asc)).forEach(tr => tbody.appendChild(tr) );

        // move last 2 summary rows back to bottom
        tbody.querySelectorAll('tr:nth-child(-n+2)').forEach(tr => tbody.appendChild(tr));
    })));
}

function fetchChartWindow(symbol, date)
{
    var url = '/market-survey/'+symbol+'/'+date;

    $.ajax({
        url: url,
        dataType: 'xml', // sets Accept: application/xml,text/xml, This is needed to distinguish this request as API call in the CalendarController
        converters: {
            "text xml": window.String // we are expecting xml content, and html comes back. This specifies to convert it into a string, which is then used in $.html
        },
        success: function(data, status, jqXHR) {
            // console.log(data);
            $('.as .chart-window-wrapper').replaceWith(data);
        }
    });
}