<?php

/*
 * LimeSurvey
 * Copyright (C) 2007-2011 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 */

/**
 * Statistics Controller
 *
 * This controller performs statistics actions
 *
 * @package        LimeSurvey
 * @subpackage    Backend
 */
class Statistics extends SurveyCommonAction
{
    public function __construct($controller, $id)
    {
        parent::__construct($controller, $id);

        Yii::app()->loadHelper("surveytranslator");
    }

    /**
     * Constructor
     */
    public function run($surveyid = 0, $subaction = null)
    {
        $surveyid = sanitize_int($surveyid);
        $imageurl = Yii::app()->getConfig("imageurl");
        $aData = array('imageurl' => $imageurl);

        /*
         * We need this later:
         *  1 - Array dual scale
         *  5 - 5 point choice
         *  A - Array (5 point choice)
         *  B - Array (10 point choice)
         *  C - Array (Yes/No/Uncertain)
         *  D - Date
         *  E - Array (Increase, Same, Decrease)
         *  F - Array
         *  G - Gender
         *  H - Array by Column
         *  I - Language Switch
         *  K - Multiple numerical input
         *  L - List (Radio)
         *  M - Multiple choice
         *  N - Numerical input
         *  O - List With Comment
         *  P - Multiple choice with comments
         *  Q - Multiple short text
         *  R - Ranking
         *  S - Short free text
         *  T - Long free text
         *  U - Huge free text
         *  X - Boilerplate Question
         *  Y - Yes/No
         *  ! - List (Dropdown)
         *  | - File Upload

         Debugging help:
         echo '<script language="javascript" type="text/javascript">alert("HI");</script>';
         */

        //split up results to extend statistics -> NOT WORKING YET! DO NOT ENABLE THIS!
        $showcombinedresults = 0;

        /*
         * this variable is used in the function shortencode() which cuts off a question/answer title
         * after $maxchars and shows the rest as tooltip
         */
        $maxchars = 50;

        //we collect all the output within this variable
        $statisticsoutput = '';

        //output for chosing questions to cross query
        $cr_statisticsoutput = '';

        // This gets all the 'to be shown questions' from the POST and puts these into an array
        $summary = returnGlobal('summary');
        $statlang = returnGlobal('statlang');

        //if $summary isn't an array we create one
        if (isset($summary) && !is_array($summary)) {
            $summary = explode("+", (string) $summary);
        }

        //no survey ID? -> come and get one
        if (!isset($surveyid)) {
            $surveyid = returnGlobal('sid');
        }

        //still no survey ID -> error
        $aData['surveyid'] = $surveyid;

        if (!Permission::model()->hasSurveyPermission($surveyid, 'statistics', 'read')) {
            throw new CHttpException(403, gT("You do not have permission to access this page."));
        }

        $oSurvey = Survey::model()->findByPk($surveyid);
        if (!$oSurvey) {
            Yii::app()->setFlashMessage(gT("Invalid survey ID"), 'error');
            $this->getController()->redirect($this->getController()->createUrl("dashboard/view"));
        }

        if (!$oSurvey->isActive) {
            Yii::app()->setFlashMessage(gT("This survey is not active and has no responses."), 'error');
            $this->getController()->redirect($this->getController()->createUrl("/surveyAdministration/view/surveyid/{$surveyid}"));
        }

        // Set language for questions and answers to base language of this survey
        $aData['language'] = $oSurvey->language;
        $language = $oSurvey->language;

        //Call the javascript file
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'statistics.js', CClientScript::POS_BEGIN);
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'json-js/json2.min.js');
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'createpdf_worker.js');
        yii::app()->clientScript->registerPackage('jszip');
        $aData['display']['menu_bars']['browse'] = gT("Quick statistics");

        //Select public language file
        $row = Survey::model()->find('sid = :sid', array(':sid' => $surveyid));

        /*
         * check if there is a datestamp available for this survey
         * yes -> $datestamp="Y"
         * no -> $datestamp="N"
         */
        $datestamp = $row->datestamp;

        // 1: Get list of questions from survey

        /*
         * We want to have the following data
         * a) "questions" -> all table namens, e.g.
         * qid
         * sid
         * gid
         * type
         * title
         * question
         * preg
         * help
         * other
         * mandatory
         * lid
         * lid1
         * question_order
         * language
         *
         * b) "groups" -> group_name + group_order *
         */

         $rows = Question::model()->primary()->getQuestionList($surveyid);

        //put the question information into the filter array
        $filters = array();
        $aGroups = array();
        $keyone = 0;
        foreach ($rows as $row) {
            $sGroupName = $row->group->questiongroupl10ns[$language]->group_name;

            //store some column names in $filters array
            $filters[] = array(
                $row['qid'],
                $row['gid'],
                $row['type'],
                $row['title'],
                $sGroupName,
                flattenText($row->questionl10ns[$language]['question'])
            );

            if (!in_array($sGroupName, $aGroups)) {
                //$aGroups[] = $sGroupName;
                $aGroups[$sGroupName]['gid'] = $row['gid'];
                $aGroups[$sGroupName]['name'] = $sGroupName;
            }
            $aGroups[$sGroupName]['questions'][$keyone] = array(
                $row['qid'],
                $row['gid'],
                $row['type'],
                $row['title'],
                $sGroupName,
                flattenText($row->questionl10ns[$language]['question'])
            );
            $keyone = $keyone + 1;
        }
        $aData['filters'] = $filters;
        $aData['aGroups'] = $aGroups;
        // SHOW ID FIELD

        $grapherror = false;
        $error = '';
        $usegraph = (int) Yii::app()->request->getPost('usegraph', 0);
        if (!function_exists("gd_info")) {
            $grapherror = true;
            $error .= '<br />' . gT('You do not have the GD Library installed. Showing charts requires the GD library to function properly.');
            $error .= '<br />' . gT('visit http://us2.php.net/manual/en/ref.image.php for more information') . '<br />';
        } elseif (!function_exists("imageftbbox")) {
            $grapherror = true;
            $error .= '<br />' . gT('You do not have the Freetype Library installed. Showing charts requires the Freetype library to function properly.');
            $error .= '<br />' . gT('visit http://us2.php.net/manual/en/ref.image.php for more information') . '<br />';
        }

        if ($grapherror) {
            $usegraph = 0;
        }


        //pre-selection of filter forms
        if (incompleteAnsFilterState() == "complete") {
            $selecthide = "selected='selected'";
            $selectshow = "";
            $selectinc = "";
        } elseif (incompleteAnsFilterState() == "incomplete") {
            $selecthide = "";
            $selectshow = "";
            $selectinc = "selected='selected'";
        } else {
            $selecthide = "";
            $selectshow = "selected='selected'";
            $selectinc = "";
        }
        $aData['selecthide'] = $selecthide;
        $aData['selectshow'] = $selectshow;
        $aData['selectinc'] = $selectinc;
        $aData['error'] = $error;

        $survlangs = $oSurvey->allLanguages;
        $aData['survlangs'] = $survlangs;
        $aData['datestamp'] = $datestamp;

        //if the survey contains timestamps you can filter by timestamp, too

        //Output selector

        //second row below options -> filter settings headline

        $filterchoice_state = returnGlobal('filterchoice_state');
        $aData['filterchoice_state'] = $filterchoice_state;


        /*
         * let's go through the filter array which contains
         *     ['qid'],
         ['gid'],
         ['type'],
         ['title'],
         ['group_name'],
         ['question'],
         ['lid'],
         ['lid1']);
         */

        $currentgroup = '';
        $counter = 0;
        foreach ($filters as $key1 => $flt) {
            //is there a previous question type set?


            /*
             * remember: $flt is structured like this
             *  ['qid'],
             ['gid'],
             ['type'],
             ['title'],
             ['group_name'],
             ['question'],
             ['lid'],
             ['lid1']);
             */

            //SGQ identifier

            //full question title

            /*
             * Check question type: This question types will be used (all others are separated in the if clause)
             *  5 - 5 Point Choice
             G - Gender
             I - Language Switch
             L - List (Radio)
             M - Multiple choice
             N - Numerical input
             | - File Upload
             O - List With Comment
             P - Multiple choice with comments
             Y - Yes/No
             ! - List (Dropdown) )
             */


            /////////////////////////////////////////////////////////////////////////////////////////////////
            //This section presents the filter list, in various different ways depending on the question type
            /////////////////////////////////////////////////////////////////////////////////////////////////

            //let's switch through the question type for each question
            switch ($flt[2]) {
                case Question::QT_K_MULTIPLE_NUMERICAL: // Multiple Numerical
                    //get answers
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1]['key1'] = $result;
                    break;



                case Question::QT_Q_MULTIPLE_SHORT_TEXT:
                    //get subqestions
                    $result = Question::model()->getQuestionsForStatistics('title as code, question as answer', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;

                    //----------------------- ARRAYS --------------------------

                case Question::QT_A_ARRAY_5_POINT:
                    //get answers
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;



                    //just like above only a different loop
                case Question::QT_B_ARRAY_10_CHOICE_QUESTIONS: // Array of 10 point choice questions
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;



                case Question::QT_C_ARRAY_YES_UNCERTAIN_NO: // ARRAY OF YES\No\gT("Uncertain") QUESTIONS
                    //get answers
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;



                    //similiar to the above one
                case Question::QT_E_ARRAY_INC_SAME_DEC: // Array of Increase/Same/Decrease questions
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;

                case Question::QT_SEMICOLON_ARRAY_TEXT:  // Array (Text)
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND scale_id = 0 AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    foreach ($result as $key => $row) {
                        $fresult = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND scale_id = 1 AND language = '{$language}'", 'question_order');
                        $aData['fresults'][$key1][$key] = $fresult;
                    }
                    break;

                case Question::QT_COLON_ARRAY_NUMBERS:  // Array (Numbers)
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND scale_id = 0 AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    foreach ($result as $row) {
                        $fresult = Question::model()->getQuestionsForStatistics('*', "parent_qid=$flt[0] AND scale_id = 1 AND language = '{$language}'", 'question_order, title');
                        $aData['fresults'][$key1] = $fresult;
                    }
                    break;
                    /*
                     * For question type "F" and "H" you can use labels.
                     * The only difference is that the labels are applied to column heading
                     * or rows respectively
                     */
                case Question::QT_F_ARRAY: // Array
                case Question::QT_H_ARRAY_COLUMN: // Array (By Column)
                    //Get answers. We always use the answer code because the label might be too long elsewise
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;

                    //check all the answers
                    foreach ($result as $row) {
                        $fresult = Answer::model()->getAnswersForStatistics('*', "qid=$flt[0] AND language = '{$language}'", 'sortorder, code');
                        $aData['fresults'][$key1] = $fresult;
                    }

                    //$statisticsoutput .= "\t\t\t\t<td>\n";
                    $counter = 0;
                    break;



                case Question::QT_R_RANKING: // Ranking
                    //get some answers
                    $result = Answer::model()->getAnswersForStatistics('code, answer', "qid=$flt[0] AND language = '{$language}'", 'sortorder, code');
                    $aData['result'][$key1] = $result;
                    break;

                case Question::QT_1_ARRAY_DUAL:
                    //get answers
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    //loop through answers
                    foreach ($result as $key => $row) {
                        //check if there is a dualscale_headerA/B
                        $dshresult = QuestionAttribute::model()->getQuestionsForStatistics('value', "qid=$flt[0] AND attribute = 'dualscale_headerA' AND language = '{$language}'", '');
                        $aData['dshresults'][$key1][$key] = $dshresult;


                        $fresult = Answer::model()->getAnswersForStatistics('*', "qid=$flt[0] AND scale_id = 0 AND language = '{$language}'", 'sortorder, code');

                        $aData['fresults'][$key1][$key] = $fresult;


                        $dshresult2 = QuestionAttribute::model()->getQuestionsForStatistics('value', "qid=$flt[0] AND attribute = 'dualscale_headerB' AND language = '{$language}'", '');
                        $aData['dshresults2'][$key1][$key] = $dshresult2;
                    }
                    break;

                case Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS:  //P - Multiple choice with comments
                case Question::QT_M_MULTIPLE_CHOICE:
                    //get answers
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid = $flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;


                    /*
                     * This question types use the default settings:
                     *     L - List (Radio)
                     O - List With Comment
                     P - Multiple choice with comments
                     ! - List (Dropdown)
                     */
                default:
                    //get answers
                    $result = Answer::model()->findAll("qid=" . $flt[0]);
                    $aData['result'][$key1] = $result;
                    break;
            }    //end switch -> check question types and create filter forms

            $currentgroup = $flt[1];

            $counter++;

            //temporary save the type of the previous question
            //used to adjust linebreaks
            $previousquestiontype = $flt[2];
        }

        // ----------------------------------- END FILTER FORM ---------------------------------------

        Yii::app()->loadHelper('admin/statistics');
        $helper = new statistics_helper();
        $showtextinline = (int) Yii::app()->request->getPost('showtextinline', 0);
        $aData['showtextinline'] = $showtextinline;
        $aData['usegraph'] = $usegraph;

        //Show Summary results
        if (isset($summary) && $summary) {
            $outputType = Yii::app()->request->getPost('outputtype', 'html');
            switch ($outputType) {
                case 'html':
                    $statisticsoutput .= $helper->generate_html_chartjs_statistics($surveyid, $summary, $summary, $usegraph, $outputType, 'DD', $statlang);
                    break;
                case 'pdf':
                    $helper->generate_statistics($surveyid, $summary, $summary, $usegraph, $outputType, 'D', $statlang);
                    exit;
                    break;
                case 'xls':
                    $helper->generate_statistics($surveyid, $summary, $summary, $usegraph, $outputType, 'DD', $statlang);
                    exit;
                    break;
                default:
                    break;
            }
        }    //end if -> show summary results

        $aData['sStatisticsLanguage'] = $statlang;
        $aData['output'] = $statisticsoutput;
        $aData['summary'] = $summary;


        $error = '';
        if (!function_exists("gd_info")) {
            $error .= '<br />' . gT('You do not have the GD Library installed. Showing charts requires the GD library to function properly.');
            $error .= '<br />' . gT('visit http://us2.php.net/manual/en/ref.image.php for more information') . '<br />';
        } elseif (!function_exists("imageftbbox")) {
            $error .= '<br />' . gT('You do not have the Freetype Library installed. Showing charts requires the Freetype library to function properly.');
            $error .= '<br />' . gT('visit http://us2.php.net/manual/en/ref.image.php for more information') . '<br />';
        }

        $aData['error'] = $error;
        $aData['oStatisticsHelper'] = $helper;
        $aData['fresults'] = $aData['fresults'] ?? false;
        $aData['dateformatdetails'] = getDateFormatData(Yii::app()->session['dateformat']);
        $aData['expertstats'] = false;

        if (!isset($aData['result'])) {
            $aData['result'] = null;
        }

        $this->renderWrappedTemplate('export', 'statistics_view', $aData);
    }


    /**
     *  Returns a simple list of values in a particular column, that meet the requirements of the SQL
     */
    public function listcolumn($surveyid, $column, $sortby = "", $sortmethod = "", $sorttype = "")
    {
        if (!Permission::model()->hasSurveyPermission($surveyid, 'statistics', 'read')) {
            throw new CHttpException(403, gT("You do not have permission to access this page."));
        }
        Yii::app()->loadHelper('admin/statistics');
        $helper = new statistics_helper();
        $aData['data'] = $helper->_listcolumn($surveyid, $column, $sortby, $sortmethod, $sorttype);
        $aData['surveyid'] = $surveyid;
        $aData['column'] = $column;
        $aData['sortby'] = $sortby;
        $aData['sortmethod'] = $sortmethod;
        $aData['sorttype'] = $sorttype;
        App()->getClientScript()->reset();
        $this->getController()->render('export/statistics_browse_view', $aData);
    }


    public function graph()
    {
        Yii::app()->loadHelper('admin/statistics');
        Yii::app()->loadHelper("surveytranslator");

        // Initialise PCHART
        require_once(Yii::app()->basePath . '/../vendor/pchart/pChart.class.php');
        require_once(Yii::app()->basePath . '/../vendor/pchart/pData.class.php');
        require_once(Yii::app()->basePath . '/../vendor/pchart/pCache.class.php');

        $tempdir = Yii::app()->getConfig("tempdir");
        $MyCache = new pCache($tempdir . '/');
        $aData['success'] = 1;

        if (isset($_POST['cmd']) && isset($_POST['id'])) {
            $sStatisticsLanguage = sanitize_languagecode($_POST['sStatisticsLanguage']);
            $sQCode = $_POST['id'];
            if (!is_numeric(substr((string) $sQCode, 0, 1))) {
                // Strip first char when not numeric (probably T or D)
                $sQCode = substr((string) $sQCode, 1);
            }
            list($qsid, $qgid, $qqid) = explode("X", substr((string) $sQCode, 0), 3);

            if (!Permission::model()->hasSurveyPermission($qsid, 'statistics', 'read')) {
                throw new CHttpException(403, gT("You do not have permission to access this page."));
            }

            $oSurvey = Survey::model()->findByPk($qsid);

            $aFieldmap = createFieldMap($oSurvey, 'full', false, false, $sStatisticsLanguage);
            $qtype = $aFieldmap[$sQCode]['type'];
            $qqid = $aFieldmap[$sQCode]['qid'];
            $aattr = QuestionAttribute::model()->getQuestionAttributes(Question::model()->findByPk($qqid));
            $field = substr((string) $_POST['id'], 1);

            switch ($_POST['cmd']) {
                case 'showmap':
                    if (isset($aattr['location_mapservice'])) {
                        $aData['mapdata'] = array(
                            "coord" => getQuestionMapData($field, $qsid),
                            "zoom" => $aattr['location_mapzoom'],
                            "width" => $aattr['location_mapwidth'],
                            "height" => $aattr['location_mapheight']
                        );
                        QuestionAttribute::model()->setQuestionAttribute($qqid, 'statistics_showmap', 1);
                    } else {
                        $aData['success'] = 0;
                    }
                    break;
                case 'hidemap':
                    if (isset($aattr['location_mapservice'])) {
                        $aData['success'] = 1;
                        QuestionAttribute::model()->setQuestionAttribute($qqid, 'statistics_showmap', 0);
                    } else {
                        $aData['success'] = 0;
                    }
                    break;
                case 'showgraph':
                    if (isset($aattr['location_mapservice'])) {
                        $aData['mapdata'] = array(
                            "coord" => getQuestionMapData($field, $qsid),
                            "zoom" => $aattr['location_mapzoom'],
                            "width" => $aattr['location_mapwidth'],
                            "height" => $aattr['location_mapheight']
                        );
                    }

                    $bChartType = $qtype != Question::QT_M_MULTIPLE_CHOICE && $qtype != Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS && $aattr["statistics_graphtype"] == "1";
                    $adata = Yii::app()->session['stats'][$_POST['id']];
                    $aData['chartdata'] = createChart($qqid, $qsid, $bChartType, $adata['lbl'], $adata['gdata'], $adata['grawdata'], $MyCache, $sStatisticsLanguage, $qtype);


                    QuestionAttribute::model()->setQuestionAttribute($qqid, 'statistics_showgraph', 1);
                    break;
                case 'hidegraph':
                    QuestionAttribute::model()->setQuestionAttribute($qqid, 'statistics_showgraph', 0);
                    break;
                case 'showbar':
                    if ($qtype == Question::QT_M_MULTIPLE_CHOICE || $qtype == Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS) {
                        $aData['success'] = 0;
                        break;
                    }

                    QuestionAttribute::model()->setQuestionAttribute($qqid, 'statistics_graphtype', 0);

                    $adata = Yii::app()->session['stats'][$_POST['id']];

                    $aData['chartdata'] = createChart($qqid, $qsid, 0, $adata['lbl'], $adata['gdata'], $adata['grawdata'], $MyCache, $sStatisticsLanguage, $qtype);

                    break;
                case 'showpie':
                    if ($qtype == Question::QT_M_MULTIPLE_CHOICE || $qtype == Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS) {
                        $aData['success'] = 0;
                        break;
                    }

                    QuestionAttribute::model()->setQuestionAttribute($qqid, 'statistics_graphtype', 1);

                    $adata = Yii::app()->session['stats'][$_POST['id']];
                    $aData['chartdata'] = createChart($qqid, $qsid, 1, $adata['lbl'], $adata['gdata'], $adata['grawdata'], $MyCache, $sStatisticsLanguage, $qtype);


                    break;
                default:
                    $aData['success'] = 0;
                    break;
            }
        } else {
            $aData['success'] = 0;
        }

        //$this->renderWrappedTemplate('export', 'statistics_graph_view', $aData);
        $this->getController()->renderPartial('export/statistics_graph_view', $aData);
    }

    /**
     * Render satistics for users
     */
    public function simpleStatistics($surveyid)
    {
        $usegraph = 1;
        $iSurveyId = sanitize_int($surveyid);
        $aData['surveyid'] = $iSurveyId;
        $showcombinedresults = 0;
        $maxchars = 50;
        $statisticsoutput = '';
        $cr_statisticsoutput = '';

        if (!Permission::model()->hasSurveyPermission($surveyid, 'statistics', 'read')) {
            throw new CHttpException(403, gT("You do not have permission to access this page."));
        }
        $oSurvey = Survey::model()->findByPk($surveyid);

        if (!$oSurvey) {
            Yii::app()->setFlashMessage(gT("Invalid survey ID"), 'error');
            $this->getController()->redirect($this->getController()->createUrl("dashboard/view"));
        }

        if (!$oSurvey->isActive) {
            Yii::app()->setFlashMessage(gT("This survey is not active and has no responses."), 'error');
            $this->getController()->redirect($this->getController()->createUrl("/surveyAdministration/view/surveyid/{$iSurveyId}"));
        }

        // Set language for questions and answers to base language of this survey
        $language = $oSurvey->language;
        $summary = array();
        $summary[0] = "datestampE";
        $summary[1] = "datestampG";
        $summary[2] = "datestampL";
        $summary[3] = "idG";
        $summary[4] = "idL";

        // 1: Get list of questions from survey
        $rows = Question::model()->primary()->getQuestionList($surveyid);
        ;

        $rawQuestions = Question::model()->findAllByAttributes([
            "sid" => $surveyid,
            "type" => Question::QT_COLON_ARRAY_NUMBERS
        ]);

        $questions = [];

        foreach ($rawQuestions as $rawQuestion) {
            $questions[$rawQuestion->qid] = $rawQuestion;
        }

        // The questions to display (all question)
        foreach ($rows as $row) {
            $type = $row['type'];
            switch ($type) {
                // Double scale cases
                case Question::QT_COLON_ARRAY_NUMBERS:
                    $qidattributes = QuestionAttribute::model()->getQuestionAttributes($questions[$row['qid']] ?? $row['qid']);
                    if (!$qidattributes['input_boxes']) {
                        $qid = $row['qid'];
                        $results = Question::model()->getQuestionsForStatistics('*', "parent_qid='$qid'  AND scale_id = 0", 'question_order, title');
                        $fresults = Question::model()->getQuestionsForStatistics('*', "parent_qid='$qid'  AND scale_id = 1", 'question_order, title');
                        foreach ($results as $row1) {
                            foreach ($fresults as $row2) {
                                $summary[] = $iSurveyId . 'X' . $row['gid'] . 'X' . $row['qid'] . $row1['title'] . '_' . $row2['title'];
                            }
                        }
                    }
                    break;

                case Question::QT_1_ARRAY_DUAL:
                    $qid = $row['qid'];
                    $results = Question::model()->getQuestionsForStatistics('*', "parent_qid='$qid' ", 'question_order, title');
                    foreach ($results as $row1) {
                        $summary[] = $iSurveyId . 'X' . $row['gid'] . 'X' . $row['qid'] . $row1['title'] . '#0';
                        $summary[] = $iSurveyId . 'X' . $row['gid'] . 'X' . $row['qid'] . $row1['title'] . '#1';
                    }

                    break;

                case Question::QT_R_RANKING: // Ranking
                    $qid = $row['qid'];
                    $results = Question::model()->getQuestionsForStatistics('title, question', "parent_qid='$qid' ", 'question_order');
                    $count = count($results);
                    //loop through all answers. if there are 3 items to rate there will be 3 statistics
                    for ($i = 1; $i <= $count; $i++) {
                        $summary[] = $type . $iSurveyId . 'X' . $row['gid'] . 'X' . $row['qid'] . '-' . $i;
                    }
                    break;

                    // Cases with subquestions
                case Question::QT_A_ARRAY_5_POINT:
                case Question::QT_F_ARRAY: // Array
                case Question::QT_H_ARRAY_COLUMN: // Array (By Column)
                case Question::QT_E_ARRAY_INC_SAME_DEC:
                case Question::QT_B_ARRAY_10_CHOICE_QUESTIONS:
                case Question::QT_C_ARRAY_YES_UNCERTAIN_NO:
                    //loop through all answers. if there are 3 items to rate there will be 3 statistics
                    $qid = $row['qid'];
                    $results = Question::model()->getQuestionsForStatistics('title, question', "parent_qid='$qid' ", 'question_order');
                    foreach ($results as $row1) {
                        $summary[] = $iSurveyId . 'X' . $row['gid'] . 'X' . $row['qid'] . $row1['title'];
                    }
                    break;

                    // Cases with subanwsers, need a question type as first letter
                case Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS:  //P - Multiple choice with comments
                case Question::QT_M_MULTIPLE_CHOICE:  //M - Multiple choice
                case Question::QT_S_SHORT_FREE_TEXT:
                case Question::QT_T_LONG_FREE_TEXT: // Long free text
                case Question::QT_N_NUMERICAL:
                    $summary[] = $type . $iSurveyId . 'X' . $row['gid'] . 'X' . $row['qid'];
                    break;

                    // Not shown (else would only show 'no answer' )
                case Question::QT_K_MULTIPLE_NUMERICAL:
                case Question::QT_ASTERISK_EQUATION:
                case Question::QT_D_DATE:
                case Question::QT_VERTICAL_FILE_UPLOAD: // File Upload, we don't show it
                case Question::QT_U_HUGE_FREE_TEXT: // Huge free text
                case Question::QT_Q_MULTIPLE_SHORT_TEXT:
                case Question::QT_SEMICOLON_ARRAY_TEXT:
                case Question::QT_X_TEXT_DISPLAY:
                    break;


                default:
                    $summary[] = $iSurveyId . 'X' . $row['gid'] . 'X' . $row['qid'];
                    break;
            }
        }

        // ----------------------------------- END FILTER FORM ---------------------------------------

        Yii::app()->loadHelper('admin/statistics');
        $helper = new statistics_helper();
        $showtextinline = (int) Yii::app()->request->getPost('showtextinline', 0);
        $aData['showtextinline'] = $showtextinline;

        //Show Summary results
        $aData['usegraph'] = $usegraph;
        $outputType = 'html';
        $statlang = returnGlobal('statlang');
        $statisticsoutput .= $helper->generate_simple_statistics($surveyid, $summary, $summary, $usegraph, $outputType, 'DD', $statlang);

        $aData['sStatisticsLanguage'] = $statlang;
        $aData['output'] = $statisticsoutput;
        $aData['summary'] = $summary;
        $aData['oStatisticsHelper'] = $helper;
        $aData['expertstats'] = true;

        //Call the javascript file
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'statistics.js', CClientScript::POS_BEGIN);
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'json-js/json2.min.js');
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'createpdf_worker.js');
        yii::app()->clientScript->registerPackage('jspdf');
        yii::app()->clientScript->registerPackage('jszip');
        echo $this->renderWrappedTemplate('export', 'statistics_user_view', $aData);
    }


    public function setIncompleteanswers()
    {
        $sIncompleteAnswers = Yii::app()->request->getPost('state');
        if (in_array($sIncompleteAnswers, array('all', 'complete', 'incomplete'))) {
            Yii::app()->session['incompleteanswers'] = $sIncompleteAnswers;
        }
    }

    /**
     * Get available filter questions for a survey
     * Returns questions that can be used for filtering statistics
     */
    public function getFilterQuestions()
    {
        try {
            $surveyid = Yii::app()->request->getParam('surveyid');

            if (!$surveyid) {
                header('HTTP/1.0 400 Bad Request');
                echo json_encode(array('error' => 'Survey ID required'));
                return;
            }

            if (!Permission::model()->hasSurveyPermission($surveyid, 'statistics', 'read')) {
                header('HTTP/1.0 403 Forbidden');
                echo json_encode(array('error' => 'Permission denied'));
                return;
            }

            $filterQuestions = $this->detectFilterQuestions($surveyid);

            header('Content-Type: application/json');
            echo json_encode($filterQuestions);
        } catch (Exception $e) {
            Yii::log('Error in getFilterQuestions: ' . $e->getMessage(), 'error');
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(array('error' => $e->getMessage(), 'trace' => $e->getTraceAsString()));
        }
    }

    /**
     * Auto-detect questions that can be used as filters
     */
    private function detectFilterQuestions($surveyid)
    {
        Yii::log('detectFilterQuestions called for survey: ' . $surveyid, 'info');

        // Valid question types for filtering (dropdown, radio, multiple choice)
        // Using actual type codes instead of constants for compatibility
        $validTypes = array('L', '!', 'O', 'Y', 'G'); // L=List(Radio), !=Dropdown, O=List with comment, Y=Yes/No, G=Gender

        $questions = Question::model()->findAllByAttributes(array(
            'sid' => $surveyid,
            'parent_qid' => 0,
        ));

        Yii::log('Total questions found: ' . count($questions), 'info');

        // Expanded keywords for better detection
        $filterKeywords = array(
            'department', 'dept', 'dpt', 'depart',
            'semester', 'sem',
            'location', 'loc', 'site', 'campus',
            'cohort', 'batch', 'class', 'group',
            'faculty', 'instructor', 'teacher', 'professor',
            'branch', 'division', 'unit', 'section',
            'program', 'programme', 'course',
            'year', 'level',
            'gender', 'sex'
        );
        $filters = array();

        // Get survey language
        $oSurvey = Survey::model()->findByPk($surveyid);
        $language = $oSurvey->language;

        foreach ($questions as $q) {
            if (!in_array($q->type, $validTypes)) {
                continue;
            }

            // Get question text from localized table
            $questionL10n = QuestionL10n::model()->findByAttributes(array(
                'qid' => $q->qid,
                'language' => $language
            ));

            if (!$questionL10n) {
                continue;
            }

            $title = strtolower(strip_tags($questionL10n->question));
            $code = strtolower($q->title);
            $score = 0;
            $matchedKeyword = null;

            // Check question code first (most reliable)
            foreach ($filterKeywords as $keyword) {
                if ($code === $keyword || $code === strtoupper($keyword) || strpos($code, $keyword) !== false) {
                    $score += 20;
                    $matchedKeyword = $keyword;
                    Yii::log('Matched by code - Q' . $q->qid . ': ' . $q->title . ' (keyword: ' . $keyword . ')', 'info');
                    break;
                }
            }

            // Check question title
            if (!$matchedKeyword) {
                foreach ($filterKeywords as $keyword) {
                    if (strpos($title, $keyword) !== false) {
                        // Higher score if keyword is at the start
                        if (strpos($title, $keyword) === 0 ||
                            preg_match('/^(your|which|select|choose|what|enter)\s+' . $keyword . '/i', $title)) {
                            $score += 15;
                        } else {
                            $score += 8;
                        }

                        // Don't penalize rating questions as heavily - they might still be useful filters
                        if (strpos($title, 'rate') !== false || strpos($title, 'how would you') !== false) {
                            $score -= 5;
                        }

                        $matchedKeyword = $keyword;
                        Yii::log('Matched by title - Q' . $q->qid . ': ' . $title . ' (keyword: ' . $keyword . ', score: ' . $score . ')', 'info');
                        break;
                    }
                }
            }

            if ($score > 0) {
                // Get answer options for this question
                $options = $this->getQuestionOptions($q->qid, $surveyid);

                if (!empty($options)) {
                    $questionText = $questionL10n ? strip_tags($questionL10n->question) : strip_tags($q->question);
                    $filters[] = array(
                        'qid' => $q->qid,
                        'gid' => $q->gid,
                        'question' => $questionText,
                        'code' => $q->title,
                        'type' => $matchedKeyword,
                        'fieldName' => $surveyid . 'X' . $q->gid . 'X' . $q->qid,
                        'options' => $options,
                        'score' => $score
                    );
                    Yii::log('Added filter - Q' . $q->qid . ': ' . $questionText . ' with ' . count($options) . ' options', 'info');
                } else {
                    Yii::log('Skipped Q' . $q->qid . ' - no answer options found', 'info');
                }
            }
        }

        // Sort by score (highest first)
        if (!empty($filters)) {
            usort($filters, array($this, 'compareFilterScores'));
        }

        Yii::log('Total filters detected: ' . count($filters), 'info');

        return $filters;
    }

    /**
     * Compare filter scores for sorting
     */
    private function compareFilterScores($a, $b)
    {
        return $b['score'] - $a['score'];
    }

    /**
     * Get answer options for a question
     */
    private function getQuestionOptions($qid, $surveyid)
    {
        $oSurvey = Survey::model()->findByPk($surveyid);
        $language = $oSurvey->language;

        $answers = Answer::model()->findAllByAttributes(array(
            'qid' => $qid,
            'language' => $language
        ), array('order' => 'sortorder ASC'));

        $options = array();
        foreach ($answers as $answer) {
            $options[] = array(
                'code' => $answer->code,
                'answer' => strip_tags($answer->answer)
            );
        }

        return $options;
    }

    /**
     * Filter statistics based on selected criteria
     */
    public function filterStatistics()
    {
        try {
            $surveyid = Yii::app()->request->getPost('surveyid');
            $filtersJson = Yii::app()->request->getPost('filters');

            Yii::log('filterStatistics called - surveyid: ' . $surveyid, 'info');
            Yii::log('Filters JSON: ' . $filtersJson, 'info');

            // Decode JSON filters
            $filters = json_decode($filtersJson, true);
            if ($filters === null && $filtersJson !== null) {
                // Try as array if JSON decode fails
                $filters = Yii::app()->request->getPost('filters', array());
            }

            if (!$surveyid) {
                header('HTTP/1.0 400 Bad Request');
                echo json_encode(array('error' => 'Survey ID required'));
                return;
            }

            if (!Permission::model()->hasSurveyPermission($surveyid, 'statistics', 'read')) {
                header('HTTP/1.0 403 Forbidden');
                echo json_encode(array('error' => 'Permission denied'));
                return;
            }

            $oSurvey = Survey::model()->findByPk($surveyid);
            if (!$oSurvey) {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(array('error' => 'Survey not found'));
                return;
            }

            Yii::log('Decoded filters: ' . print_r($filters, true), 'info');

            // Build WHERE clause from filters
            $whereConditions = $this->buildFilterWhereClause($filters);

            Yii::log('WHERE conditions: ' . print_r($whereConditions, true), 'info');

            // Generate filtered statistics
            $filteredStats = $this->generateFilteredStats($surveyid, $whereConditions);

            header('Content-Type: application/json');
            echo json_encode($filteredStats);

        } catch (Exception $e) {
            Yii::log('Error in filterStatistics: ' . $e->getMessage(), 'error');
            Yii::log('Stack trace: ' . $e->getTraceAsString(), 'error');
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(array('error' => $e->getMessage(), 'trace' => $e->getTraceAsString()));
        }
    }

    /**
     * Build SQL WHERE clause from filter array
     */
    private function buildFilterWhereClause($filters)
    {
        $where = '1=1';
        $params = array();

        foreach ($filters as $filter) {
            $filterType = $filter['type'];
            $value = $filter['value'];

            switch ($filterType) {
                case 'date':
                    if (!empty($value['start']) && !empty($value['end'])) {
                        $where .= ' AND submitdate >= :dateStart AND submitdate <= :dateEnd';
                        $params[':dateStart'] = $value['start'] . ' 00:00:00';
                        $params[':dateEnd'] = $value['end'] . ' 23:59:59';
                    }
                    break;

                case 'question':
                    // Filter by specific question answer
                    if (!empty($value['fieldName']) && !empty($value['answer'])) {
                        $where .= ' AND ' . $value['fieldName'] . ' = :filter_' . $value['fieldName'];
                        $params[':filter_' . $value['fieldName']] = $value['answer'];
                    }
                    break;
            }
        }

        return array('where' => $where, 'params' => $params);
    }

    /**
     * Generate filtered statistics
     */
    private function generateFilteredStats($surveyid, $whereConditions)
    {
        $responseTable = '{{survey_' . $surveyid . '}}';
        $language = Survey::model()->findByPk($surveyid)->language;
        $questions = Question::model()->primary()->getQuestionList($surveyid);

        $results = array();

        foreach ($questions as $question) {
            $qid = $question['qid'];
            $gid = $question['gid'];
            $type = $question['type'];
            $fieldName = $surveyid . 'X' . $gid . 'X' . $qid;

            try {
                // Get filtered responses
                $command = Yii::app()->db->createCommand()
                    ->select(array($fieldName))
                    ->from($responseTable)
                    ->where($whereConditions['where'], $whereConditions['params'])
                    ->andWhere($fieldName . ' IS NOT NULL')
                    ->andWhere($fieldName . ' != :empty', array(':empty' => ''));

                $responses = $command->queryAll();

                if (empty($responses)) {
                    continue;
                }

                // Count occurrences
                $counts = array();
                foreach ($responses as $response) {
                    $value = $response[$fieldName];
                    if (!isset($counts[$value])) {
                        $counts[$value] = 0;
                    }
                    $counts[$value]++;
                }

                // Get answer labels
                $answerLabels = $this->getAnswerLabelsMap($qid, $language);

                $labels = array();
                $data = array();

                foreach ($counts as $code => $count) {
                    $labels[] = isset($answerLabels[$code]) ? $answerLabels[$code] : $code;
                    $data[] = $count;
                }

                $results['quid' . $qid] = array(
                    'labels' => $labels,
                    'grawdata' => $data,
                    'title' => strip_tags($question['question'])
                );

            } catch (Exception $e) {
                // Skip questions with errors
                continue;
            }
        }

        return $results;
    }

    /**
     * Get answer labels as a map
     */
    private function getAnswerLabelsMap($qid, $language)
    {
        $answers = Answer::model()->findAllByAttributes(array(
            'qid' => $qid,
            'language' => $language
        ));

        $map = array();
        foreach ($answers as $answer) {
            $map[$answer->code] = strip_tags($answer->answer);
        }

        return $map;
    }

    /**
     * Get qualitative feedback (text responses) for analysis
     */
    public function getQualitativeFeedback()
    {
        try {
            $surveyid = Yii::app()->request->getParam('surveyid');

            if (!$surveyid) {
                header('HTTP/1.0 400 Bad Request');
                echo json_encode(array('error' => 'Survey ID required'));
                return;
            }

            if (!Permission::model()->hasSurveyPermission($surveyid, 'statistics', 'read')) {
                header('HTTP/1.0 403 Forbidden');
                echo json_encode(array('error' => 'Permission denied'));
                return;
            }

            $oSurvey = Survey::model()->findByPk($surveyid);
            if (!$oSurvey) {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(array('error' => 'Survey not found'));
                return;
            }

            $language = $oSurvey->language;
            $responseTable = '{{survey_' . $surveyid . '}}';

            // Get text questions (S=Short text, T=Long text, U=Huge text, Q=Multiple short text)
            $textQuestions = Question::model()->findAllByAttributes(
                array(
                    'sid' => $surveyid,
                    'parent_qid' => 0,
                ),
                array(
                    'condition' => "type IN ('S', 'T', 'U', 'Q')",
                    'order' => 'group_order, question_order'
                )
            );

            $qualitativeData = array();

            foreach ($textQuestions as $question) {
                $qid = $question->qid;
                $gid = $question->gid;
                $fieldName = $surveyid . 'X' . $gid . 'X' . $qid;

                // Get question text
                $questionL10n = QuestionL10n::model()->findByAttributes(array(
                    'qid' => $qid,
                    'language' => $language
                ));

                $questionText = $questionL10n ? strip_tags($questionL10n->question) : 'Question ' . $qid;

                // Get all text responses for this question
                try {
                    $responses = Yii::app()->db->createCommand()
                        ->select($fieldName)
                        ->from($responseTable)
                        ->where($fieldName . ' IS NOT NULL AND ' . $fieldName . ' != :empty', array(':empty' => ''))
                        ->queryAll();

                    $comments = array();
                    foreach ($responses as $response) {
                        $text = trim($response[$fieldName]);
                        if (!empty($text)) {
                            $comments[] = $text;
                        }
                    }

                    if (!empty($comments)) {
                        $qualitativeData[] = array(
                            'qid' => $qid,
                            'question' => $questionText,
                            'comments' => $comments,
                            'count' => count($comments),
                            'wordFrequency' => $this->calculateWordFrequency($comments)
                        );
                    }

                } catch (Exception $e) {
                    // Skip questions with errors (table might not exist for this question)
                    continue;
                }
            }

            header('Content-Type: application/json');
            echo json_encode($qualitativeData);

        } catch (Exception $e) {
            Yii::log('Error in getQualitativeFeedback: ' . $e->getMessage(), 'error');
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(array('error' => $e->getMessage()));
        }
    }

    /**
     * Calculate word frequency from comments for word cloud
     */
    private function calculateWordFrequency($comments)
    {
        // Common stop words to exclude
        $stopWords = array(
            'the', 'is', 'at', 'which', 'on', 'a', 'an', 'as', 'are', 'was', 'were',
            'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
            'could', 'should', 'may', 'might', 'must', 'can', 'to', 'of', 'in', 'for',
            'with', 'from', 'by', 'and', 'or', 'but', 'not', 'it', 'this', 'that',
            'these', 'those', 'i', 'you', 'he', 'she', 'we', 'they', 'them', 'their',
            'my', 'your', 'his', 'her', 'its', 'our', 'very', 'so', 'just', 'too',
            'also', 'about', 'into', 'through', 'during', 'before', 'after', 'above',
            'below', 'up', 'down', 'out', 'off', 'over', 'under', 'again', 'further',
            'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all',
            'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'only',
            'own', 'same', 'than', 'too', 'very', 'what', 'who', 'whom', 'whose'
        );

        $wordCounts = array();
        $totalWords = 0;

        foreach ($comments as $comment) {
            // Convert to lowercase and remove punctuation
            $text = strtolower($comment);
            $text = preg_replace('/[^\w\s]/', ' ', $text);

            // Split into words
            $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($words as $word) {
                // Skip short words and stop words
                if (strlen($word) < 3 || in_array($word, $stopWords)) {
                    continue;
                }

                if (!isset($wordCounts[$word])) {
                    $wordCounts[$word] = 0;
                }
                $wordCounts[$word]++;
                $totalWords++;
            }
        }

        // Sort by frequency
        arsort($wordCounts);

        // Take top 50 words for word cloud
        $topWords = array_slice($wordCounts, 0, 50, true);

        // Format for word cloud (word, frequency)
        $wordCloud = array();
        foreach ($topWords as $word => $count) {
            $wordCloud[] = array(
                'text' => $word,
                'weight' => $count,
                'percentage' => $totalWords > 0 ? round(($count / $totalWords) * 100, 2) : 0
            );
        }

        return $wordCloud;
    }

    /**
     * Get response rate and survey metrics
     */
    public function getResponseMetrics()
    {
        try {
            $surveyid = Yii::app()->request->getParam('surveyid');

            if (!$surveyid) {
                header('HTTP/1.0 400 Bad Request');
                echo json_encode(array('error' => 'Survey ID required'));
                return;
            }

            if (!Permission::model()->hasSurveyPermission($surveyid, 'statistics', 'read')) {
                header('HTTP/1.0 403 Forbidden');
                echo json_encode(array('error' => 'Permission denied'));
                return;
            }

            $responseTable = '{{survey_' . $surveyid . '}}';
            $oSurvey = Survey::model()->findByPk($surveyid);

            if (!$oSurvey) {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(array('error' => 'Survey not found'));
                return;
            }

            // Count total responses
            $totalResponses = Yii::app()->db->createCommand()
                ->select('COUNT(*) as count')
                ->from($responseTable)
                ->queryScalar();

            // Count complete responses
            $completeResponses = Yii::app()->db->createCommand()
                ->select('COUNT(*) as count')
                ->from($responseTable)
                ->where('submitdate IS NOT NULL')
                ->queryScalar();

            // Count incomplete responses
            $incompleteResponses = $totalResponses - $completeResponses;

            // Get survey token count (invited participants)
            $invitedCount = 0;
            if ($oSurvey->anonymized == 'N') {
                $tokenTable = '{{tokens_' . $surveyid . '}}';
                try {
                    $invitedCount = Yii::app()->db->createCommand()
                        ->select('COUNT(*) as count')
                        ->from($tokenTable)
                        ->queryScalar();
                } catch (Exception $e) {
                    // Token table might not exist
                    $invitedCount = 0;
                }
            }

            // Calculate response rate
            $responseRate = 0;
            if ($invitedCount > 0) {
                $responseRate = round(($totalResponses / $invitedCount) * 100, 2);
            }

            // Calculate completion rate
            $completionRate = 0;
            if ($totalResponses > 0) {
                $completionRate = round(($completeResponses / $totalResponses) * 100, 2);
            }

            // Get date range of responses
            $dateRange = Yii::app()->db->createCommand()
                ->select('MIN(startdate) as first_response, MAX(submitdate) as last_response')
                ->from($responseTable)
                ->queryRow();

            $metrics = array(
                'totalResponses' => (int)$totalResponses,
                'completeResponses' => (int)$completeResponses,
                'incompleteResponses' => (int)$incompleteResponses,
                'invitedParticipants' => (int)$invitedCount,
                'responseRate' => $responseRate,
                'completionRate' => $completionRate,
                'firstResponse' => $dateRange['first_response'],
                'lastResponse' => $dateRange['last_response'],
                'surveyActive' => $oSurvey->active == 'Y'
            );

            header('Content-Type: application/json');
            echo json_encode($metrics);

        } catch (Exception $e) {
            Yii::log('Error in getResponseMetrics: ' . $e->getMessage(), 'error');
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(array('error' => $e->getMessage()));
        }
    }

    /**
     * Renders template(s) wrapped in header and footer
     *
     * @param string $sAction Current action, the folder to fetch views from
     * @param string $aViewUrls View url(s)
     * @param array $aData Data to be passed on. Optional.
     */
    protected function renderWrappedTemplate($sAction = 'export', $aViewUrls = array(), $aData = array(), $sRenderFile = false)
    {
        yii::app()->clientScript->registerPackage('bootstrap-switch');
        yii::app()->clientScript->registerPackage('jspdf');
        $oSurvey = Survey::model()->findByPk($aData['surveyid']);

        $aData['menu']['closeurl'] = Yii::app()->request->getUrlReferrer(Yii::app()->createUrl("/surveyAdministration/view/surveyid/" . $aData['surveyid']));

        $aData['display'] = array();
        $aData['display']['menu_bars']['browse'] = gT('Browse responses'); // browse is independent of the above

        $aData['topbar']['rightButtons'] = Yii::app()->getController()->renderPartial(
            '/surveyAdministration/partial/topbar_statistics/rightSideButtons',
            ['expertstats' => $aData['expertstats'], 'surveyid' => $aData['surveyid']],
            true
        );

        $aData['sidemenu']['state'] = false;

        // Set the active Sidemenu (for deeper navigation)
        $aData['sidemenu']['isSideMenuElementActive'] = true;
        $aData['sidemenu']['activeSideMenuElement']   = 'statistics';

        $aData['title_bar']['title'] = gT('Browse responses') . ': ' . $oSurvey->currentLanguageSettings->surveyls_title;
        $aData['subaction'] = gT('Statistics');
        parent::renderWrappedTemplate($sAction, $aViewUrls, $aData, $sRenderFile);
    }
}
