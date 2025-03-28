<?php

/**
 * Class IndexController
 */
class DashboardController extends LSBaseController
{
    /**
     * responses constructor.
     * @param $controller
     * @param $id
     */
    public function __construct($controller, $id)
    {
        parent::__construct($controller, $id);

        App()->loadHelper('surveytranslator');
    }

    /**
     * Set filters for all actions
     * @return string[]
     */
    public function filters()
    {
        return [];
    }

    /**
     * this is part of _renderWrappedTemplate implement in old responses.php
     *
     * @param string $view
     * @return bool
     */
    public function beforeRender($view)
    {
        $this->layout = 'with_sidebar';

        return parent::beforeRender($view);
    }

    /**
     * View the dashboard index/index
     */
    public function actionView(): void
    {
        $aData = $this->getData();
        $this->render('welcome', $aData);
    }

    /**
     * Used to get responses data for browse etc
     *
     * @param int|null $surveyId
     * @param int|null $responseId
     * @param string|null $language
     * @return array
     */
    private function getData(): array
    {
        $aData = [];
        $aData['issuperadmin'] = false;
        if (Permission::model()->hasGlobalPermission('superadmin', 'read')) {
            $aData['issuperadmin'] = true;
        }
        // display old dashboard interface
        $aData['oldDashboard'] = App()->getConfig('display_old_dashboard') === '1';
        $setting_entry = 'last_survey_' . App()->user->getId();
        $lastsurvey = App()->getConfig($setting_entry);
        if ($lastsurvey) {
            try {
                $survey = Survey::model()->findByPk($lastsurvey);
                if ($survey) {
                    $aData['showLastSurvey'] = true;
                    $iSurveyID = $lastsurvey;
                    $aData['surveyTitle'] = $survey->currentLanguageSettings->surveyls_title . " (" . gT("ID") . ":" . $iSurveyID . ")";
                    $aData['surveyUrl'] = $this->createUrl("surveyAdministration/view/surveyid/{$iSurveyID}");
                } else {
                    $aData['showLastSurvey'] = false;
                }
            } catch (Exception $e) {
                $aData['showLastSurvey'] = false;
            }
        } else {
            $aData['showLastSurvey'] = false;
        }

        // We get the last question visited by user
        $setting_entry = 'last_question_' . App()->user->getId();
        $lastquestion = App()->getConfig($setting_entry);

        // the question group of this question
        $setting_entry = 'last_question_gid_' . App()->user->getId();
        $lastquestiongroup = App()->getConfig($setting_entry);

        // the sid of this question : last_question_sid_1
        $setting_entry = 'last_question_sid_' . App()->user->getId();
        $lastquestionsid = App()->getConfig($setting_entry);

        if ($lastquestion && $lastquestiongroup && $lastquestionsid) {
            $survey = Survey::model()->findByPk($lastquestionsid);
            if ($survey) {
                $baselang = $survey->language;
                $aData['showLastQuestion'] = true;
                $qid = $lastquestion;
                $gid = $lastquestiongroup;
                $sid = $lastquestionsid;
                $qrrow = Question::model()->findByAttributes(['qid' => $qid, 'gid' => $gid, 'sid' => $sid]);
                if ($qrrow) {
                    $aData['last_question_name'] = $qrrow['title'];
                    if (!empty($qrrow->questionl10ns[$baselang]['question'])) {
                        $aData['last_question_name'] .= ' : ' . $qrrow->questionl10ns[$baselang]['question'];
                    }
                    $aData['last_question_link'] = $this->createUrl("questionAdministration/view/surveyid/$sid/gid/$gid/qid/$qid");
                } else {
                    $aData['showLastQuestion'] = false;
                }
            } else {
                $aData['showLastQuestion'] = false;
            }
        } else {
            $aData['showLastQuestion'] = false;
        }

        $aData['countSurveyList'] = Survey::model()->count();

        //show banner after welcome logo
        $event = new PluginEvent('beforeWelcomePageRender');
        App()->getPluginManager()->dispatchEvent($event);
        $belowLogoHtml = $event->get('html');

        // We get the home page display setting
        $aData['bShowSurveyList'] = (App()->getConfig('show_survey_list') == "show");
        $aData['bShowSurveyListSearch'] = (App()->getConfig('show_survey_list_search') == "show");
        $aData['bShowLogo'] = (App()->getConfig('show_logo') == "show");
        $aData['oSurveySearch'] = new Survey('search');
        $aData['bShowLastSurveyAndQuestion'] = (App()->getConfig('show_last_survey_and_question') == "show");
        $aData['iBoxesByRow'] = (int)App()->getConfig('boxes_by_row');
        $aData['sBoxesOffSet'] = (int)App()->getConfig('boxes_offset');
        $aData['bBoxesInContainer'] = (App()->getConfig('boxes_in_container') == 'yes');
        $aData['belowLogoHtml'] = $belowLogoHtml;

        return $aData;
    }


