<?php

/**
 * SearchBoxWidget is a custom Yii widget used to render a search box with filtering capabilities.
 * It supports different view types and can switch between them based on user preferences or query parameters.
 */
class SearchBoxWidget extends CWidget
{
    /**
     * @var string $formUrl The URL to which the form will be submitted. Defaults to 'dashboard/view'.
     */
    public string $formUrl = 'dashboard/view';

    /**
     * @var CActiveRecord $model The model associated with the search form.
     */
    public CActiveRecord $model;

    /**
     * @var bool $onlyfilter If true, only the filter section of the widget is rendered.
     */
    public bool $onlyfilter = false;

    /**
     * @var string|null $viewtype The type of view widget (list-widget or box-widget) to render.
     * Can be set via query parameter, user settings, or defaults to 'list-widget'.
     */
    public ?string $viewtype = '';

    /**
     * @var bool $switch If true, the view type selection is saved to user settings.
     */
    public bool $switch = false;

    public $pageSize;

    /**
     * Runs the widget, rendering the appropriate view based on the viewtype and switch properties.
     * It determines the viewtype from the query parameters, user settings, or defaults.
     *
     * @throws CException If an error occurs during rendering.
     */
    public function run()
    {
        $this->formUrl = $this->getFormUrl();
        if (App()->request->getQuery('viewtype')) {
            $this->viewtype = App()->request->getQuery('viewtype');
        } elseif (SettingsUser::getUserSettingValue('welcome_page_widget')) {
            $this->viewtype = SettingsUser::getUserSettingValue('welcome_page_widget');
        } else {
            $this->viewtype = 'list-widget';
        }

        if (!empty($this->viewtype) && $this->switch) {
            SettingsUser::setUserSetting('welcome_page_widget', $this->viewtype);
        }

        $this->render('searchBox');
    }

    /**
     * Initializes the widget by registering necessary client scripts.
     */
    public function init(): void
    {
        $this->registerClientScript();
    }

    /**
     * Registers the necessary JavaScript files for the widget.
     */
    public function registerClientScript()
    {
        App()->getClientScript()->registerScriptFile(
            App()->getConfig("extensionsurl") . 'admin/SearchBoxWidget/assets/filters.js',
            CClientScript::POS_END
        );
    }

    /**
     * Generates and returns the form URL, handling URL formatting and GET parameters.
     *
     * @return string The generated form URL.
     * @throws CException
     */
    public function getFormUrl(): string
    {
        $url = App()->createAbsoluteUrl(App()->request->getPathInfo());
        if (Yii::app()->getUrlManager()->getUrlFormat() == CUrlManager::GET_FORMAT) {
            // Ignore all GET params (searchbox filters) except the 'r' param.
            return $url . '?' . http_build_query(['r' => App()->request->getParam('r')]);
        }
        return $url;
    }










    // public function getSurveyResponseTrends($sid)
    // {

    //     // Construct the table name dynamically
    //     $responseTable = '{{survey_' . (int)$sid . '}}';

    //     try {
    //         // Query to get response trends
    //         $data = Yii::app()->db->createCommand()
    //             ->select([
    //                 "DATE_FORMAT(submitdate, '%Y-%m-%d') AS response_date",
    //                 "COUNT(*) AS response_count"
    //             ])
    //             ->from($responseTable)
    //             ->where('submitdate IS NOT NULL') // Filter only submitted responses
    //             ->group('response_date')
    //             ->order('response_date ASC')
    //             ->queryAll();

    //         // Log the result for debugging
    //         Yii::log('Response trends data: ' . print_r($data, true), 'info');

    //         return $data;
    //     } catch (Exception $e) {
    //         // Log the error for debugging
    //         Yii::log('Error fetching response trends: ' . $e->getMessage(), 'error');
    //         return [];
    //     }
    // }



