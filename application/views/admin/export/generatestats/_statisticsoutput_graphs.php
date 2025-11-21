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
 // TODO: move to controller
 $qqid = str_replace ( '-', '__' , (string) $qqid );
?>
<tr>
    <td colspan='4' id='statzone_<?php echo $rt;?>'>
    <?php if(count($labels) < 70): ?>
        <!-- Charts -->
        <div class="row chart-row-container">
            <div class="col-xl-8 col-12 chartjs-container text-center" id="chartjs-container-<?php echo $qqid; ?>"
                data-chartname="<?php echo $sChartname; // The name of the jschart object ?>"
                data-qid="<?php echo $qqid; // the question id ?>"
                data-type="<?php echo $charttype; // the chart start type (bar, donut, etc.) ?>"
                data-color="<?php echo $color; // the background color for bar, etc. ?>"
            >

            <?php if (array_sum($grawdata_percent)<1):?>
                <div class="stat-no-answer text-center" id="stat-no-answer-<?php echo $qqid; ?>">
                    <i class="ri-information-line" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                    <?php eT('Not enough response data');?>
                </div>
            <?php endif;?>

                <!-- a default width/height is provided from the server side. But it's overwritten by javascript-->
                <canvas class="canvas-chart" id="chartjs-<?php echo $qqid; ?>" width="<?php echo $canvaWidth?>" height="<?php echo $canvaHeight?>"
                    data-color="<?php echo $color; // the background color for bar, etc. ?>"></canvas>


            </div>
            <!-- legends -->
            <div class="legend legend-no-percent col-xl-4 col-12" id="legend-no-percent-<?php echo $qqid;?>">
                <h5 style="color: #122867; font-weight: 600; margin-bottom: 1rem; font-size: 1rem;">
                    <i class="ri-list-check"></i> Legend
                </h5>
                <?php foreach($labels as $i=>$label): ?>
                    <?php $colorindex = $color+$i; $colorindex = ($colorindex < 72)?$colorindex:0;?>
                    <div class="row legend-item" style="margin-bottom: 10px; align-items: center;">
                        <div class="col-2 col-md-1">
                            <span style="background-color:rgba(<?php echo $COLORS_FOR_SURVEY[$colorindex];?>,0.7) !important; display: block; width: 22px; height: 22px; border-radius: 50%; margin: 0px; padding: 0px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            </span>
                        </div>
                        <div class="col-10 col-md-11">
                            <?php echo $label;?>
                        </div>
                    </div>
                <?php endforeach;?>
            </div>
        </div>
    </td>
</tr>

<!-- Buttons to change graph type -->
<tr class="d-print-none">
    <td colspan='4'>
        <div class="chartjs-buttons" style="text-align:center">
            <div class="chart-controls-wrapper" style="margin-bottom: 1rem;">
                <h6 style="color: #122867; font-weight: 600; margin-bottom: 1rem; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">
                    <i class="ri-layout-grid-line"></i> Chart Types
                </h6>

                <!-- Bar chart -->
                <button type="button" data-qid="<?php echo $qqid; ?>" id="button-chartjs-Bar-<?php echo $qqid; ?>" class="btn btn-outline-secondary chart-type-control" data-type="Bar" data-canva-id="chartjs-Bar-<?php echo $qqid; ?>" aria-label="Bar Chart">
                    <i class="ri-bar-chart-fill"></i>
                    <?php eT('Bar chart'); ?>
                </button>

                <!-- Pie chart -->
                <button type="button" data-qid="<?php echo $qqid; ?>" id="button-chartjs-Pie-<?php echo $qqid; ?>" class="btn btn-outline-secondary chart-type-control" data-type="Pie" data-canva-id="chartjs-Pie-<?php echo $qqid; ?>" aria-label="Pie Chart">
                    <i class="ri-pie-chart-fill"></i>
                    <?php eT('Pie chart'); ?>
                </button>

                <!-- Radar chart -->
                <button type="button" data-qid="<?php echo $qqid; ?>" id="button-chartjs-Radar-<?php echo $qqid; ?>" class="btn btn-outline-secondary chart-type-control" data-type="Radar" data-canva-id="chartjs-Radar-<?php echo $qqid; ?>" aria-label="Radar Chart">
                    <i class="ri-focus-3-line"></i>
                    <?php eT('Radar chart'); ?>
                </button>

                <!-- Line chart -->
                <button type="button" data-qid="<?php echo $qqid; ?>" id="button-chartjs-Line-<?php echo $qqid; ?>" class="btn btn-outline-secondary chart-type-control" data-type="Line" data-canva-id="chartjs-Line-<?php echo $qqid; ?>" aria-label="Line Chart">
                    <i class="ri-line-chart-fill"></i>
                    <?php eT('Line chart'); ?>
                </button>

                <!-- Polar chart -->
                <button type="button" data-qid="<?php echo $qqid; ?>" id="button-chartjs-PolarArea-<?php echo $qqid; ?>" class="btn btn-outline-secondary chart-type-control" data-type="PolarArea" data-canva-id="chartjs-PolarArea-<?php echo $qqid; ?>" aria-label="Polar Chart">
                    <i class="ri-sun-line"></i>
                    <?php eT('Polar chart'); ?>
                </button>

                <!-- Doughnut chart -->
                <button type="button" data-qid="<?php echo $qqid; ?>" id="button-chartjs-Doughnut-<?php echo $qqid; ?>" class="btn btn-outline-secondary chart-type-control" data-type="Doughnut" data-canva-id="chartjs-Doughnut-<?php echo $qqid; ?>" aria-label="Doughnut Chart">
                    <i class="ri-checkbox-blank-circle-line"></i>
                    <?php eT('Doughnut chart'); ?>
                </button>
            </div>

            <div style="margin: 1.5rem 0; border-top: 2px solid rgba(18, 40, 103, 0.1);"></div>

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
    <?php endif;?>
    </td>
</tr>
<?php
/*<script>
    var labels_<?php echo $qqid; ?>=<?php echo json_encode($graph_labels); // the array of labels ?>;
    var grawdata_<?php echo $qqid;?>=<?php echo json_encode($grawdata); // the datas to generate the graph ?>;
    var labels_percent_<?php echo $qqid; ?>=<?php echo json_encode($graph_labels_percent); // the array of labels ?>;
    var grawdata_percent_<?php echo $qqid;?>=<?php echo json_encode($grawdata_percent); // the datas to generate the graph using percentages (pie, Doughnut, polar ) ?>;
</script>*/
?>