    public function getSurveyCounts()
    {
        // Active Surveys
        $activeSurveysCount = Yii::app()->db->createCommand()
            ->select('COUNT(*)')
            ->from('{{surveys}}')
            ->where('active = :active', [':active' => 'Y'])
            ->queryScalar();

        // Draft Surveys
        $draftSurveysCount = Yii::app()->db->createCommand()
            ->select('COUNT(*)')
            ->from('{{surveys}}')
            ->where('active = :active AND expires IS NULL', [':active' => 'N'])
            ->queryScalar();

        // Closed Surveys
        $closedSurveysCount = Yii::app()->db->createCommand()
            ->select('COUNT(*)')
            ->from('{{surveys}}')
            ->where('active = :active AND expires IS NOT NULL', [':active' => 'N'])
            ->queryScalar();

        // All surveys
        $allSurveysCount = Yii::app()->db->createCommand()
            ->select('COUNT(*)')
            ->from('{{surveys}}')

            ->queryScalar();

        return [
            'active' => $activeSurveysCount,
            'draft' => $draftSurveysCount,
            'closed' => $closedSurveysCount,
            'all' => $allSurveysCount
        ];
    }


    public function getActiveSurveys()
    {
        // Query to get all active surveys with their titles
        $surveys = Yii::app()->db->createCommand()
            ->select(['s.sid', 'sl.surveyls_title'])  // Select survey id and title
            ->from('{{surveys}} s')
            ->join('{{surveys_languagesettings}} sl', 's.sid = sl.surveyls_survey_id') // Join with the surveys_languagesettings table
            ->where('s.active = :active AND sl.surveyls_language = :language', [':active' => 'Y', ':language' => 'en'])  // Only active surveys in English
            ->queryAll();

        return $surveys;
    }

    public function getFirstFiveActiveSurveysWithResponses()
    {
        // Query to get the first 5 active surveys
        $activeSurveys = Yii::app()->db->createCommand()
            ->select(['sid', 'datecreated'])
            ->from('{{surveys}}')
            ->where('active = :active', [':active' => 'Y'])
            ->limit(5)
            ->queryAll();

        $results = [];
        foreach ($activeSurveys as $survey) {
            // Query to get the survey title from the survey_languagesettings table
            $surveyTitle = Yii::app()->db->createCommand()
                ->select('surveyls_title')
                ->from('{{surveys_languagesettings}}')
                ->where('surveyls_survey_id = :sid AND surveyls_language = :language', [':sid' => $survey['sid'], ':language' => 'en'])
                ->queryScalar();

            // Construct the response table name for each survey
            $responseTable = '{{survey_' . $survey['sid'] . '}}';

            try {
                // Try to get the response count for the survey from its specific response table
                $responseCount = Yii::app()->db->createCommand()
                    ->select('COUNT(*)')
                    ->from($responseTable)
                    ->queryScalar();
            } catch (Exception $e) {
                // In case the table doesn't exist or any error occurs, set response count to 0
                $responseCount = 0;
            }

            // Add the survey details to the result
            $results[] = [
                'survey_id' => $survey['sid'],
                'survey_title' => $surveyTitle,
                'response_count' => $responseCount,
                'date_created' => $survey['datecreated'],
            ];
        }

        return $results;
    }


