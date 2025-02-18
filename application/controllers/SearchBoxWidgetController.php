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

    // public function actionGetAverageResponseTime()
    // {
    //     $widget = new SearchBoxWidget();
    //     $surveyData = $widget->getOverallAverageResponseTime();

    //     if (!$surveyData || empty($surveyData)) {
    //         echo json_encode(['error' => 'No response times available']);
    //         Yii::app()->end();
    //     }

    //     $allSurveyStats = [];

    //     foreach ($surveyData as $surveyId => $responses) {
    //         $totalTime = 0;
    //         $responseCount = count($responses);

    //         foreach ($responses as $response) {
    //             $startTime = strtotime($response['start_time']);
    //             $submitTime = strtotime($response['submit_time']);

    //             if ($startTime && $submitTime && $submitTime > $startTime) {
    //                 $totalTime += ($submitTime - $startTime); // Time taken in seconds
    //             }
    //         }

    //         $averageTime = $responseCount > 0 ? round($totalTime / $responseCount, 2) : 0;
    //         $allSurveyStats[] = ['survey_id' => $surveyId, 'average_response_time' => $averageTime];
    //     }

    //     echo json_encode($allSurveyStats); // Return JSON array
    //     Yii::app()->end();
    // }





}
