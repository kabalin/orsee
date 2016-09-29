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

if ($_REQUEST['clear']) {
    $query="DELETE FROM ".table('bulk_invite_participants');
    or_query($query);
    message("The list of potential subjects was cleared.");
    redirect ("admin/participants_bulk_invite_new.php");
}

if ($_REQUEST['sendallunique'] || $_REQUEST['sendallnotsent']) {
    $sendallnotsent = false;
    if ($_REQUEST['sendallnotsent']) {
        $sendallnotsent = true;
    }
    $number = experimentmail__send_bulk_invite_new_mail_to_queue($sendallnotsent);
    message($number.' '.lang('xxx_bulk_invite_new_mails_sent_to_mail_queue'));
    log__admin("bulk_invite_new_mail","recipients:".$number);
    redirect("admin/participants_bulk_invite_new.php");
}

if ($_REQUEST['upload']) {
    $error_count = 0;
    $records = 0;
    if ($_FILES['csvfile']['error']) {
        message(lang('bulk_invite_new_form_error_upload'));
        $error_count++;
    } elseif (strtolower(end(explode('.', $_FILES['csvfile']['name']))) != 'csv') {
        message(lang('bulk_invite_new_form_error_not_csv'));
        $error_count++;
    } else {
        message($_FILES['csvfile']['name'].' ('.round($_FILES['csvfile']['size']/1000, 2).'Kb) '.lang('bulk_invite_new_form_sucessfully_uploaded'));
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

echo '<center>';
show_message();
echo '<BR>
        <TABLE width="80%" border="0">';

if ($proceed) {
    $query="SELECT COUNT(*) AS total, SUM(email_sent) AS total_sent FROM ".table('bulk_invite_participants');
    $result=or_query($query);
    $total=pdo_fetch_assoc($result);

    $preview = 10;
    if ($_REQUEST['show'] && $_REQUEST['preview']) {
        $preview = $_REQUEST['preview'];
    }

    if ($total['total']) {

        $query="SELECT count(bip.bip_id) AS total_unique FROM ".table('bulk_invite_participants')." bip
                    LEFT JOIN ".table('participants')." p ON (bip.email = p.email)
                    WHERE p.email IS NULL";
        $result=or_query($query);
        $total_unique=pdo_fetch_assoc($result);
        $qmails=experimentmail__mails_in_queue("bulk_invite_new_mail");
        $disabled_element = '';
        if ($qmails) {
            $disabled_element = 'disabled';
        }

        echo '<FORM action="participants_bulk_invite_new.php" method="POST">';
        echo '
            <TR>
                <TD colspan=2>
                    <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                    <TR>
                    <TD>'.lang('bulk_invite_new_total_uploaded').': '.$total['total'].'</TD>
                    <TD>'.lang('bulk_invite_new_total_unique').': '.$total_unique['total_unique'].'</TD>
                    <TD>'.lang('bulk_invite_new_total_sent').': '.$total['total_sent'].'</TD>
                    </TR>
                    <TR class="empty">
                    <TD colspan="3">'.lang('inv_mails_in_mail_queue').': ';
                    echo $qmails;
        if (check_allow('mailqueue_show_all')) {
            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.button_link('mailqueue_show.php?type=bulk_invite_new_mail',lang('monitor_bulk_invite_new_mailqueue'),'envelope-square');
        }
            echo '</TD></TR></TABLE>
                </TD>
            </TR>';
        echo '
            <TR>
                <TD colspan=2>
                    <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                    <TR class="empty"><TD align="right" colspan="2">
                        <INPUT class="button" '.$disabled_element.' type=submit name="sendallnotsent" value="'.lang('send_to_all_unique_not_sent').'">
                        <INPUT class="button" '.$disabled_element.' type=submit name="sendallunique" value="'.lang('send_to_all_unique').'">
                    </TD></TR>';
            echo '  </TABLE>
                </TD>
            </TR>';

        echo '<tr><td colspan="2" align="center">';
        echo '<span>'.lang('bulk_invite_new_form_preview').'</span><SELECT name="preview">';

        $options = array(10, 50, 100, 'all');
        foreach($options as $option) {
            $default = '';
            if ($preview == $option) {
                $default = 'selected';
            }
            $langitem = $option;
            if ($option == 'all') {
                $langitem = lang('all');
            }

            echo '<OPTION value="'.$option.'" '.$default.'>'.$langitem.'</OPTION>';
        }
        echo '    </SELECT>
                  <INPUT class="button" name="show" type="submit" value="'.lang('show').'">
                  <INPUT class="button" type="submit" name="clear" value="Clear the list" '.$disabled_element.' onclick="return confirm(\'This will clear the whole list of subjects. Click OK to proceed.\')">
              </FORM>
              </td></tr>';
        echo '<tr><td colspan="2" align="center">&nbsp;</td></tr>';
        echo '<tr><th width="50%">'.lang('Name').'</th><th width="50%">'.lang('email').'</th></tr>';

        $query_part = 'LIMIT :limit';
        $query_param = array('limit' => 10);
        if (is_numeric($preview)) {
            $query_param = array('limit' => $preview);
        } else {
            // Show all.
            $query_part = '';
            $query_param = array();
        }

        $query="select * from ".table('bulk_invite_participants')." ORDER BY name ".$query_part;
        $result=or_query($query, $query_param);
        while ($line=pdo_fetch_assoc($result)) {
             echo '<tr><td>'.$line['name'].'</td><td>'.$line['email'].'</td></tr>';
        }
    } else {
        echo '<TR><TD>';
        echo '
<p>This feature is designed for sending invitation emails to potential subjects who were not previously registered with ORSEE. The text of this email is defined in "E-Mail templates" section of ORSEE Settings and can be identified as "public_bulk_invite_new_participants".</p>
<p>The script is expecting you to upload CSV file (comma seprated values) containing two columns - \'name\' and \'email\' of particpants to invite. The first row optinally may contain column titles (\'name\' and \'email\'), column order is not important when first row contain titles, but if the first row is containing data of participant, it is assumed that first column is \'name\' and the second is \'email\'. To obtain CSV file from Excel spreadsheet, use \'File\' > \'Save as\' > \'Other formats\'.</p>
<p>When the text file is ready, please upload it using the dialog below. All the data will be stored in the designated table in the database. Once the file is uploaded you will see the summary of participants and you can initialte mailing by pressing "Send to all unique" button. The status of the email sending can be monitored by checking the number of sent emails at the summary page or by monitoring mailing queue. If you want to upload the file again, you have to clear the data first by clicking "Clear the list" button, it is only possible when there are no invitation mails in the queue to avoid data corruption.</p><br>';

        echo '</TD></TR>';

        echo '<tr><td colspan="2" align="center">';
        echo '<FORM action="participants_bulk_invite_new.php" method="POST" ENCTYPE="multipart/form-data">
                  <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="3000000">
                  <INPUT NAME="csvfile" TYPE="file">';
        echo '</td></tr>';
        echo '<tr><td colspan="2" align="center">
                  <INPUT class="button" name="upload" type="submit" value="'.lang('upload').'">
              </FORM>
              </td></tr>';
    }
}

echo '</TABLE>';
echo '<BR><BR>'.button_link('participants_main.php',lang('back'),'level-up').'<BR><BR>';
echo '</CENTER>';
include ("footer.php");
?>
