<?php
    /**
    * Statistic simple view
    *
    */


App()->getClientScript()->registerCssFile(Yii::app()->getConfig('publicurl') . 'assets/styles/statistics-custom-v2.css?v=' . time());
App()->getClientScript()->registerCssFile(Yii::app()->getConfig('publicurl') . 'assets/styles/sidebar-menu-beautiful.css?v=' . time());

?>

<!-- TEMPORARILY DISABLED - Custom Statistics Styling V2 - Clean Reset -->
<!-- <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->getConfig('publicurl'); ?>assets/styles/statistics-custom-v2.css?v=<?php echo time(); ?>"> -->

<!-- Export Libraries - Load before other scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous"></script>
<script src="https://unpkg.com/pptxgenjs@3.12.0/dist/pptxgen.bundle.js" crossorigin="anonymous"></script>

<script>
// Library availability check - detailed debugging
window.addEventListener('load', function() {
    console.log('=== Export Libraries Status ===');
    console.log('jsPDF available:', typeof window.jspdf !== 'undefined' || typeof jsPDF !== 'undefined');
    console.log('window.jspdf:', window.jspdf);
    console.log('PptxGenJS available:', typeof window.pptxgen !== 'undefined');
    console.log('window.pptxgen:', window.pptxgen);
    console.log('window.PptxGenJS:', window.PptxGenJS);

    // List all window properties containing 'pptx'
    var pptxProps = Object.keys(window).filter(function(k) {
        return k.toLowerCase().includes('pptx');
    });
    console.log('All PPTX-related properties:', pptxProps);
    console.log('================================');
});
</script>

<!-- Javascript variables  -->
<?php $this->renderPartial('/admin/export/statistics_subviews/_statistics_view_scripts', array('sStatisticsLanguage'=>$sStatisticsLanguage, 'surveyid'=>$surveyid, 'showtextinline'=>$showtextinline)) ; ?>

