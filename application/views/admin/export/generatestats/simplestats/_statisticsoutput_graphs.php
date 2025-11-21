<?php
/**
 * This view render the graphs
 *
 * @var $rt
 * @var $qqid
 * @var $labels
 * @var $COLORS_FOR_SURVEY
 * @var $charttype
 * @var $sChartname
 * @var $grawdata
 * @var $color
 *
 */
?>
<!-- _statisticsoutput_graphs -->
    <?php if(count($labels) < 70): ?>
        <!-- Charts -->
        <div class="row custom custom-padding bottom-20">
            <div class="col-md-12 vcenter chartjs-container text-center" id="chartjs-container-<?php echo $qqid; ?>"
                data-chartname="<?php echo $sChartname; // The name of the jschart object ?>"
                data-qid="<?php echo $qqid; // the question id ?>"
                data-type="<?php echo $charttype; // the chart start type (bar, donut, etc.) ?>"
                data-color="<?php echo $color; // the background color for bar, etc. ?>"
            >

            <?php
            //var_dump($labels);
            ?>
                <canvas class="canvas-chart" id="chartjs-<?php echo $qqid; ?>" width="400" height="300<?php // echo $iCanvaHeight;?>"
                    data-color="<?php echo $color; // the background color for bar, etc. ?>"></canvas>
            </div>
        </div>

        <div class="row">
            <!-- legends -->
            <div class="legend col-md-12 vcenter">
                <h5 style="color: #122867; font-weight: 600; margin-bottom: 1rem; font-size: 1rem;">
                    <i class="ri-list-check"></i> Legend
                </h5>
                <?php foreach($fullLabels as $i=>$label): ?>
                    <?php $colorindex = $color+$i; ?>
                    <div class="row legend-item" style="margin-bottom: 10px; align-items: center;">
                        <div class="col-2 col-md-1">
                            <span style="background-color:rgba(<?php echo $COLORS_FOR_SURVEY[$colorindex];?>,0.7); display: block; width: 22px; height: 22px; border-radius: 50%; margin: 0px; padding: 0px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            </span>
                        </div>
                        <div class="col-10 col-md-11">
                            <?php echo $label; ?>
                        </div>
                    </div>
                <?php endforeach;?>
            </div>
        </div>

<!-- Chart Type Selector -->
        <div class="chartjs-buttons" style="text-align:center; margin-top: 20px;">
            <div class="chart-type-controls-wrapper" style="margin-bottom: 20px;">
                <h6 style="color: #122867; font-weight: 600; margin-bottom: 1rem; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">
                    <i class="ri-bar-chart-2-line"></i> Chart Type
                </h6>
                <div class="btn-group" role="group" aria-label="Chart Type Selector">
                    <button type="button" data-qid="<?php echo $qqid; ?>" data-type="Bar" class="btn btn-sm btn-outline-primary chart-type-control <?php echo ($charttype == 'Bar') ? 'active' : ''; ?>" title="Bar Chart">
                        <i class="ri-bar-chart-line"></i> Bar
                    </button>
                    <button type="button" data-qid="<?php echo $qqid; ?>" data-type="Line" class="btn btn-sm btn-outline-primary chart-type-control <?php echo ($charttype == 'Line') ? 'active' : ''; ?>" title="Line Chart">
                        <i class="ri-line-chart-line"></i> Line
                    </button>
                    <button type="button" data-qid="<?php echo $qqid; ?>" data-type="Pie" class="btn btn-sm btn-outline-primary chart-type-control <?php echo ($charttype == 'Pie') ? 'active' : ''; ?>" title="Pie Chart">
                        <i class="ri-pie-chart-line"></i> Pie
                    </button>
                    <button type="button" data-qid="<?php echo $qqid; ?>" data-type="Doughnut" class="btn btn-sm btn-outline-primary chart-type-control <?php echo ($charttype == 'Doughnut') ? 'active' : ''; ?>" title="Doughnut Chart">
                        <i class="ri-donut-chart-line"></i> Doughnut
                    </button>
                </div>
            </div>

            <div class="download-controls-wrapper">
                <h6 style="color: #122867; font-weight: 600; margin-bottom: 1rem; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">
                    <i class="ri-download-cloud-line"></i> Export Options
                </h6>

                <!-- Download Chart Image -->
                <button type="button" data-qid="<?php echo $qqid; ?>" class="btn btn-success btn-download-chart" aria-label="Download Chart">
                    <i class="ri-download-line"></i>
                    <?php eT('Download Chart'); ?>
                </button>

                <!-- Download CSV -->
                <button type="button" data-qid="<?php echo $qqid; ?>" class="btn btn-info btn-download-csv" aria-label="Download CSV">
                    <i class="ri-file-excel-line"></i>
                    <?php eT('Download CSV'); ?>
                </button>
            </div>
        </div>

        <div id='stats_<?php echo $rt;?>' class='graphdisplay' style="text-align:center">
        </div>
        </div><!-- Close chart-section -->
    <?php else: ?>
        <div class="row">
            <div class="col-md-12">
                <?php
                $this->widget('ext.AlertWidget.AlertWidget', [
                    'text' => gT("Too many labels, can't generate chart"),
                    'type' => 'warning',
                ]);
                ?>
            </div>
        </div>
        </div><!-- Close chart-section -->
    <?php endif;?>

<?php //Simpler js-aggregation of values through global object. Approx 30% faster than parsing through eval ?>
<script>
    statisticsData['quid'+'<?php echo $qqid; ?>'] = {
        labels : <?php echo json_encode($labels); ?>,
        grawdata : <?php echo json_encode($grawdata); ?>, // the datas to generate the graph
        title : <?php echo json_encode($qtitle ?? ''); ?> // the question title
    };
</script>
</div><!-- Close question-card -->
<!-- endof  _statisticsoutput_graphs -->
