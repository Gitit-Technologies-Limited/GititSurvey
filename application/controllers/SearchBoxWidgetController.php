<?php

use LimeSurvey\Api\Rest\Endpoint\EndpointFactory;
use LimeSurvey\DI;

// Ensure the Controller class is imported
Yii::import('application.components.Controller');
// Ensure the SearchBoxWidget class is imported
Yii::import('application.extensions.admin.SearchBoxWidget.SearchBoxWidget');

class SearchBoxWidgetController extends LSBaseController
{
    public function actionGetSurveyResponseTrend()
    {
        // Fetch the surveyid from GET parameters
        $interval = Yii::app()->request->getParam('period', null);
        Yii::log('Survey ID in widget: ' . $interval, 'info');

        if ($interval) {

            $widget = new SearchBoxWidget();
            $data = $widget->getResponseTrends($interval);
            header('Content-Type: application/json');
            echo json_encode($data, JSON_PRETTY_PRINT);
            Yii::app()->end();
        } else {
            Yii::log('No interval received', 'error'); // Error Logging
            echo json_encode(['error' => 'No interval ID provided']);
        }
        Yii::app()->end();
    }


    public function actionGetResponsesAcrossSurveys()
    {
        // Fetch the period from GET parameters
        $period = Yii::app()->request->getParam('period', null);  // Using getParam() for query string


        if ($period) {

            $widget = new SearchBoxWidget();
            $data = $widget->getResponsesAcrossSurveys($period);
            echo json_encode($data);
        } else {

            echo json_encode(['error' => 'No period provided']);
        }
        Yii::app()->end();
    }

    public function actionGetDistributionAcrossSurveys()
    {

        $period = Yii::app()->request->getParam('period', null);
        Yii::log('period : ' . $period, 'info');


        $widget = new SearchBoxWidget();
        $data = $widget->getSurveyStatusDistribution();
        echo json_encode($data);

        Yii::app()->end();
    }

    public function actionGetSurveyStats()
    {



        $widget = new SearchBoxWidget();
        $data = $widget->getSurveyStats();
        echo json_encode($data);

        Yii::app()->end();
    }

    public function actionGetAverageResponseTime()
    {
        // Get all survey IDs
        $sql = "SELECT sid FROM {{surveys}}";
        $surveys = Yii::app()->db->createCommand($sql)->queryAll();

        $totalTime = 0;
        $totalResponses = 0;
        $surveyData = [];

        foreach ($surveys as $survey) {
            $surveyId = intval($survey['sid']);
            $surveyTable = "{{survey_$surveyId}}"; // Use curly brackets to avoid table prefix issues

            // Check if the survey table exists
            $checkTableSql = "SHOW TABLES LIKE :table";
            $checkCommand = Yii::app()->db->createCommand($checkTableSql);
            $checkCommand->bindValue(":table", Yii::app()->db->tablePrefix . "survey_" . $surveyId, PDO::PARAM_STR);

            if (!$checkCommand->queryScalar()) {
                continue; // Skip if table does not exist
            }

            // Get sum of response times and count of responses
            $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND, startdate, submitdate)) AS total_time, 
                           COUNT(*) AS response_count 
                    FROM $surveyTable 
                    WHERE submitdate IS NOT NULL AND startdate IS NOT NULL";

            $command = Yii::app()->db->createCommand($sql);
            $result = $command->queryRow();

            if ($result && $result['response_count'] > 0) {
                $averageTime = round($result['total_time'] / $result['response_count'], 2);
                $totalTime += $result['total_time'];
                $totalResponses += $result['response_count'];

                // Store per-survey response time
                $surveyData[] = [
                    "survey_id" => $surveyId,
                    "average_response_time" => $averageTime
                ];
            }
        }

        // Compute the overall average
        $overallAverage = ($totalResponses > 0) ? round($totalTime / $totalResponses, 2) : 0;

        // Return JSON response
        echo json_encode([
            "overall_average_response_time" => $overallAverage,
            "surveys" => $surveyData
        ]);
    }
}
