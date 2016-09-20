<?php
// part of orsee. see orsee.org
ob_start();
$jquery=array('popup');
$title="bulk_invite_new_participants";
$menu__area="participants_bulk_invite_new";
include ("header.php");
if ($proceed) {
    $allow=check_allow('participants_bulk_invite_new');
}

if ($_REQUEST['submit']) {
    $error_count = 0;
    $records = 0;
    if ($_FILES['csvfile']['error']) {
        message("An error occured during upload, please make sure that the file is less than 3Mb");
        $error_count++;
    } elseif (strtolower(end(explode('.', $_FILES['csvfile']['name']))) != 'csv') {
        message("The uploaded file is not csv file.");
        $error_count++;
    } else {
        message($_FILES['csvfile']['name'].' ('.round($_FILES['csvfile']['size']/1000, 2).'Kb) was successfully uploaded.');
    }

    if (!$error_count) {
        $csvcontent = array();

        if(($handle = fopen($_FILES['csvfile']['tmp_name'], 'r')) !== false) {
            $row = 0;
            while(($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                // number of fields in the csv
                $colcount = count($data);

                // Get the values from the csv.
                if ($colcount == 2) {
                    $csvcontent[$row]['col0'] = trim($data[0]);
                    $csvcontent[$row]['col1'] = trim($data[1]);
                    $row++;
                }
            }
            fclose($handle);
        }
    }

    if ($csvcontent[0]['col0'] == 'name' && $csvcontent[0]['col1'] == 'email') {
        $namecol = 'col0';
        $emailcol = 'col1';
        array_shift ($csvcontent);
    } elseif ($csvcontent[0]['col0'] == 'email' && $csvcontent[0]['col1'] == 'name') {
        $namecol = 'col1';
        $emailcol = 'col0';
        array_shift ($csvcontent);
    } else {
        $namecol = 'col0';
        $emailcol = 'col1';
    }

    foreach ($csvcontent as $row) {
        $pars = array(':name' => $row[$namecol], ':email' => $row[$emailcol]);
        $query = "INSERT INTO " . table('bulk_invite_participants') . " SET name = :name, email = :email";
        $done = or_query($query, $pars);
    }
}

if ($proceed) {
    echo '<center>';
    show_message();
    echo '<BR>
         <TABLE width="80%" border="0">
         <TR><TD>';

    //$emails=query_show_query_result($query,"participants_unconfirmed",false);
echo '
<p>This feature is designed for sending invitation emails to potential subjects who were not previously registered with ORSEE. The text of this email is defined in "Default mails" section of ORSEE Settings and can be identified as "default_bulk_invite_new_participants".</p>
<p>The script is expecting you to upload CSV file (comma seprated values) containing two columns - \'name\' and \'email\' of particpants to invite. The first row optinally may contain column titles (\'name\' and \'email\'), column order is not important when first row contain titles, but if the first row is containing data of participant, it is assumed that first column is \'name\' and the second is \'email\'. To obtain CSV file from Excel spreadsheet, use \'File\' > \'Save as\' > \'Other formats\'.</p>
<p>When the text file is ready, please upload it using the dialog below. All the data will be stored in the designated table in the database. Once the file is uploaded you will see the status of the email sending (and you will be able to control it by revisiting this page). If you want to upload the file again, you have to clear the data first by clicking "Clear the list" button. To initiate mail sending go to "Regular tasks" section of ORSEE settings and enable "send_invitations_bulk" job. You will not be able to clear or amend the data while "send_invitations_bulk" job is functioning.</p>
<p>When you will see that all recepients received email (it will be reflcted in the status), please disable "send_invitations_bulk" job and clear the data.</p>';

    echo '</TD></TR>';

    echo '<tr><td colspan="2" align="center">';
    echo '<FORM action="participants_bulk_invite_new.php" method="POST" ENCTYPE="multipart/form-data">
              <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="3000000">
              <INPUT NAME="csvfile" TYPE="file">
              <INPUT class="button" name="submit" type="submit" value="'.lang('upload').'">
          </FORM>
          </td></tr>';

    echo '</TABLE>';

    echo '<BR><BR>'.button_link('participants_main.php',lang('back'),'level-up').'<BR><BR>';

    echo '</CENTER>';

}
include ("footer.php");
?>
