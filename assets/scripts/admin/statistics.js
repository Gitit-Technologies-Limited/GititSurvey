
function hideSection(section) {
    var collapsible = document.getElementById(section);
    // Try to get the bootstrap collapse instance
    var bsCollapse = bootstrap.Collapse.getInstance(collapsible);
    // If there is no previous instance, create a new one
    if (!bsCollapse) {
        bsCollapse = new bootstrap.Collapse(collapsible);
    }
    bsCollapse.hide();
}

/**
 * Display chartjs graph
 */
var chartjs = new Array();
// Updated color palette with primary color #122867
var COLORS_FOR_SURVEY = new Array(
    '18,40,103',    // Primary #122867
    '26,52,128',    // Primary variant 1
    '45,74,158',    // Primary variant 2
    '61,90,254',    // Accent blue
    '29,78,216',    // Deep blue
    '37,99,235',    // Bright blue
    '59,130,246',   // Sky blue
    '96,165,250',   // Light blue
    '147,197,253',  // Lighter blue
    '191,219,254',  // Pale blue
    '16,185,129',   // Teal accent
    '5,150,105',    // Dark teal
    '20,184,166',   // Turquoise
    '45,212,191',   // Light teal
    '99,102,241',   // Indigo
    '79,70,229',    // Deep indigo
    '124,58,237',   // Purple
    '168,85,247',   // Light purple
    '232,95,51',    // Orange accent
    '251,146,60',   // Light orange
    '34,197,94',    // Green
    '134,239,172',  // Light green
    '251,191,36',   // Yellow
    '253,224,71',   // Light yellow
    // Continue with variations
    '18,40,103', '26,52,128', '45,74,158', '61,90,254', '29,78,216', '37,99,235',
    '59,130,246', '96,165,250', '147,197,253', '191,219,254', '16,185,129', '5,150,105',
    '20,184,166', '45,212,191', '99,102,241', '79,70,229', '124,58,237', '168,85,247',
    '232,95,51', '251,146,60', '34,197,94', '134,239,172', '251,191,36', '253,224,71',
    '18,40,103', '26,52,128', '45,74,158', '61,90,254', '29,78,216', '37,99,235',
    '59,130,246', '96,165,250', '147,197,253', '191,219,254', '16,185,129', '5,150,105',
    '20,184,166', '45,212,191', '99,102,241', '79,70,229', '124,58,237', '168,85,247',
    '232,95,51', '251,146,60', '34,197,94', '134,239,172', '251,191,36', '253,224,71'
);
var initChartGraph = function (element, type, qid) {
    if (typeof chartjs[qid] == "undefined" || typeof chartjs == "undefined") // typeof chartjs[$qid] == "undefined" || typeof chartjs == "undefined"
    {
        if (type === 'Bar' || type === 'Radar' || type === 'Line' || type === 'Doughnut' || type === 'Pie' || type === 'PolarArea') {
            init_chart_js_graph_with_datasets(type, qid);
        } else {
            init_chart_js_graph_with_datas(type, qid);
        }
    }
};
/**
 * loadGraphOnScroll jQuery plugin
 * This plugin will load graph on scroll
 */
(function ($) {
    $.fn.loadGraphOnScroll = function () {
        this.each(function () {
            var $elem = $(this);
            var $type = $elem.data('type');
            var $qid = $elem.data('qid');

            $(window).scroll(function () {
                var $window = $(window);
                var docViewTop = $window.scrollTop();
                var docViewBottom = docViewTop + $window.height();
                var elemTop = $elem.offset().top;
                var elemBottom = elemTop + $elem.height();

                if ((elemBottom <= docViewBottom) && (elemTop >= docViewTop)) {
                    // chartjs
                    initChartGraph($elem, $type, $qid);
                };
            });
        });
        return this;
    };
})(jQuery);

(function ($) {
    $.fn.loadGraph = function () {
        this.each(function () {
            var $elem = $(this);
            var $type = $elem.data('type');
            var $qid = $elem.data('qid');
            // chartjs
            initChartGraph($elem, $type, $qid);
        });
        return this;
    };
})(jQuery);

function parseType(typeDef) {
    switch(typeDef) {
        case "Bar":
        case "bar":
            return "bar";
        
        case "Pie":
        case "pie":
            return "pie";

        case "Radar":
        case "radar":
            return "radar";
        
        case "Line":
        case "line":
            return "line";
        
        case "PolarArea":
        case "polarArea":
        case "polararea":
            return "polarArea";
        
        case "Doughnut":
        case "doughnut":
            return "doughnut";

    }
}

/**
 * This function load the graph needing datasets (bars, etc.)
 */
function init_chart_js_graph_with_datasets($type, $qid) {
    var canvasId = 'chartjs-' + $qid;
    var $canvas = document.getElementById(canvasId).getContext("2d");
    var $canva = $('#' + canvasId);
    var $container = $('#chartjs-container-' + $qid);
    var $statistics = statisticsData['quid' + $qid];
    if ($statistics == undefined) return;
    var $labels = $statistics.labels
    var $grawdata = $statistics.grawdata
    var $color = $canva.data('color');
    var $chartTitle = $statistics.title || '';

    if (typeof chartjs != "undefined") {
        if (typeof chartjs[$qid] != "undefined") {
            window.chartjs[$qid].destroy();
        }
    }

    var dataDefinition = {
        labels: $labels,
    };

    dataDefinition.datasets = [{
        label: $chartTitle || 'Responses',
        data: $grawdata,
        backgroundColor: [],
        borderColor: [],
        hoverBackgroundColor: [],
        pointBackgroundColor: "#fff",
        pointHoverBackgroundColor: "#fff",
        pointHoverBorderColor: []
    }];

    // different color for each bar
    LS.ld.forEach($labels, function (label, key) {
        var colorIndex = (parseInt(key) + $color);
        dataDefinition.datasets[0].backgroundColor.push("rgba(" + COLORS_FOR_SURVEY[colorIndex] + ",0.6)");
        dataDefinition.datasets[0].borderColor.push("rgba(" + COLORS_FOR_SURVEY[colorIndex] + ",1)");
        dataDefinition.datasets[0].hoverBackgroundColor.push("rgba(" + COLORS_FOR_SURVEY[colorIndex] + ",0.9)");
        dataDefinition.datasets[0].pointHoverBorderColor.push("rgba(" + COLORS_FOR_SURVEY[colorIndex] + ",1)");
    });

    var parsedType = parseType($type);
    var options = {
        title: {
            display: true,
            text: $chartTitle,
            fontSize: 16,
            fontStyle: 'bold',
            padding: 20
        },
        legend: {
            display: true,
            position: 'bottom',
            labels: {
                padding: 15,
                fontSize: 12,
                fontColor: '#333',
                generateLabels: function(chart) {
                    var data = chart.data;
                    if (data.labels.length && data.datasets.length) {
                        return data.labels.map(function(label, i) {
                            var meta = chart.getDatasetMeta(0);
                            var ds = data.datasets[0];
                            var value = ds.data[i];
                            return {
                                text: label + ' (' + value + ')',
                                fillStyle: ds.backgroundColor[i],
                                strokeStyle: ds.borderColor[i],
                                lineWidth: 1,
                                hidden: false,
                                index: i
                            };
                        });
                    }
                    return [];
                }
            }
        },
        tooltips: {
            enabled: true,
            callbacks: {
                label: function(tooltipItem, data) {
                    var label = data.labels[tooltipItem.index] || '';
                    var value = data.datasets[0].data[tooltipItem.index];
                    return label + ': ' + value;
                }
            }
        }
    };

    if (parsedType == 'bar' || parsedType == 'line') {
        options.scales = {
            yAxes: [{
                ticks: {
                    suggestedMin: 0,
                }
            }]
        };
    }

    console.ls.log("Creating chart with definition: ", dataDefinition);

    window.chartjs[$qid] = new Chart($canvas, {
        type: parsedType,
        data: dataDefinition,
        options: options,
    });
}

/**
 * This function load the graphs needing datas (pie chart, polar, Doughnut)
 */