    public function getResponseCountInAllSurveys()
    {
        // Query to get the first 5 active surveys
        $activeSurveys = Yii::app()->db->createCommand()
            ->select(['sid', 'datecreated'])
            ->from('{{surveys}}')
            // ->where('active = :active', [':active' => 'Y'])
            ->queryAll();

        $results = [];
        foreach ($activeSurveys as $survey) {
            // Query to get the survey title from the survey_languagesettings table
            $surveyTitle = Yii::app()->db->createCommand()
                ->select('surveyls_title')
                ->from('{{surveys_languagesettings}}')
                ->where('surveyls_survey_id = :sid AND surveyls_language = :language', [':sid' => $survey['sid'], ':language' => 'en'])
                ->queryScalar();

            // Construct the response table name for each survey
            $responseTable = '{{survey_' . $survey['sid'] . '}}';

            try {
                // Try to get the response count for the survey from its specific response table
                $responseCount = Yii::app()->db->createCommand()
                    ->select('COUNT(*)')
                    ->from($responseTable)
                    ->queryScalar();
            } catch (Exception $e) {
                // In case the table doesn't exist or any error occurs, set response count to 0
                $responseCount = 0;
            }

            // Add the survey details to the result
            $results[] = [
                'survey_id' => $survey['sid'],
                'survey_title' => $surveyTitle,
                'response_count' => $responseCount,
                'date_created' => $survey['datecreated'],
            ];
        }

        return $results;
    }


    // public function countAllSurveys()
    // {
    //     $surveysCount = Yii::app()->db->createCommand()
    //         ->select('COUNT(*)')
    //         ->from('{{surveys}}')
    //         ->queryScalar();

    //     return  $surveysCount;
    // }

    public function getSurveyList()
    {
        // Query to get all active surveys with their titles
        $surveys = Yii::app()->db->createCommand()
            ->select(['s.sid', 'sl.surveyls_title'])  // Select survey id and title
            ->from('{{surveys}} s')
            ->join('{{surveys_languagesettings}} sl', 's.sid = sl.surveyls_survey_id') // Join with the surveys_languagesettings table
            ->where('sl.surveyls_language = :language', [':language' => 'en'])  // Only active surveys in English
            ->queryAll();

        return $surveys;
    }


    public function getSurveyResponseTrends($sid)
    {

        // Construct the table name dynamically
        $responseTable = '{{survey_' . (int)$sid . '}}';

        try {
            // Query to get response trends
            $data = Yii::app()->db->createCommand()
                ->select([
                    "DATE_FORMAT(submitdate, '%Y-%m-%d') AS response_date",
                    "COUNT(*) AS response_count"
                ])
                ->from($responseTable)
                ->where('submitdate IS NOT NULL') // Filter only submitted responses
                ->group('response_date')
                ->order('response_date ASC')
                ->queryAll();



            return $data;
        } catch (Exception $e) {
            // Log the error for debugging
            Yii::log('Error fetching response trends: ' . $e->getMessage(), 'error');
            return [];
        }
    }


    public function actionGetSurveyResponseTrends()
    {

        $rawPost = file_get_contents('php://input');


        $decodedPost = json_decode($rawPost, true);


        $surveyId = isset($decodedPost['surveyid']) ? $decodedPost['surveyid'] : null;

        // Fetch the specific POST parameter 'surveyid'
        $surveyId = Yii::app()->request->getPost('surveyid', null);


        if ($surveyId) {
            $data = $this->getSurveyResponseTrends($surveyId);
            echo json_encode($data);
        } else {
            Yii::log('No survey ID received', 'error'); // Error Logging
            echo json_encode(['error' => 'No survey ID provided']);
        }
        Yii::app()->end();
    }