    public function getResponseTrends($timeframe = 'daily')
    {
        $surveys = Yii::app()->db->createCommand()
            ->select(['sid'])
            ->from('{{surveys}}')
            ->queryAll();

        $responseTrends = [];

        foreach ($surveys as $survey) {
            $responseTable = '{{survey_' . $survey['sid'] . '}}';

            try {
                if (Yii::app()->db->schema->getTable($responseTable, true) !== null) {
                    $query = Yii::app()->db->createCommand();
                    $query->from($responseTable)->where('submitdate IS NOT NULL');

                    if ($timeframe === 'daily') {
                        $query->select("DATE(submitdate) AS response_date, COUNT(*) AS total_responses")
                            ->group('DATE(submitdate)');
                    } elseif ($timeframe === 'weekly') {
                        $query->select("YEARWEEK(submitdate) AS response_week, COUNT(*) AS total_responses")
                            ->group('YEARWEEK(submitdate)');
                    } elseif ($timeframe === 'yearly') {
                        $query->select("YEAR(submitdate) AS response_year, COUNT(*) AS total_responses")
                            ->group('YEAR(submitdate)');
                    } else {
                        continue; // Skip if timeframe is invalid
                    }

                    $responseData = $query->queryAll();

                    $responseTrends[] = [
                        'survey_id' => $survey['sid'],
                        'timeframe' => $timeframe,
                        'data' => $responseData
                    ];
                }
            } catch (Exception $e) {
                Yii::log("Error fetching survey responses for survey {$survey['sid']}: " . $e->getMessage(), 'error');
            }
        }

        return $responseTrends;
    }