function init_chart_js_graph_with_datas($type, $qid) {
    var canvasId = 'chartjs-' + $qid;
    var $canvas = document.getElementById(canvasId).getContext("2d");
    var $canva = $('#' + canvasId);
    var $container = $('#chartjs-container-' + $qid);
    var $color = $canva.data('color');
    var $statistics = statisticsData['quid' + $qid];
    if ($statistics == undefined) return;
    var $labels = $statistics.labels
    var $grawdata = $statistics.grawdata
    var $chartTitle = $statistics.title || '';
    var $chartDef = {
        labels: $labels,
        datasets: [{
            data: [],
            backgroundColor: [],
            hoverBackgroundColor: [],
        }],
    };
    var $max = 0;

    $.each($labels, function($i, $label) {
        $max = $max + parseInt($grawdata[$i]);
    });

    $.each($labels, function ($i, $label) {
        var colorIndex = (parseInt($i) + $color);
        $chartDef.datasets[0].data.push(Math.round($grawdata[$i]/$max * 100 * 100) / 100);
        $chartDef.datasets[0].backgroundColor.push("rgba(" + COLORS_FOR_SURVEY[colorIndex] + ",0.6)");
        $chartDef.datasets[0].hoverBackgroundColor.push("rgba(" + COLORS_FOR_SURVEY[colorIndex] + ",0.9)");
    });

    var parsedType = parseType($type);
    var $options = {
        tooltipTemplate: "<%if (label){%><%=label %>: <%}%><%= value + '%' %>",
        title: {
            display: true,
            text: $chartTitle,
            fontSize: 16,
            fontStyle: 'bold',
            padding: 20
        },
        legend: {
            display: true,
            position: 'bottom',
            labels: {
                padding: 15,
                fontSize: 12,
                fontColor: '#333',
                generateLabels: function(chart) {
                    var data = chart.data;
                    if (data.labels.length && data.datasets.length) {
                        return data.labels.map(function(label, i) {
                            var ds = data.datasets[0];
                            var value = ds.data[i];
                            // Get original count from grawdata
                            var count = $grawdata[i];
                            return {
                                text: label + ' (' + count + ' - ' + value + '%)',
                                fillStyle: ds.backgroundColor[i],
                                strokeStyle: ds.backgroundColor[i],
                                lineWidth: 1,
                                hidden: false,
                                index: i
                            };
                        });
                    }
                    return [];
                }
            }
        },
        tooltips: {
            enabled: true,
            callbacks: {
                label: function(tooltipItem, data) {
                    var label = data.labels[tooltipItem.index] || '';
                    var value = data.datasets[0].data[tooltipItem.index];
                    var count = $grawdata[tooltipItem.index];
                    return label + ': ' + count + ' (' + value + '%)';
                }
            }
        }
    };

    if (parsedType == 'bar' || parsedType == 'line') {
        $options.scales = {
            yAxes: [{
                ticks: {
                    suggestedMin: 0,
                }
            }]
        };
    }

    if (typeof chartjs != "undefined") {
        if (typeof chartjs[$qid] != "undefined") {
            window.chartjs[$qid].destroy();
        }
    }

    console.ls.log("Creating chart with definition: ", $chartDef);

    window.chartjs[$qid] = new Chart($canvas, {
        type: parsedType,
        data: $chartDef,
        options: $options
    });
}