<div id='statisticsview' class='side-body' data-surveyid="<?php echo $surveyid; ?>">

    <div class="row">
        <div class="col-12">
            <div class="col-lg-3 text-start">
                <h4>
                    <span class="ri-bar-chart-fill"></span> &nbsp;&nbsp;&nbsp;
                    <?php eT("Statistics"); ?>
                </h4>
            </div>
            <div class="col-lg-9 text-end">
                <div class="mb-3">
                    <div >
                        <label for='completionstate' class="form-label"><?php eT("Include:"); ?> </label>
                        <?php
                        echo CHtml::dropDownList(
                            'completionstate',
                            incompleteAnsFilterState(),
                            array(
                                "all"=>gT("All responses",'unescaped'),
                                "complete"=>gT("Complete only",'unescaped'),
                                "incomplete"=>gT("Incomplete only",'unescaped'),
                            ),
                            array(
                                'class'=>'form-control',
                                'style'=>'display: inline;width: auto;',
                                'data-url'=>App()->createUrl('/admin/statistics/sa/setIncompleteanswers/')
                            ))
                        ;
                        ?>
                    </div>
                </div>

            </div>
        </div>
        <h3></h3>
    </div>


    <!-- Enhanced Export Toolbar -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="export-toolbar">
                <h5 class="toolbar-title">
                    <i class="ri-download-cloud-line"></i> Export Options
                </h5>

                <!-- Global Chart Type Selector -->
                <div class="global-chart-type-selector">
                    <label for="globalChartType" style="font-weight: 600; color: #122867; margin-right: 1rem;">
                        <i class="ri-bar-chart-2-line"></i> Chart Type for Export:
                    </label>
                    <div class="btn-group" role="group" aria-label="Global Chart Type Selector">
                        <button type="button" data-type="Bar" class="btn btn-sm btn-outline-primary global-chart-type-btn active" title="Export all as Bar Charts">
                            <i class="ri-bar-chart-line"></i> Bar
                        </button>
                        <button type="button" data-type="Line" class="btn btn-sm btn-outline-primary global-chart-type-btn" title="Export all as Line Charts">
                            <i class="ri-line-chart-line"></i> Line
                        </button>
                        <button type="button" data-type="Pie" class="btn btn-sm btn-outline-primary global-chart-type-btn" title="Export all as Pie Charts">
                            <i class="ri-pie-chart-line"></i> Pie
                        </button>
                        <button type="button" data-type="Doughnut" class="btn btn-sm btn-outline-primary global-chart-type-btn" title="Export all as Doughnut Charts">
                            <i class="ri-donut-chart-line"></i> Doughnut
                        </button>
                    </div>
                </div>

                <div class="export-buttons-group">
                    <button type="button" id="exportSummaryPDF" class="btn btn-export btn-pdf">
                        <i class="ri-file-pdf-line"></i>
                        <span>Export as PDF</span>
                    </button>
                    <button type="button" id="exportSummaryPPT" class="btn btn-export btn-ppt">
                        <i class="ri-file-ppt-line"></i>
                        <span>Export as PowerPoint</span>
                    </button>
                    <button type="button" id="statisticsExportImages" class="btn btn-export btn-images">
                        <i class="ri-image-line"></i>
                        <span>Export All Charts</span>
                    </button>
                    <button type="button" id="exportAllCSV" class="btn btn-export btn-csv">
                        <i class="ri-file-excel-line"></i>
                        <span>Export All Data (CSV)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dataset Comparison Filter -->
    <!-- <div class="row mb-4">
        <div class="col-12">
            <div class="comparison-panel">
                <h5 class="panel-title">
                    <i class="ri-git-compare-line"></i> Dataset Comparison
                </h5>
                <div class="comparison-filters">
                    <div class="filter-group">
                        <label for="dateRangeStart">Compare From:</label>
                        <input type="date" id="dateRangeStart" class="form-control">
                    </div>
                    <div class="filter-group">
                        <label for="dateRangeEnd">To:</label>
                        <input type="date" id="dateRangeEnd" class="form-control">
                    </div>
                    <div class="filter-group">
                        <label for="comparisonType">Comparison Type:</label>
                        <select id="comparisonType" class="form-control">
                            <option value="none">No Comparison</option>
                            <option value="date">Date Range</option>
                            <option value="demographic">By Demographics</option>
                            <option value="location">By Location</option>
                        </select>
                    </div>
                    <button type="button" id="applyComparison" class="btn btn-primary">
                        <i class="ri-refresh-line"></i> Apply Filters
                    </button>
                    <button type="button" id="resetFilters" class="btn btn-secondary">
                        <i class="ri-close-circle-line"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Qualitative Feedback Section -->
    <div class="row mb-4" id="qualitativeFeedbackSection" style="display: none;">
        <div class="col-12">
            <div class="feedback-panel">
                <h5 class="panel-title">
                    <i class="ri-chat-quote-line"></i> Qualitative Feedback Analysis
                    <button type="button" id="toggleFeedbackPanel" class="btn btn-sm btn-secondary float-end">
                        <i class="ri-arrow-up-s-line"></i> Collapse
                    </button>
                </h5>
                <div id="feedbackContent" class="feedback-content">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="feedback-subtitle">Word Cloud - Most Frequent Terms</h6>
                            <div id="wordCloudContainer" class="word-cloud-container">
                                <canvas id="wordCloudCanvas"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="feedback-subtitle">Comment Summary</h6>
                            <div id="commentSummary" class="comment-summary">
                                <p class="text-muted">Loading comments...</p>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="feedback-subtitle">All Comments by Question</h6>
                            <div id="commentsList" class="comments-list">
                                <!-- Comments will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 content-right">
            <input type="hidden" id="showGraphOnPageLoad" />
            <div id='statisticsoutput' class='statisticsfilters'>
                <?php echo $output; ?>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="completionstateSimpleStat"  />