    public function getSurveyStats()
    {
        $surveys = Yii::app()->db->createCommand()
            ->select(['sid'])
            ->from('{{surveys}}')
            ->queryAll();

        $responseData = [];

        foreach ($surveys as $survey) {
            $responseTable = '{{survey_' . $survey['sid'] . '}}';

            try {
                if (Yii::app()->db->schema->getTable($responseTable, true) !== null) {
                    $totalResponses = Yii::app()->db->createCommand()
                        ->select('COUNT(*)')
                        ->from($responseTable)
                        ->where('submitdate IS NOT NULL')
                        ->queryScalar();

                    $totalCompletionTime = Yii::app()->db->createCommand()
                        ->select('SUM(TIMESTAMPDIFF(MINUTE, startdate, submitdate))')
                        ->from($responseTable)
                        ->where('submitdate IS NOT NULL')
                        ->queryScalar();

                    $avgResponseTime = Yii::app()->db->createCommand()
                        ->select('AVG(TIMESTAMPDIFF(MINUTE, startdate, submitdate))')
                        ->from($responseTable)
                        ->where('submitdate IS NOT NULL')
                        ->queryScalar();
                } else {
                    $totalResponses = 0;
                    $totalCompletionTime = 0;
                    $avgResponseTime = 0;
                }
            } catch (Exception $e) {
                Yii::log("Error fetching survey stats for survey {$survey['sid']}: " . $e->getMessage(), 'error');
                $totalResponses = 0;
                $totalCompletionTime = 0;
                $avgResponseTime = 0;
            }

            $responseData[] = [
                'survey_id' => $survey['sid'],
                'total_responses' => (int)$totalResponses,
                'total_completion_time' => (int)$totalCompletionTime,
                'avg_response_time' => round($avgResponseTime, 2),
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($responseData);
    }



    // public function actionGetSurveyResponseTrends()
    // {

    //     $rawPost = file_get_contents('php://input');
    //     Yii::log('Raw POST data: ' . $rawPost, 'info'); // Debugging

    //     $decodedPost = json_decode($rawPost, true);
    //     Yii::log('Decoded POST data: ' . print_r($decodedPost, true), 'info');

    //     $surveyId = isset($decodedPost['surveyid']) ? $decodedPost['surveyid'] : null;
    //     Yii::log('Survey ID: ' . $surveyId, 'info');

    //     // Fetch the specific POST parameter 'surveyid'
    //     $surveyId = Yii::app()->request->getPost('surveyid', null);
    //     Yii::log('Survey ID in widget: ' . $surveyId, 'info'); // Debugging

    //     if ($surveyId) {
    //         $data = $this->getSurveyResponseTrends($surveyId);
    //         echo json_encode($data);
    //     } else {
    //         Yii::log('No survey ID received', 'error'); // Error Logging
    //         echo json_encode(['error' => 'No survey ID provided']);
    //     }
    //     Yii::app()->end();
    // }


    public function getSurveyStatusDistribution()
    {
        $active = Yii::app()->db->createCommand()
            ->select('COUNT(*)')
            ->from('{{surveys}}')
            ->where('active = :active', [':active' => 'Y'])
            ->queryScalar();

        $inactive = Yii::app()->db->createCommand()
            ->select('COUNT(*)')
            ->from('{{surveys}}')
            ->where('active = :active', [':active' => 'N'])
            ->queryScalar();

        $expired = Yii::app()->db->createCommand()
            ->select('COUNT(*)')
            ->from('{{surveys}}')
            ->where('expires IS NOT NULL AND expires < NOW()')
            ->queryScalar();

        return [
            'active' => $active,
            'inactive' => $inactive,
            'expired' => $expired,
        ];
    }


    public function getResponsesAcrossSurveys($period)
    {
        // Determine the date range for daily, weekly, or monthly
        switch ($period) {
            case 'daily':
                $dateCondition = 'DATE(submitdate) = CURDATE()'; // Today
                break;
            case 'weekly':
                $dateCondition = 'YEARWEEK(submitdate, 1) = YEARWEEK(CURDATE(), 1)'; // Current week
                break;
            case 'monthly':
                $dateCondition = 'MONTH(submitdate) = MONTH(CURDATE()) AND YEAR(submitdate) = YEAR(CURDATE())'; // Current month
                break;
            default:
                $dateCondition = '1=1'; // No filtering if an invalid period is passed
                break;
        }

        // Fetch all active surveys
        $surveys = Yii::app()->db->createCommand()
            ->select(['sid', 'surveyls_title'])
            ->from('{{surveys}} s')
            ->join('{{surveys_languagesettings}} sl', 's.sid = sl.surveyls_survey_id')
            ->where('s.active = :active AND sl.surveyls_language = :language', [
                ':active' => 'Y',
                ':language' => 'en'
            ])
            ->queryAll();

        $results = [];

        foreach ($surveys as $survey) {
            $responseTable = '{{survey_' . $survey['sid'] . '}}';

            try {
                $responseCount = Yii::app()->db->createCommand()
                    ->select('COUNT(*)')
                    ->from($responseTable)
                    ->where($dateCondition)
                    ->queryScalar();
            } catch (Exception $e) {
                $responseCount = 0; // Handle cases where the response table doesn't exist
            }

            $results[] = [
                'survey' => $survey['surveyls_title'],
                'responses' => $responseCount,
            ];
        }

        return $results;
    }


    public function getOverallAverageResponseTime()
    {
        $sql = "SELECT sid FROM {{surveys}}";
        $command = Yii::app()->db->createCommand($sql);
        $surveys = $command->queryAll();

        $totalTime = 0;
        $totalResponses = 0;
        $surveyData = [];

        foreach ($surveys as $survey) {
            $surveyId = intval($survey['sid']);
            $surveyTable = "survey_" . $surveyId;

            // Check if the survey table exists
            $checkTableSql = "SHOW TABLES LIKE :table";
            $checkCommand = Yii::app()->db->createCommand($checkTableSql);
            $checkCommand->bindValue(":table", $surveyTable, PDO::PARAM_STR);

            if (!$checkCommand->queryScalar()) {
                continue;
            }

            // Get sum of response times and count of responses
            $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND, datestamp, submitdate)) AS total_time, 
                           COUNT(*) AS response_count 
                    FROM $surveyTable 
                    WHERE submitdate IS NOT NULL AND datestamp IS NOT NULL";

            $command = Yii::app()->db->createCommand($sql);
            $result = $command->queryRow();

            if ($result && $result['response_count'] > 0) {
                $totalTime += $result['total_time'];
                $totalResponses += $result['response_count'];

                // Store survey-specific response times
                $surveyData[] = [
                    "survey_id" => $surveyId,
                    "average_response_time" => round($result['total_time'] / $result['response_count'], 2)
                ];
            }
        }

        // Compute the overall average
        $overallAverage = $totalResponses > 0 ? round($totalTime / $totalResponses, 2) : 0;

        // Return JSON response
        echo json_encode([
            "overall_average_response_time" => $overallAverage,
            "surveys" => $surveyData
        ]);
    }
}