LS.Statistics2 = function () {

    // Enable legends for better chart understanding when exporting
    Chart.defaults.global.legend.display = true;
    Chart.defaults.global.legend.position = 'bottom';
    Chart.defaults.global.legend.labels = {
        padding: 15,
        fontSize: 12,
        fontColor: '#333',
        usePointStyle: true,
        boxWidth: 15
    };

    if ($('#completionstateSimpleStat').length > 0) {
        $actionUrl = $('#completionstateSimpleStat').data('grid-display-url');

        $(document).on("change", '#completionstate', function () {
            $that = $(this);
            $actionUrl = $(this).data('url');
            $display = $that.val();
            $postDatas = { state: $display };

            $.ajax({
                url: $actionUrl,
                type: 'POST',
                data: $postDatas,

                // html contains the buttons
                success: function (html, statut) {
                    // Reload page
                    location.reload();
                },
                error: function (html, statut) {
                    console.ls.error(html);
                }
            });

        });
    }

    if ($('.chartjs-container').length > 0) {
        $elChartJsContainer = $('.chartjs-container').first();
        $('.canvas-chart').width($elChartJsContainer.width());
    }

    if ($('#showGraphOnPageLoad').length > 0) {
        $('#statisticsoutput .row').first().find('.chartjs-container').loadGraph();
    }

    $('#generate-statistics').submit(function () {
        hideSection('general-filters-item-body');
        hideSection('response-filters-item-body');
        $('#statisticsoutput').show();
        $('#view-stats-alert-info').hide();
        $('#statsContainerLoading').show();
        if ($('input[name=outputtype]:checked').val() != 'html') {
            var data = new FormData($(this).get(0));
            var url = $(this).attr('action');
            ajaxDownloadStats(url, data);
            return false;
        }
        //alert('ok');
    });

    // If the graph are displayed
    if ($('.chartjs-container').length > 0) {

        // On scroll, display the graph
        $('.chartjs-container').loadGraphOnScroll();

        // Buttons changing the graph type
        $('.chart-type-control').click(function () {

            $type = $(this).data('type');
            $qid = $(this).data('qid');

            // Update active button state
            $(this).siblings('.chart-type-control').removeClass('active');
            $(this).addClass('active');

            // Update the data-type attribute on the container for export functions
            $('#chartjs-container-' + $qid).attr('data-type', $type);

            // chartjs
            if ($type === 'Bar' || $type === 'Radar' || $type === 'Line' || $type === 'Doughnut' || $type === 'Pie' || $type === 'PolarArea') {
                init_chart_js_graph_with_datasets($type, $qid);
            } else {
                init_chart_js_graph_with_datas($type, $qid);
            }
        });

        // Download chart image button
        $('.btn-download-chart').click(function () {
            var qid = $(this).data('qid');
            downloadChartImage(qid);
        });

        // Download CSV button
        $('.btn-download-csv').click(function () {
            var qid = $(this).data('qid');
            downloadQuestionCSV(qid);
        });

    }

    /**
     * Load responses for one question.
     * Used at question summary.
     */
    var loadBrowse = (function () {

        // Static variable for function loadBrowse, catched through closure
        // Use this to track if we should hide/show responses
        var toggle = {};

        var fn = function loadBrowse(id, extra) {

            var destinationdiv = $('#columnlist_' + id);

            // First time initialization
            if (toggle[id] === undefined) {
                toggle[id] = 0;
            }
            toggle[id] = 1 - toggle[id]; // Switch between 1 and 0

            if (toggle[id] === 0) {
                $('#' + id).parent().find('.statisticscolumndata, .statisticscolumnid').remove();
                return;
            }

            if (extra == '') {
                destinationdiv.parents("td:first").toggle();
            } else {
                destinationdiv.parents("td:first").show();
            }

            if (destinationdiv.parents("td:first").css("display") != "none") {
                $.get(listColumnUrl + '/' + id + '/' + extra, function (data) {
                    $('#' + id).parent().append(data);
                });
            }
        };

        // Closure return function
        return fn;
    })();

    if (showTextInline == 1) {
        /* Enable all the browse divs, and fill with data */
        $('.statisticsbrowsebutton').each(function () {
            if (!$(this).hasClass('numericalbrowse')) {
                loadBrowse(this.id, '');
            }
        });
    }
    $('.statisticsbrowsebutton').click(function () {
        if ($(this).hasClass('numericalbrowse')) {
            var destinationdiv = $('#columnlist_' + this.id);
            var extra = '';
            if (destinationdiv.parents("td:first").css("display") == "none") {
                extra = 'sortby/' + this.id + '/sortmethod/asc/sorttype/N/';
            }
            loadBrowse(this.id, extra);
        } else {
            loadBrowse(this.id, '');
        }

    });
    $(".sortorder").click(function (e) {
        var details = this.id.split('_');
        var order = 'sortby/' + details[2] + '/sortmethod/' + details[3] + '/sorttype/' + details[4];
        loadBrowse(details[1], order);
    });

    $('#usegraph').click(function () {
        if ($('#grapherror').length > 0) {
            $('#grapherror').show();
            $('#usegraph_2').prop('checked', true);
        }
    });

    /***
     * Select all questions
     */
    let viewsummaryallbuttons = document.querySelectorAll('input[name="viewsummaryall"]');
    for (let viewsummaryallbutton of viewsummaryallbuttons) {
        viewsummaryallbutton.addEventListener("change", () => {
            if (viewsummaryallbutton.value === '1') {
                let filterchoices = document.querySelectorAll('#filterchoices input[type=checkbox]');
                filterchoices.forEach((filterchoice) => {
                    filterchoice.checked = true;
                });
            } else {
                let filterchoices = document.querySelectorAll('#filterchoices input[type=checkbox]');
                filterchoices.forEach((filterchoice) => {
                    filterchoice.checked = false;
                });
            }
        });
    }

    /* Show and hide the three major sections of the statistics page */
    /* The response filters */
    $('#hidefilter').click(function () {
        $('#statisticsresponsefilters').hide();
        $('#filterchoices').hide();
        $('#filterchoice_state').val('1');
        $('#vertical_slide2').hide();
    });
    $('#showfilter').click(function () {
        $('#statisticsresponsefilters').show();
        $('#filterchoices').show();
        $('#filterchoice_state').val('');
        $('#vertical_slide2').show();
    });
    /* The general settings/filters */
    $('#hidegfilter').click(function () {
        $('#statisticsgeneralfilters').hide();
    });
    $('#showgfilter').click(function () {
        $('#statisticsgeneralfilters').show();
    });
    /* The actual statistics results */
    $('#hidesfilter').click(function () {
        $('#statisticsoutput').hide(1000);
    });
    $('#showsfilter').click(function () {
        $('#statisticsoutput').show(1000);
    });

    function showhidefilters(value) {
        if (value == true) {
            hide('filterchoices');
        } else {
            show('filterchoices');
        }
    }
    /* End of show/hide sections */

    if (typeof aGMapData == "object") {
        for (var i in aGMapData) {
            gMapInit("statisticsmap_" + i, aGMapData[i]);
        }
    }

    if (typeof aStatData == "object") {
        for (var i in aStatData) {
            statInit(aStatData[i]);
        }
    }

    $(".stats-hidegraph").click(function () {

        var id = statGetId(this.parentNode);
        if (!id) {
            return;
        }

        $("#statzone_" + id).html(getWaiter());
        graphQuery(id, 'hidegraph', function (res) {
            if (!res) {
                ajaxError();
                return;
            }

            data = JSON.parse(res);

            if (!data || !data.ok) {
                ajaxError();
                return;
            }

            isWaiting[id] = false;
            aStatData[id].sg = false;
            statInit(aStatData[id]);
        });
    });

    $(".stats-showgraph").click(function () {
        var id = statGetId(this.parentNode);
        if (!id) {
            return;
        }

        $("#statzone_" + id).html(getWaiter()).show();
        graphQuery(id, 'showgraph', function (res) {
            if (!res) {
                ajaxError();
                return;
            }
            data = JSON.parse(res);

            if (!data || !data.ok || !data.chartdata) {
                ajaxError();
                return;
            }

            isWaiting[id] = false;
            aStatData[id].sg = true;
            statInit(aStatData[id]);

            $("#statzone_" + id).append("<img border='1' src='" + temppath + "/" + data.chartdata + "' />");

            if (aStatData[id].sm) {
                if (!data.mapdata) {
                    ajaxError();
                    return;
                }

                $("#statzone_" + id).append("<div id=\"statisticsmap_" + id + "\" class=\"statisticsmap\"></div>");
                gMapInit('statisticsmap_' + id, data.mapdata);
            }

            $("#statzone_" + id + " .wait").remove();

        });
    });

    $(".stats-hidemap").click(function () {
        var id = statGetId(this.parentNode);
        if (!id) {
            return;
        }

        $("#statzone_" + id + ">div").replaceWith(getWaiter());

        graphQuery(id, 'hidemap', function (res) {
            if (!res) {
                ajaxError();
                return;
            }

            data = JSON.parse(res);

            if (!data || !data.ok) {
                ajaxError();
                return;
            }

            isWaiting[id] = false;
            aStatData[id].sm = false;
            statInit(aStatData[id]);

            $("#statzone_" + id + " .wait").remove();
        });
    });

    $(".stats-showmap").click(function () {
        var id = statGetId(this.parentNode);
        if (!id) {
            return;
        }

        $("#statzone_" + id).append(getWaiter());

        graphQuery(id, 'showmap', function (res) {
            if (!res) {
                ajaxError();
                return;
            }

            data = JSON.parse(res);

            if (!data || !data.ok || !data.mapdata) {
                ajaxError();
                return;
            }

            isWaiting[id] = false;
            aStatData[id].sm = true;
            statInit(aStatData[id]);

            $("#statzone_" + id + " .wait").remove();
            $("#statzone_" + id).append("<div id=\"statisticsmap_" + id + "\" class=\"statisticsmap\"></div>");

            gMapInit('statisticsmap_' + id, data.mapdata);
        });
    });

    $(".stats-showbar").click(function () {
        changeGraphType('showbar', this.parentNode);
    });

    $(".stats-showpie").click(function () {
        changeGraphType('showpie', this.parentNode);
    });

    var ajaxDownloadStats = function (url, data) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.responseType = 'blob';
        xhr.onload = () => {
            const contentDisposition = xhr.getResponseHeader('Content-Disposition');
            const fileName = contentDisposition ? contentDisposition.match(/filename[^;=\n]*=['"](.*?|[^;\n]*)['"]/)[1] : '';
            if (fileName.length > 0) {
                // saveAs is implemented by jszip/fileSaver.js
                saveAs(xhr.response, fileName);
            } else {
                ajaxError();
            }
            $('#statsContainerLoading').hide();
        };
        xhr.onerror = () => {
            ajaxError();
            $('#statsContainerLoading').hide();
        };
        xhr.send(data);
    };
};

var isWaiting = {};

function getWaiter() {
    return "<img style='margin:auto;display:block;'class='wait' src='" + imgpath + "/ajax-loader.gif'/>";
}

function graphQuery(id, cmd, success) {
    $.ajax({
        type: "POST",
        url: graphUrl,
        data: {
            'id': id,
            'cmd': cmd,
            'sStatisticsLanguage': sStatisticsLanguage
        },
        success: success,
        error: function (res) {
            ajaxError();
        }
    });
}

function ajaxError() {
    // TODO: Use NotifyFader?
    alert("An error occured! Please reload the page!");
}

function selectCheckboxes(Div, CheckBoxName, Button) {
    var aDiv = document.getElementById(Div);
    var nInput = aDiv.getElementsByTagName("input");
    var Value = document.getElementById(Button).checked;
    //alert(Value);

    for (var i = 0; i < nInput.length; i++) {
        if (nInput[i].getAttribute("name") == CheckBoxName)
            nInput[i].checked = Value;
    }
}

function nographs() {
    document.getElementById('usegraph_2').checked = false;
}

function gMapInit(id, data) {
    if (!data || !data["coord"] || !data["zoom"] ||
        !data.width || !data.height || typeof google == "undefined") {
        return;
    }

    $("#" + id).width(data.width);
    $("#" + id).height(data.height);

    var latlng;
    if (data["coord"].length > 0) {
        var c = data["coord"][0].split(" ");
        latlng = new google.maps.LatLng(parseFloat(c[0]), parseFloat(c[1]));
    } else {
        latlng = new google.maps.LatLng(0.1, 0.1);
    }

    var myOptions = {
        zoom: parseFloat(data["zoom"]),
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    var map = new google.maps.Map(document.getElementById(id), myOptions);

    for (var i = 0; i < data["coord"].length; ++i) {
        var c = data["coord"][i].split(" ");

        var marker = new google.maps.Marker({
            position: new google.maps.LatLng(parseFloat(c[0]), parseFloat(c[1])),
            map: map
        });
    }
}

function statGetId(elem) {
    var id = $(elem).attr("id");

    if (id.substr(0, 6) == "stats_") {
        return id.substr(6, id.length);
    }

    if (id == '' || isWaiting[id]) {
        return false;
    }

    isWaiting[id] = true;
    return id;
}

function statInit(data) {
    var elem = $("#stats_" + data.id);

    elem.children().hide();

    if (data.sg) {
        $("#statzone_" + data.id).show();
        $(".stats-hidegraph", elem).show();

        if (data.ap) {
            $(".stats-" + (data.sp ? "showbar" : "showpie"), elem).show();
        }

        if (data.am) {
            $(".stats-" + (data.sm ? "hidemap" : "showmap"), elem).show();
        }
    } else {
        $("#statzone_" + data.id).hide();
        $(".stats-showgraph", elem).show();
    }
}

function changeGraphType(cmd, id) {
    id = statGetId(id);
    if (!id) {
        return;
    }

    if (!aStatData[id]) {
        alert('Error');
    }

    if (!aStatData[id].ap) {
        return;
    }

    $("#statzone_" + id).append(getWaiter());

    graphQuery(id, cmd, function (res) {
        if (!res) {
            ajaxError();
            return;
        }

        data = JSON.parse(res);

        if (!data || !data.ok || !data.chartdata) {
            ajaxError();
            return;
        }

        isWaiting[id] = false;
        aStatData[id].sp = (cmd == 'showpie');
        statInit(aStatData[id]);

        $("#statzone_" + id + " .wait").remove();
        $("#statzone_" + id + ">img:first").attr("src", temppath + "/" + data.chartdata);
    });

}


var createPDFworker = function (tableArray) {
    "use strict";
    return new Promise(function (res, rej) {
        var createPDF = new CreatePDF();

        $.each(tableArray, function (i, table) {
            var sizes = { h: $(table).height(), w: $(table).width() };
            var answerObject = createPDF('sendImg', { html: table, sizes: sizes });
        });

        createPDF('getParseHtmlPromise').then(function (resolve) {
            var answerObject = createPDF('exportPdf');
            console.ls.log(answerObject);
            var a = document.createElement('a');
            if(typeof a.download != "undefined") {
                $('body').append("<a id='exportPdf-download-link' style='display:none;' href='" + answerObject.msg + "' download='pdf-survey.pdf'></a>");// Must add sid and other info
                $("#exportPdf-download-link").get(0).click();
                $("#exportPdf-download-link").remove();
                res('done');
                return;
            } 
            var newWindow = window.open("about:blank", 600, 800);
            newWindow.document.write("<html style='height:100%;width:100%'><iframe style='width:100%;height:100%;' src='"+answerObject.msg+"' border=0></iframe></html>");
            res('done');
        }, function (reject) {
            rej(arguments);
        });
    });
};

var createOverlay = function () {
    var overlay = $('<div></div>')
        .attr('id', 'overlay')
        .css({
            position: 'fixed',
            width: "100%",
            height: "100%",
            top: 0,
            "z-index": 5000,
            "pointer-events": 'none',
            left: 0,
            right: 0,
            bottom: 0,
            "background-color": "hsla(0,0%,65%,0.6)"
        });
    $('#statsContainerLoading').clone().css({ display: 'block', position: 'fixed', top: "25%", left: 0, width: "100%" }).appendTo(overlay);
    overlay.appendTo('body');
    return overlay;
};

var exportImages = function () {
    var zip = new JSZip(),
        overlay = createOverlay();

    // Get global chart type if set
    var globalType = window.globalChartType || null;

    $('.chartjs-container').each(function (i, container) {
        var $container = $(container);
        var qid = $container.data('qid');

        // Use global chart type if set, otherwise use container's current type
        var chartType = globalType || $container.data('type') || 'Bar';

        // Re-render chart with the selected type before exporting
        if (chartType === 'Bar' || chartType === 'Radar' || chartType === 'Line' ||
            chartType === 'Doughnut' || chartType === 'Pie' || chartType === 'PolarArea') {
            init_chart_js_graph_with_datasets(chartType, qid);
        } else {
            init_chart_js_graph_with_datas(chartType, qid);
        }
    });

    // Wait for all charts to render with legends
    setTimeout(function() {
        $('.chartjs-container').each(function (i, container) {
            var canvasElements = $(container).find('canvas');
            if (canvasElements.length > 0) {
                var canvas = canvasElements.get(0);
                var qid = $(container).data('qid');
                var statistics = statisticsData['quid' + qid];
                var chartType = window.globalChartType || $(container).data('type') || 'Bar';

                // Create enhanced canvas with metadata
                var tempCanvas = document.createElement('canvas');
                var ctx = tempCanvas.getContext('2d');

                // Set canvas size with extra space
                var extraTopSpace = 80;
                var extraBottomSpace = 40;
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height + extraTopSpace + extraBottomSpace;

                // Fill white background
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

                // Add title
                ctx.fillStyle = '#122867';
                ctx.font = 'bold 18px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(statistics ? (statistics.title || 'Chart ' + qid) : 'Chart ' + qid, tempCanvas.width / 2, 30);

                // Add metadata
                ctx.fillStyle = '#666666';
                ctx.font = '12px Arial';
                var totalResponses = 0;
                if (statistics && statistics.grawdata) {
                    for (var j = 0; j < statistics.grawdata.length; j++) {
                        totalResponses += parseInt(statistics.grawdata[j]) || 0;
                    }
                }
                ctx.fillText('Total Responses: ' + totalResponses + ' | Chart Type: ' + chartType, tempCanvas.width / 2, 55);

                // Draw the chart with legend
                ctx.drawImage(canvas, 0, extraTopSpace);

                // Add footer
                ctx.fillStyle = '#999999';
                ctx.font = 'italic 11px Arial';
                ctx.fillText('Generated: ' + new Date().toLocaleDateString(), tempCanvas.width / 2, tempCanvas.height - 15);

                // Get enhanced image data
                var imgData = tempCanvas.toDataURL('image/png');
                imgData = imgData.replace(/^data:image\/(png|jpg);base64,/, "");
                var fileName = qid + '_' + chartType.toLowerCase() + '_with_legend.png';
                zip.file(fileName, imgData, { base64: true });
            }
        });

        zip.generateAsync({ type: "blob" })
            .then(function (content) {
                // see FileSaver.js
                var chartTypeName = window.globalChartType ? '_' + window.globalChartType.toLowerCase() : '';
                saveAs(content, "allChartImages_with_legends" + chartTypeName + ".zip");
                overlay.remove();
            });
    }, 1500);
};

/**
 * Download individual chart as PNG with title and legend
 */
var downloadChartImage = function (qid) {
    var canvasId = 'chartjs-' + qid;
    var canvas = document.getElementById(canvasId);
    var statistics = statisticsData['quid' + qid];
    var container = $('#chartjs-container-' + qid);

    if (!canvas || !statistics) {
        console.ls.error('Chart or data not found for qid:', qid);
        return;
    }

    // Get the current chart type from container
    var chartType = container.data('type') || 'Bar';

    // Re-render the chart with legend visible
    if (chartType === 'Bar' || chartType === 'Radar' || chartType === 'Line' ||
        chartType === 'Doughnut' || chartType === 'Pie' || chartType === 'PolarArea') {
        init_chart_js_graph_with_datasets(chartType, qid);
    } else {
        init_chart_js_graph_with_datas(chartType, qid);
    }

    // Add a delay to ensure chart is fully rendered with legend
    setTimeout(function () {
        // Create a new canvas with extra space for additional information
        var tempCanvas = document.createElement('canvas');
        var ctx = tempCanvas.getContext('2d');

        // Set canvas size (add space at top for title and bottom for notes)
        var extraTopSpace = 80;
        var extraBottomSpace = 40;
        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height + extraTopSpace + extraBottomSpace;

        // Fill white background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

        // Add title at top
        ctx.fillStyle = '#122867';
        ctx.font = 'bold 18px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(statistics.title || 'Chart ' + qid, tempCanvas.width / 2, 30);

        // Add metadata
        ctx.fillStyle = '#666666';
        ctx.font = '12px Arial';
        var totalResponses = 0;
        if (statistics.grawdata) {
            for (var i = 0; i < statistics.grawdata.length; i++) {
                totalResponses += parseInt(statistics.grawdata[i]) || 0;
            }
        }
        ctx.fillText('Total Responses: ' + totalResponses + ' | Chart Type: ' + chartType, tempCanvas.width / 2, 55);

        // Draw the original chart
        ctx.drawImage(canvas, 0, extraTopSpace);

        // Add footer note
        ctx.fillStyle = '#999999';
        ctx.font = 'italic 11px Arial';
        ctx.fillText('Generated: ' + new Date().toLocaleDateString(), tempCanvas.width / 2, tempCanvas.height - 15);

        // Download the enhanced image
        var imgData = tempCanvas.toDataURL('image/png');
        var chartTypeName = chartType.toLowerCase();
        var fileName = (statistics.title || 'chart-' + qid).replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_' + chartTypeName + '.png';

        // Create download link
        var link = document.createElement('a');
        link.href = imgData;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        console.ls.log('Chart with legend downloaded as ' + chartType + ' chart: ' + fileName);
    }, 500);
};

/**
 * Download question data as CSV
 */
var downloadQuestionCSV = function (qid) {
    var statistics = statisticsData['quid' + qid];

    if (!statistics) {
        console.ls.error('Data not found for qid:', qid);
        return;
    }

    var csv = [];
    var questionTitle = statistics.title || 'Question ' + qid;

    // Add title row
    csv.push([questionTitle]);
    csv.push([]); // Empty row

    // Add headers
    csv.push(['Answer', 'Count', 'Percentage']);

    // Calculate total for percentages
    var total = 0;
    if (statistics.grawdata && statistics.grawdata.length > 0) {
        for (var i = 0; i < statistics.grawdata.length; i++) {
            total += parseInt(statistics.grawdata[i]) || 0;
        }
    }

    // Add data rows
    if (statistics.labels && statistics.grawdata) {
        for (var i = 0; i < statistics.labels.length; i++) {
            var count = parseInt(statistics.grawdata[i]) || 0;
            var percentage = total > 0 ? ((count / total) * 100).toFixed(2) + '%' : '0%';
            csv.push([
                '"' + (statistics.labels[i] || '').replace(/"/g, '""') + '"',
                count,
                percentage
            ]);
        }
    }

    // Add total row
    csv.push([]);
    csv.push(['Total', total, '100%']);

    // Convert to CSV string
    var csvContent = csv.map(function (row) {
        return row.join(',');
    }).join('\n');

    // Create blob and download
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var fileName = (statistics.title || 'question-' + qid).replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.csv';
    saveAs(blob, fileName);
};

/**
 * Export all question data as combined CSV
 */
var exportAllCSV = function () {
    var csv = [];
    var surveyTitle = $('h4:first', '#statisticsview').text().replace('Statistics', 'Survey Statistics').trim();

    // Add survey title
    csv.push([surveyTitle]);
    csv.push(['Generated: ' + new Date().toLocaleString()]);
    csv.push([]); // Empty row

    // Iterate through all questions
    $('.question-card').each(function (index, card) {
        var $card = $(card);
        var questionTitle = $card.find('h4').first().text().trim();
        var qid = $card.find('canvas').attr('id');

        if (qid) {
            qid = qid.replace('chartjs-', '');
            var statistics = statisticsData['quid' + qid];

            if (statistics) {
                // Add question section
                csv.push([]);
                csv.push(['Question: ' + questionTitle]);
                csv.push(['Answer', 'Count', 'Percentage']);

                // Calculate total
                var total = 0;
                if (statistics.grawdata && statistics.grawdata.length > 0) {
                    for (var i = 0; i < statistics.grawdata.length; i++) {
                        total += parseInt(statistics.grawdata[i]) || 0;
                    }
                }

                // Add data rows
                if (statistics.labels && statistics.grawdata) {
                    for (var i = 0; i < statistics.labels.length; i++) {
                        var count = parseInt(statistics.grawdata[i]) || 0;
                        var percentage = total > 0 ? ((count / total) * 100).toFixed(2) + '%' : '0%';
                        csv.push([
                            '"' + (statistics.labels[i] || '').replace(/"/g, '""') + '"',
                            count,
                            percentage
                        ]);
                    }
                }

                // Add total
                csv.push(['Total', total, '100%']);
            }
        }
    });

    // Convert to CSV string
    var csvContent = csv.map(function (row) {
        return row.join(',');
    }).join('\n');

    // Create blob and download
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var fileName = 'survey_statistics_all_data.csv';
    saveAs(blob, fileName);
};

/**
 * Export summary report as PDF
 */
var exportSummaryPDF = function () {
    var overlay = createOverlay();

    // Get global chart type if set
    var globalType = window.globalChartType || null;

    // Re-render all charts with the selected type
    if (globalType) {
        $('.chartjs-container').each(function (i, container) {
            var $container = $(container);
            var qid = $container.data('qid');

            if (globalType === 'Bar' || globalType === 'Radar' || globalType === 'Line' ||
                globalType === 'Doughnut' || globalType === 'Pie' || globalType === 'PolarArea') {
                init_chart_js_graph_with_datasets(globalType, qid);
            } else {
                init_chart_js_graph_with_datas(globalType, qid);
            }
        });
    } else {
        // Load all charts with their current types
        $('.chartjs-container').loadGraph();
    }

    // Wait for charts to render with legends
    setTimeout(function() {
        try {
            // Check if jsPDF is available
            if (typeof window.jspdf === 'undefined' && typeof jsPDF === 'undefined') {
                alert('PDF export requires jsPDF library. Please ensure it is loaded.');
                overlay.remove();
                return;
            }

            var pdf = window.jspdf ? new window.jspdf.jsPDF('p', 'mm', 'a4') : new jsPDF('p', 'mm', 'a4');
            var pageHeight = pdf.internal.pageSize.getHeight();
            var pageWidth = pdf.internal.pageSize.getWidth();
            var margin = 15;
            var yPosition = margin;
            var lineHeight = 7;

            // Add title page
            pdf.setFontSize(20);
            pdf.setTextColor(18, 40, 103);
            pdf.text('Statistics Summary Report', pageWidth / 2, pageHeight / 2 - 20, { align: 'center' });

            pdf.setFontSize(12);
            pdf.setTextColor(100);
            pdf.text('Generated: ' + new Date().toLocaleString(), pageWidth / 2, pageHeight / 2, { align: 'center' });

            // Add note about legends
            pdf.setFontSize(10);
            // pdf.text('All charts include color-coded legends for clarity', pageWidth / 2, pageHeight / 2 + 15, { align: 'center' });

            // Iterate through all question cards
            var cardIndex = 0;
            $('.question-card').each(function (index, card) {
                var $card = $(card);
                var questionTitle = $card.find('h4').first().text().trim();
                var canvasElement = $card.find('canvas');

                if (canvasElement.length > 0) {
                    var canvas = canvasElement.get(0);
                    var qid = $(canvasElement).attr('id').replace('chartjs-', '');
                    var statistics = statisticsData['quid' + qid];

                    // Add new page
                    pdf.addPage();
                    yPosition = margin;

                    // Add question title
                    pdf.setFontSize(14);
                    pdf.setTextColor(18, 40, 103);
                    var splitTitle = pdf.splitTextToSize(questionTitle, pageWidth - (2 * margin));
                    pdf.text(splitTitle, margin, yPosition);
                    yPosition += (splitTitle.length * lineHeight) + 5;

                    // Add metadata
                    pdf.setFontSize(10);
                    pdf.setTextColor(100);
                    var totalResponses = 0;
                    if (statistics && statistics.grawdata) {
                        for (var i = 0; i < statistics.grawdata.length; i++) {
                            totalResponses += parseInt(statistics.grawdata[i]) || 0;
                        }
                    }
                    pdf.text('Total Responses: ' + totalResponses, margin, yPosition);
                    yPosition += 10;

                    // Add chart image with legend
                    var imgData = canvas.toDataURL('image/png');
                    var imgWidth = pageWidth - (2 * margin);
                    var imgHeight = (canvas.height * imgWidth) / canvas.width;

                    // Scale down if too tall
                    var maxHeight = pageHeight - yPosition - margin - 15;
                    if (imgHeight > maxHeight) {
                        imgHeight = maxHeight;
                        imgWidth = (canvas.width * imgHeight) / canvas.height;
                    }

                    // Center the image
                    var xOffset = (pageWidth - imgWidth) / 2;
                    pdf.addImage(imgData, 'PNG', xOffset, yPosition, imgWidth, imgHeight);

                    // Add note at bottom
                    pdf.setFontSize(8);
                    pdf.setTextColor(150);
                    pdf.text('Legend colors correspond to chart segments', pageWidth / 2, pageHeight - 10, { align: 'center' });

                    cardIndex++;
                }
            });

            // Save PDF
            pdf.save('statistics_summary_report_with_legends.pdf');
            overlay.remove();

        } catch (error) {
            console.error('PDF export error:', error);
            alert('Error generating PDF: ' + error.message);
            overlay.remove();
        }
    }, 1000);
};

/**
 * Export summary report as PowerPoint
 */
var exportSummaryPPT = function () {
    var overlay = createOverlay();

    // Get global chart type if set
    var globalType = window.globalChartType || null;

    // Re-render all charts with the selected type
    if (globalType) {
        $('.chartjs-container').each(function (i, container) {
            var $container = $(container);
            var qid = $container.data('qid');

            if (globalType === 'Bar' || globalType === 'Radar' || globalType === 'Line' ||
                globalType === 'Doughnut' || globalType === 'Pie' || globalType === 'PolarArea') {
                init_chart_js_graph_with_datasets(globalType, qid);
            } else {
                init_chart_js_graph_with_datas(globalType, qid);
            }
        });
    } else {
        // Load all charts with their current types
        $('.chartjs-container').loadGraph();
    }

    // Wait for charts to render with legends
    setTimeout(function() {
        try {
            // Check if PptxGenJS is available - try multiple possible namespaces
            var PptxGenJS = window.pptxgen || window.PptxGenJS || window.pptxgenjs;

            if (!PptxGenJS) {
                console.error('PptxGenJS library not found. Checked: window.pptxgen, window.PptxGenJS, window.pptxgenjs');
                console.log('Available window properties:', Object.keys(window).filter(function(k) { return k.toLowerCase().includes('pptx'); }));
                alert('PowerPoint export requires PptxGenJS library. Please ensure it is loaded.\n\nCheck browser console for details.');
                overlay.remove();
                return;
            }

            var pptx = new PptxGenJS();

            // Set presentation properties
            pptx.author = 'LimeSurvey Statistics';
            pptx.title = 'Statistics Summary Report with Legends';
            pptx.subject = 'Survey Statistics with Color-Coded Legends';

            // Add title slide
            var titleSlide = pptx.addSlide();
            titleSlide.background = { color: '122867' };
            titleSlide.addText('Statistics Summary Report', {
                x: 0.5,
                y: 1.5,
                w: 9,
                h: 1,
                fontSize: 32,
                color: 'FFFFFF',
                bold: true,
                align: 'center'
            });
            titleSlide.addText('Generated: ' + new Date().toLocaleString(), {
                x: 0.5,
                y: 3,
                w: 9,
                h: 0.5,
                fontSize: 14,
                color: 'FFFFFF',
                align: 'center'
            });
            // titleSlide.addText('All charts include color-coded legends for clarity', {
            //     x: 0.5,
            //     y: 3.8,
            //     w: 9,
            //     h: 0.4,
            //     fontSize: 12,
            //     color: 'FFFFFF',
            //     italic: true,
            //     align: 'center'
            // });

            // Iterate through all question cards
            $('.question-card').each(function (index, card) {
                var $card = $(card);
                var questionTitle = $card.find('h4').first().text().trim();
                var canvasElement = $card.find('canvas');

                if (canvasElement.length > 0) {
                    var canvas = canvasElement.get(0);
                    var qid = $(canvasElement).attr('id').replace('chartjs-', '');
                    var statistics = statisticsData['quid' + qid];
                    var slide = pptx.addSlide();

                    // Add question title
                    slide.addText(questionTitle, {
                        x: 0.5,
                        y: 0.3,
                        w: 9,
                        h: 0.6,
                        fontSize: 16,
                        color: '122867',
                        bold: true
                    });

                    // Add metadata
                    var totalResponses = 0;
                    if (statistics && statistics.grawdata) {
                        for (var i = 0; i < statistics.grawdata.length; i++) {
                            totalResponses += parseInt(statistics.grawdata[i]) || 0;
                        }
                    }
                    slide.addText('Total Responses: ' + totalResponses, {
                        x: 0.5,
                        y: 0.9,
                        w: 9,
                        h: 0.3,
                        fontSize: 11,
                        color: '666666'
                    });

                    // Add chart image with legend
                    var imgData = canvas.toDataURL('image/png');
                    slide.addImage({
                        data: imgData,
                        x: 0.5,
                        y: 1.4,
                        w: 9,
                        h: 5
                    });

                    // Add note about legend
                    slide.addText('Legend shows color-coded response categories with counts/percentages', {
                        x: 0.5,
                        y: 6.7,
                        w: 9,
                        h: 0.3,
                        fontSize: 9,
                        color: '999999',
                        italic: true,
                        align: 'center'
                    });
                }
            });

            // Save PowerPoint
            pptx.writeFile({ fileName: 'statistics_summary_report_with_legends.pptx' })
                .then(function() {
                    overlay.remove();
                })
                .catch(function(error) {
                    console.error('PPT export error:', error);
                    alert('Error generating PowerPoint: ' + error.message);
                    overlay.remove();
                });

        } catch (error) {
            console.error('PPT export error:', error);
            alert('Error generating PowerPoint: ' + error.message);
            overlay.remove();
        }
    }, 1000);
};

/**
 * Load available filter questions and populate UI
 */
var loadFilterQuestions = function() {
    var surveyid = getSurveyId();

    if (!surveyid) {
        console.log('Survey ID not found, skipping filter questions');
        return;
    }

    console.log('Attempting to load filter questions for survey:', surveyid);

    // Create URL using LS helper or fallback
    var filterUrl = (typeof LS !== 'undefined' && LS.createUrl) ?
        LS.createUrl('admin/statistics/sa/getFilterQuestions') :
        'index.php/admin/statistics/sa/getFilterQuestions';

    $.ajax({
        url: filterUrl,
        type: 'GET',
        data: { surveyid: surveyid },
        dataType: 'json',
        success: function(filterQuestions) {
            console.log('✓ Filter questions loaded successfully');
            console.log('Filters found:', filterQuestions);

            if (filterQuestions && filterQuestions.length > 0) {
                displayFilterQuestions(filterQuestions);
            } else {
                console.log('No filter questions detected for this survey');
                // Hide comparison type since we don't have filters
                $('#comparisonType').closest('.filter-group').show();
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load filter questions');
            console.log('Status:', xhr.status);
            console.log('Response:', xhr.responseText);
            console.log('Error:', error);

            // Show the comparison type dropdown as fallback
            $('#comparisonType').closest('.filter-group').show();
        }
    });
};

/**
 * Display filter questions in the comparison panel
 */
var displayFilterQuestions = function(filterQuestions) {
    var filterHTML = '';

    filterQuestions.forEach(function(filter, index) {
        // Only show top 3 most relevant filters
        if (index >= 3) return;

        filterHTML += '<div class="filter-group">';
        filterHTML += '<label for="filter_' + filter.qid + '">' + filter.question + ':</label>';
        filterHTML += '<select id="filter_' + filter.qid + '" class="form-control filter-question" data-fieldname="' + filter.fieldName + '">';
        filterHTML += '<option value="">All ' + filter.type + 's</option>';

        filter.options.forEach(function(option) {
            filterHTML += '<option value="' + option.code + '">' + option.answer + '</option>';
        });

        filterHTML += '</select>';
        filterHTML += '</div>';
    });

    // Insert filter questions before the apply button
    $('.comparison-filters').prepend(filterHTML);

    // Hide the old comparison type dropdown (we're using dynamic filters now)
    $('#comparisonType').closest('.filter-group').hide();
};

/**
 * Apply dataset comparison filters
 */
var applyComparison = function () {
    var dateStart = $('#dateRangeStart').val();
    var dateEnd = $('#dateRangeEnd').val();
    var surveyid = getSurveyId();

    // Collect all active filters
    var filters = [];

    // Add date filter if specified
    if (dateStart && dateEnd) {
        if (new Date(dateStart) > new Date(dateEnd)) {
            alert('Start date must be before end date');
            return;
        }

        filters.push({
            type: 'date',
            value: {
                start: dateStart,
                end: dateEnd
            }
        });
    }

    // Add question filters
    $('.filter-question').each(function() {
        var $select = $(this);
        var value = $select.val();

        if (value) {
            filters.push({
                type: 'question',
                value: {
                    fieldName: $select.data('fieldname'),
                    answer: value
                }
            });
        }
    });

    // Check if any filters are selected
    if (filters.length === 0) {
        alert('Please select at least one filter criteria');
        return;
    }

    // Show loading overlay
    var overlay = createOverlay();

    console.log('Applying filters:', filters);

    // Create URL using LS helper or fallback
    var filterStatsUrl = (typeof LS !== 'undefined' && LS.createUrl) ?
        LS.createUrl('admin/statistics/sa/filterStatistics') :
        'index.php/admin/statistics/sa/filterStatistics';

    // Make AJAX request to filter statistics
    $.ajax({
        url: filterStatsUrl,
        type: 'POST',
        data: {
            surveyid: surveyid,
            filters: JSON.stringify(filters)
        },
        dataType: 'json',
        success: function(data) {
            console.log('✓ Filtered statistics received:', data);

            // Update statisticsData with filtered data
            statisticsData = data;

            // Reload all charts with new data
            reloadAllCharts();

            // Update metrics and insights
            $('.metrics-dashboard').remove();
            $('.insights-panel').remove();
            displayMetrics();
            generateInsights();
            addPerformanceIndicators();

            // Hide overlay
            overlay.remove();

            // Show success message
            var filterSummary = 'Showing filtered results';
            if (dateStart && dateEnd) {
                filterSummary += ' from ' + dateStart + ' to ' + dateEnd;
            }
            alert(filterSummary);
        },
        error: function(xhr, status, error) {
            console.error('Filter error:', error);
            alert('Error applying filters: ' + error);
            overlay.remove();
        }
    });
};

/**
 * Helper: Extract survey ID from page
 */
function getSurveyId() {
    // Try multiple methods to get survey ID
    // Method 1: From URL
    var match = window.location.href.match(/surveyid\/(\d+)/);
    if (match) {
        return match[1];
    }

    // Method 2: From data attribute
    var surveyid = $('#statisticsview').data('surveyid');
    if (surveyid) {
        return surveyid;
    }

    // Method 3: From hidden input
    return $('input[name="sid"]').val();
}

/**
 * Reload all charts with updated data
 */
function reloadAllCharts() {
    $('.question-card').each(function() {
        var $card = $(this);
        var $canvas = $card.find('canvas');

        if ($canvas.length > 0) {
            var qid = $canvas.attr('id').replace('chartjs-', '');
            var $container = $('#chartjs-container-' + qid);
            var chartType = $container.data('type');

            // Destroy existing chart
            if (window.chartjs && window.chartjs[qid]) {
                window.chartjs[qid].destroy();
            }

            // Recreate chart with new data
            if (chartType === 'Bar' || chartType === 'Radar' || chartType === 'Line' ||
                chartType === 'Doughnut' || chartType === 'Pie' || chartType === 'PolarArea') {
                init_chart_js_graph_with_datasets(chartType, qid);
            } else {
                init_chart_js_graph_with_datas(chartType, qid);
            }
        }
    });
}

/**
 * Calculate performance rating based on score
 */
function getPerformanceRating(score) {
    if (score >= 4.5) return { level: 'excellent', label: 'Excellent', color: 'success' };
    if (score >= 3.5) return { level: 'good', label: 'Good', color: 'primary' };
    if (score >= 2.5) return { level: 'average', label: 'Average', color: 'warning' };
    if (score >= 1.5) return { level: 'poor', label: 'Poor', color: 'danger' };
    return { level: 'critical', label: 'Critical', color: 'danger' };
}

/**
 * Add performance badges and flags to questions
 */
function addPerformanceIndicators() {
    // Check if statisticsData exists
    if (typeof statisticsData === 'undefined') {
        console.log('statisticsData not available yet, skipping performance indicators');
        return;
    }

    $('.question-card').each(function() {
        var $card = $(this);
        var $canvas = $card.find('canvas');

        if ($canvas.length > 0) {
            var qid = $canvas.attr('id').replace('chartjs-', '');
            var statistics = statisticsData['quid' + qid];

            if (statistics && statistics.grawdata && statistics.grawdata.length > 0) {
                // Calculate average score
                var total = 0;
                var count = 0;

                for (var i = 0; i < statistics.grawdata.length; i++) {
                    total += parseInt(statistics.grawdata[i]) || 0;
                    count++;
                }

                // Assume scores are on a 5-point scale
                // Calculate weighted average based on answer values
                var avgScore = calculateAverageScore(statistics.labels, statistics.grawdata);

                if (avgScore !== null) {
                    var rating = getPerformanceRating(avgScore);

                    // Add badge to question title
                    var $title = $card.find('h4').first();
                    var badge = '<span class="performance-badge badge-' + rating.level + '">' +
                        rating.label + ' (' + avgScore.toFixed(2) + '/5.0)' +
                        '</span>';
                    $title.append(badge);

                    // Add flag for poor/critical performance
                    if (rating.level === 'poor' || rating.level === 'critical') {
                        var flagClass = rating.level === 'critical' ? 'flag-critical' : 'flag-warning';
                        var flagIcon = rating.level === 'critical' ? 'ri-alarm-warning-fill' : 'ri-flag-fill';
                        var flag = '<i class="' + flagIcon + ' performance-flag ' + flagClass + '"></i>';
                        $card.css('position', 'relative').prepend(flag);
                    }

                    // Add score indicator bar
                    var scoreIndicator = '<div class="score-indicator">' +
                        '<span class="score-value">' + avgScore.toFixed(1) + '</span>' +
                        '<div class="score-bar">' +
                        '<div class="score-fill score-' + rating.level + '" style="width: ' + (avgScore / 5 * 100) + '%"></div>' +
                        '</div>' +
                        '</div>';
                    $card.find('.chart-section').prepend(scoreIndicator);
                }
            }
        }
    });
}

/**
 * Calculate average score from labels and counts
 */
function calculateAverageScore(labels, counts) {
    var totalScore = 0;
    var totalResponses = 0;

    for (var i = 0; i < labels.length && i < counts.length; i++) {
        var label = labels[i];
        var count = parseInt(counts[i]) || 0;

        // Try to extract numeric value from label
        var scoreValue = extractScoreFromLabel(label, i, labels.length);

        if (scoreValue !== null) {
            totalScore += scoreValue * count;
            totalResponses += count;
        }
    }

    return totalResponses > 0 ? totalScore / totalResponses : null;
}

/**
 * Extract numeric score from answer label
 */
function extractScoreFromLabel(label, index, totalOptions) {
    // Method 1: Direct number in label (e.g., "5", "4", "3")
    var directNumber = parseFloat(label);
    if (!isNaN(directNumber) && directNumber >= 1 && directNumber <= 5) {
        return directNumber;
    }

    // Method 2: Word mapping
    var wordMap = {
        'excellent': 5,
        'very good': 4.5,
        'good': 4,
        'satisfactory': 3,
        'average': 3,
        'fair': 2.5,
        'poor': 2,
        'very poor': 1.5,
        'unsatisfactory': 1,
        'strongly agree': 5,
        'agree': 4,
        'neutral': 3,
        'disagree': 2,
        'strongly disagree': 1
    };

    var labelLower = label.toLowerCase();
    for (var key in wordMap) {
        if (labelLower.includes(key)) {
            return wordMap[key];
        }
    }

    // Method 3: Position-based (assume ordered from best to worst or vice versa)
    // This is a fallback - assumes 5-point scale distributed across options
    if (totalOptions > 0) {
        return 5 - (index / (totalOptions - 1)) * 4;
    }

    return null;
}

/**
 * Generate and display insights
 */
function generateInsights() {
    // Check if statisticsData exists
    if (typeof statisticsData === 'undefined') {
        console.log('statisticsData not available yet, skipping insights');
        return;
    }

    // Check if insights already displayed
    if ($('.insights-panel').length > 0) {
        console.log('Insights already displayed');
        return;
    }

    var insights = [];
    var lowScoreCount = 0;
    var highScoreCount = 0;
    var totalQuestions = 0;
    var sumScores = 0;

    $('.question-card').each(function() {
        var $card = $(this);
        var $canvas = $card.find('canvas');

        if ($canvas.length > 0) {
            var qid = $canvas.attr('id').replace('chartjs-', '');
            var statistics = statisticsData['quid' + qid];

            if (statistics && statistics.grawdata && statistics.grawdata.length > 0) {
                var avgScore = calculateAverageScore(statistics.labels, statistics.grawdata);

                if (avgScore !== null) {
                    totalQuestions++;
                    sumScores += avgScore;

                    if (avgScore < 3.5) {
                        lowScoreCount++;
                        insights.push({
                            type: 'negative',
                            text: '<strong>' + (statistics.title || 'Question ' + qid) + '</strong> scored ' +
                                avgScore.toFixed(2) + '/5.0 - needs improvement'
                        });
                    } else if (avgScore >= 4.5) {
                        highScoreCount++;
                        insights.push({
                            type: 'positive',
                            text: '<strong>' + (statistics.title || 'Question ' + qid) + '</strong> scored ' +
                                avgScore.toFixed(2) + '/5.0 - excellent performance!'
                        });
                    }
                }
            }
        }
    });

    // General insights
    if (totalQuestions > 0) {
        var overallAverage = sumScores / totalQuestions;
        insights.unshift({
            type: overallAverage >= 4 ? 'positive' : overallAverage >= 3 ? 'neutral' : 'negative',
            text: '<strong>Overall Performance:</strong> Average score across all questions is ' +
                overallAverage.toFixed(2) + '/5.0 (' + totalQuestions + ' questions analyzed)'
        });

        if (lowScoreCount > 0) {
            insights.push({
                type: 'negative',
                text: '<strong>Action Required:</strong> ' + lowScoreCount + ' question(s) scored below 3.5/5.0 and require immediate attention'
            });
        }

        if (highScoreCount > 0) {
            insights.push({
                type: 'positive',
                text: '<strong>Strengths:</strong> ' + highScoreCount + ' question(s) scored above 4.5/5.0 - these areas are performing excellently'
            });
        }
    }

    // Display insights
    if (insights.length > 0) {
        var insightsHTML = '<div class="insights-panel">' +
            '<h5 class="panel-title"><i class="ri-lightbulb-flash-line"></i> Auto-Generated Insights</h5>';

        insights.forEach(function(insight) {
            insightsHTML += '<div class="insight-item ' + insight.type + '">' +
                '<p class="insight-text">' + insight.text + '</p>' +
                '</div>';
        });

        insightsHTML += '</div>';

        // Insert before statistics output
        $('#statisticsoutput').before(insightsHTML);
    }
}

/**
 * Display process performance metrics
 */
function displayMetrics() {
    // Check if statisticsData exists
    if (typeof statisticsData === 'undefined') {
        console.log('statisticsData not available yet, skipping metrics display');
        return;
    }

    // Check if metrics already displayed
    if ($('.metrics-dashboard').length > 0) {
        console.log('Metrics already displayed');
        return;
    }

    // Calculate metrics from chart data
    var totalQuestions = $('.question-card').length;

    if (totalQuestions === 0) {
        console.log('No question cards found yet, skipping metrics display');
        return;
    }

    var sumScores = 0;
    var questionCount = 0;

    $('.question-card').each(function() {
        var $canvas = $(this).find('canvas');
        if ($canvas.length > 0) {
            var qid = $canvas.attr('id').replace('chartjs-', '');
            var statistics = statisticsData['quid' + qid];

            if (statistics && statistics.grawdata) {
                var avgScore = calculateAverageScore(statistics.labels, statistics.grawdata);
                if (avgScore !== null) {
                    sumScores += avgScore;
                    questionCount++;
                }
            }
        }
    });

    var overallAvg = questionCount > 0 ? (sumScores / questionCount).toFixed(2) : 'N/A';

    // Use real response metrics if available
    var totalResponses = responseMetricsData ? responseMetricsData.totalResponses : 'N/A';
    var completeResponses = responseMetricsData ? responseMetricsData.completeResponses : 'N/A';
    var responseRate = responseMetricsData && responseMetricsData.responseRate ? responseMetricsData.responseRate + '%' : 'N/A';
    var completionRate = responseMetricsData && responseMetricsData.completionRate ? responseMetricsData.completionRate + '%' : 'N/A';

    var metricsHTML = '<div class="metrics-dashboard">' +
        '<div class="metric-card">' +
        '<i class="ri-questionnaire-line metric-icon"></i>' +
        '<div class="metric-label">Total Questions</div>' +
        '<div class="metric-value">' + totalQuestions + '</div>' +
        '</div>' +
        '<div class="metric-card">' +
        '<i class="ri-user-line metric-icon"></i>' +
        '<div class="metric-label">Total Responses</div>' +
        '<div class="metric-value">' + totalResponses + '</div>' +
        '<div class="metric-sublabel">(' + completeResponses + ' complete)</div>' +
        '</div>' +
        '<div class="metric-card">' +
        '<i class="ri-bar-chart-box-line metric-icon"></i>' +
        '<div class="metric-label">Overall Average</div>' +
        '<div class="metric-value">' + overallAvg + '</div>' +
        '<div class="metric-trend ' + (overallAvg >= 4 ? 'trend-up' : overallAvg >= 3 ? 'trend-neutral' : 'trend-down') + '">' +
        (overallAvg >= 4 ? '↑ Excellent' : overallAvg >= 3 ? '→ Good' : '↓ Needs Work') +
        '</div>' +
        '</div>' +
        '<div class="metric-card">' +
        '<i class="ri-percent-line metric-icon"></i>' +
        '<div class="metric-label">Response Rate</div>' +
        '<div class="metric-value">' + responseRate + '</div>' +
        '<div class="metric-sublabel">Completion: ' + completionRate + '</div>' +
        '</div>' +
        '</div>';

    // Insert before statistics output
    $('#statisticsoutput').before(metricsHTML);
}

/**
 * Load qualitative feedback (comments) and generate word cloud
 */
var loadQualitativeFeedback = function() {
    var surveyid = getSurveyId();

    if (!surveyid) {
        console.log('Survey ID not found, skipping qualitative feedback');
        return;
    }

    console.log('Loading qualitative feedback for survey:', surveyid);

    // Create URL using LS helper or fallback
    var feedbackUrl = (typeof LS !== 'undefined' && LS.createUrl) ?
        LS.createUrl('admin/statistics/sa/getQualitativeFeedback') :
        'index.php/admin/statistics/sa/getQualitativeFeedback';

    $.ajax({
        url: feedbackUrl,
        type: 'GET',
        data: { surveyid: surveyid },
        dataType: 'json',
        success: function(qualitativeData) {
            if (qualitativeData && qualitativeData.length > 0) {
                console.log('✓ Qualitative feedback loaded:', qualitativeData.length, 'questions with comments');

                // Show the feedback section
                $('#qualitativeFeedbackSection').show();

                // Generate word cloud from all comments
                var allWordFrequencies = [];
                var totalComments = 0;

                qualitativeData.forEach(function(item) {
                    totalComments += item.count;
                    if (item.wordFrequency) {
                        allWordFrequencies = allWordFrequencies.concat(item.wordFrequency);
                    }
                });

                // Merge word frequencies
                var mergedWords = {};
                allWordFrequencies.forEach(function(word) {
                    if (!mergedWords[word.text]) {
                        mergedWords[word.text] = 0;
                    }
                    mergedWords[word.text] += word.weight;
                });

                // Convert back to array and sort
                var wordCloudData = [];
                for (var text in mergedWords) {
                    wordCloudData.push({
                        text: text,
                        weight: mergedWords[text]
                    });
                }
                wordCloudData.sort(function(a, b) { return b.weight - a.weight; });

                // Generate word cloud
                generateWordCloud(wordCloudData);

                // Display comment summary
                displayCommentSummary(qualitativeData, totalComments);

                // Display all comments by question
                displayCommentsList(qualitativeData);

            } else {
                console.log('No qualitative feedback found for this survey');
                $('#qualitativeFeedbackSection').hide();
            }
        },
        error: function(xhr, status, error) {
            console.log('Failed to load qualitative feedback:', status, error);
            $('#qualitativeFeedbackSection').hide();
        }
    });
};

/**
 * Generate word cloud on canvas
 */
var generateWordCloud = function(wordData) {
    if (wordData.length === 0) {
        $('#wordCloudContainer').html('<p class="text-muted">No comments to display</p>');
        return;
    }

    var canvas = document.getElementById('wordCloudCanvas');
    var container = document.getElementById('wordCloudContainer');

    // Set canvas size
    canvas.width = container.offsetWidth || 500;
    canvas.height = 400;

    var ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Calculate font sizes based on weight
    var maxWeight = wordData[0].weight;
    var minWeight = wordData[wordData.length - 1].weight;

    // Simple word cloud layout (positioned words)
    var usedPositions = [];
    var centerX = canvas.width / 2;
    var centerY = canvas.height / 2;

    wordData.slice(0, 30).forEach(function(word, index) {
        // Calculate font size (12-48px based on weight)
        var fontSize = Math.floor(12 + (word.weight / maxWeight) * 36);

        // Color gradient based on weight (primary blue shades)
        var intensity = Math.floor((word.weight / maxWeight) * 150);
        var color = 'rgb(' + (18 + intensity) + ', ' + (40 + intensity) + ', ' + (103 + intensity) + ')';

        ctx.font = fontSize + 'px Arial';
        ctx.fillStyle = color;

        // Measure text
        var metrics = ctx.measureText(word.text);
        var textWidth = metrics.width;

        // Find position (simple spiral layout)
        var angle = index * 0.5;
        var radius = index * 8;
        var x = centerX + Math.cos(angle) * radius - textWidth / 2;
        var y = centerY + Math.sin(angle) * radius;

        // Keep within bounds
        x = Math.max(10, Math.min(x, canvas.width - textWidth - 10));
        y = Math.max(fontSize, Math.min(y, canvas.height - 10));

        // Draw the word
        ctx.fillText(word.text, x, y);

        // Add tooltip on hover (add data attribute)
        canvas.title = 'Word Cloud - Top ' + wordData.length + ' words';
    });
};

/**
 * Display comment summary statistics
 */
var displayCommentSummary = function(qualitativeData, totalComments) {
    var html = '<div class="summary-stats">';
    html += '<div class="stat-item"><strong>Total Comments:</strong> ' + totalComments + '</div>';
    html += '<div class="stat-item"><strong>Questions with Comments:</strong> ' + qualitativeData.length + '</div>';

    // Average comments per question
    var avgComments = qualitativeData.length > 0 ? (totalComments / qualitativeData.length).toFixed(1) : 0;
    html += '<div class="stat-item"><strong>Avg Comments/Question:</strong> ' + avgComments + '</div>';

    html += '</div>';

    $('#commentSummary').html(html);
};

/**
 * Display list of all comments by question
 */
var displayCommentsList = function(qualitativeData) {
    var html = '';

    qualitativeData.forEach(function(item) {
        html += '<div class="question-comments">';
        html += '<h6 class="comment-question-title"><i class="ri-question-line"></i> ' + item.question + '</h6>';
        html += '<p class="comment-count">' + item.count + ' response(s)</p>';
        html += '<div class="comments-wrapper">';

        // Show first 5 comments, with "Show more" option
        var displayLimit = Math.min(5, item.comments.length);
        for (var i = 0; i < displayLimit; i++) {
            html += '<div class="comment-item">';
            html += '<i class="ri-chat-3-line comment-icon"></i>';
            html += '<p class="comment-text">' + escapeHtml(item.comments[i]) + '</p>';
            html += '</div>';
        }

        if (item.comments.length > 5) {
            html += '<button class="btn btn-sm btn-link show-all-comments" data-qid="' + item.qid + '">';
            html += 'Show all ' + item.comments.length + ' comments <i class="ri-arrow-down-s-line"></i>';
            html += '</button>';
            html += '<div class="hidden-comments" id="hidden-comments-' + item.qid + '" style="display: none;">';
            for (var j = 5; j < item.comments.length; j++) {
                html += '<div class="comment-item">';
                html += '<i class="ri-chat-3-line comment-icon"></i>';
                html += '<p class="comment-text">' + escapeHtml(item.comments[j]) + '</p>';
                html += '</div>';
            }
            html += '</div>';
        }

        html += '</div></div>';
    });

    $('#commentsList').html(html);

    // Add click handler for "Show all" buttons
    $('.show-all-comments').on('click', function() {
        var qid = $(this).data('qid');
        var $hidden = $('#hidden-comments-' + qid);
        var $btn = $(this);

        if ($hidden.is(':visible')) {
            $hidden.slideUp();
            $btn.html('Show all ' + $btn.text().match(/\d+/)[0] + ' comments <i class="ri-arrow-down-s-line"></i>');
        } else {
            $hidden.slideDown();
            $btn.html('Hide comments <i class="ri-arrow-up-s-line"></i>');
        }
    });
};

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Load response metrics from server
 */
var responseMetricsData = null;

var loadResponseMetrics = function() {
    var surveyid = getSurveyId();

    if (!surveyid) {
        return;
    }

    console.log('Loading response metrics for survey:', surveyid);

    // Create URL using LS helper or fallback
    var metricsUrl = (typeof LS !== 'undefined' && LS.createUrl) ?
        LS.createUrl('admin/statistics/sa/getResponseMetrics') :
        'index.php/admin/statistics/sa/getResponseMetrics';

    $.ajax({
        url: metricsUrl,
        type: 'GET',
        data: { surveyid: surveyid },
        dataType: 'json',
        success: function(metrics) {
            console.log('✓ Response metrics loaded:', metrics);
            responseMetricsData = metrics;

            // Update metrics display if it exists
            if ($('.metrics-dashboard').length > 0) {
                $('.metrics-dashboard').remove();
                displayMetrics();
            }
        },
        error: function(xhr, status, error) {
            console.log('Failed to load response metrics:', status, error);
        }
    });
};

$(document).on('ready  pjax:scriptcomplete', function () {
    LS.Statistics2();
    $('body').addClass('onStatistics');
    var exportImagesButton = $('#statisticsExportImages');
    exportImagesButton.on('click', exportImages);
    exportImagesButton.wrap('<div class="col-12 text-center"></div>')
    $('#statisticsview').children('div.row').last().append(exportImagesButton);

    // Global Chart Type Selector
    $('.global-chart-type-btn').on('click', function() {
        var selectedType = $(this).data('type');

        // Update active state
        $('.global-chart-type-btn').removeClass('active');
        $(this).addClass('active');

        // Store the selected type globally
        window.globalChartType = selectedType;

        // Update all charts to the selected type
        $('.chartjs-container').each(function() {
            var $container = $(this);
            var qid = $container.data('qid');
            var chartType = selectedType;

            // Update container data-type
            $container.attr('data-type', chartType);

            // Update individual chart type buttons
            var $chartButtons = $container.closest('.question-card').find('.chart-type-control');
            $chartButtons.removeClass('active');
            $chartButtons.filter('[data-type="' + chartType + '"]').addClass('active');

            // Re-render the chart
            if (chartType === 'Bar' || chartType === 'Radar' || chartType === 'Line' ||
                chartType === 'Doughnut' || chartType === 'Pie' || chartType === 'PolarArea') {
                init_chart_js_graph_with_datasets(chartType, qid);
            } else {
                init_chart_js_graph_with_datas(chartType, qid);
            }
        });

        console.ls.log('All charts updated to type: ' + selectedType);
    });

    // New export functionality
    $('#exportSummaryPDF').on('click', exportSummaryPDF);
    $('#exportSummaryPPT').on('click', exportSummaryPPT);
    $('#exportAllCSV').on('click', exportAllCSV);
    $('#applyComparison').on('click', applyComparison);
    $('#resetFilters').on('click', function() {
        // Clear all filter selections
        $('.filter-question').val('');
        $('#dateRangeStart').val('');
        $('#dateRangeEnd').val('');

        // Reload page to show all data
        location.reload();
    });

    // TEMPORARILY DISABLED - Load filter questions dynamically
    // loadFilterQuestions();

    // TEMPORARILY DISABLED - Add performance indicators, insights, and metrics
    // setTimeout(function() {
    //     try {
    //         loadResponseMetrics();  // Load metrics with real response rate data
    //     } catch (e) {
    //         console.error('Error loading response metrics:', e);
    //     }

    //     try {
    //         displayMetrics();
    //     } catch (e) {
    //         console.error('Error displaying metrics:', e);
    //     }

    //     try {
    //         generateInsights();
    //     } catch (e) {
    //         console.error('Error generating insights:', e);
    //     }

    //     try {
    //         addPerformanceIndicators();
    //     } catch (e) {
    //         console.error('Error adding performance indicators:', e);
    //     }

    //     try {
    //         loadQualitativeFeedback();  // Load qualitative feedback and word cloud
    //     } catch (e) {
    //         console.error('Error loading qualitative feedback:', e);
    //     }
    // }, 1500); // Wait for charts to load

    // Toggle feedback panel
    $('#toggleFeedbackPanel').on('click', function() {
        var $content = $('#feedbackContent');
        var $icon = $(this).find('i');

        if ($content.is(':visible')) {
            $content.slideUp();
            $icon.removeClass('ri-arrow-up-s-line').addClass('ri-arrow-down-s-line');
            $(this).html('<i class="ri-arrow-down-s-line"></i> Expand');
        } else {
            $content.slideDown();
            $icon.removeClass('ri-arrow-down-s-line').addClass('ri-arrow-up-s-line');
            $(this).html('<i class="ri-arrow-up-s-line"></i> Collapse');
        }
    });

    $('body').on('click', '.action_js_export_to_pdf', function () {

        // var thisTable = $('#'+$(this).data('questionId'));
        // domtoimage.toPng(thisTable[0]).then(
        //     function(image){
        //         $('body').prepend($('<img/>').attr('src',image));
        //     }
        // )

        // var thisTable = $('#'+$(this).data('questionId'));
        // console.ls.log(thisTable.html());

        var $self = $(this),
            overlay = createOverlay(),
            thisTable = $('#' + $self.data('questionId'));

        $self.css({ display: 'none' });
        thisTable.find('.chartjs-buttons').closest('tr').css({ display: 'none' });
        createPDFworker.call(null, thisTable).then(
            function (success) {
                overlay.remove();
                thisTable.find('.chartjs-buttons').closest('tr').css({ display: '' });
                $self.css({ display: '' });
            },
            function () { console.ls.error(arguments); }
        )
    });
});

$(document).on('triggerReady', LS.Statistics2);
