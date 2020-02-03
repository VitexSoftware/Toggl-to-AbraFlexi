<?php
require dirname(__FILE__).'/../vendor/autoload.php';

use AJT\Toggl\TogglClient;

$toggl_token  = 'fa66bce608f983e93649e6c2fbd385cc'; // Fill in your token here
// if you want to see what is happening, add debug => true to the factory call
$toggl_client = TogglClient::factory(array('api_key' => $toggl_token));

$month = ['start_date' => (new DateTime("first day of last month"))->format('c'),
    'end_date' => (new DateTime("last day of last month"))->format('c')];


echo '<pre>';
$me = $toggl_client->getCurrentUser(['with_related_data' => true])->toArray();
echo '</pre>';

$timeEntries = $toggl_client->getTimeEntries(array_merge(['user_ids' => $me['data']['id']],
        $month));

$tasks = [];


echo 'time entries<br>';


foreach ($timeEntries as $timeEntry) {


//    [1] => Array
//        (
//            [id] => 1400425510
//            [guid] => 8884830f6d35da3b3c1ee2c2b1513f53
//            [wid] => 1961752
//            [pid] => 156032777
//            [billable] => 1
//            [start] => 2019-12-28T18:41:01+00:00
//            [stop] => 2019-12-28T19:37:50+00:00
//            [duration] => 3409
//            [description] => API getyourlocalguid pro martintour
//            [duronly] => 
//            [at] => 2019-12-28T19:37:50+00:00
//            [uid] => 5291295
//        )
//
//    [2] => Array
//        (
//            [id] => 1400506081
//            [guid] => de721df76e142c28f2cd69139c31204d
//            [wid] => 1961752
//            [pid] => 156032777
//            [billable] => 1
//            [start] => 2019-12-29T07:58:39+00:00
//            [stop] => 2019-12-29T09:51:13+00:00
//            [duration] => 6754
//            [description] => API getyourlocalguid pro martintour
//            [duronly] => 
//            [at] => 2019-12-29T09:51:14+00:00
//            [uid] => 5291295
//        )

    if (array_key_exists('pid', $timeEntry)) {
        if (isset($tasks[$timeEntry['pid']][$timeEntry['description']])) {
            $tasks[$timeEntry['pid']][$timeEntry['description']] += intval($timeEntry['duration']);
        } else {
            $tasks[$timeEntry['pid']][$timeEntry['description']] = intval($timeEntry['duration']);
        }
    } else {
        //No projects
    }
}





echo 'tasks<pre>';

print_r($tasks);

echo 'workspaces<br>';


$workspaces = $toggl_client->getWorkspaces(array());

foreach ($workspaces as $workspace) {
    $id = $workspace['id'];

    $projects = $toggl_client->getWorkspaceProjects(['id' => $workspace['id']]);
    print_r($projects);

    foreach ($projects as $project) {
        $tasks = $toggl_client->getProjectTasks(['id' => $project['id']]);
        print_r($tasks);
    }


//    $users = $toggl_client->getWorkspaceUsers(['id' => $workspace['id']]);    
//    print_r($users);
//    $tasks = $toggl_client->getWorkspaceTasks(['id' => $workspace['id']]);
//    print_r($tasks);
}


echo '<pre/>';