    public function getRecentActivitySummary()
    {
        $recentActivities = [];

        // Fetch active surveys and their recent responses
        $surveyIds = Yii::app()->db->createCommand("
            SELECT sid, surveyls_title
            FROM surveys
            JOIN surveys_languagesettings 
              ON surveys.sid = surveys_languagesettings.surveyls_survey_id
            WHERE active = 'Y'
        ")->queryAll();

        foreach ($surveyIds as $survey) {
            $surveyId = $survey['sid'];
            $surveyTitle = $survey['surveyls_title'];

            // Count new responses
            $responseCount = Yii::app()->db->createCommand("
                SELECT COUNT(*) AS response_count 
                FROM {{survey_$surveyId}}
                WHERE submitdate IS NOT NULL
            ")->queryScalar();

            $recentActivities[] = [
                'type' => 'survey_response',
                'message' => "Survey \"$surveyTitle\" received $responseCount new responses.",
                'surveyId' => $surveyId,
                'date' => date('Y-m-d'),
            ];
        }

        // Fetch recently edited drafts
        $editedDrafts = Yii::app()->db->createCommand("
        SELECT surveys.sid, surveyls_title, 
               GREATEST(
                   IFNULL(surveys.last_edited, '1970-01-01'), 
                   IFNULL(MAX(questions.last_modified), '1970-01-01')
               ) AS last_edited
        FROM surveys
        JOIN surveys_languagesettings 
          ON surveys.sid = surveys_languagesettings.surveyls_survey_id
        LEFT JOIN questions 
          ON surveys.sid = questions.sid
        WHERE surveys.active = 'N'
        GROUP BY surveys.sid, surveyls_title
        ORDER BY last_edited DESC
        LIMIT 5
    ")->queryAll();

        foreach ($editedDrafts as $draft) {
            $recentActivities[] = [
                'type' => 'draft_edited',
                'message' => "Survey \"{$draft['surveyls_title']}\" was edited.",
                'surveyId' => $draft['sid'],
                'date' => $draft['last_edited'],
            ];
        }



        // Fetch new user creation actions
        $newUsers = Yii::app()->db->createCommand("
            SELECT users_name, created
            FROM users
            ORDER BY created DESC
            LIMIT 5
        ")->queryAll();

        foreach ($newUsers as $user) {
            $recentActivities[] = [
                'type' => 'user_creation',
                'message' => "New user \"{$user['users_name']}\" created.",
                'date' => $user['created'], // Use the created date
            ];
        }

        // Sort activities by date (most recent first)
        usort($recentActivities, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        // Group activities by Today, Yesterday, and other dates
        $groupedActivities = [];
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        foreach ($recentActivities as $activity) {
            $activityDate = date('Y-m-d', strtotime($activity['date']));
            if ($activityDate === $today) {
                $groupedActivities['Today'][] = $activity;
            } elseif ($activityDate === $yesterday) {
                $groupedActivities['Yesterday'][] = $activity;
            } else {
                $groupedActivities[date('F d, Y', strtotime($activity['date']))][] = $activity;
            }
        }

        return $groupedActivities;
    }

    public function getSurveyResponseTrend($frequency = 'daily')
    {
        // Define the date format based on the frequency filter
        switch ($frequency) {
            case 'daily':
                $dateFormat = "DATE(submitdate)";
                $groupBy = "DATE(submitdate)";
                $orderBy = "DATE(submitdate)";
                break;
            case 'weekly':
                $dateFormat = "CONCAT(YEAR(submitdate), '-', WEEK(submitdate))";
                $groupBy = "YEAR(submitdate), WEEK(submitdate)";
                $orderBy = "YEAR(submitdate), WEEK(submitdate)";
                break;
            case 'monthly':
                $dateFormat = "DATE_FORMAT(submitdate, '%Y-%m')";
                $groupBy = "YEAR(submitdate), MONTH(submitdate)";
                $orderBy = "YEAR(submitdate), MONTH(submitdate)";
                break;
            default:
                return "Invalid frequency parameter!";
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

        $responseTrends = [];

        foreach ($surveys as $survey) {
            $surveyId = $survey['sid'];
            $responseTable = "{{survey_$surveyId}}";

            try {
                $sql = "
                    SELECT 
                        $dateFormat AS time_period,
                        COUNT(*) AS total_responses
                    FROM $responseTable
                    WHERE submitdate IS NOT NULL
                    GROUP BY $groupBy
                    ORDER BY $orderBy
                ";

                $command = Yii::app()->db->createCommand($sql);
                $surveyData = $command->queryAll();

                $responseTrends[] = [
                    'survey' => $survey['surveyls_title'],
                    'responses' => $surveyData,
                ];
            } catch (Exception $e) {
                // Skip if the table doesn't exist
                continue;
            }
        }

        return empty($responseTrends) ? "No survey responses found!" : $responseTrends;
    }




    public function getResponsesAcrossSurveys($period = 'daily')
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
}


// CREATE TRIGGER update_questions_last_modified
// AFTER UPDATE ON question_l10ns
// FOR EACH ROW
// BEGIN
//   UPDATE questions
//   SET last_modified = NOW()
//   WHERE qid = NEW.qid;
// END;


// 
// CREATE TRIGGER insert_questions_last_modified
// AFTER INSERT ON question_l10ns
// FOR EACH ROW
// BEGIN
//   UPDATE questions
//   SET last_modified = NOW()
//   WHERE qid = NEW.qid;
// END;

//
// CREATE TRIGGER update_surveys_last_modified
// AFTER UPDATE ON questions
// FOR EACH ROW
// BEGIN
//   UPDATE surveys
//   SET last_edited = NOW()
//   WHERE sid = NEW.sid;
// END;
