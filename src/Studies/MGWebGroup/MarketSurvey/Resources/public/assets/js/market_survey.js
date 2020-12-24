import $ from 'jquery';

import '../scss/study.scss';

window.onload = function()
{
    $('.symbol').click(function() {
        var date = $(this).attr('date');
        var symbol = $(this).attr('symbol');

        // console.log('date='+date+' symbol='+symbol);
        fetchChartWindow(symbol, date);
    });
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