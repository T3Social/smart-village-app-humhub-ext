<?php

namespace humhub\modules\smartVillage\controllers\calendar;

use DateTime;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\fullcalendar\FullCalendar;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseContentController;
use humhub\modules\rest\definitions\ContentDefinitions;

class RecurringEventsController extends BaseContentController
{
    public static $moduleId = 'Calendar';

    /**
     * {@inheritdoc}
     */
    public function getContentActiveRecordClass()
    {
        return CalendarEntry::class;
    }

    /**
     * {@inheritdoc}
     */
    public function returnContentDefinition(ContentActiveRecord $contentRecord)
    {
        /** @var CalendarEntry $contentRecord */
        return FullCalendar::getFullCalendarArray($contentRecord);
    }

    /**
     * @return array
     * @throws \Throwable
     * Get all the recurring events lies between start_date and end_date
     * We filter the events based on their title
     */
    public function actionIndex(){
        $output = [];
        if(!isset($_GET['start_date']) || !isset($_GET['end_date'])){
            return $this->returnError(400,"start_date and end_date parameters are required!");
        }

        //Get all the calendar entries lies between the start_date and end_date
        $calendarService = new CalendarService();
        $entries = $calendarService->getCalendarItems(new DateTime($_GET['start_date']), new DateTime($_GET['end_date']));

        //If the calendar entry not found in given date of range
        if($entries == null){
            return $this->returnError(404, "No recurring events are present between ".$_GET['start_date'] ." and ". $_GET['end_date']);
        }
        //Get those calendar events which are recurring
        $calendarEntryTitle = [];
        $calendarEntries = CalendarEntry::find()->all();
        foreach($calendarEntries as $calendarEntry){
            if(isset($calendarEntry->rrule)){
                array_push($calendarEntryTitle,$calendarEntry['title']);
            }
        }

        foreach ($entries as $entry) {
            /** @var ContentActiveRecord $entry */
            //return only recurring events
            if(in_array($entry['title'],$calendarEntryTitle)){
                $output[] = $this->returnContentDefinition($entry);
            }
        }
        //If recurring event not found between given date range
        if($output == null){
            return $this->returnError(404, "No recurring events are present between ".$_GET['start_date'] ." and ". $_GET['end_date']);
        }
        return $output;
    }

    /**
     * @param $containerId
     * @return array
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\IntegrityException
     * $containerId = Id of the container
     * Get all the recurring events based on the containerId
     */
    public function actionRecurringContainer($containerId){
        $contentContainer = ContentContainer::findOne(['id'=>$containerId]);
        if($contentContainer == null){
            return $this->returnError(404,"Content container not found!");
        }

        if(!isset($_GET['start_date']) || !isset($_GET['end_date'])){
            return $this->returnError(400,"start_date and end_date parameters are required!");
        }

        /** @var ActiveQueryContent $calendarEntries */
        $calendarEntries = CalendarEntry::find()->contentContainer($contentContainer->getPolymorphicRelation())->orderBy(['content.created_at' => SORT_DESC])->readable();

        ContentDefinitions::handleTopicsParam($calendarEntries, $containerId);

        //Get those calendar events which are recurring
        $calendarEntryTitle = [];
        foreach($calendarEntries->all() as $calendarEntry){
            /** @var ContentActiveRecord $calendarEntry */
            if(isset($calendarEntry->rrule)){
                array_push($calendarEntryTitle,$calendarEntry['title']);
            }
        }

        $output = [];

        //Get all the calendar entries lies between the start_date and end_date
        $calendarService = new CalendarService();
        $entries = $calendarService->getCalendarItems(new DateTime($_GET['start_date']), new DateTime($_GET['end_date']));

        foreach ($entries as $entry) {
            /** @var ContentActiveRecord $entry */
            //Filter out the entries by checking rrule and title
            if(in_array($entry['title'],$calendarEntryTitle)){
                $output[] = $this->returnContentDefinition($entry);
            }

        }
        //If recurring event not found between given date range
        if($output == null){
            return $this->returnError(404, "No recurring events are present between ".$_GET['start_date'] ." and ". $_GET['end_date']);
        }

        return $output;
    }

    /**
     * @param $Id
     * @return array
     * @throws \Throwable
     * $id = id of the calendar entry
     * Get all the recurring events based on the id of calendar entry
     *
     */
    public function actionRecurringEntry($Id){
        $output = [];
        $calendarEntry = CalendarEntry::findOne($Id);

        if($calendarEntry == null){
            return $this->returnError(404,"Calendar entry not found!");
        }
        //If Calendar entry is present but it is not recurring
        if(!isset($calendarEntry->rrule)){
            return $this->returnError(400,"Calendar event is not recurring");
        }
        if(!isset($_GET['start_date']) || !isset($_GET['end_date'])){
            return $this->returnError(400,"start_date and end_date parameters are required!");
        }

        $calendarService = new CalendarService();
        $entries = $calendarService->getCalendarItems(new DateTime($_GET['start_date']), new DateTime($_GET['end_date']));

        //Get all the calendar entries lies between the start_date and end_date
        foreach ($entries as $entry) {
            /** @var ContentActiveRecord $entry */
            //Filter out the entries by checking rrule and title
            if($calendarEntry->title == $entry['title']){
                $output[] = $this->returnContentDefinition($entry);
            }
        }
        //If recurring event not found between given date range
        if($output == null){
            return $this->returnError(404, "No recurring events are present between ".$_GET['start_date'] ." and ". $_GET['end_date']);
        }
        return $output;

    }

}