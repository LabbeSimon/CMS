<?php
if (isset($_GET['datetime'])) {
    $datetime = str_replace("-", " ", $_GET['datetime']);

    // Check if is unix (epoch_time) format
    if (is_numeric($datetime)) {
        // if it's epoch we convert to D m y etc
        $human_readable_date = date('d m Y : H i s', $datetime);
        $century = date('Y', $datetime); // get the year
        $response = array(
            'epoch_time' => intval($datetime),
            'human_readable_date' => $human_readable_date
        );

        // change this if you don't want to get json (it's very a greatfull langugage to send with api)
        header('Content-Type: application/json');

        // send it
        echo json_encode($response);
    } else {
        // Check if it's humain time
        $parts = preg_split('/[\s:]+/', $datetime);
        if (count($parts) >= 3) {
            // extract part day mouth year
            $day = $parts[0];
            $month = $parts[1];
            $year = $parts[2];

            $hour = isset($parts[3]) ? $parts[3] : 0;
            $minute = isset($parts[4]) ? $parts[4] : 0;
            $second = isset($parts[5]) ? $parts[5] : 0;

            $formatted_datetime = "$year-$month-$day $hour:$minute:$second";
            $epoch = strtotime($formatted_datetime);

            // check if it's work
            if ($epoch !== false) {
                $response = array(
                    'input_datetime' => $datetime,
                    'epoch_time' => $epoch
                );

                // the same at top
                header('Content-Type: application/json');

                echo json_encode($response);
            } else {
                
                $error_response = array(
                    'error' => 'Invalid datetime format. Please use Day Month Year : Hours Minutes Second or epoch time. like 13-10-24-15-30-00'
                );
                header('Content-Type: application/json');
                echo json_encode($error_response);
            }
        } else {
            $error_response = array(
                'error' => 'Invalid datetime format. Please provide at least day, month, and year.'
            );
            header('Content-Type: application/json');
            echo json_encode($error_response);
        }
    }
} else {
    // if datatime it's not here just send json error
    $error_response = array(
        'error' => 'Use ?datetime= to convert'
    );
    header('Content-Type: application/json');
    echo json_encode($error_response);
}
?>
